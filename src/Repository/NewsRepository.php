<?php

namespace App\Repository;

use App\Entity\News;
use App\Entity\NewsCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<News>
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    /**
     * Regra ÚNICA de visibilidade pública de notícias: ativa e com data de publicação já alcançada.
     * Toda consulta do site público deve partir daqui (ou de applyPublicCriteria) para que nenhuma
     * notícia inativa ou agendada escape.
     */
    public function createPublicQueryBuilder(string $alias = 'n'): QueryBuilder
    {
        return self::applyPublicCriteria($this->createQueryBuilder($alias), $alias);
    }

    /**
     * Aplica os critérios de visibilidade pública a um QueryBuilder que já contém o alias da notícia
     * (útil em consultas de outras entidades que fazem JOIN com News).
     */
    public static function applyPublicCriteria(QueryBuilder $qb, string $alias = 'n'): QueryBuilder
    {
        return $qb
            ->andWhere(sprintf('%s.active = :public_news_active', $alias))
            ->andWhere(sprintf('%s.publishedAt <= :public_news_now', $alias))
            ->setParameter('public_news_active', true)
            ->setParameter('public_news_now', new \DateTimeImmutable());
    }

    /**
     * Notícia pública pelo slug (null se não existir, estiver inativa ou agendada).
     */
    public function findOnePublicBySlug(string $slug): ?News
    {
        return $this->createPublicQueryBuilder('n')
            ->andWhere('n.slug = :slug')
            ->setParameter('slug', $slug)
            ->orderBy('n.publishedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Busca pública (página /pesquisa).
     *
     * @return News[]
     */
    public function search(string $query): array
    {
        return $this->createPublicQueryBuilder('n')
            ->andWhere('n.title LIKE :query OR n.content LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('n.publishedAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    /**
     * Listagem pública (/noticias), opcionalmente filtrada por categoria ativa e por termo.
     *
     * @return News[]
     */
    public function findActive(?NewsCategory $category = null, ?string $search = null): array
    {
        $qb = $this->createPublicQueryBuilder('n')
            ->orderBy('n.publishedAt', 'DESC');

        if ($category) {
            $qb->innerJoin('n.categories', 'c')
               ->andWhere('c = :category')
               ->andWhere('c.active = :category_active')
               ->setParameter('category', $category)
               ->setParameter('category_active', true);
        }

        if ($search) {
            $qb->andWhere('n.title LIKE :search OR n.content LIKE :search OR n.summary LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return News[]
     */
    public function findRecent(int $limit = 5, ?int $excludeId = null): array
    {
        $qb = $this->createPublicQueryBuilder('n')
            ->orderBy('n.publishedAt', 'DESC')
            ->setMaxResults($limit);

        if ($excludeId) {
            $qb->andWhere('n.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getResult();
    }

    public function findPrevious(News $news): ?News
    {
        return $this->createPublicQueryBuilder('n')
            ->andWhere('n.publishedAt < :currentDate')
            ->setParameter('currentDate', $news->getPublishedAt())
            ->orderBy('n.publishedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Listagem do painel, com filtro opcional de status (true = ativas, false = inativas, null = todas).
     *
     * @return News[]
     */
    public function findForAdmin(?bool $active = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->orderBy('n.publishedAt', 'DESC')
            ->addOrderBy('n.id', 'DESC');

        if (null !== $active) {
            $qb->andWhere('n.active = :active')->setParameter('active', $active);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array{all: int, active: int, inactive: int}
     */
    public function countByStatus(): array
    {
        $active = $this->count(['active' => true]);
        $inactive = $this->count(['active' => false]);

        return ['all' => $active + $inactive, 'active' => $active, 'inactive' => $inactive];
    }
}

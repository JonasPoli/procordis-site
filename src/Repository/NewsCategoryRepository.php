<?php

namespace App\Repository;

use App\Entity\NewsCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsCategory>
 */
class NewsCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsCategory::class);
    }

    /**
     * Regra ÚNICA de visibilidade pública de categorias: somente as ativas.
     * Toda consulta de categorias do site público deve partir daqui.
     */
    public function createPublicQueryBuilder(string $alias = 'c'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->andWhere(sprintf('%s.active = :public_category_active', $alias))
            ->setParameter('public_category_active', true);
    }

    /**
     * Categoria pública pelo slug (null se não existir ou estiver inativa).
     */
    public function findOnePublicBySlug(string $slug): ?NewsCategory
    {
        return $this->createPublicQueryBuilder('c')
            ->andWhere('c.slug = :slug')
            ->setParameter('slug', $slug)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Categorias ativas que têm ao menos uma notícia pública, com a contagem dessas notícias.
     *
     * @return list<array{category: NewsCategory, newsCount: int}>
     */
    public function findSidebarCategories(): array
    {
        $qb = $this->createPublicQueryBuilder('c')
            ->select('c AS category', 'COUNT(n.id) AS newsCount')
            ->innerJoin('c.news', 'n')
            ->groupBy('c.id')
            ->orderBy('c.title', 'ASC');

        NewsRepository::applyPublicCriteria($qb, 'n');

        return array_map(
            static fn (array $row): array => ['category' => $row['category'], 'newsCount' => (int) $row['newsCount']],
            $qb->getQuery()->getResult()
        );
    }

    /**
     * Listagem do painel, com filtro opcional de status (true = ativas, false = inativas, null = todas).
     *
     * @return NewsCategory[]
     */
    public function findForAdmin(?bool $active = null): array
    {
        $qb = $this->createQueryBuilder('c')->orderBy('c.title', 'ASC');

        if (null !== $active) {
            $qb->andWhere('c.active = :active')->setParameter('active', $active);
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

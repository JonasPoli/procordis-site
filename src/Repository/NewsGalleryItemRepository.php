<?php

namespace App\Repository;

use App\Entity\News;
use App\Entity\NewsGalleryItem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsGalleryItem>
 */
class NewsGalleryItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsGalleryItem::class);
    }

    /**
     * Regra ÚNICA de visibilidade pública dos itens da galeria: somente os ativos.
     */
    public function createPublicQueryBuilder(string $alias = 'g'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->andWhere(sprintf('%s.active = :public_gallery_active', $alias))
            ->setParameter('public_gallery_active', true);
    }

    /**
     * Itens exibidos no slideshow/lightbox públicos da notícia, na ordem do painel.
     *
     * @return NewsGalleryItem[]
     */
    public function findPublicByNews(News $news): array
    {
        return $this->createPublicQueryBuilder('g')
            ->andWhere('g.news = :news')
            ->setParameter('news', $news)
            ->orderBy('g.position', 'ASC')
            ->addOrderBy('g.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Todos os itens (inclusive inativos), para o painel.
     *
     * @return NewsGalleryItem[]
     */
    public function findByNewsOrdered(News $news): array
    {
        return $this->findBy(['news' => $news], ['position' => 'ASC', 'id' => 'ASC']);
    }

    public function findOneByNewsAndYoutubeId(News $news, string $youtubeId): ?NewsGalleryItem
    {
        return $this->findOneBy(['news' => $news, 'youtubeId' => $youtubeId]);
    }

    public function getNextPosition(News $news): int
    {
        $max = $this->createQueryBuilder('g')
            ->select('MAX(g.position)')
            ->andWhere('g.news = :news')
            ->setParameter('news', $news)
            ->getQuery()
            ->getSingleScalarResult();

        return null === $max ? 0 : ((int) $max) + 1;
    }

    /**
     * Quantidade de itens por notícia (para a listagem do painel, sem N+1).
     *
     * @param int[] $newsIds
     *
     * @return array<int, array{total: int, active: int}>
     */
    public function countByNewsIds(array $newsIds): array
    {
        if ([] === $newsIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('g')
            ->select('IDENTITY(g.news) AS newsId', 'COUNT(g.id) AS total', 'SUM(CASE WHEN g.active = true THEN 1 ELSE 0 END) AS activeTotal')
            ->andWhere('g.news IN (:ids)')
            ->setParameter('ids', $newsIds)
            ->groupBy('g.news')
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['newsId']] = ['total' => (int) $row['total'], 'active' => (int) $row['activeTotal']];
        }

        return $counts;
    }
}

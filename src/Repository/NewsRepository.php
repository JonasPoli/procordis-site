<?php

namespace App\Repository;

use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    //    /**
    //     * @return News[] Returns an array of News objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('n.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?News
    //    {
    //        return $this->createQueryBuilder('n')
    //            ->andWhere('n.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    /**
     * @return News[] Returns an array of News objects matching the query
     */
    /**
     * @return News[] Returns an array of News objects matching the query
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.title LIKE :query OR n.content LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('n.publishedAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    public function findActive(?string $categorySlug = null, ?string $search = null)
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.publishedAt <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('n.publishedAt', 'DESC');

        if ($categorySlug) {
            $qb->leftJoin('n.categories', 'c')
               ->andWhere('c.slug = :categorySlug')
               ->setParameter('categorySlug', $categorySlug);
        }

        if ($search) {
            $qb->andWhere('n.title LIKE :search OR n.content LIKE :search OR n.summary LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function findRecent(int $limit = 5, ?int $excludeId = null): array
    {
        $qb = $this->createQueryBuilder('n')
            ->where('n.publishedAt <= :now')
            ->setParameter('now', new \DateTimeImmutable())
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
        return $this->createQueryBuilder('n')
            ->where('n.publishedAt < :currentDate')
            ->setParameter('currentDate', $news->getPublishedAt())
            ->orderBy('n.publishedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

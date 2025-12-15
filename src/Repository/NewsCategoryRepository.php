<?php

namespace App\Repository;

use App\Entity\NewsCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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
     * @return NewsCategory[] Returns an array of Sidebar NewsCategory objects
     */
    public function findSidebarCategories(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.active = :val')
            ->setParameter('val', true)
            ->leftJoin('c.news', 'n')
            ->andWhere('n.id IS NOT NULL') // Only categories with news
            ->orderBy('c.title', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

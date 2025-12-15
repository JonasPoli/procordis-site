<?php

namespace App\Repository;

use App\Entity\HomeBanner;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HomeBanner>
 */
class HomeBannerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HomeBanner::class);
    }

    public function findActive(): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.active = :val')
            ->setParameter('val', true)
            ->orderBy('h.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

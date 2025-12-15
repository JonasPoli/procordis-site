<?php

namespace App\Repository;

use App\Entity\Testimony;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Testimony>
 */
class TestimonyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Testimony::class);
    }

    /**
     * @return Testimony[] Returns an array of active Testimony objects
     */
    public function findActive(int $limit = 5): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.active = :val')
            ->setParameter('val', true)
            ->orderBy('t.rating', 'DESC')
            ->addOrderBy('t.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\Specialty;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Specialty>
 */
class SpecialtyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Specialty::class);
    }

    /**
     * @return Specialty[] Returns an array of active Specialty objects
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.active = :val')
            ->setParameter('val', true)
            ->orderBy('s.sortOrder', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

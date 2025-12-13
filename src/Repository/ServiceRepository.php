<?php

namespace App\Repository;

use App\Entity\Service;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Service>
 */
class ServiceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Service::class);
    }
    /**
     * @return Service[] Returns an array of Service objects matching the query
     */
    public function search(string $query): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.title LIKE :query OR s.content LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('s.title', 'ASC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\Transparency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transparency>
 *
 * @method Transparency|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transparency|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transparency[]    findAll()
 * @method Transparency[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransparencyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transparency::class);
    }

    public function save(Transparency $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Transparency $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return Transparency[] Returns an array of Transparency objects
     */
    public function findActive(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.isActive = :val')
            ->setParameter('val', true)
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

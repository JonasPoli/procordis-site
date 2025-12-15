<?php

namespace App\Repository;

use App\Entity\SystemVariable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SystemVariable>
 */
class SystemVariableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SystemVariable::class);
    }
    
    public function getValue(string $key, string $default = null): ?string
    {
        $variable = $this->findOneBy(['variableKey' => $key]);
        return $variable ? $variable->getVariableValue() : $default;
    }
}

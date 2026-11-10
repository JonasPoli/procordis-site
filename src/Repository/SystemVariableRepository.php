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
    
    public function getValue(string $key, ?string $default = null): ?string
    {
        $variable = $this->findOneBy(['variableKey' => $key]);
        return $variable ? $variable->getVariableValue() : $default;
    }

    /**
     * Endereços de e-mail válidos de uma variável de sistema, separados por vírgula ou por linha.
     * Linhas que não são e-mail são ignoradas; sem a variável, devolve uma lista vazia.
     *
     * @return list<string>
     */
    public function getEmailList(string $key): array
    {
        $value = $this->getValue($key);
        if (null === $value || '' === trim($value)) {
            return [];
        }

        $emails = [];
        foreach (preg_split('/\r\n|\r|\n|,/', $value) as $candidate) {
            $candidate = trim($candidate);
            if (false !== filter_var($candidate, \FILTER_VALIDATE_EMAIL)) {
                $emails[] = $candidate;
            }
        }

        return $emails;
    }
}

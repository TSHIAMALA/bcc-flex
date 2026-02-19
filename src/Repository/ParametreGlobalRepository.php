<?php

namespace App\Repository;

use App\Entity\ParametreGlobal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParametreGlobal>
 */
class ParametreGlobalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParametreGlobal::class);
    }

    /**
     * Get value by code, with a fallback default.
     */
    public function getValue(string $code, float $default = 0): float
    {
        $param = $this->findOneBy(['code' => $code]);
        return $param ? (float)$param->getValeur() : $default;
    }

    /**
     * Get all parameters as an associative array [code => value]
     */
    public function getAllParams(): array
    {
        $params = $this->findAll();
        $result = [];
        foreach ($params as $p) {
            $result[$p->getCode()] = (float)$p->getValeur();
        }
        return $result;
    }
}

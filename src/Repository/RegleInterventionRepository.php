<?php

namespace App\Repository;

use App\Entity\RegleIntervention;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RegleIntervention>
 */
class RegleInterventionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RegleIntervention::class);
    }

    /**
     * Find active rules
     */
    public function findActiveRules(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.actif = :actif')
            ->setParameter('actif', true)
            ->getQuery()
            ->getResult();
    }
}

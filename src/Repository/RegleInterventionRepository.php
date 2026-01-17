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

    public function findAllWithIndicateurs(): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.indicateur', 'i')
            ->addSelect('i')
            ->getQuery()
            ->getResult();
    }
}

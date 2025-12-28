<?php

namespace App\Repository;

use App\Entity\FinancesPubliques;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FinancesPubliques>
 */
class FinancesPubliquesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FinancesPubliques::class);
    }

    public function getEvolutionData(int $limit = 30): array
    {
        return $this->createQueryBuilder('f')
            ->join('f.conjoncture', 'c')
            ->orderBy('c.date_situation', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

<?php

namespace App\Repository;

use App\Entity\ReservesFinancieres;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReservesFinancieres>
 */
class ReservesFinancieresRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReservesFinancieres::class);
    }

    public function getReservesHistory(int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.conjoncture', 'c')
            ->orderBy('c.date_situation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

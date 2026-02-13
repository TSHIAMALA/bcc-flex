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

    /**
     * Get reserves history filtered by date range
     */
    public function getReservesHistoryByPeriod(?string $dateDebut = null, ?string $dateFin = null, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->join('r.conjoncture', 'c')
            ->orderBy('c.date_situation', 'DESC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        if ($dateFin) {
            $qb->andWhere('c.date_situation <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        if ($dateDebut) {
            $qb->andWhere('c.date_situation >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Find the single most recent record on or before a given date
     */
    public function findMostRecentBeforeOrEqual(string $date): ?ReservesFinancieres
    {
        return $this->createQueryBuilder('r')
            ->join('r.conjoncture', 'c')
            ->where('c.date_situation <= :date')
            ->setParameter('date', $date)
            ->orderBy('c.date_situation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

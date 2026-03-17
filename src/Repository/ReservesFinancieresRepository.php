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
            ->orderBy('c.date_situation', 'ASC');

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

    /**
     * Find the single most recent record STRICTLY BEFORE a given date
     */
    public function findMostRecentBefore(\DateTimeInterface $date): ?ReservesFinancieres
    {
        return $this->createQueryBuilder('r')
            ->join('r.conjoncture', 'c')
            ->where('c.date_situation < :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('c.date_situation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Agrégats réserves sur une période libre (moyennes).
     */
    public function getPeriodAggregates(string $dateDebut, string $dateFin): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT
                COUNT(r.id)                                                AS nb_jours,
                AVG(CAST(r.reserves_internationales_usd AS DECIMAL(18,2))) AS reserves_int_moy,
                MIN(CAST(r.reserves_internationales_usd AS DECIMAL(18,2))) AS reserves_int_min,
                MAX(CAST(r.reserves_internationales_usd AS DECIMAL(18,2))) AS reserves_int_max,
                AVG(CAST(r.avoirs_externes_usd AS DECIMAL(18,2)))          AS avoirs_ext_moy,
                AVG(CAST(r.avoirs_libres_cdf AS DECIMAL(18,2)))            AS avoirs_libres_moy,
                MAX(CAST(r.avoirs_libres_cdf AS DECIMAL(18,2)))            AS avoirs_libres_max
            FROM reserves_financieres r
            INNER JOIN conjoncture_jour c ON r.conjoncture_id = c.id
            WHERE c.date_situation BETWEEN :dateDebut AND :dateFin
        ";
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['dateDebut' => $dateDebut, 'dateFin' => $dateFin]);
        return $result->fetchAssociative() ?: [];
    }
}

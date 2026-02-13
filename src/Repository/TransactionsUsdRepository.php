<?php

namespace App\Repository;

use App\Entity\TransactionsUsd;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TransactionsUsd>
 */
class TransactionsUsdRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TransactionsUsd::class);
    }

    public function getLatestVolumesByBank(): array
    {
        // 1. Get latest date
        $latestDate = $this->createQueryBuilder('t')
            ->select('MAX(c.date_situation)')
            ->join('t.conjoncture', 'c')
            ->getQuery()
            ->getSingleScalarResult();

        if (!$latestDate) {
            return [];
        }

        // 2. Aggregate volume by bank for this date
        // Return array format compatible with template: ['banque' => 'Name', 'volumeTotalUsd' => 123.45]
        return $this->createQueryBuilder('t')
            ->select('b.nom as banque', 'SUM(t.volume_usd) as volumeTotalUsd')
            ->join('t.conjoncture', 'c')
            ->join('t.banque', 'b')
            ->where('c.date_situation = :latestDate')
            ->setParameter('latestDate', $latestDate)
            ->groupBy('b.nom')
            ->orderBy('volumeTotalUsd', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get volumes aggregated by bank for a given date period
     */
    public function getVolumesByBankForPeriod(?string $dateDebut = null, ?string $dateFin = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select('b.nom as banque', 'SUM(t.volume_usd) as volumeTotalUsd')
            ->join('t.conjoncture', 'c')
            ->join('t.banque', 'b');

        if ($dateDebut) {
            $qb->andWhere('c.date_situation >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }
        if ($dateFin) {
            $qb->andWhere('c.date_situation <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        return $qb->groupBy('b.nom')
            ->orderBy('volumeTotalUsd', 'DESC')
            ->getQuery()
            ->getResult();
    }
    /**
     * Get total volume USD for a specific conjoncture
     */
    public function getTotalVolumeForConjoncture(\App\Entity\ConjonctureJour $conjoncture): ?float
    {
        return (float) $this->createQueryBuilder('t')
            ->select('SUM(t.volume_usd)')
            ->where('t.conjoncture = :conjoncture')
            ->setParameter('conjoncture', $conjoncture)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

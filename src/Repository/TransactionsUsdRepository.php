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

    /**
     * Get top N banks by type (ACHAT or VENTE) for a given period
     * Returns: [['banque' => 'Name', 'volumeTotalUsd' => 123.45, 'coursmoyen' => 2800.50, 'nbTransactions' => 5], ...]
     */
    public function getTopBanksByTypeForPeriod(string $type, ?string $dateDebut = null, ?string $dateFin = null, int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select(
                'b.nom as banque',
                'SUM(t.volume_usd) as volumeTotalUsd',
                'AVG(t.cours) as coursMoyen',
                'COUNT(t.id) as nbTransactions'
            )
            ->join('t.conjoncture', 'c')
            ->join('t.banque', 'b')
            ->where('t.type_transaction = :type')
            ->setParameter('type', $type);

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
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get daily volume evolution by bank for a given period and type
     * Returns: [['date' => '2026-01-01', 'banque' => 'Bank A', 'volumeUsd' => 500.00], ...]
     */
    public function getBankVolumeEvolutionByPeriod(string $type, ?string $dateDebut = null, ?string $dateFin = null, int $topN = 5): array
    {
        // First get top N bank names
        $topBanks = $this->getTopBanksByTypeForPeriod($type, $dateDebut, $dateFin, $topN);
        $bankNames = array_map(fn($b) => $b['banque'], $topBanks);

        if (empty($bankNames)) {
            return [];
        }

        $qb = $this->createQueryBuilder('t')
            ->select(
                'c.date_situation as dateSituation',
                'b.nom as banque',
                'SUM(t.volume_usd) as volumeUsd'
            )
            ->join('t.conjoncture', 'c')
            ->join('t.banque', 'b')
            ->where('t.type_transaction = :type')
            ->andWhere('b.nom IN (:bankNames)')
            ->setParameter('type', $type)
            ->setParameter('bankNames', $bankNames);

        if ($dateDebut) {
            $qb->andWhere('c.date_situation >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }
        if ($dateFin) {
            $qb->andWhere('c.date_situation <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        return $qb->groupBy('c.date_situation', 'b.nom')
            ->orderBy('c.date_situation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get a complete summary with ACHAT and VENTE volumes per bank for a period
     * Uses native SQL because DQL does not support CASE WHEN with aggregates on decimal fields
     */
    public function getBankSummaryForPeriod(?string $dateDebut = null, ?string $dateFin = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT 
                b.nom as banque,
                SUM(CASE WHEN t.type_transaction = 'ACHAT' THEN t.volume_usd + 0 ELSE 0 END) as volumeAchat,
                SUM(CASE WHEN t.type_transaction = 'VENTE' THEN t.volume_usd + 0 ELSE 0 END) as volumeVente,
                SUM(t.volume_usd + 0) as volumeTotal,
                AVG(CASE WHEN t.type_transaction = 'ACHAT' THEN t.cours + 0 ELSE NULL END) as coursMoyenAchat,
                AVG(CASE WHEN t.type_transaction = 'VENTE' THEN t.cours + 0 ELSE NULL END) as coursMoyenVente,
                COUNT(t.id) as nbTransactions
            FROM transactions_usd t
            INNER JOIN conjoncture_jour c ON t.conjoncture_id = c.id
            INNER JOIN banques b ON t.banque_id = b.id
            WHERE 1=1
        ";
        
        $params = [];
        if ($dateDebut) {
            $sql .= " AND c.date_situation >= :dateDebut";
            $params['dateDebut'] = $dateDebut;
        }
        if ($dateFin) {
            $sql .= " AND c.date_situation <= :dateFin";
            $params['dateFin'] = $dateFin;
        }

        $sql .= " GROUP BY b.nom ORDER BY volumeTotal DESC";

        return $conn->executeQuery($sql, $params)->fetchAllAssociative();
    }
}

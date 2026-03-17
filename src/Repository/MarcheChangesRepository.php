<?php

namespace App\Repository;

use App\Entity\MarcheChanges;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MarcheChanges>
 */
class MarcheChangesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MarcheChanges::class);
    }

    public function getEvolutionData(int $limit = 30): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.conjoncture', 'c')
            ->orderBy('c.date_situation', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get market evolution data filtered by date range
     */
    public function getEvolutionDataByPeriod(?string $dateDebut = null, ?string $dateFin = null, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('m')
            ->join('m.conjoncture', 'c')
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
    public function findMostRecentBeforeOrEqual(string $date): ?MarcheChanges
    {
        return $this->createQueryBuilder('m')
            ->join('m.conjoncture', 'c')
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
    public function findMostRecentBefore(\DateTimeInterface $date): ?MarcheChanges
    {
        return $this->createQueryBuilder('m')
            ->join('m.conjoncture', 'c')
            ->where('c.date_situation < :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('c.date_situation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne les agrégats marché des changes sur une période libre.
     * Toutes les valeurs numériques sont des moyennes ou extrema sur 
     * l'ensemble des jours de la période.
     */
    public function getPeriodAggregates(string $dateDebut, string $dateFin): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT
                COUNT(m.id)                                                  AS nb_jours,
                AVG(CAST(m.cours_indicatif AS DECIMAL(18,4)))               AS cours_indicatif_moy,
                AVG((CAST(m.parallele_achat AS DECIMAL(18,4)) + CAST(m.parallele_vente AS DECIMAL(18,4))) / 2) AS mid_parallele_moy,
                AVG(
                    CASE WHEN m.cours_indicatif > 0
                    THEN (((CAST(m.parallele_achat AS DECIMAL(18,4)) + CAST(m.parallele_vente AS DECIMAL(18,4))) / 2
                           - CAST(m.cours_indicatif AS DECIMAL(18,4)))
                         / CAST(m.cours_indicatif AS DECIMAL(18,4))) * 100
                    ELSE NULL END
                )                                                            AS ecart_pct_moy,
                MIN(
                    CASE WHEN m.cours_indicatif > 0
                    THEN (((CAST(m.parallele_achat AS DECIMAL(18,4)) + CAST(m.parallele_vente AS DECIMAL(18,4))) / 2
                           - CAST(m.cours_indicatif AS DECIMAL(18,4)))
                         / CAST(m.cours_indicatif AS DECIMAL(18,4))) * 100
                    ELSE NULL END
                )                                                            AS ecart_pct_min,
                MAX(
                    CASE WHEN m.cours_indicatif > 0
                    THEN (((CAST(m.parallele_achat AS DECIMAL(18,4)) + CAST(m.parallele_vente AS DECIMAL(18,4))) / 2
                           - CAST(m.cours_indicatif AS DECIMAL(18,4)))
                         / CAST(m.cours_indicatif AS DECIMAL(18,4))) * 100
                    ELSE NULL END
                )                                                            AS ecart_pct_max,
                AVG(CAST(m.parallele_achat AS DECIMAL(18,4)))               AS parallele_achat_moy,
                AVG(CAST(m.parallele_vente AS DECIMAL(18,4)))               AS parallele_vente_moy
            FROM marche_changes m
            INNER JOIN conjoncture_jour c ON m.conjoncture_id = c.id
            WHERE c.date_situation BETWEEN :dateDebut AND :dateFin
                AND m.cours_indicatif IS NOT NULL
                AND m.parallele_achat IS NOT NULL
                AND m.parallele_vente IS NOT NULL
        ";

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['dateDebut' => $dateDebut, 'dateFin' => $dateFin]);
        return $result->fetchAssociative() ?: [];
    }
}

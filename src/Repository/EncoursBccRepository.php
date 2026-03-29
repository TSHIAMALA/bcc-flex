<?php

namespace App\Repository;

use App\Entity\EncoursBcc;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EncoursBcc>
 */
class EncoursBccRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EncoursBcc::class);
    }

    public function getLatestEncours(): ?EncoursBcc
    {
        return $this->createQueryBuilder('e')
            ->join('e.conjoncture', 'c')
            ->orderBy('c.date_situation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get latest encours within a date period
     */
    public function getEncoursByPeriod(?string $dateDebut = null, ?string $dateFin = null): ?EncoursBcc
    {
        $qb = $this->createQueryBuilder('e')
            ->join('e.conjoncture', 'c')
            ->orderBy('c.date_situation', 'DESC')
            ->setMaxResults(1);

        if ($dateDebut) {
            $qb->andWhere('c.date_situation >= :dateDebut')
                ->setParameter('dateDebut', $dateDebut);
        }
        if ($dateFin) {
            $qb->andWhere('c.date_situation <= :dateFin')
                ->setParameter('dateFin', $dateFin);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
    /**
     * Find the single most recent record on or before a given date
     */
    public function findMostRecentBeforeOrEqual(string $date): ?EncoursBcc
    {
        return $this->createQueryBuilder('e')
            ->join('e.conjoncture', 'c')
            ->where('c.date_situation <= :date')
            ->setParameter('date', $date)
            ->orderBy('c.date_situation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Agrégats encours BCC sur une période libre.
     */
    public function getPeriodAggregates(string $dateDebut, string $dateFin): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT
                COUNT(e.id)                                              AS nb_jours,
                AVG(CAST(e.encours_ot_bcc AS DECIMAL(18,2)))            AS ot_bcc_moy,
                AVG(CAST(e.encours_b_bcc AS DECIMAL(18,2)))             AS b_bcc_moy,
                AVG(CAST(e.encours_ot_bcc AS DECIMAL(18,2))
                  + CAST(e.encours_b_bcc AS DECIMAL(18,2)))             AS encours_bons_moy,
                MAX(CAST(e.encours_ot_bcc AS DECIMAL(18,2))
                  + CAST(e.encours_b_bcc AS DECIMAL(18,2)))             AS encours_bons_max,
                AVG(CAST(e.taux_interbancaire AS DECIMAL(6,2)))         AS taux_interbancaire_moy,
                AVG(CAST(e.taux_moyen_pondere_bbcc AS DECIMAL(6,2)))    AS taux_moyen_pondere_moy,
                AVG(CAST(e.billets_en_circulation AS DECIMAL(18,2)))    AS billets_circulation_moy
            FROM encours_bcc e
            INNER JOIN conjoncture_jour c ON e.conjoncture_id = c.id
            WHERE c.date_situation BETWEEN :dateDebut AND :dateFin
        ";
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['dateDebut' => $dateDebut, 'dateFin' => $dateFin]);
        return $result->fetchAssociative() ?: [];
    }
}

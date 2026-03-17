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

    /**
     * Get finances data filtered by date range
     */
    public function getEvolutionDataByPeriod(?string $dateDebut = null, ?string $dateFin = null, ?int $limit = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->join('f.conjoncture', 'c')
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
     * Agrégats finances publiques sur une période libre.
     */
    public function getPeriodAggregates(string $dateDebut, string $dateFin): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $sql = "
            SELECT
                COUNT(f.id)                                              AS nb_jours,
                SUM(CAST(f.recettes_totales AS DECIMAL(18,2)))          AS recettes_cumul,
                SUM(CAST(f.depenses_totales AS DECIMAL(18,2)))          AS depenses_cumul,
                AVG(CAST(f.recettes_totales AS DECIMAL(18,2)))          AS recettes_moy,
                AVG(CAST(f.depenses_totales AS DECIMAL(18,2)))          AS depenses_moy,
                AVG(CAST(f.solde AS DECIMAL(18,2)))                     AS solde_moy,
                MIN(CAST(f.solde AS DECIMAL(18,2)))                     AS solde_min,
                MAX(CAST(f.solde AS DECIMAL(18,2)))                     AS solde_max,
                SUM(CAST(f.solde AS DECIMAL(18,2)))                     AS solde_cumul
            FROM finances_publiques f
            INNER JOIN conjoncture_jour c ON f.conjoncture_id = c.id
            WHERE c.date_situation BETWEEN :dateDebut AND :dateFin
        ";
        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery(['dateDebut' => $dateDebut, 'dateFin' => $dateFin]);
        return $result->fetchAssociative() ?: [];
    }
}

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
}

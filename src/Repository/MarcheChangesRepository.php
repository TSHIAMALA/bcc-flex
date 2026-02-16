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
}

<?php

namespace App\Repository;

use App\Entity\AlerteChange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AlerteChange>
 */
class AlerteChangeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AlerteChange::class);
    }

    /**
     * Find active (non-normal) alerts ordered by date
     */
    public function findActiveAlerts(int $limit = 20): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.conjoncture', 'c')
            ->where('a.statut != :normal')
            ->andWhere('c.id > 0')
            ->setParameter('normal', 'NORMAL')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find alerts for a specific date
     */
    public function findByDate(\DateTimeInterface $date): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.conjoncture', 'c')
            ->where('c.date_situation = :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active (non-normal) alerts within a date period
     */
    public function findActiveAlertsByPeriod(string $dateDebut, string $dateFin, int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.conjoncture', 'c')
            ->where('a.statut != :normal')
            ->andWhere('c.date_situation >= :dateDebut')
            ->andWhere('c.date_situation <= :dateFin')
            ->setParameter('normal', 'NORMAL')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

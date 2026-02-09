<?php

namespace App\Repository;

use App\Entity\KPIJournalier;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<KPIJournalier>
 */
class KPIJournalierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, KPIJournalier::class);
    }

    public function getLatestKPI(): ?KPIJournalier
    {
        return $this->createQueryBuilder('k')
            ->orderBy('k.date_situation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getPreviousKPI(): ?KPIJournalier
    {
        return $this->createQueryBuilder('k')
            ->orderBy('k.date_situation', 'DESC')
            ->setFirstResult(1)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getKPIHistory(int $limit = 7): array
    {
        return $this->createQueryBuilder('k')
            ->orderBy('k.date_situation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get KPI for a specific date or the latest within a date range
     */
    public function getKPIByDate(?string $dateDebut = null, ?string $dateFin = null): ?KPIJournalier
    {
        $qb = $this->createQueryBuilder('k')
            ->orderBy('k.date_situation', 'DESC')
            ->setMaxResults(1);

        if ($dateFin) {
            $qb->andWhere('k.date_situation <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        if ($dateDebut) {
            $qb->andWhere('k.date_situation >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get previous KPI before the given date range
     */
    public function getPreviousKPIByDate(?string $dateDebut = null): ?KPIJournalier
    {
        $qb = $this->createQueryBuilder('k')
            ->orderBy('k.date_situation', 'DESC')
            ->setMaxResults(1);

        if ($dateDebut) {
            $qb->andWhere('k.date_situation < :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        } else {
            $qb->setFirstResult(1);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Get KPI history filtered by date range
     */
    public function getKPIByPeriod(?string $dateDebut = null, ?string $dateFin = null, int $limit = 30): array
    {
        $qb = $this->createQueryBuilder('k')
            ->orderBy('k.date_situation', 'DESC')
            ->setMaxResults($limit);

        if ($dateFin) {
            $qb->andWhere('k.date_situation <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        if ($dateDebut) {
            $qb->andWhere('k.date_situation >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }

        return $qb->getQuery()->getResult();
    }
}

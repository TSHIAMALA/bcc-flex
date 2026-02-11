<?php

namespace App\Repository;

use App\Entity\ConjonctureJour;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ConjonctureJour>
 */
class ConjonctureJourRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ConjonctureJour::class);
    }

    public function findLatest(): ?ConjonctureJour
    {
        return $this->createQueryBuilder('c')
            ->orderBy('c.date_situation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find the latest conjoncture within a date range
     */
    public function findLatestByPeriod(string $dateDebut, string $dateFin): ?ConjonctureJour
    {
        return $this->createQueryBuilder('c')
            ->where('c.date_situation >= :dateDebut')
            ->andWhere('c.date_situation <= :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->orderBy('c.date_situation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all conjonctures within a date range
     */
    public function findByPeriod(string $dateDebut, string $dateFin): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.date_situation >= :dateDebut')
            ->andWhere('c.date_situation <= :dateFin')
            ->setParameter('dateDebut', $dateDebut)
            ->setParameter('dateFin', $dateFin)
            ->orderBy('c.date_situation', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

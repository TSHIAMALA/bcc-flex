<?php

namespace App\Repository;

use App\Entity\TauxDirecteur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TauxDirecteur>
 */
class TauxDirecteurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TauxDirecteur::class);
    }

    /**
     * Trouve le taux directeur actif à une date donnée.
     * C'est le taux dont la date d'application est la plus récente, mais inférieure ou égale à la date demandée.
     */
    public function findActiveRateAt(\DateTimeInterface $date): ?TauxDirecteur
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.dateApplication <= :date')
            ->setParameter('date', $date->format('Y-m-d'))
            ->orderBy('t.dateApplication', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve le taux précédant le taux actuellement fourni.
     * (Recherche le taux dont la date d'application est strictement inférieure à la date d'application du taux fourni).
     */
    public function findPreviousRate(\DateTimeInterface $activeDate): ?TauxDirecteur
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.dateApplication < :date')
            ->setParameter('date', $activeDate->format('Y-m-d'))
            ->orderBy('t.dateApplication', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Retourne tout l'historique trié du plus récent au plus ancien.
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.dateApplication', 'DESC')
            ->getQuery()
            ->getResult();
    }
}

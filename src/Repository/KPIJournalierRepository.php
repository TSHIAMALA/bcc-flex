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
}

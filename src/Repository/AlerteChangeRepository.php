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

    public function findActiveAlerts(): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.indicateur', 'i')
            ->join('a.conjoncture', 'c')
            ->addSelect('i', 'c')
            ->where('a.statut != :normal')
            ->setParameter('normal', 'NORMAL')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults(20)
            ->getQuery()
            ->getResult();
    }

    public function findLatestByConjoncture(int $conjonctureId): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.indicateur', 'i')
            ->addSelect('i')
            ->where('a.conjoncture = :cid')
            ->setParameter('cid', $conjonctureId)
            ->getQuery()
            ->getResult();
    }

    public function findAlertHistory(int $limit = 50): array
    {
        return $this->createQueryBuilder('a')
            ->join('a.indicateur', 'i')
            ->join('a.conjoncture', 'c')
            ->addSelect('i', 'c')
            ->orderBy('a.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}

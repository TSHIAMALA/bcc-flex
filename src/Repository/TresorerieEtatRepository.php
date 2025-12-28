<?php

namespace App\Repository;

use App\Entity\TresorerieEtat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TresorerieEtat>
 */
class TresorerieEtatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TresorerieEtat::class);
    }

    public function getLatestTresorerie(): ?TresorerieEtat
    {
        return $this->createQueryBuilder('t')
            ->join('t.conjoncture', 'c')
            ->orderBy('c.date_situation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

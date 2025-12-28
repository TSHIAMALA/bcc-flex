<?php

namespace App\Repository;

use App\Entity\TitresPublics;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TitresPublics>
 */
class TitresPublicsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TitresPublics::class);
    }

    public function getLatestTitres(): ?TitresPublics
    {
        return $this->createQueryBuilder('t')
            ->join('t.conjoncture', 'c')
            ->orderBy('c.date_situation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

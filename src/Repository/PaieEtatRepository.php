<?php

namespace App\Repository;

use App\Entity\PaieEtat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PaieEtat>
 */
class PaieEtatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PaieEtat::class);
    }

    public function getLatestPaie(): ?\App\Entity\PaieEtat
    {
        return $this->createQueryBuilder('p')
            ->join('p.conjoncture', 'c')
            ->orderBy('c.date_situation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}

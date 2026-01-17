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
}

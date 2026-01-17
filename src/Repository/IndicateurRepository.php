<?php

namespace App\Repository;

use App\Entity\Indicateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Indicateur>
 */
class IndicateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Indicateur::class);
    }

    /**
     * Find all indicators with their intervention rules loaded
     */
    public function findAllWithRules(): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.regles', 'r')
            ->addSelect('r')
            ->where('r.actif = :actif')
            ->setParameter('actif', true)
            ->getQuery()
            ->getResult();
    }
}

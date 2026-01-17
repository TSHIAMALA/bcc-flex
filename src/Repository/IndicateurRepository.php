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

    public function findByCode(string $code): ?Indicateur
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function findAllWithRules(): array
    {
        return $this->createQueryBuilder('i')
            ->leftJoin('i.regles', 'r')
            ->addSelect('r')
            ->getQuery()
            ->getResult();
    }
}

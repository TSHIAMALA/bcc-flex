<?php

namespace App\Repository;

use App\Entity\EncoursBcc;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<EncoursBcc>
 */
class EncoursBccRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EncoursBcc::class);
    }

    public function getLatestEncours(): ?EncoursBcc
    {
        return $this->createQueryBuilder('e')
            ->join('e.conjoncture', 'c')
            ->orderBy('c.date_situation', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get latest encours within a date period
     */
    public function getEncoursByPeriod(?string $dateDebut = null, ?string $dateFin = null): ?EncoursBcc
    {
        $qb = $this->createQueryBuilder('e')
            ->join('e.conjoncture', 'c')
            ->orderBy('c.date_situation', 'DESC')
            ->setMaxResults(1);

        if ($dateDebut) {
            $qb->andWhere('c.date_situation >= :dateDebut')
               ->setParameter('dateDebut', $dateDebut);
        }
        if ($dateFin) {
            $qb->andWhere('c.date_situation <= :dateFin')
               ->setParameter('dateFin', $dateFin);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}

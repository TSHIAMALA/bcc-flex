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

    /**
     * Get latest tresorerie within a date period
     */
    public function getTresorerieByPeriod(?string $dateDebut = null, ?string $dateFin = null): ?TresorerieEtat
    {
        $qb = $this->createQueryBuilder('t')
            ->join('t.conjoncture', 'c')
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

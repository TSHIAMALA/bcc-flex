<?php

namespace App\Repository;

use App\Entity\VolumeUSD;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VolumeUSD>
 */
class VolumeUSDRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VolumeUSD::class);
    }

    public function getLatestVolumes(): array
    {
        return $this->createQueryBuilder('v')
            ->where('v.date_situation = (SELECT MAX(v2.date_situation) FROM App\Entity\VolumeUSD v2)')
            ->getQuery()
            ->getResult();
    }
}

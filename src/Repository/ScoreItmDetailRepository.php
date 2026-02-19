<?php

namespace App\Repository;

use App\Entity\ScoreItmDetail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScoreItmDetail>
 */
class ScoreItmDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScoreItmDetail::class);
    }

    /**
     * Get detailed scores for a given date
     */
    public function getScoresForDate(string $date): array
    {
        $conn = $this->getEntityManager()->getConnection();
        
        $sql = "
            SELECT 
                indicateur_code, indicateur, poids, 
                valeur_brute, score_calcule,
                seuil_alerte, seuil_intervention
            FROM v_score_itm_detail
            WHERE date_situation = :date
        ";
        
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery(['date' => $date]);
        
        return $resultSet->fetchAllAssociative();
    }
}

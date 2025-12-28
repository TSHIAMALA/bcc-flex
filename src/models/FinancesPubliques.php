<?php
/**
 * Modèle FinancesPubliques
 * Gestion des données des finances publiques
 */

require_once __DIR__ . '/Database.php';

class FinancesPubliques {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère les finances publiques
     */
    public function getAll($limit = 30) {
        $sql = "SELECT fp.*, cj.date_situation 
                FROM finances_publiques fp 
                JOIN conjoncture_jour cj ON fp.conjoncture_id = cj.id 
                ORDER BY cj.date_situation DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Récupère les dernières données
     */
    public function getLatest() {
        $sql = "SELECT fp.*, cj.date_situation 
                FROM finances_publiques fp 
                JOIN conjoncture_jour cj ON fp.conjoncture_id = cj.id 
                ORDER BY cj.date_situation DESC 
                LIMIT 1";
        return $this->db->fetchOne($sql);
    }

    /**
     * Récupère la trésorerie
     */
    public function getTresorerie($limit = 10) {
        $sql = "SELECT te.*, cj.date_situation 
                FROM tresorerie_etat te 
                JOIN conjoncture_jour cj ON te.conjoncture_id = cj.id 
                ORDER BY cj.date_situation DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Récupère la dernière trésorerie
     */
    public function getLatestTresorerie() {
        $sql = "SELECT te.*, cj.date_situation 
                FROM tresorerie_etat te 
                JOIN conjoncture_jour cj ON te.conjoncture_id = cj.id 
                ORDER BY cj.date_situation DESC 
                LIMIT 1";
        return $this->db->fetchOne($sql);
    }

    /**
     * Récupère les titres publics
     */
    public function getTitresPublics($limit = 10) {
        $sql = "SELECT tp.*, cj.date_situation 
                FROM titres_publics tp 
                JOIN conjoncture_jour cj ON tp.conjoncture_id = cj.id 
                ORDER BY cj.date_situation DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Récupère les derniers titres
     */
    public function getLatestTitres() {
        $sql = "SELECT tp.*, cj.date_situation 
                FROM titres_publics tp 
                JOIN conjoncture_jour cj ON tp.conjoncture_id = cj.id 
                ORDER BY cj.date_situation DESC 
                LIMIT 1";
        return $this->db->fetchOne($sql);
    }

    /**
     * Récupère les données de paie
     */
    public function getPaie($limit = 10) {
        $sql = "SELECT p.*, cj.date_situation 
                FROM paie_etat p 
                JOIN conjoncture_jour cj ON p.conjoncture_id = cj.id 
                ORDER BY cj.date_situation DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Récupère la dernière paie
     */
    public function getLatestPaie() {
        $sql = "SELECT p.*, cj.date_situation 
                FROM paie_etat p 
                JOIN conjoncture_jour cj ON p.conjoncture_id = cj.id 
                ORDER BY cj.date_situation DESC 
                LIMIT 1";
        return $this->db->fetchOne($sql);
    }

    /**
     * Données pour graphique d'évolution
     */
    public function getEvolutionData($days = 30) {
        $sql = "SELECT cj.date_situation, fp.recettes_totales, fp.depenses_totales, fp.solde
                FROM finances_publiques fp 
                JOIN conjoncture_jour cj ON fp.conjoncture_id = cj.id 
                ORDER BY cj.date_situation ASC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$days]);
    }

    /**
     * Calcul du solde cumulé
     */
    public function getSoldeCumule() {
        $sql = "SELECT SUM(fp.solde) as solde_cumule 
                FROM finances_publiques fp";
        $result = $this->db->fetchOne($sql);
        return $result['solde_cumule'] ?? 0;
    }
}

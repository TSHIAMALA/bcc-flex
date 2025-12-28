<?php
/**
 * Modèle MarcheChanges
 * Gestion des données du marché des changes
 */

require_once __DIR__ . '/Database.php';

class MarcheChanges {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère les données du marché des changes
     */
    public function getAll($limit = 30) {
        $sql = "SELECT mc.*, cj.date_situation, cj.date_applicable 
                FROM marche_changes mc 
                JOIN conjoncture_jour cj ON mc.conjoncture_id = cj.id 
                ORDER BY cj.date_situation DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Récupère les dernières données
     */
    public function getLatest() {
        $sql = "SELECT mc.*, cj.date_situation, cj.date_applicable 
                FROM marche_changes mc 
                JOIN conjoncture_jour cj ON mc.conjoncture_id = cj.id 
                ORDER BY cj.date_situation DESC 
                LIMIT 1";
        return $this->db->fetchOne($sql);
    }

    /**
     * Récupère les volumes USD par banque
     */
    public function getVolumesParBanque() {
        return $this->db->fetchAll("SELECT * FROM v_volumes_usd_par_banque ORDER BY date_situation DESC, volume_total_usd DESC");
    }

    /**
     * Récupère les volumes du jour
     */
    public function getLatestVolumes() {
        $sql = "SELECT v.* FROM v_volumes_usd_par_banque v 
                WHERE v.date_situation = (SELECT MAX(date_situation) FROM v_volumes_usd_par_banque)";
        return $this->db->fetchAll($sql);
    }

    /**
     * Récupère les réserves financières
     */
    public function getReserves($limit = 10) {
        $sql = "SELECT rf.*, cj.date_situation 
                FROM reserves_financieres rf 
                JOIN conjoncture_jour cj ON rf.conjoncture_id = cj.id 
                ORDER BY cj.date_situation DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Récupère les dernières réserves
     */
    public function getLatestReserves() {
        $sql = "SELECT rf.*, cj.date_situation 
                FROM reserves_financieres rf 
                JOIN conjoncture_jour cj ON rf.conjoncture_id = cj.id 
                ORDER BY cj.date_situation DESC 
                LIMIT 1";
        return $this->db->fetchOne($sql);
    }

    /**
     * Récupère les encours BCC
     */
    public function getEncoursBCC($limit = 10) {
        $sql = "SELECT e.*, cj.date_situation 
                FROM encours_bcc e 
                JOIN conjoncture_jour cj ON e.conjoncture_id = cj.id 
                ORDER BY cj.date_situation DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Récupère les derniers encours
     */
    public function getLatestEncours() {
        $sql = "SELECT e.*, cj.date_situation 
                FROM encours_bcc e 
                JOIN conjoncture_jour cj ON e.conjoncture_id = cj.id 
                ORDER BY cj.date_situation DESC 
                LIMIT 1";
        return $this->db->fetchOne($sql);
    }

    /**
     * Calcule les cours max (vendeur/acheteur le plus élevé)
     */
    public function getCoursMax() {
        $sql = "SELECT 
                    MAX(t.cours) as cours_max,
                    t.type_transaction,
                    b.nom as banque,
                    cj.date_situation
                FROM transactions_usd t
                JOIN banques b ON t.banque_id = b.id
                JOIN conjoncture_jour cj ON t.conjoncture_id = cj.id
                WHERE cj.date_situation = (SELECT MAX(date_situation) FROM conjoncture_jour)
                GROUP BY t.type_transaction, b.nom, cj.date_situation
                ORDER BY t.cours DESC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Données pour graphique d'évolution
     */
    public function getEvolutionData($days = 30) {
        $sql = "SELECT cj.date_situation, mc.cours_indicatif, mc.parallele_vente, mc.parallele_achat, mc.ecart_indic_parallele
                FROM marche_changes mc 
                JOIN conjoncture_jour cj ON mc.conjoncture_id = cj.id 
                ORDER BY cj.date_situation ASC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$days]);
    }
}

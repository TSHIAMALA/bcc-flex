<?php
/**
 * Modèle Conjoncture
 * Gestion des données de conjoncture journalière
 */

require_once __DIR__ . '/Database.php';

class Conjoncture {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère les KPIs journaliers depuis la vue
     */
    public function getKPIJournalier($limit = 10) {
        $sql = "SELECT * FROM v_kpi_journalier ORDER BY date_situation DESC LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }

    /**
     * Récupère le dernier KPI
     */
    public function getLatestKPI() {
        $sql = "SELECT * FROM v_kpi_journalier ORDER BY date_situation DESC LIMIT 1";
        return $this->db->fetchOne($sql);
    }

    /**
     * Récupère le KPI J-1
     */
    public function getPreviousKPI() {
        $sql = "SELECT * FROM v_kpi_journalier ORDER BY date_situation DESC LIMIT 1 OFFSET 1";
        return $this->db->fetchOne($sql);
    }

    /**
     * Récupère le KPI J-7
     */
    public function getKPIWeekAgo() {
        $sql = "SELECT * FROM v_kpi_journalier ORDER BY date_situation DESC LIMIT 1 OFFSET 6";
        return $this->db->fetchOne($sql);
    }

    /**
     * Récupère toutes les dates disponibles
     */
    public function getAllDates() {
        $sql = "SELECT id, date_situation, commentaire FROM conjoncture_jour ORDER BY date_situation DESC";
        return $this->db->fetchAll($sql);
    }

    /**
     * Compare plusieurs jours
     */
    public function compareMultipleDays($ids) {
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "SELECT * FROM v_kpi_journalier WHERE conjoncture_id IN ($placeholders) ORDER BY date_situation";
        return $this->db->fetchAll($sql, $ids);
    }
}

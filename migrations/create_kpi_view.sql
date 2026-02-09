-- Vue v_kpi_journalier : agrégation des données journalières pour le dashboard
-- À exécuter dans phpMyAdmin ou via MySQL CLI

DROP VIEW IF EXISTS v_kpi_journalier;
DROP TABLE IF EXISTS v_kpi_journalier;

CREATE VIEW v_kpi_journalier AS
SELECT 
    DATE_FORMAT(c.date_situation, '%Y-%m-%d') as date_situation,
    m.cours_indicatif,
    m.ecart_indic_parallele,
    r.reserves_internationales_usd,
    f.solde
FROM conjoncture_jour c
LEFT JOIN marche_changes m ON c.id = m.conjoncture_id
LEFT JOIN reserves_financieres r ON c.id = r.conjoncture_id
LEFT JOIN finances_publiques f ON c.id = f.conjoncture_id
ORDER BY c.date_situation DESC;

-- Vérification
SELECT * FROM v_kpi_journalier;

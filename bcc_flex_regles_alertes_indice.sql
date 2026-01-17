
-- =====================================================
-- BCC FLEX – REGLES, ALERTES & INDICE DE TENSION
-- =====================================================

CREATE TABLE IF NOT EXISTS indicateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE,
    libelle VARCHAR(255),
    unite VARCHAR(50)
);

CREATE TABLE IF NOT EXISTS regles_intervention (
    id INT AUTO_INCREMENT PRIMARY KEY,
    indicateur_id INT NOT NULL,
    base_calcul ENUM('JOUR','HEBDO'),
    seuil_alerte DECIMAL(10,2),
    seuil_intervention DECIMAL(10,2),
    sens ENUM('HAUSSE','BAISSE'),
    poids INT,
    FOREIGN KEY (indicateur_id) REFERENCES indicateurs(id)
);

CREATE TABLE IF NOT EXISTS alertes_change (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conjoncture_id INT,
    indicateur_id INT,
    valeur DECIMAL(18,4),
    statut ENUM('NORMAL','VIGILANCE','ALERTE'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour(id),
    FOREIGN KEY (indicateur_id) REFERENCES indicateurs(id)
);

CREATE TABLE IF NOT EXISTS indice_tension_marche (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conjoncture_id INT UNIQUE,
    score_total DECIMAL(5,2),
    statut ENUM('NORMAL','VIGILANCE','INTERVENTION'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour(id)
);

INSERT IGNORE INTO indicateurs (code, libelle, unite) VALUES
('ECART_CHANGE','Écart indicatif / parallèle','CDF'),
('AVOIRS_LIBRES','Avoirs libres des banques','Mds CDF'),
('RESERVES_USD','Réserves internationales','Mds USD'),
('SOLDE_BUDG','Solde budgétaire','Mds CDF'),
('VOLUME_USD','Volume USD marché','USD');

INSERT INTO regles_intervention (indicateur_id, base_calcul, seuil_alerte, seuil_intervention, sens, poids)
SELECT id,'JOUR',100,150,'HAUSSE',30 FROM indicateurs WHERE code='ECART_CHANGE';

INSERT INTO regles_intervention (indicateur_id, base_calcul, seuil_alerte, seuil_intervention, sens, poids)
SELECT id,'JOUR',800,600,'BAISSE',20 FROM indicateurs WHERE code='AVOIRS_LIBRES';

INSERT INTO regles_intervention (indicateur_id, base_calcul, seuil_alerte, seuil_intervention, sens, poids)
SELECT id,'JOUR',7.0,6.5,'BAISSE',20 FROM indicateurs WHERE code='RESERVES_USD';

INSERT INTO regles_intervention (indicateur_id, base_calcul, seuil_alerte, seuil_intervention, sens, poids)
SELECT id,'JOUR',-100,-200,'BAISSE',15 FROM indicateurs WHERE code='SOLDE_BUDG';

INSERT INTO regles_intervention (indicateur_id, base_calcul, seuil_alerte, seuil_intervention, sens, poids)
SELECT id,'JOUR',10000000,15000000,'HAUSSE',15 FROM indicateurs WHERE code='VOLUME_USD';

CREATE OR REPLACE VIEW v_valeurs_indicateurs AS
SELECT cj.id AS conjoncture_id, cj.date_situation, 'ECART_CHANGE' AS code, mc.ecart_indic_parallele AS valeur
FROM conjoncture_jour cj JOIN marche_changes mc ON mc.conjoncture_id = cj.id
UNION ALL
SELECT cj.id, cj.date_situation, 'AVOIRS_LIBRES', rf.avoirs_libres_cdf
FROM conjoncture_jour cj JOIN reserves_financieres rf ON rf.conjoncture_id = cj.id
UNION ALL
SELECT cj.id, cj.date_situation, 'RESERVES_USD', rf.reserves_internationales_usd
FROM conjoncture_jour cj JOIN reserves_financieres rf ON rf.conjoncture_id = cj.id
UNION ALL
SELECT cj.id, cj.date_situation, 'SOLDE_BUDG', fp.solde
FROM conjoncture_jour cj JOIN finances_publiques fp ON fp.conjoncture_id = cj.id;

CREATE OR REPLACE VIEW v_indice_tension AS
SELECT
    v.conjoncture_id,
    SUM(
        CASE
            WHEN (r.sens='HAUSSE' AND v.valeur >= r.seuil_intervention)
              OR (r.sens='BAISSE' AND v.valeur <= r.seuil_intervention)
            THEN r.poids
            WHEN (r.sens='HAUSSE' AND v.valeur >= r.seuil_alerte)
              OR (r.sens='BAISSE' AND v.valeur <= r.seuil_alerte)
            THEN r.poids * 0.5
            ELSE 0
        END
    ) AS score_total
FROM v_valeurs_indicateurs v
JOIN indicateurs i ON i.code = v.code
JOIN regles_intervention r ON r.indicateur_id = i.id
GROUP BY v.conjoncture_id;

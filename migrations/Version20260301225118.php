<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260301225118 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix FK/index names + create MySQL views (v_kpi_journalier, v_score_itm_detail, v_volumes_usd_par_banque)';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE parametre_globaux (code VARCHAR(50) NOT NULL, valeur NUMERIC(18, 4) NOT NULL, description VARCHAR(255) DEFAULT NULL, PRIMARY KEY(code)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        // --- VUES MySQL (pas des tables Doctrine) ---
        $this->addSql("
            CREATE OR REPLACE VIEW v_kpi_journalier AS
            SELECT
                DATE_FORMAT(c.date_situation, '%Y-%m-%d') AS date_situation,
                m.cours_indicatif,
                m.ecart_indic_parallele,
                r.reserves_internationales_usd,
                f.solde,
                m.parallele_vente
            FROM conjoncture_jour c
            LEFT JOIN marche_changes m ON c.id = m.conjoncture_id
            LEFT JOIN reserves_financieres r ON c.id = r.conjoncture_id
            LEFT JOIN finances_publiques f ON c.id = f.conjoncture_id
        ");
        $this->addSql("
            CREATE OR REPLACE VIEW v_volumes_usd_par_banque AS
            SELECT
                b.nom              AS banque,
                DATE_FORMAT(c.date_situation, '%Y-%m-%d') AS date_situation,
                t.type_transaction AS type_transaction,
                SUM(t.volume_usd)  AS volume_total_usd,
                AVG(t.cours)       AS cours_moyen
            FROM transactions_usd t
            JOIN conjoncture_jour c ON c.id = t.conjoncture_id
            JOIN banques          b ON b.id = t.banque_id
            GROUP BY b.nom, c.date_situation, t.type_transaction
        ");
        $this->addSql("
            CREATE OR REPLACE VIEW v_score_itm_detail AS
            SELECT
                CONCAT(DATE_FORMAT(cj.date_situation, '%Y-%m-%d'), '_', i.code) AS pk,
                i.code    AS indicateur_code,
                i.libelle AS indicateur,
                DATE_FORMAT(cj.date_situation, '%Y-%m-%d')                      AS date_situation,
                vv.valeur AS valeur_brute,
                ROUND(
                    CASE
                        WHEN (ri.sens = 'HAUSSE' AND vv.valeur >= ri.seuil_intervention)
                          OR (ri.sens = 'BAISSE' AND vv.valeur <= ri.seuil_intervention) THEN ri.poids
                        WHEN (ri.sens = 'HAUSSE' AND vv.valeur >= ri.seuil_alerte)
                          OR (ri.sens = 'BAISSE' AND vv.valeur <= ri.seuil_alerte) THEN ri.poids * 0.5
                        ELSE 0
                    END, 2) AS score_calcule,
                ri.poids,
                ri.seuil_alerte,
                ri.seuil_intervention
            FROM conjoncture_jour cj
            JOIN v_valeurs_indicateurs vv ON vv.conjoncture_id = cj.id
            JOIN indicateur i            ON i.code = vv.code
            JOIN regle_intervention ri   ON ri.indicateur_id = i.id
        ");
        $this->addSql('ALTER TABLE alerte_change ADD CONSTRAINT FK_333AF1D4C497CF91 FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id)');
        $this->addSql('ALTER TABLE alerte_change RENAME INDEX idx_ac_conjoncture TO IDX_333AF1D4C497CF91');
        $this->addSql('ALTER TABLE alerte_change RENAME INDEX idx_ac_indicateur TO IDX_333AF1D4DA3B8F3D');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_34D045476C6E55B5 ON banques (nom)');
        $this->addSql('ALTER TABLE conjoncture_jour RENAME INDEX uk_date_situation TO UNIQ_D0FB6FBED35BA97E');
        $this->addSql('ALTER TABLE encours_bcc DROP FOREIGN KEY fk_eb_conjoncture');
        $this->addSql('ALTER TABLE encours_bcc ADD CONSTRAINT FK_ED917146C497CF91 FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id)');
        $this->addSql('ALTER TABLE encours_bcc RENAME INDEX idx_eb_conjoncture TO IDX_ED917146C497CF91');
        $this->addSql('ALTER TABLE finances_publiques DROP FOREIGN KEY fk_fp_conjoncture');
        $this->addSql('ALTER TABLE finances_publiques ADD CONSTRAINT FK_A6B91AACC497CF91 FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id)');
        $this->addSql('ALTER TABLE finances_publiques RENAME INDEX idx_fp_conjoncture TO IDX_A6B91AACC497CF91');
        $this->addSql('ALTER TABLE indicateur RENAME INDEX uk_indicateur_code TO UNIQ_7C663A2777153098');
        $this->addSql('ALTER TABLE marche_changes DROP FOREIGN KEY fk_mc_conjoncture');
        $this->addSql('ALTER TABLE marche_changes ADD CONSTRAINT FK_DD306C7AC497CF91 FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id)');
        $this->addSql('ALTER TABLE marche_changes RENAME INDEX idx_mc_conjoncture TO IDX_DD306C7AC497CF91');
        $this->addSql('ALTER TABLE paie_etat DROP FOREIGN KEY fk_pe_conjoncture');
        $this->addSql('ALTER TABLE paie_etat ADD CONSTRAINT FK_823190E5C497CF91 FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id)');
        $this->addSql('ALTER TABLE paie_etat RENAME INDEX idx_pe_conjoncture TO IDX_823190E5C497CF91');
        $this->addSql('ALTER TABLE regle_intervention RENAME INDEX idx_ri_indicateur TO IDX_63D6C335DA3B8F3D');
        $this->addSql('ALTER TABLE reserves_financieres DROP FOREIGN KEY fk_rf_conjoncture');
        $this->addSql('ALTER TABLE reserves_financieres ADD CONSTRAINT FK_90062D89C497CF91 FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id)');
        $this->addSql('ALTER TABLE reserves_financieres RENAME INDEX idx_rf_conjoncture TO IDX_90062D89C497CF91');
        $this->addSql('ALTER TABLE titres_publics DROP FOREIGN KEY fk_tp_conjoncture');
        $this->addSql('ALTER TABLE titres_publics DROP paiement_coupon_usd, DROP remb_ot_cdf, DROP remb_bt_cdf, DROP remb_ot_usd, DROP remb_bt_usd');
        $this->addSql('ALTER TABLE titres_publics ADD CONSTRAINT FK_BDA5DD5AC497CF91 FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id)');
        $this->addSql('ALTER TABLE titres_publics RENAME INDEX idx_tp_conjoncture TO IDX_BDA5DD5AC497CF91');
        $this->addSql('ALTER TABLE transactions_usd DROP FOREIGN KEY fk_tu_conjoncture');
        $this->addSql('ALTER TABLE transactions_usd ADD CONSTRAINT FK_48AF1983C497CF91 FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id)');
        $this->addSql('ALTER TABLE transactions_usd RENAME INDEX idx_tu_conjoncture TO IDX_48AF1983C497CF91');
        $this->addSql('ALTER TABLE transactions_usd RENAME INDEX idx_tu_banque TO IDX_48AF198337E080D9');
        $this->addSql('ALTER TABLE tresorerie_etat DROP FOREIGN KEY fk_te_conjoncture');
        $this->addSql('ALTER TABLE tresorerie_etat ADD CONSTRAINT FK_5131736FC497CF91 FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id)');
        $this->addSql('ALTER TABLE tresorerie_etat RENAME INDEX idx_te_conjoncture TO IDX_5131736FC497CF91');
        $this->addSql('ALTER TABLE user RENAME INDEX uk_user_email TO UNIQ_8D93D649E7927C74');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('ALTER TABLE messenger_messages RENAME INDEX idx_mm_queue TO IDX_75EA56E0FB7336F0');
        $this->addSql('ALTER TABLE messenger_messages RENAME INDEX idx_mm_available TO IDX_75EA56E0E3BD61CE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE parametre_globaux');
        $this->addSql('DROP VIEW IF EXISTS v_kpi_journalier');
        $this->addSql('DROP VIEW IF EXISTS v_score_itm_detail');
        $this->addSql('DROP VIEW IF EXISTS v_volumes_usd_par_banque');
        $this->addSql('ALTER TABLE alerte_change DROP FOREIGN KEY FK_333AF1D4C497CF91');
        $this->addSql('ALTER TABLE alerte_change RENAME INDEX idx_333af1d4da3b8f3d TO idx_ac_indicateur');
        $this->addSql('ALTER TABLE alerte_change RENAME INDEX idx_333af1d4c497cf91 TO idx_ac_conjoncture');
        $this->addSql('DROP INDEX UNIQ_34D045476C6E55B5 ON banques');
        $this->addSql('ALTER TABLE conjoncture_jour RENAME INDEX uniq_d0fb6fbed35ba97e TO uk_date_situation');
        $this->addSql('ALTER TABLE encours_bcc DROP FOREIGN KEY FK_ED917146C497CF91');
        $this->addSql('ALTER TABLE encours_bcc ADD CONSTRAINT fk_eb_conjoncture FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE encours_bcc RENAME INDEX idx_ed917146c497cf91 TO idx_eb_conjoncture');
        $this->addSql('ALTER TABLE finances_publiques DROP FOREIGN KEY FK_A6B91AACC497CF91');
        $this->addSql('ALTER TABLE finances_publiques ADD CONSTRAINT fk_fp_conjoncture FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE finances_publiques RENAME INDEX idx_a6b91aacc497cf91 TO idx_fp_conjoncture');
        $this->addSql('ALTER TABLE indicateur RENAME INDEX uniq_7c663a2777153098 TO uk_indicateur_code');
        $this->addSql('ALTER TABLE marche_changes DROP FOREIGN KEY FK_DD306C7AC497CF91');
        $this->addSql('ALTER TABLE marche_changes ADD CONSTRAINT fk_mc_conjoncture FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE marche_changes RENAME INDEX idx_dd306c7ac497cf91 TO idx_mc_conjoncture');
        $this->addSql('DROP INDEX IDX_75EA56E016BA31DB ON messenger_messages');
        $this->addSql('ALTER TABLE messenger_messages RENAME INDEX idx_75ea56e0fb7336f0 TO idx_mm_queue');
        $this->addSql('ALTER TABLE messenger_messages RENAME INDEX idx_75ea56e0e3bd61ce TO idx_mm_available');
        $this->addSql('ALTER TABLE paie_etat DROP FOREIGN KEY FK_823190E5C497CF91');
        $this->addSql('ALTER TABLE paie_etat ADD CONSTRAINT fk_pe_conjoncture FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE paie_etat RENAME INDEX idx_823190e5c497cf91 TO idx_pe_conjoncture');
        $this->addSql('ALTER TABLE regle_intervention RENAME INDEX idx_63d6c335da3b8f3d TO idx_ri_indicateur');
        $this->addSql('ALTER TABLE reserves_financieres DROP FOREIGN KEY FK_90062D89C497CF91');
        $this->addSql('ALTER TABLE reserves_financieres ADD CONSTRAINT fk_rf_conjoncture FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reserves_financieres RENAME INDEX idx_90062d89c497cf91 TO idx_rf_conjoncture');
        $this->addSql('ALTER TABLE titres_publics DROP FOREIGN KEY FK_BDA5DD5AC497CF91');
        $this->addSql('ALTER TABLE titres_publics ADD paiement_coupon_usd NUMERIC(12, 4) DEFAULT NULL, ADD remb_ot_cdf NUMERIC(18, 2) DEFAULT NULL, ADD remb_bt_cdf NUMERIC(18, 2) DEFAULT NULL, ADD remb_ot_usd NUMERIC(12, 4) DEFAULT NULL, ADD remb_bt_usd NUMERIC(12, 4) DEFAULT NULL');
        $this->addSql('ALTER TABLE titres_publics ADD CONSTRAINT fk_tp_conjoncture FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE titres_publics RENAME INDEX idx_bda5dd5ac497cf91 TO idx_tp_conjoncture');
        $this->addSql('ALTER TABLE transactions_usd DROP FOREIGN KEY FK_48AF1983C497CF91');
        $this->addSql('ALTER TABLE transactions_usd ADD CONSTRAINT fk_tu_conjoncture FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE transactions_usd RENAME INDEX idx_48af198337e080d9 TO idx_tu_banque');
        $this->addSql('ALTER TABLE transactions_usd RENAME INDEX idx_48af1983c497cf91 TO idx_tu_conjoncture');
        $this->addSql('ALTER TABLE tresorerie_etat DROP FOREIGN KEY FK_5131736FC497CF91');
        $this->addSql('ALTER TABLE tresorerie_etat ADD CONSTRAINT fk_te_conjoncture FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tresorerie_etat RENAME INDEX idx_5131736fc497cf91 TO idx_te_conjoncture');
        $this->addSql('ALTER TABLE `user` RENAME INDEX uniq_8d93d649e7927c74 TO uk_user_email');
    }
}

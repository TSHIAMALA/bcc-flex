<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260311092404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE v_kpi_journalier (date_situation VARCHAR(10) NOT NULL, cours_indicatif NUMERIC(12, 4) DEFAULT NULL, ecart_indic_parallele NUMERIC(12, 4) DEFAULT NULL, reserves_internationales_usd NUMERIC(18, 2) DEFAULT NULL, solde NUMERIC(18, 2) DEFAULT NULL, parallele_vente NUMERIC(12, 4) DEFAULT NULL, PRIMARY KEY(date_situation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE v_score_itm_detail (pk VARCHAR(10) NOT NULL, indicateur_code VARCHAR(50) NOT NULL, indicateur VARCHAR(255) NOT NULL, date_situation VARCHAR(10) NOT NULL, valeur_brute NUMERIC(18, 2) NOT NULL, score_calcule NUMERIC(18, 2) NOT NULL, poids INT NOT NULL, seuil_alerte NUMERIC(18, 2) DEFAULT NULL, seuil_intervention NUMERIC(18, 2) DEFAULT NULL, PRIMARY KEY(pk)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE v_volumes_usd_par_banque (banque VARCHAR(255) NOT NULL, date_situation VARCHAR(255) NOT NULL, type_transaction VARCHAR(255) NOT NULL, volume_total_usd NUMERIC(40, 2) DEFAULT NULL, cours_moyen NUMERIC(16, 8) DEFAULT NULL, PRIMARY KEY(banque, date_situation, type_transaction)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
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
        $this->addSql('DROP TABLE v_kpi_journalier');
        $this->addSql('DROP TABLE v_score_itm_detail');
        $this->addSql('DROP TABLE v_volumes_usd_par_banque');
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
        $this->addSql('ALTER TABLE transactions_usd RENAME INDEX idx_48af1983c497cf91 TO idx_tu_conjoncture');
        $this->addSql('ALTER TABLE transactions_usd RENAME INDEX idx_48af198337e080d9 TO idx_tu_banque');
        $this->addSql('ALTER TABLE tresorerie_etat DROP FOREIGN KEY FK_5131736FC497CF91');
        $this->addSql('ALTER TABLE tresorerie_etat ADD CONSTRAINT fk_te_conjoncture FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tresorerie_etat RENAME INDEX idx_5131736fc497cf91 TO idx_te_conjoncture');
        $this->addSql('ALTER TABLE `user` RENAME INDEX uniq_8d93d649e7927c74 TO uk_user_email');
    }
}

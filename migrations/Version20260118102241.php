<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260118102241 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE alerte_change (id INT AUTO_INCREMENT NOT NULL, conjoncture_id INT NOT NULL, indicateur_id INT NOT NULL, valeur NUMERIC(18, 4) NOT NULL, statut VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_333AF1D4C497CF91 (conjoncture_id), INDEX IDX_333AF1D4DA3B8F3D (indicateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE indicateur (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, nom VARCHAR(255) NOT NULL, unite VARCHAR(50) DEFAULT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_7C663A2777153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE regle_intervention (id INT AUTO_INCREMENT NOT NULL, indicateur_id INT NOT NULL, seuil_alerte NUMERIC(18, 4) DEFAULT NULL, seuil_intervention NUMERIC(18, 4) DEFAULT NULL, sens VARCHAR(20) NOT NULL, poids INT NOT NULL, actif TINYINT(1) NOT NULL, INDEX IDX_63D6C335DA3B8F3D (indicateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        // $this->addSql('CREATE TABLE v_kpi_journalier (conjoncture_id INT NOT NULL, date_situation DATE NOT NULL, date_applicable DATE NOT NULL, cours_indicatif NUMERIC(12, 4) DEFAULT NULL, parallele_vente NUMERIC(12, 4) DEFAULT NULL, ecart_indic_parallele NUMERIC(12, 4) DEFAULT NULL, reserves_internationales_usd NUMERIC(18, 2) DEFAULT NULL, avoirs_externes_usd NUMERIC(18, 2) DEFAULT NULL, recettes_totales NUMERIC(18, 2) DEFAULT NULL, depenses_totales NUMERIC(18, 2) DEFAULT NULL, solde NUMERIC(18, 2) DEFAULT NULL, PRIMARY KEY(conjoncture_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        // $this->addSql('CREATE TABLE v_volumes_usd_par_banque (banque VARCHAR(255) NOT NULL, date_situation VARCHAR(255) NOT NULL, type_transaction VARCHAR(255) NOT NULL, volume_total_usd NUMERIC(40, 2) DEFAULT NULL, cours_moyen NUMERIC(16, 8) DEFAULT NULL, PRIMARY KEY(banque, date_situation, type_transaction)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE alerte_change ADD CONSTRAINT FK_333AF1D4C497CF91 FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id)');
        $this->addSql('ALTER TABLE alerte_change ADD CONSTRAINT FK_333AF1D4DA3B8F3D FOREIGN KEY (indicateur_id) REFERENCES indicateur (id)');
        $this->addSql('ALTER TABLE regle_intervention ADD CONSTRAINT FK_63D6C335DA3B8F3D FOREIGN KEY (indicateur_id) REFERENCES indicateur (id)');
        $this->addSql('ALTER TABLE indice_tension_marche DROP FOREIGN KEY indice_tension_marche_ibfk_1');
        $this->addSql('ALTER TABLE alertes_change DROP FOREIGN KEY alertes_change_ibfk_1');
        $this->addSql('ALTER TABLE alertes_change DROP FOREIGN KEY alertes_change_ibfk_2');
        $this->addSql('ALTER TABLE regles_intervention DROP FOREIGN KEY regles_intervention_ibfk_1');
        $this->addSql('ALTER TABLE transactions_usd DROP FOREIGN KEY transactions_usd_ibfk_1');
        $this->addSql('ALTER TABLE transactions_usd DROP FOREIGN KEY transactions_usd_ibfk_2');
        $this->addSql('DROP TABLE indice_tension_marche');
        $this->addSql('DROP TABLE alertes_change');
        $this->addSql('DROP TABLE regles_intervention');
        $this->addSql('DROP TABLE banques');
        $this->addSql('DROP TABLE transactions_usd');
        $this->addSql('DROP TABLE indicateurs');
        $this->addSql('ALTER TABLE conjoncture_jour CHANGE commentaire commentaire LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE conjoncture_jour RENAME INDEX date_situation TO UNIQ_D0FB6FBED35BA97E');
        $this->addSql('ALTER TABLE encours_bcc RENAME INDEX conjoncture_id TO IDX_ED917146C497CF91');
        $this->addSql('ALTER TABLE finances_publiques RENAME INDEX conjoncture_id TO IDX_A6B91AACC497CF91');
        $this->addSql('ALTER TABLE marche_changes RENAME INDEX conjoncture_id TO IDX_DD306C7AC497CF91');
        $this->addSql('ALTER TABLE paie_etat RENAME INDEX conjoncture_id TO IDX_823190E5C497CF91');
        $this->addSql('ALTER TABLE reserves_financieres RENAME INDEX conjoncture_id TO IDX_90062D89C497CF91');
        $this->addSql('ALTER TABLE titres_publics RENAME INDEX conjoncture_id TO IDX_BDA5DD5AC497CF91');
        $this->addSql('ALTER TABLE tresorerie_etat RENAME INDEX conjoncture_id TO IDX_5131736FC497CF91');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE indice_tension_marche (id INT AUTO_INCREMENT NOT NULL, conjoncture_id INT DEFAULT NULL, score_total NUMERIC(5, 2) DEFAULT NULL, statut VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, UNIQUE INDEX conjoncture_id (conjoncture_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE alertes_change (id INT AUTO_INCREMENT NOT NULL, conjoncture_id INT DEFAULT NULL, indicateur_id INT DEFAULT NULL, valeur NUMERIC(18, 4) DEFAULT NULL, statut VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME DEFAULT CURRENT_TIMESTAMP, INDEX conjoncture_id (conjoncture_id), INDEX indicateur_id (indicateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE regles_intervention (id INT AUTO_INCREMENT NOT NULL, indicateur_id INT NOT NULL, base_calcul VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, seuil_alerte NUMERIC(10, 2) DEFAULT NULL, seuil_intervention NUMERIC(10, 2) DEFAULT NULL, sens VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, poids INT DEFAULT NULL, INDEX indicateur_id (indicateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE banques (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX nom (nom), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE transactions_usd (id INT AUTO_INCREMENT NOT NULL, conjoncture_id INT NOT NULL, banque_id INT NOT NULL, type_transaction VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, cours NUMERIC(12, 4) DEFAULT NULL, volume_usd NUMERIC(18, 2) DEFAULT NULL, INDEX conjoncture_id (conjoncture_id), INDEX banque_id (banque_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE indicateurs (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, libelle VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, unite VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX code (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE indice_tension_marche ADD CONSTRAINT indice_tension_marche_ibfk_1 FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE alertes_change ADD CONSTRAINT alertes_change_ibfk_1 FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE alertes_change ADD CONSTRAINT alertes_change_ibfk_2 FOREIGN KEY (indicateur_id) REFERENCES indicateurs (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE regles_intervention ADD CONSTRAINT regles_intervention_ibfk_1 FOREIGN KEY (indicateur_id) REFERENCES indicateurs (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE transactions_usd ADD CONSTRAINT transactions_usd_ibfk_1 FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE transactions_usd ADD CONSTRAINT transactions_usd_ibfk_2 FOREIGN KEY (banque_id) REFERENCES banques (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE alerte_change DROP FOREIGN KEY FK_333AF1D4C497CF91');
        $this->addSql('ALTER TABLE alerte_change DROP FOREIGN KEY FK_333AF1D4DA3B8F3D');
        $this->addSql('ALTER TABLE regle_intervention DROP FOREIGN KEY FK_63D6C335DA3B8F3D');
        $this->addSql('DROP TABLE alerte_change');
        $this->addSql('DROP TABLE indicateur');
        $this->addSql('DROP TABLE regle_intervention');
        $this->addSql('DROP TABLE v_kpi_journalier');
        $this->addSql('DROP TABLE v_volumes_usd_par_banque');
        $this->addSql('DROP TABLE messenger_messages');
        $this->addSql('ALTER TABLE tresorerie_etat RENAME INDEX idx_5131736fc497cf91 TO conjoncture_id');
        $this->addSql('ALTER TABLE conjoncture_jour CHANGE commentaire commentaire TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE conjoncture_jour RENAME INDEX uniq_d0fb6fbed35ba97e TO date_situation');
        $this->addSql('ALTER TABLE paie_etat RENAME INDEX idx_823190e5c497cf91 TO conjoncture_id');
        $this->addSql('ALTER TABLE encours_bcc RENAME INDEX idx_ed917146c497cf91 TO conjoncture_id');
        $this->addSql('ALTER TABLE titres_publics RENAME INDEX idx_bda5dd5ac497cf91 TO conjoncture_id');
        $this->addSql('ALTER TABLE finances_publiques RENAME INDEX idx_a6b91aacc497cf91 TO conjoncture_id');
        $this->addSql('ALTER TABLE marche_changes RENAME INDEX idx_dd306c7ac497cf91 TO conjoncture_id');
        $this->addSql('ALTER TABLE reserves_financieres RENAME INDEX idx_90062d89c497cf91 TO conjoncture_id');
    }
}

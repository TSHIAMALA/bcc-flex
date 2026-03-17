<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260312122628 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE taux_directeur (id INT AUTO_INCREMENT NOT NULL, date_application DATE NOT NULL, valeur NUMERIC(6, 2) NOT NULL, commentaire VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE v_kpi_journalier (date_situation VARCHAR(10) NOT NULL, cours_indicatif NUMERIC(12, 4) DEFAULT NULL, ecart_indic_parallele NUMERIC(12, 4) DEFAULT NULL, reserves_internationales_usd NUMERIC(18, 2) DEFAULT NULL, solde NUMERIC(18, 2) DEFAULT NULL, parallele_vente NUMERIC(12, 4) DEFAULT NULL, PRIMARY KEY(date_situation)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE v_score_itm_detail (pk VARCHAR(10) NOT NULL, indicateur_code VARCHAR(50) NOT NULL, indicateur VARCHAR(255) NOT NULL, date_situation VARCHAR(10) NOT NULL, valeur_brute NUMERIC(18, 2) NOT NULL, score_calcule NUMERIC(18, 2) NOT NULL, poids INT NOT NULL, seuil_alerte NUMERIC(18, 2) DEFAULT NULL, seuil_intervention NUMERIC(18, 2) DEFAULT NULL, PRIMARY KEY(pk)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE v_volumes_usd_par_banque (banque VARCHAR(255) NOT NULL, date_situation VARCHAR(255) NOT NULL, type_transaction VARCHAR(255) NOT NULL, volume_total_usd NUMERIC(40, 2) DEFAULT NULL, cours_moyen NUMERIC(16, 8) DEFAULT NULL, PRIMARY KEY(banque, date_situation, type_transaction)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE taux_directeur');
        $this->addSql('DROP TABLE v_kpi_journalier');
        $this->addSql('DROP TABLE v_score_itm_detail');
        $this->addSql('DROP TABLE v_volumes_usd_par_banque');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260115230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tables for market intervention rules and alerts';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE indicateur (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, code VARCHAR(50) NOT NULL, description LONGTEXT DEFAULT NULL, unite VARCHAR(20) DEFAULT NULL, type VARCHAR(50) NOT NULL, UNIQUE INDEX UNIQ_INDICATEUR_CODE (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE regle_intervention (id INT AUTO_INCREMENT NOT NULL, indicateur_id INT NOT NULL, seuil_alerte DECIMAL(18, 4) NOT NULL, seuil_intervention DECIMAL(18, 4) NOT NULL, base_comparaison VARCHAR(20) NOT NULL, poids INT NOT NULL, operateur VARCHAR(2) NOT NULL, actif TINYINT(1) NOT NULL, INDEX IDX_REGLE_INDICATEUR (indicateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE alerte_change (id INT AUTO_INCREMENT NOT NULL, indicateur_id INT NOT NULL, date_alerte DATETIME NOT NULL, valeur_constatee DECIMAL(18, 4) NOT NULL, seuil_declenche DECIMAL(18, 4) NOT NULL, niveau VARCHAR(20) NOT NULL, statut VARCHAR(20) NOT NULL, INDEX IDX_ALERTE_INDICATEUR (indicateur_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE indice_tension (id INT AUTO_INCREMENT NOT NULL, date_situation DATE NOT NULL, score DECIMAL(5, 2) NOT NULL, niveau VARCHAR(20) NOT NULL, details JSON NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE regle_intervention ADD CONSTRAINT FK_REGLE_INDICATEUR FOREIGN KEY (indicateur_id) REFERENCES indicateur (id)');
        $this->addSql('ALTER TABLE alerte_change ADD CONSTRAINT FK_ALERTE_INDICATEUR FOREIGN KEY (indicateur_id) REFERENCES indicateur (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE regle_intervention DROP FOREIGN KEY FK_REGLE_INDICATEUR');
        $this->addSql('ALTER TABLE alerte_change DROP FOREIGN KEY FK_ALERTE_INDICATEUR');
        $this->addSql('DROP TABLE indicateur');
        $this->addSql('DROP TABLE regle_intervention');
        $this->addSql('DROP TABLE alerte_change');
        $this->addSql('DROP TABLE indice_tension');
    }
}

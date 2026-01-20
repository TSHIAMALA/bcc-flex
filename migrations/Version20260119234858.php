<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260119234858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Tables already exist from import, convert to InnoDB to support FKs
        $this->addSql('ALTER TABLE banques ENGINE=InnoDB');
        $this->addSql('ALTER TABLE transactions_usd ENGINE=InnoDB');
        
        // Remove CREATE TABLE statements as they exist
        // $this->addSql('CREATE TABLE banques ...');
        // $this->addSql('CREATE TABLE transactions_usd ...');
        
        // v_kpi_journalier and v_volumes_usd_par_banque are views or should be handled separately
        // $this->addSql('CREATE TABLE v_kpi_journalier ...');
        // $this->addSql('CREATE TABLE v_volumes_usd_par_banque ...');

        $this->addSql('ALTER TABLE transactions_usd ADD CONSTRAINT FK_48AF1983C497CF91 FOREIGN KEY (conjoncture_id) REFERENCES conjoncture_jour (id)');
        $this->addSql('ALTER TABLE transactions_usd ADD CONSTRAINT FK_48AF198337E080D9 FOREIGN KEY (banque_id) REFERENCES banques (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transactions_usd DROP FOREIGN KEY FK_48AF1983C497CF91');
        $this->addSql('ALTER TABLE transactions_usd DROP FOREIGN KEY FK_48AF198337E080D9');
        $this->addSql('DROP TABLE banques');
        $this->addSql('DROP TABLE transactions_usd');
        $this->addSql('DROP TABLE v_kpi_journalier');
        $this->addSql('DROP TABLE v_volumes_usd_par_banque');
    }
}

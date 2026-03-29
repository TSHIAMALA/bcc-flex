<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajout des indicateurs marché monétaire dans encours_bcc :
 * - taux_interbancaire
 * - taux_moyen_pondere_bbcc
 * - billets_en_circulation
 */
final class Version20260328220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs marché monétaire (taux_interbancaire, taux_moyen_pondere_bbcc, billets_en_circulation) dans encours_bcc';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE encours_bcc ADD taux_interbancaire NUMERIC(6, 2) DEFAULT NULL, ADD taux_moyen_pondere_bbcc NUMERIC(6, 2) DEFAULT NULL, ADD billets_en_circulation NUMERIC(18, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE encours_bcc DROP COLUMN taux_interbancaire, DROP COLUMN taux_moyen_pondere_bbcc, DROP COLUMN billets_en_circulation');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260119234946 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // v_kpi_journalier
        $this->addSql('DROP TABLE IF EXISTS v_kpi_journalier');
        $this->addSql('DROP VIEW IF EXISTS v_kpi_journalier');
        $this->addSql("CREATE OR REPLACE VIEW v_kpi_journalier AS
            SELECT
                cj.date_situation,
                mc.cours_indicatif,
                mc.ecart_indic_parallele,
                rf.reserves_internationales_usd,
                fp.solde
            FROM conjoncture_jour cj
            LEFT JOIN marche_changes mc ON mc.conjoncture_id = cj.id
            LEFT JOIN reserves_financieres rf ON rf.conjoncture_id = cj.id
            LEFT JOIN finances_publiques fp ON fp.conjoncture_id = cj.id
        ");

        // v_banque_vendeur_max
        $this->addSql('DROP TABLE IF EXISTS v_banque_vendeur_max');
        $this->addSql('DROP VIEW IF EXISTS v_banque_vendeur_max');
        $this->addSql("CREATE OR REPLACE VIEW v_banque_vendeur_max AS
            SELECT
                cj.date_situation,
                b.nom,
                t.cours,
                t.volume_usd
            FROM transactions_usd t
            JOIN banques b ON t.banque_id = b.id
            JOIN conjoncture_jour cj ON t.conjoncture_id = cj.id
            WHERE t.type_transaction = 'VENTE'
            AND t.cours = (
                SELECT MAX(t2.cours)
                FROM transactions_usd t2
                WHERE t2.type_transaction = 'VENTE'
                AND t2.conjoncture_id = t.conjoncture_id
            )
        ");

        // v_banque_acheteur_max
        $this->addSql('DROP TABLE IF EXISTS v_banque_acheteur_max');
        $this->addSql('DROP VIEW IF EXISTS v_banque_acheteur_max');
        $this->addSql("CREATE OR REPLACE VIEW v_banque_acheteur_max AS
            SELECT
                cj.date_situation,
                b.nom,
                t.cours,
                t.volume_usd
            FROM transactions_usd t
            JOIN banques b ON t.banque_id = b.id
            JOIN conjoncture_jour cj ON t.conjoncture_id = cj.id
            WHERE t.type_transaction = 'ACHAT'
            AND t.cours = (
                SELECT MAX(t2.cours)
                FROM transactions_usd t2
                WHERE t2.type_transaction = 'ACHAT'
                AND t2.conjoncture_id = t.conjoncture_id
            )
        ");

        // v_marche_interbancaire
        $this->addSql('DROP TABLE IF EXISTS v_marche_interbancaire');
        $this->addSql('DROP VIEW IF EXISTS v_marche_interbancaire');
        $this->addSql("CREATE OR REPLACE VIEW v_marche_interbancaire AS
            SELECT
                cj.date_situation,
                SUM(t.volume_usd) AS volume_total_usd,
                MAX(CASE WHEN t.type_transaction = 'VENTE' THEN t.cours END) AS taux_vendeur_max,
                MAX(CASE WHEN t.type_transaction = 'ACHAT' THEN t.cours END) AS taux_acheteur_max
            FROM conjoncture_jour cj
            JOIN transactions_usd t ON t.conjoncture_id = cj.id
            GROUP BY cj.id, cj.date_situation
        ");

        // v_valeurs_indicateurs
        $this->addSql('DROP TABLE IF EXISTS v_valeurs_indicateurs');
        $this->addSql('DROP VIEW IF EXISTS v_valeurs_indicateurs');
        $this->addSql("CREATE OR REPLACE VIEW v_valeurs_indicateurs AS
            SELECT
                cj.id AS conjoncture_id,
                i.id AS indicateur_id,
                i.code AS code,
                (CASE
                    WHEN (i.code = 'ECART_CHANGE') THEN mc.ecart_indic_parallele
                    WHEN (i.code = 'RESERVES_INT') THEN rf.reserves_internationales_usd
                    WHEN (i.code = 'AVOIRS_LIBRES') THEN rf.avoirs_libres_cdf
                    WHEN (i.code = 'SOLDE_BUDGET') THEN fp.solde
                END) AS valeur
            FROM conjoncture_jour cj
            JOIN indicateur i
            LEFT JOIN marche_changes mc ON mc.conjoncture_id = cj.id
            LEFT JOIN reserves_financieres rf ON rf.conjoncture_id = cj.id
            LEFT JOIN finances_publiques fp ON fp.conjoncture_id = cj.id
        ");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP VIEW IF EXISTS v_kpi_journalier');
        $this->addSql('DROP VIEW IF EXISTS v_banque_vendeur_max');
        $this->addSql('DROP VIEW IF EXISTS v_banque_acheteur_max');
        $this->addSql('DROP VIEW IF EXISTS v_marche_interbancaire');
        $this->addSql('DROP VIEW IF EXISTS v_valeurs_indicateurs');
    }
}

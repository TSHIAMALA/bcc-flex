<?php
// Script qui utilise directement Symfony pour créer la vue dans la bonne base

use Symfony\Component\Dotenv\Dotenv;
use Doctrine\DBAL\DriverManager;

require __DIR__ . '/../vendor/autoload.php';

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/../.env');

// Utiliser DATABASE_URL de l'environnement
$databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;

if (!$databaseUrl) {
    die("DATABASE_URL non trouvée\n");
}

echo "DATABASE_URL: " . preg_replace('/:[^:@]+@/', ':***@', $databaseUrl) . "\n\n";

// Connexion via DBAL
$connectionParams = ['url' => $databaseUrl];
$conn = DriverManager::getConnection($connectionParams);

try {
    echo "Base de données: " . $conn->getDatabase() . "\n";
    
    // Supprimer l'ancienne vue
    $conn->executeStatement("DROP VIEW IF EXISTS v_kpi_journalier");
    echo "Ancienne vue supprimée\n";
    
    // Créer la nouvelle vue avec parallele_vente
    $sql = "
    CREATE VIEW v_kpi_journalier AS
    SELECT 
        DATE_FORMAT(c.date_situation, '%Y-%m-%d') as date_situation,
        m.cours_indicatif,
        m.ecart_indic_parallele,
        m.parallele_vente,
        r.reserves_internationales_usd,
        f.solde
    FROM conjoncture_jour c
    LEFT JOIN marche_changes m ON c.id = m.conjoncture_id
    LEFT JOIN reserves_financieres r ON c.id = r.conjoncture_id
    LEFT JOIN finances_publiques f ON c.id = f.conjoncture_id
    ";
    
    $conn->executeStatement($sql);
    echo "Vue v_kpi_journalier créée avec succès!\n\n";
    
    // Vérifier les colonnes
    $result = $conn->executeQuery("DESCRIBE v_kpi_journalier")->fetchAllAssociative();
    echo "Colonnes de la vue:\n";
    foreach ($result as $col) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    echo "\n";
    
    // Vérifier les données
    $data = $conn->executeQuery("SELECT * FROM v_kpi_journalier LIMIT 3")->fetchAllAssociative();
    echo "Données (3 premiers enregistrements):\n";
    foreach ($data as $row) {
        echo sprintf(
            "  %s: Cours=%.2f, Écart=%.2f, Parallèle=%.2f, Réserves=%.2f, Solde=%.2f\n",
            $row['date_situation'],
            $row['cours_indicatif'] ?? 0,
            $row['ecart_indic_parallele'] ?? 0,
            $row['parallele_vente'] ?? 0,
            $row['reserves_internationales_usd'] ?? 0,
            $row['solde'] ?? 0
        );
    }
    
    echo "\n✓ Migration terminée avec succès!\n";
    
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

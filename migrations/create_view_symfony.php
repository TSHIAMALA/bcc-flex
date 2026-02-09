<?php
// Script qui utilise le kernel Symfony pour accéder à la bonne connexion DB

require __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->bootEnv(__DIR__ . '/../.env');

$kernel = new Kernel($_SERVER['APP_ENV'] ?? 'dev', (bool)($_SERVER['APP_DEBUG'] ?? true));
$kernel->boot();

$container = $kernel->getContainer();
$entityManager = $container->get('doctrine')->getManager();
$conn = $entityManager->getConnection();

echo "Base de données: " . $conn->getDatabase() . "\n\n";

try {
    // Supprimer l'ancienne vue
    $conn->executeStatement("DROP VIEW IF EXISTS v_kpi_journalier");
    echo "Vue supprimée\n";
    
    // Créer la nouvelle vue
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
    echo "Vue créée avec succès!\n\n";
    
    // Vérification
    $result = $conn->executeQuery("DESCRIBE v_kpi_journalier")->fetchAllAssociative();
    echo "Colonnes:\n";
    foreach ($result as $col) {
        echo "  - " . $col['Field'] . "\n";
    }
    
    echo "\nVérification des données:\n";
    $data = $conn->executeQuery("SELECT * FROM v_kpi_journalier LIMIT 2")->fetchAllAssociative();
    print_r($data);
    
    echo "\n✓ Terminé!\n";
    
} catch (\Exception $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
}

$kernel->shutdown();

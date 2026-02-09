<?php
// Script pour créer la vue v_kpi_journalier

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/../.env');

$dsn = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');

// Parse the DSN
$url = parse_url($dsn);
$dbname = ltrim($url['path'], '/');
$host = $url['host'] ?? 'localhost';
$port = $url['port'] ?? 3306;
$user = $url['user'] ?? 'root';
$pass = $url['pass'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "Connexion réussie à la base de données: $dbname\n";

    // Supprimer l'ancienne vue/table si elle existe
    $pdo->exec("DROP VIEW IF EXISTS v_kpi_journalier");
    $pdo->exec("DROP TABLE IF EXISTS v_kpi_journalier");
    echo "Ancienne vue/table supprimée\n";

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
    
    $pdo->exec($sql);
    echo "Vue v_kpi_journalier créée avec succès!\n";

    // Vérifier les données
    $stmt = $pdo->query("SELECT * FROM v_kpi_journalier LIMIT 5");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nDonnées dans la vue:\n";
    echo str_repeat('-', 80) . "\n";
    
    if (empty($results)) {
        echo "Aucune donnée trouvée\n";
    } else {
        foreach ($results as $row) {
            echo sprintf(
                "Date: %s | Cours: %.2f | Écart: %.2f | Réserves: %.2f M USD | Solde: %.2f\n",
                $row['date_situation'],
                $row['cours_indicatif'] ?? 0,
                $row['ecart_indic_parallele'] ?? 0,
                $row['reserves_internationales_usd'] ?? 0,
                $row['solde'] ?? 0
            );
        }
    }

} catch (PDOException $e) {
    echo "Erreur: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n✓ Migration terminée avec succès!\n";

<?php
/**
 * BCC-Flex - Point d'entrée principal
 * Tableau de Bord de Conjoncture Économique
 * Banque Centrale du Congo
 */

// Configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Définir le chemin de base
define('BASE_PATH', dirname(__DIR__));

// Autoloader simple
spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . '/src/models/' . $class . '.php',
        BASE_PATH . '/src/controllers/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Récupérer la page demandée
$page = $_GET['page'] ?? 'dashboard';
$currentPage = $page;

// Définir les métadonnées par page
$pageMeta = [
    'dashboard' => [
        'title' => 'Vue d\'ensemble',
        'headerTitle' => 'Tableau de Bord',
        'headerSubtitle' => 'Indicateurs de Conjoncture Économique'
    ],
    'marche' => [
        'title' => 'Marché des Changes',
        'headerTitle' => 'Marché des Changes',
        'headerSubtitle' => 'Cours, Volumes et Réserves'
    ],
    'finances' => [
        'title' => 'Finances Publiques',
        'headerTitle' => 'Finances Publiques',
        'headerSubtitle' => 'Recettes, Dépenses et Trésorerie'
    ],
    'analyse' => [
        'title' => 'Analyse & Décision',
        'headerTitle' => 'Analyse & Aide à la Décision',
        'headerSubtitle' => 'Indicateurs Synthétiques et Alertes'
    ],
    'alertes' => [
        'title' => 'Alertes',
        'headerTitle' => 'Centre d\'Alertes',
        'headerSubtitle' => 'Configuration des Seuils et Notifications'
    ]
];

// Récupérer les métadonnées
$meta = $pageMeta[$page] ?? $pageMeta['dashboard'];
$pageTitle = $meta['title'];
$headerTitle = $meta['headerTitle'];
$headerSubtitle = $meta['headerSubtitle'];

// Charger les données selon la page
try {
    $conjoncture = new Conjoncture();
    $marcheChanges = new MarcheChanges();
    $financesPubliques = new FinancesPubliques();
    
    // Données communes
    $latestKPI = $conjoncture->getLatestKPI();
    $previousKPI = $conjoncture->getPreviousKPI();
    
} catch (Exception $e) {
    $error = "Erreur de connexion à la base de données: " . $e->getMessage();
}

// Fonction helper pour calculer la variation
function calculateVariation($current, $previous) {
    if (!$previous || $previous == 0) return null;
    return (($current - $previous) / $previous) * 100;
}

// Fonction helper pour formater les nombres
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, ',', ' ');
}

// Fonction helper pour formater en millions
function formatMillions($number) {
    return number_format($number, 2, ',', ' ') . ' Mds';
}

// Inclure le layout header
include BASE_PATH . '/src/views/layout/header.php';

// Inclure la vue correspondante
$viewPath = BASE_PATH . '/src/views/' . $page . '/index.php';
if (file_exists($viewPath)) {
    include $viewPath;
} else {
    include BASE_PATH . '/src/views/dashboard/index.php';
}

// Inclure le layout footer
include BASE_PATH . '/src/views/layout/footer.php';

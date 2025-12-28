<?php
/**
 * Vue Alertes
 * Configuration des seuils et centre de notifications
 */

// Récupérer les données actuelles pour analyse
$latestKPI = $conjoncture->getLatestKPI();
$latestMarche = $marcheChanges->getLatest();
$latestFinances = $financesPubliques->getLatest();

// Définir les seuils d'alerte
$seuils = [
    'ecart_change' => 100,
    'reserves_min' => 5000,
    'deficit_max' => -200
];

// Vérifier les alertes actives
$alertes = [];

if ($latestMarche && $latestMarche['ecart_indic_parallele'] > $seuils['ecart_change']) {
    $alertes[] = [
        'type' => 'warning',
        'icon' => 'exchange-alt',
        'titre' => 'Écart de change élevé',
        'message' => sprintf('L\'écart indicatif/parallèle (%s CDF) dépasse le seuil de %s CDF', 
            formatNumber($latestMarche['ecart_indic_parallele']), 
            formatNumber($seuils['ecart_change'])),
        'date' => $latestMarche['date_situation']
    ];
}

if ($latestKPI && $latestKPI['reserves_internationales_usd'] < $seuils['reserves_min']) {
    $alertes[] = [
        'type' => 'danger',
        'icon' => 'piggy-bank',
        'titre' => 'Réserves internationales basses',
        'message' => sprintf('Les réserves (%s Mio USD) sont inférieures au seuil de %s Mio USD',
            formatNumber($latestKPI['reserves_internationales_usd']),
            formatNumber($seuils['reserves_min'])),
        'date' => $latestKPI['date_situation']
    ];
}

if ($latestFinances && $latestFinances['solde'] < $seuils['deficit_max']) {
    $alertes[] = [
        'type' => 'danger',
        'icon' => 'chart-line',
        'titre' => 'Déficit budgétaire critique',
        'message' => sprintf('Le déficit (%s Mds CDF) dépasse le seuil de %s Mds CDF',
            formatNumber($latestFinances['solde']),
            formatNumber($seuils['deficit_max'])),
        'date' => $latestFinances['date_situation']
    ];
}
?>

<!-- Statistiques des Alertes -->
<div class="kpi-grid mb-30">
    <div class="kpi-card <?= count($alertes) > 0 ? 'warning' : 'success' ?>">
        <div class="kpi-header">
            <div class="kpi-icon"><i class="fas fa-bell"></i></div>
        </div>
        <div class="kpi-value"><?= count($alertes) ?></div>
        <div class="kpi-label">Alertes Actives</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-header">
            <div class="kpi-icon"><i class="fas fa-check-circle"></i></div>
        </div>
        <div class="kpi-value">3</div>
        <div class="kpi-label">Règles Configurées</div>
    </div>
    
    <div class="kpi-card">
        <div class="kpi-header">
            <div class="kpi-icon"><i class="fas fa-clock"></i></div>
        </div>
        <div class="kpi-value"><?= date('H:i') ?></div>
        <div class="kpi-label">Dernière Vérification</div>
    </div>
</div>

<!-- Alertes Actives -->
<div class="card mb-30">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-exclamation-triangle"></i>
            Alertes Actives
        </h3>
        <span class="card-badge badge-<?= count($alertes) > 0 ? 'warning' : 'success' ?>">
            <?= count($alertes) ?> alerte(s)
        </span>
    </div>
    
    <?php if (empty($alertes)): ?>
    <div class="alert-box alert-success">
        <i class="fas fa-check-circle alert-icon"></i>
        <div class="alert-content">
            <h4>Aucune alerte active</h4>
            <p>Tous les indicateurs sont dans les limites normales.</p>
        </div>
    </div>
    <?php else: ?>
        <?php foreach ($alertes as $alerte): ?>
        <div class="alert-box alert-<?= $alerte['type'] ?>">
            <i class="fas fa-<?= $alerte['icon'] ?> alert-icon"></i>
            <div class="alert-content">
                <h4><?= $alerte['titre'] ?></h4>
                <p><?= $alerte['message'] ?></p>
                <small class="text-muted">Détecté le <?= date('d/m/Y', strtotime($alerte['date'])) ?></small>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Configuration des Seuils -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-cog"></i>
            Configuration des Seuils d'Alerte
        </h3>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Indicateur</th>
                    <th>Seuil</th>
                    <th>Valeur Actuelle</th>
                    <th>État</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <i class="fas fa-exchange-alt text-primary"></i>
                        <strong>Écart Indicatif/Parallèle</strong>
                    </td>
                    <td>> <?= formatNumber($seuils['ecart_change']) ?> CDF</td>
                    <td><?= formatNumber($latestMarche['ecart_indic_parallele'] ?? 0) ?> CDF</td>
                    <td>
                        <?php if (($latestMarche['ecart_indic_parallele'] ?? 0) > $seuils['ecart_change']): ?>
                            <span class="variation negative"><i class="fas fa-exclamation-triangle"></i> Dépassé</span>
                        <?php else: ?>
                            <span class="variation positive"><i class="fas fa-check"></i> Normal</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <i class="fas fa-piggy-bank text-primary"></i>
                        <strong>Réserves Internationales</strong>
                    </td>
                    <td>< <?= formatNumber($seuils['reserves_min']) ?> Mio USD</td>
                    <td><?= formatNumber($latestKPI['reserves_internationales_usd'] ?? 0) ?> Mio USD</td>
                    <td>
                        <?php if (($latestKPI['reserves_internationales_usd'] ?? 0) < $seuils['reserves_min']): ?>
                            <span class="variation negative"><i class="fas fa-exclamation-triangle"></i> Critique</span>
                        <?php else: ?>
                            <span class="variation positive"><i class="fas fa-check"></i> Normal</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <i class="fas fa-chart-line text-primary"></i>
                        <strong>Déficit Budgétaire</strong>
                    </td>
                    <td>< <?= formatNumber($seuils['deficit_max']) ?> Mds CDF</td>
                    <td class="<?= ($latestFinances['solde'] ?? 0) < 0 ? 'text-danger' : 'text-success' ?>">
                        <?= formatNumber($latestFinances['solde'] ?? 0) ?> Mds CDF
                    </td>
                    <td>
                        <?php if (($latestFinances['solde'] ?? 0) < $seuils['deficit_max']): ?>
                            <span class="variation negative"><i class="fas fa-exclamation-triangle"></i> Critique</span>
                        <?php else: ?>
                            <span class="variation positive"><i class="fas fa-check"></i> Normal</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <div class="alert-box alert-info mt-20">
        <i class="fas fa-info-circle alert-icon"></i>
        <div class="alert-content">
            <h4>Configuration avancée</h4>
            <p>La modification des seuils d'alerte sera disponible dans une prochaine version.</p>
        </div>
    </div>
</div>

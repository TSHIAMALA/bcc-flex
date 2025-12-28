<?php
/**
 * Vue Dashboard - Vue d'ensemble
 * Design inspiré Power BI / Tableau avec graphiques variés
 */

if (isset($error)) {
    echo '<div class="alert-box alert-danger"><i class="fas fa-exclamation-circle alert-icon"></i><div class="alert-content"><h4>Erreur</h4><p>' . htmlspecialchars($error) . '</p></div></div>';
    return;
}

// Récupérer les données
$kpiData = $conjoncture->getKPIJournalier(7);
$latestMarche = $marcheChanges->getLatest();
$latestReserves = $marcheChanges->getLatestReserves();
$latestFinances = $financesPubliques->getLatest();
$evolutionMarche = $marcheChanges->getEvolutionData(7);
$volumes = $marcheChanges->getLatestVolumes();
$paie = $financesPubliques->getLatestPaie();

// Calcul des variations
$varCoursIndicatif = $previousKPI ? calculateVariation($latestKPI['cours_indicatif'], $previousKPI['cours_indicatif']) : 0;
$varReserves = $previousKPI ? calculateVariation($latestKPI['reserves_internationales_usd'], $previousKPI['reserves_internationales_usd']) : 0;
$varSolde = $previousKPI ? ($latestKPI['solde'] - $previousKPI['solde']) : 0;
?>

<!-- Header Stats Row - Style Power BI -->
<div class="stats-header mb-30">
    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-icon"><i class="fas fa-university"></i></div>
            <div class="stat-content">
                <span class="stat-label">COURS INDICATIF</span>
                <span class="stat-value"><?= formatNumber($latestKPI['cours_indicatif'] ?? 0, 2) ?></span>
                <span class="stat-change <?= $varCoursIndicatif > 0 ? 'negative' : 'positive' ?>">
                    <i class="fas fa-<?= $varCoursIndicatif > 0 ? 'caret-up' : 'caret-down' ?>"></i>
                    <?= formatNumber(abs($varCoursIndicatif), 2) ?>%
                </span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon warning"><i class="fas fa-exchange-alt"></i></div>
            <div class="stat-content">
                <span class="stat-label">COURS PARALLÈLE</span>
                <span class="stat-value"><?= formatNumber($latestKPI['parallele_vente'] ?? 0, 2) ?></span>
                <span class="stat-change neutral">Vente</span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon success"><i class="fas fa-piggy-bank"></i></div>
            <div class="stat-content">
                <span class="stat-label">RÉSERVES INT.</span>
                <span class="stat-value"><?= formatNumber($latestKPI['reserves_internationales_usd'] ?? 0, 0) ?>M</span>
                <span class="stat-change <?= $varReserves >= 0 ? 'positive' : 'negative' ?>">
                    <i class="fas fa-<?= $varReserves >= 0 ? 'caret-up' : 'caret-down' ?>"></i>
                    <?= formatNumber(abs($varReserves), 2) ?>%
                </span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon <?= ($latestKPI['solde'] ?? 0) >= 0 ? 'success' : 'danger' ?>"><i class="fas fa-balance-scale"></i></div>
            <div class="stat-content">
                <span class="stat-label">SOLDE BUDGET</span>
                <span class="stat-value"><?= formatNumber($latestKPI['solde'] ?? 0, 2) ?></span>
                <span class="stat-change <?= ($latestKPI['solde'] ?? 0) >= 0 ? 'positive' : 'negative' ?>">
                    <?= ($latestKPI['solde'] ?? 0) >= 0 ? 'Excédent' : 'Déficit' ?>
                </span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon info"><i class="fas fa-chart-area"></i></div>
            <div class="stat-content">
                <span class="stat-label">ÉCART CHANGE</span>
                <span class="stat-value"><?= formatNumber($latestMarche['ecart_indic_parallele'] ?? 0, 0) ?></span>
                <span class="stat-change <?= ($latestMarche['ecart_indic_parallele'] ?? 0) > 100 ? 'negative' : 'positive' ?>">CDF</span>
            </div>
        </div>
    </div>
</div>

<!-- Alertes -->
<?php if ($latestMarche && $latestMarche['ecart_indic_parallele'] > 100): ?>
<div class="alert-box alert-warning">
    <i class="fas fa-exclamation-triangle alert-icon"></i>
    <div class="alert-content">
        <h4>⚠️ Alerte : Écart de change élevé (<?= formatNumber($latestMarche['ecart_indic_parallele']) ?> CDF)</h4>
        <p>L'écart entre le cours indicatif et le cours parallèle dépasse le seuil d'alerte.</p>
    </div>
</div>
<?php endif; ?>

<!-- Row 1: Evolution Chart + Pie Chart -->
<div class="grid-2 mb-30">
    <!-- Graphique Évolution des Cours (Multi-ligne) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-chart-line"></i>
                Évolution du Taux de Change
            </h3>
            <div class="card-actions">
                <span class="card-badge badge-info">7 jours</span>
            </div>
        </div>
        <div class="chart-container" style="height: 280px;">
            <canvas id="chartCoursEvolution"></canvas>
        </div>
    </div>
    
    <!-- Camembert Répartition Recettes -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-chart-pie"></i>
                Répartition des Recettes
            </h3>
        </div>
        <div style="display: flex; align-items: center; gap: 20px;">
            <div class="chart-container" style="height: 250px; flex: 1;">
                <canvas id="chartRecettesPie"></canvas>
            </div>
            <div class="pie-legend">
                <div class="legend-item">
                    <span class="legend-color" style="background: #007FFF;"></span>
                    <div>
                        <div class="legend-label">Recettes Fiscales</div>
                        <div class="legend-value"><?= formatNumber($latestFinances['recettes_fiscales'] ?? 0) ?> Mds</div>
                    </div>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: #10b981;"></span>
                    <div>
                        <div class="legend-label">Autres Recettes</div>
                        <div class="legend-value"><?= formatNumber($latestFinances['autres_recettes'] ?? 0) ?> Mds</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 2: Barres horizontales + Waterfall -->
<div class="grid-2 mb-30">
    <!-- Barres Horizontales - Volumes par Banque -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-university"></i>
                Volumes USD par Banque
            </h3>
        </div>
        <div class="chart-container" style="height: 280px;">
            <canvas id="chartVolumesBar"></canvas>
        </div>
    </div>
    
    <!-- Waterfall - Recettes vs Dépenses -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-water"></i>
                Cascade Budgétaire
            </h3>
        </div>
        <div class="chart-container" style="height: 280px;">
            <canvas id="chartWaterfall"></canvas>
        </div>
    </div>
</div>

<!-- Row 3: Area Chart + Doughnut -->
<div class="grid-2 mb-30">
    <!-- Area Chart - Réserves -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-layer-group"></i>
                Évolution des Réserves
            </h3>
        </div>
        <div class="chart-container" style="height: 280px;">
            <canvas id="chartReservesArea"></canvas>
        </div>
    </div>
    
    <!-- Doughnut avec centre - Paie -->
    <?php if ($paie): ?>
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-users"></i>
                Exécution de la Paie
            </h3>
        </div>
        <div style="position: relative;">
            <div class="chart-container" style="height: 250px;">
                <canvas id="chartPaieDoughnut"></canvas>
            </div>
            <div class="doughnut-center">
                <div class="doughnut-value"><?= formatNumber(($paie['montant_paye'] / $paie['montant_total']) * 100, 0) ?>%</div>
                <div class="doughnut-label">Payé</div>
            </div>
        </div>
        <div class="paie-stats">
            <div class="paie-stat success">
                <i class="fas fa-check-circle"></i>
                <span>Payé: <?= formatNumber($paie['montant_paye']) ?> Mds</span>
            </div>
            <div class="paie-stat danger">
                <i class="fas fa-clock"></i>
                <span>Restant: <?= formatNumber($paie['montant_restant']) ?> Mds</span>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Row 4: Table + Polar Area -->
<div class="grid-2">
    <!-- Tableau des données -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-table"></i>
                Historique des Indicateurs
            </h3>
        </div>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Cours BCC</th>
                        <th>Parallèle</th>
                        <th>Écart</th>
                        <th>Solde</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kpiData as $kpi): ?>
                    <tr>
                        <td><strong><?= date('d/m', strtotime($kpi['date_situation'])) ?></strong></td>
                        <td><?= formatNumber($kpi['cours_indicatif'], 2) ?></td>
                        <td><?= formatNumber($kpi['parallele_vente'], 2) ?></td>
                        <td>
                            <span class="inline-badge <?= $kpi['ecart_indic_parallele'] > 100 ? 'danger' : 'success' ?>">
                                <?= formatNumber($kpi['ecart_indic_parallele'], 0) ?>
                            </span>
                        </td>
                        <td class="<?= $kpi['solde'] >= 0 ? 'text-success font-bold' : 'text-danger font-bold' ?>">
                            <?= formatNumber($kpi['solde'], 2) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Radar / Indicateurs Synthétiques -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-radar"></i>
                Indicateurs de Vigilance
            </h3>
        </div>
        <div class="chart-container" style="height: 280px;">
            <canvas id="chartRadar"></canvas>
        </div>
    </div>
</div>

<style>
/* Stats Header Row - Power BI Style */
.stats-header {
    background: linear-gradient(135deg, #001a3d 0%, #003366 100%);
    border-radius: var(--border-radius);
    padding: 25px;
    box-shadow: var(--shadow-lg);
}

.stats-row {
    display: flex;
    gap: 15px;
    overflow-x: auto;
}

.stat-box {
    flex: 1;
    min-width: 180px;
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 12px;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    transition: var(--transition);
}

.stat-box:hover {
    background: rgba(255, 255, 255, 0.15);
    transform: translateY(-3px);
}

.stat-icon {
    width: 50px;
    height: 50px;
    background: rgba(0, 127, 255, 0.3);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    color: #4da3ff;
}

.stat-icon.success { background: rgba(16, 185, 129, 0.3); color: #34d399; }
.stat-icon.danger { background: rgba(239, 68, 68, 0.3); color: #f87171; }
.stat-icon.warning { background: rgba(245, 158, 11, 0.3); color: #fbbf24; }
.stat-icon.info { background: rgba(59, 130, 246, 0.3); color: #60a5fa; }

.stat-content {
    display: flex;
    flex-direction: column;
}

.stat-label {
    font-size: 0.7rem;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.6);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.stat-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: white;
    line-height: 1.2;
}

.stat-change {
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
}

.stat-change.positive { color: #34d399; }
.stat-change.negative { color: #f87171; }
.stat-change.neutral { color: rgba(255, 255, 255, 0.6); }

/* Pie Chart Legend */
.pie-legend {
    min-width: 160px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.legend-item:last-child {
    border-bottom: none;
}

.legend-color {
    width: 14px;
    height: 14px;
    border-radius: 4px;
}

.legend-label {
    font-size: 0.85rem;
    color: var(--text-secondary);
}

.legend-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
}

/* Doughnut Center */
.doughnut-center {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    text-align: center;
}

.doughnut-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--bcc-primary);
}

.doughnut-label {
    font-size: 0.9rem;
    color: var(--text-secondary);
}

/* Paie Stats */
.paie-stats {
    display: flex;
    justify-content: center;
    gap: 30px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f1f5f9;
}

.paie-stat {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    font-weight: 600;
}

.paie-stat.success { color: var(--success); }
.paie-stat.danger { color: var(--danger); }

/* Inline Badge */
.inline-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
}

.inline-badge.success { background: var(--success-light); color: var(--success); }
.inline-badge.danger { background: var(--danger-light); color: var(--danger); }

/* Card Actions */
.card-actions {
    display: flex;
    gap: 10px;
}
</style>

<script>
// Données
const evolutionData = <?= json_encode($evolutionMarche) ?>;
const financesData = <?= json_encode($financesPubliques->getEvolutionData(7)) ?>;
const reservesData = <?= json_encode($marcheChanges->getReserves(7)) ?>;

// Config commune
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.plugins.legend.labels.usePointStyle = true;

// 1. Graphique Évolution des Cours (Ligne multi-séries)
new Chart(document.getElementById('chartCoursEvolution'), {
    type: 'line',
    data: {
        labels: evolutionData.map(d => new Date(d.date_situation).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })),
        datasets: [
            {
                label: 'Indicatif BCC',
                data: evolutionData.map(d => d.cours_indicatif),
                borderColor: '#007FFF',
                backgroundColor: 'rgba(0, 127, 255, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointBackgroundColor: '#007FFF',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            },
            {
                label: 'Parallèle Vente',
                data: evolutionData.map(d => d.parallele_vente),
                borderColor: '#ef4444',
                borderWidth: 2,
                borderDash: [5, 5],
                fill: false,
                tension: 0.4,
                pointRadius: 4
            },
            {
                label: 'Parallèle Achat',
                data: evolutionData.map(d => d.parallele_achat),
                borderColor: '#10b981',
                borderWidth: 2,
                fill: false,
                tension: 0.4,
                pointRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
            legend: { position: 'top', labels: { padding: 15 } }
        },
        scales: {
            y: { beginAtZero: false, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// 2. Camembert Recettes
new Chart(document.getElementById('chartRecettesPie'), {
    type: 'pie',
    data: {
        labels: ['Recettes Fiscales', 'Autres Recettes'],
        datasets: [{
            data: [<?= $latestFinances['recettes_fiscales'] ?? 0 ?>, <?= $latestFinances['autres_recettes'] ?? 0 ?>],
            backgroundColor: ['#007FFF', '#10b981'],
            borderWidth: 0,
            hoverOffset: 15
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        }
    }
});

// 3. Barres Horizontales - Volumes
<?php 
$volumeLabels = [];
$volumeValues = [];
foreach ($volumes as $v) {
    $volumeLabels[] = $v['banque'] . ' (' . $v['type_transaction'] . ')';
    $volumeValues[] = $v['volume_total_usd'];
}
?>
new Chart(document.getElementById('chartVolumesBar'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($volumeLabels) ?>,
        datasets: [{
            label: 'Volume USD',
            data: <?= json_encode($volumeValues) ?>,
            backgroundColor: ['#007FFF', '#0056b3', '#10b981', '#059669', '#f59e0b'],
            borderRadius: 8,
            barThickness: 25
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(0,0,0,0.05)' }, ticks: { callback: v => (v/1000000).toFixed(1) + 'M' } },
            y: { grid: { display: false } }
        }
    }
});

// 4. Waterfall Chart (Cascade)
new Chart(document.getElementById('chartWaterfall'), {
    type: 'bar',
    data: {
        labels: ['Recettes Fiscales', 'Autres Recettes', 'Total Recettes', 'Dépenses', 'Solde'],
        datasets: [{
            label: 'Montant',
            data: [
                <?= $latestFinances['recettes_fiscales'] ?? 0 ?>,
                <?= $latestFinances['autres_recettes'] ?? 0 ?>,
                <?= $latestFinances['recettes_totales'] ?? 0 ?>,
                -<?= $latestFinances['depenses_totales'] ?? 0 ?>,
                <?= $latestFinances['solde'] ?? 0 ?>
            ],
            backgroundColor: [
                '#10b981', '#34d399', '#007FFF', '#ef4444', 
                <?= ($latestFinances['solde'] ?? 0) >= 0 ? "'#10b981'" : "'#ef4444'" ?>
            ],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// 5. Area Chart - Réserves
new Chart(document.getElementById('chartReservesArea'), {
    type: 'line',
    data: {
        labels: reservesData.reverse().map(d => new Date(d.date_situation).toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' })),
        datasets: [
            {
                label: 'Réserves Int. (Mio USD)',
                data: reservesData.map(d => d.reserves_internationales_usd),
                borderColor: '#007FFF',
                backgroundColor: 'rgba(0, 127, 255, 0.2)',
                fill: true,
                tension: 0.4
            },
            {
                label: 'Avoirs Ext. (Mio USD)',
                data: reservesData.map(d => d.avoirs_externes_usd),
                borderColor: '#8b5cf6',
                backgroundColor: 'rgba(139, 92, 246, 0.1)',
                fill: true,
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: {
            y: { stacked: false, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// 6. Doughnut Paie
<?php if ($paie): ?>
new Chart(document.getElementById('chartPaieDoughnut'), {
    type: 'doughnut',
    data: {
        labels: ['Payé', 'Restant'],
        datasets: [{
            data: [<?= $paie['montant_paye'] ?>, <?= $paie['montant_restant'] ?>],
            backgroundColor: ['#10b981', '#e2e8f0'],
            borderWidth: 0,
            cutout: '75%'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
    }
});
<?php endif; ?>

// 7. Radar Chart - Indicateurs
new Chart(document.getElementById('chartRadar'), {
    type: 'radar',
    data: {
        labels: ['Stabilité Change', 'Niveau Réserves', 'Équilibre Budget', 'Liquidité', 'Croissance'],
        datasets: [{
            label: 'Situation Actuelle',
            data: [
                Math.max(0, 100 - <?= ($latestMarche['ecart_indic_parallele'] ?? 0) / 2 ?>),
                Math.min(100, <?= ($latestKPI['reserves_internationales_usd'] ?? 0) / 100 ?>),
                <?= ($latestKPI['solde'] ?? 0) >= 0 ? 80 : 40 ?>,
                70,
                65
            ],
            borderColor: '#007FFF',
            backgroundColor: 'rgba(0, 127, 255, 0.2)',
            borderWidth: 2,
            pointBackgroundColor: '#007FFF'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            r: {
                beginAtZero: true,
                max: 100,
                ticks: { stepSize: 25 },
                grid: { color: 'rgba(0,0,0,0.05)' }
            }
        },
        plugins: { legend: { position: 'top' } }
    }
});
</script>

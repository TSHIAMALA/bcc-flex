<?php
/**
 * Vue Finances Publiques
 * Design inspiré du template "Statistiques Budgétaires"
 */

$finData = $financesPubliques->getAll(10);
$latestFin = $financesPubliques->getLatest();
$tresorerie = $financesPubliques->getLatestTresorerie();
$titres = $financesPubliques->getLatestTitres();
$paie = $financesPubliques->getLatestPaie();
$evolutionData = $financesPubliques->getEvolutionData(30);

$previousFin = count($finData) > 1 ? $finData[1] : null;
$varRecettes = $previousFin ? calculateVariation($latestFin['recettes_totales'], $previousFin['recettes_totales']) : 0;
$varDepenses = $previousFin ? calculateVariation($latestFin['depenses_totales'], $previousFin['depenses_totales']) : 0;

// Calculs de performance
$tauxExecution = $latestFin['recettes_totales'] > 0 
    ? min(100, ($latestFin['recettes_totales'] / ($latestFin['recettes_totales'] + 50)) * 100) 
    : 0;
$tauxPaie = $paie ? ($paie['montant_paye'] / $paie['montant_total']) * 100 : 0;

// Données par type de recette
$postes = [
    ['nom' => 'Recettes Fiscales', 'planifie' => $latestFin['recettes_totales'] * 1.2, 'realise' => $latestFin['recettes_fiscales'] ?? 0, 'couleur' => 'primary'],
    ['nom' => 'Autres Recettes', 'planifie' => $latestFin['recettes_totales'] * 0.3, 'realise' => $latestFin['autres_recettes'] ?? 0, 'couleur' => 'info'],
    ['nom' => 'Dépenses Courantes', 'planifie' => $latestFin['depenses_totales'] * 1.1, 'realise' => $latestFin['depenses_totales'] ?? 0, 'couleur' => 'warning'],
    ['nom' => 'Trésorerie', 'planifie' => 100, 'realise' => $tresorerie['solde_apres_financement'] ?? 50, 'couleur' => 'success'],
];
?>

<!-- Header KPI Cards -->
<div class="kpi-header-grid mb-30">
    <div class="kpi-header-card">
        <div class="kpi-header-icon success"><i class="fas fa-coins"></i></div>
        <div class="kpi-header-content">
            <span class="kpi-header-label">RECETTES TOTALES</span>
            <span class="kpi-header-value"><?= formatNumber($latestFin['recettes_totales'] ?? 0, 2) ?></span>
            <span class="kpi-header-sublabel">Milliards CDF</span>
        </div>
    </div>
    <div class="kpi-header-card">
        <div class="kpi-header-icon danger"><i class="fas fa-file-invoice-dollar"></i></div>
        <div class="kpi-header-content">
            <span class="kpi-header-label">DÉPENSES TOTALES</span>
            <span class="kpi-header-value"><?= formatNumber($latestFin['depenses_totales'] ?? 0, 2) ?></span>
            <span class="kpi-header-sublabel">Milliards CDF</span>
        </div>
    </div>
    <div class="kpi-header-card highlight">
        <div class="kpi-header-content">
            <span class="kpi-header-label">TAUX D'EXÉCUTION</span>
            <span class="kpi-header-value"><?= formatNumber($tauxExecution, 1) ?>%</span>
        </div>
        <div class="kpi-header-progress-circle">
            <svg viewBox="0 0 36 36">
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="3"/>
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="white" stroke-width="3" stroke-dasharray="<?= $tauxExecution ?>, 100"/>
            </svg>
        </div>
    </div>
    <div class="kpi-header-card <?= ($latestFin['solde'] ?? 0) >= 0 ? '' : 'deficit' ?>">
        <div class="kpi-header-icon <?= ($latestFin['solde'] ?? 0) >= 0 ? 'success' : 'danger' ?>">
            <i class="fas fa-balance-scale"></i>
        </div>
        <div class="kpi-header-content">
            <span class="kpi-header-label">SOLDE BUDGÉTAIRE</span>
            <span class="kpi-header-value"><?= formatNumber($latestFin['solde'] ?? 0, 2) ?></span>
            <span class="kpi-header-sublabel"><?= ($latestFin['solde'] ?? 0) >= 0 ? 'Excédent' : 'Déficit' ?></span>
        </div>
    </div>
</div>

<?php if (($latestFin['solde'] ?? 0) < 0): ?>
<div class="alert-box alert-danger">
    <i class="fas fa-exclamation-circle alert-icon"></i>
    <div class="alert-content">
        <h4>⚠️ Déficit Budgétaire de <?= formatNumber(abs($latestFin['solde'])) ?> Mds CDF</h4>
        <p>Les dépenses dépassent les recettes. Une attention particulière est requise.</p>
    </div>
</div>
<?php endif; ?>

<!-- Row 1: Table with Progress + Bar Chart -->
<div class="grid-2 mb-30">
    <!-- Tableau des Postes Budgétaires -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-tasks"></i>
                Exécution Budgétaire par Poste
            </h3>
        </div>
        <div class="table-container">
            <table class="data-table progress-table">
                <thead>
                    <tr>
                        <th>Poste</th>
                        <th>Prévu (Mds)</th>
                        <th>Réalisé (Mds)</th>
                        <th>Taux</th>
                        <th style="width: 150px;">Exécution</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($postes as $p): ?>
                    <?php $taux = $p['planifie'] > 0 ? ($p['realise'] / $p['planifie']) * 100 : 0; ?>
                    <tr>
                        <td><strong><?= $p['nom'] ?></strong></td>
                        <td><?= formatNumber($p['planifie'], 2) ?></td>
                        <td><?= formatNumber($p['realise'], 2) ?></td>
                        <td>
                            <span class="taux-badge <?= $taux >= 80 ? 'success' : ($taux >= 50 ? 'warning' : 'danger') ?>">
                                <?= formatNumber($taux, 0) ?>%
                            </span>
                        </td>
                        <td>
                            <div class="table-progress">
                                <div class="table-progress-fill <?= $p['couleur'] ?>" style="width: <?= min(100, $taux) ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Histogramme Recettes vs Dépenses -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-chart-bar"></i>
                Histogramme Recettes vs Dépenses
            </h3>
            <div class="chart-legend-inline">
                <span class="legend-dot success"></span> Recettes
                <span class="legend-dot danger"></span> Dépenses
            </div>
        </div>
        <div class="chart-container" style="height: 300px;">
            <canvas id="chartBudget"></canvas>
        </div>
    </div>
</div>

<!-- Row 2: Liste détaillée + Donut -->
<div class="grid-2 mb-30">
    <!-- Liste des Finances Journalières -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list"></i>
                Historique des Finances - Total : <?= count($finData) ?>
            </h3>
        </div>
        <div class="table-controls">
            <div class="table-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Rechercher..." id="searchFinances">
            </div>
        </div>
        <div class="table-container" style="max-height: 320px; overflow-y: auto;">
            <table class="data-table" id="finTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Recettes</th>
                        <th>Dépenses</th>
                        <th>Taux</th>
                        <th>Solde</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($finData as $fin): 
                        $taux = $fin['depenses_totales'] > 0 ? ($fin['recettes_totales'] / $fin['depenses_totales']) * 100 : 100;
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($fin['date_situation'])) ?></td>
                        <td class="text-success font-bold"><?= formatNumber($fin['recettes_totales'], 2) ?></td>
                        <td class="text-danger"><?= formatNumber($fin['depenses_totales'], 2) ?></td>
                        <td>
                            <div class="inline-progress">
                                <div class="inline-progress-fill" style="width: <?= min(100, $taux) ?>%; background: <?= $taux >= 100 ? '#10b981' : ($taux >= 80 ? '#f59e0b' : '#ef4444') ?>;"></div>
                            </div>
                            <span class="inline-perf"><?= formatNumber($taux, 0) ?>%</span>
                        </td>
                        <td>
                            <span class="score-badge <?= $fin['solde'] >= 0 ? 'success' : 'danger' ?>">
                                <?= $fin['solde'] >= 0 ? '+' : '' ?><?= formatNumber($fin['solde'], 2) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Donut Répartition des Recettes -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-chart-pie"></i>
                Répartition des Recettes
            </h3>
        </div>
        <div class="donut-wrapper">
            <div class="chart-container" style="height: 220px;">
                <canvas id="chartRecettes"></canvas>
            </div>
            <div class="donut-legend-horizontal">
                <div class="donut-legend-item">
                    <span class="donut-legend-color" style="background: #007FFF;"></span>
                    <span class="donut-legend-label">Fiscales: <?= formatNumber($latestFin['recettes_fiscales'] ?? 0, 2) ?> Mds</span>
                </div>
                <div class="donut-legend-item">
                    <span class="donut-legend-color" style="background: #10b981;"></span>
                    <span class="donut-legend-label">Autres: <?= formatNumber($latestFin['autres_recettes'] ?? 0, 2) ?> Mds</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Trésorerie + Titres + Paie -->
<div class="grid-3">
    <!-- Trésorerie -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-vault"></i> Trésorerie</h3>
        </div>
        <?php if ($tresorerie): ?>
        <div class="tresorerie-stats">
            <div class="tresorerie-item">
                <span class="tresorerie-label">Avant financement</span>
                <span class="tresorerie-value"><?= formatNumber($tresorerie['solde_avant_financement']) ?></span>
            </div>
            <div class="tresorerie-item highlight">
                <span class="tresorerie-label">Après financement</span>
                <span class="tresorerie-value"><?= formatNumber($tresorerie['solde_apres_financement']) ?></span>
            </div>
            <div class="tresorerie-item">
                <span class="tresorerie-label">Cumulé annuel</span>
                <span class="tresorerie-value"><?= formatNumber($tresorerie['cumul_annuel']) ?></span>
            </div>
        </div>
        <?php else: ?>
        <p class="text-muted text-center">Données non disponibles</p>
        <?php endif; ?>
    </div>
    
    <!-- Titres Publics -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-contract"></i> Titres Publics</h3>
        </div>
        <?php if ($titres): ?>
        <div class="titres-grid">
            <div class="titre-box">
                <div class="titre-type">OT CDF</div>
                <div class="titre-value"><?= formatNumber($titres['ot_cdf']) ?></div>
            </div>
            <div class="titre-box">
                <div class="titre-type">OT USD</div>
                <div class="titre-value"><?= formatNumber($titres['ot_usd']) ?></div>
            </div>
            <div class="titre-box">
                <div class="titre-type">BT CDF</div>
                <div class="titre-value"><?= formatNumber($titres['bt_cdf']) ?></div>
            </div>
            <div class="titre-box">
                <div class="titre-type">BT USD</div>
                <div class="titre-value"><?= formatNumber($titres['bt_usd']) ?></div>
            </div>
        </div>
        <?php else: ?>
        <p class="text-muted text-center">Données non disponibles</p>
        <?php endif; ?>
    </div>
    
    <!-- Exécution Paie -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-users"></i> Exécution Paie</h3>
        </div>
        <?php if ($paie): ?>
        <div class="paie-wrapper">
            <div class="paie-progress-circle">
                <svg viewBox="0 0 36 36">
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e0e0e0" stroke-width="4"/>
                    <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#10b981" stroke-width="4" stroke-dasharray="<?= $tauxPaie ?>, 100"/>
                </svg>
                <div class="paie-percent"><?= formatNumber($tauxPaie, 0) ?>%</div>
            </div>
            <div class="paie-details">
                <div class="paie-detail success">
                    <i class="fas fa-check-circle"></i>
                    <span>Payé: <?= formatNumber($paie['montant_paye']) ?> Mds</span>
                </div>
                <div class="paie-detail danger">
                    <i class="fas fa-clock"></i>
                    <span>Restant: <?= formatNumber($paie['montant_restant']) ?> Mds</span>
                </div>
            </div>
        </div>
        <?php else: ?>
        <p class="text-muted text-center">Données non disponibles</p>
        <?php endif; ?>
    </div>
</div>

<style>
/* KPI Header Grid */
.kpi-header-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
.kpi-header-card { background: white; border-radius: var(--border-radius); padding: 20px 25px; display: flex; align-items: center; gap: 15px; box-shadow: var(--shadow); border-left: 4px solid var(--bcc-primary); transition: var(--transition); }
.kpi-header-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); }
.kpi-header-card.highlight { background: linear-gradient(135deg, #007FFF, #0056b3); color: white; border-left: none; }
.kpi-header-card.deficit { border-left-color: var(--danger); }
.kpi-header-card.highlight .kpi-header-label, .kpi-header-card.highlight .kpi-header-sublabel { color: rgba(255,255,255,0.8); }
.kpi-header-card.highlight .kpi-header-value { color: white; }
.kpi-header-icon { width: 50px; height: 50px; border-radius: 12px; background: linear-gradient(135deg, rgba(0,127,255,0.15), rgba(0,127,255,0.05)); display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: var(--bcc-primary); }
.kpi-header-icon.success { background: rgba(16,185,129,0.15); color: var(--success); }
.kpi-header-icon.danger { background: rgba(239,68,68,0.15); color: var(--danger); }
.kpi-header-content { display: flex; flex-direction: column; }
.kpi-header-label { font-size: 0.7rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; }
.kpi-header-value { font-size: 1.6rem; font-weight: 800; color: var(--text-primary); }
.kpi-header-sublabel { font-size: 0.75rem; color: var(--text-secondary); }
.kpi-header-progress-circle { width: 50px; height: 50px; }
.kpi-header-progress-circle svg { transform: rotate(-90deg); }

/* Table styles */
.progress-table td { padding: 14px 16px; }
.table-progress { width: 100%; height: 10px; background: #f1f5f9; border-radius: 10px; overflow: hidden; }
.table-progress-fill { height: 100%; border-radius: 10px; transition: width 0.8s ease; }
.table-progress-fill.primary { background: linear-gradient(90deg, #007FFF, #4da3ff); }
.table-progress-fill.info { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.table-progress-fill.success { background: linear-gradient(90deg, #10b981, #34d399); }
.table-progress-fill.warning { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.taux-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; }
.taux-badge.success { background: var(--success-light); color: var(--success); }
.taux-badge.warning { background: var(--warning-light); color: #b45309; }
.taux-badge.danger { background: var(--danger-light); color: var(--danger); }

/* Controls */
.table-controls { padding: 15px 0; border-bottom: 1px solid #f1f5f9; margin-bottom: 15px; }
.table-search { display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 10px 15px; border-radius: 8px; max-width: 250px; }
.table-search i { color: var(--text-secondary); }
.table-search input { border: none; background: transparent; outline: none; font-size: 0.9rem; width: 100%; }
.inline-progress { width: 60px; height: 6px; background: #e0e0e0; border-radius: 6px; display: inline-block; vertical-align: middle; margin-right: 8px; }
.inline-progress-fill { height: 100%; border-radius: 6px; }
.inline-perf { font-size: 0.8rem; font-weight: 600; }
.score-badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; }
.score-badge.success { background: var(--success-light); color: var(--success); }
.score-badge.danger { background: var(--danger-light); color: var(--danger); }
.chart-legend-inline { display: flex; align-items: center; gap: 15px; font-size: 0.85rem; color: var(--text-secondary); }
.legend-dot { display: inline-block; width: 12px; height: 12px; border-radius: 3px; margin-right: 5px; }
.legend-dot.success { background: #10b981; }
.legend-dot.danger { background: #ef4444; }

/* Donut */
.donut-wrapper { padding: 20px; display: flex; flex-direction: column; align-items: center; }
.donut-legend-horizontal { display: flex; flex-wrap: wrap; justify-content: center; gap: 15px; margin-top: 20px; padding-top: 15px; border-top: 1px solid #f1f5f9; }
.donut-legend-horizontal .donut-legend-item { display: flex; align-items: center; gap: 8px; padding: 6px 12px; background: #f8fafc; border-radius: 20px; font-size: 0.8rem; }
.donut-legend-color { width: 14px; height: 14px; border-radius: 4px; flex-shrink: 0; }
.donut-legend-label { font-size: 0.85rem; color: var(--text-primary); }

/* Trésorerie */
.tresorerie-stats { padding: 15px; }
.tresorerie-item { display: flex; justify-content: space-between; padding: 15px; border-bottom: 1px solid #f1f5f9; }
.tresorerie-item:last-child { border-bottom: none; }
.tresorerie-item.highlight { background: linear-gradient(90deg, rgba(0,127,255,0.05), transparent); border-radius: 8px; }
.tresorerie-label { font-size: 0.9rem; color: var(--text-secondary); }
.tresorerie-value { font-size: 1.1rem; font-weight: 700; color: var(--text-primary); }

/* Titres */
.titres-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; padding: 15px; }
.titre-box { text-align: center; padding: 15px; background: #f8fafc; border-radius: 10px; }
.titre-type { font-size: 0.75rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; }
.titre-value { font-size: 1.3rem; font-weight: 800; color: var(--bcc-primary); margin-top: 5px; }

/* Paie */
.paie-wrapper { padding: 20px; display: flex; flex-direction: column; align-items: center; gap: 20px; }
.paie-progress-circle { width: 100px; height: 100px; position: relative; }
.paie-progress-circle svg { transform: rotate(-90deg); }
.paie-percent { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-size: 1.4rem; font-weight: 800; color: var(--success); }
.paie-details { display: flex; flex-direction: column; gap: 10px; width: 100%; }
.paie-detail { display: flex; align-items: center; gap: 10px; padding: 10px 15px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; }
.paie-detail.success { background: var(--success-light); color: var(--success); }
.paie-detail.danger { background: var(--danger-light); color: var(--danger); }

@media (max-width: 1200px) { .kpi-header-grid, .grid-3 { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 768px) { .kpi-header-grid, .grid-3, .grid-2 { grid-template-columns: 1fr; } }
</style>

<script>
const evoData = <?= json_encode($evolutionData) ?>;

// Histogramme Recettes vs Dépenses
new Chart(document.getElementById('chartBudget'), {
    type: 'bar',
    data: {
        labels: evoData.map(d => new Date(d.date_situation).toLocaleDateString('fr-FR', {day:'2-digit',month:'2-digit'})),
        datasets: [
            { label: 'Recettes', data: evoData.map(d => d.recettes_totales), backgroundColor: '#10b981', borderRadius: 6 },
            { label: 'Dépenses', data: evoData.map(d => d.depenses_totales), backgroundColor: '#ef4444', borderRadius: 6 }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,0.05)' } }, x: { grid: { display: false } } }
    }
});

// Donut Recettes
new Chart(document.getElementById('chartRecettes'), {
    type: 'doughnut',
    data: {
        labels: ['Recettes Fiscales', 'Autres Recettes'],
        datasets: [{ data: [<?= $latestFin['recettes_fiscales'] ?? 0 ?>, <?= $latestFin['autres_recettes'] ?? 0 ?>], backgroundColor: ['#007FFF', '#10b981'], borderWidth: 0, hoverOffset: 10 }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
});

// Search
document.getElementById('searchFinances').addEventListener('input', function(e) {
    const filter = e.target.value.toLowerCase();
    document.querySelectorAll('#finTable tbody tr').forEach(row => {
        row.style.display = row.textContent.toLowerCase().includes(filter) ? '' : 'none';
    });
});
</script>

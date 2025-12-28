<?php
/**
 * Vue Marché des Changes
 * Design avancé avec graphiques variés
 */

$marcheData = $marcheChanges->getAll(10);
$latestMarche = $marcheChanges->getLatest();
$volumes = $marcheChanges->getLatestVolumes();
$reserves = $marcheChanges->getLatestReserves();
$encours = $marcheChanges->getLatestEncours();
$evolutionData = $marcheChanges->getEvolutionData(30);
$allReserves = $marcheChanges->getReserves(7);

$previousMarche = count($marcheData) > 1 ? $marcheData[1] : null;
$varIndicatif = $previousMarche ? calculateVariation($latestMarche['cours_indicatif'], $previousMarche['cours_indicatif']) : 0;
?>

<!-- Header Stats -->
<div class="stats-header mb-30">
    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-icon"><i class="fas fa-university"></i></div>
            <div class="stat-content">
                <span class="stat-label">COURS INDICATIF BCC</span>
                <span class="stat-value"><?= formatNumber($latestMarche['cours_indicatif'] ?? 0, 2) ?></span>
                <span class="stat-change <?= $varIndicatif > 0 ? 'negative' : 'positive' ?>">
                    <i class="fas fa-<?= $varIndicatif > 0 ? 'caret-up' : 'caret-down' ?>"></i> <?= formatNumber(abs($varIndicatif), 2) ?>%
                </span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon success"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-content">
                <span class="stat-label">PARALLÈLE ACHAT</span>
                <span class="stat-value"><?= formatNumber($latestMarche['parallele_achat'] ?? 0, 2) ?></span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon danger"><i class="fas fa-hand-holding-usd"></i></div>
            <div class="stat-content">
                <span class="stat-label">PARALLÈLE VENTE</span>
                <span class="stat-value"><?= formatNumber($latestMarche['parallele_vente'] ?? 0, 2) ?></span>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon <?= ($latestMarche['ecart_indic_parallele'] ?? 0) > 100 ? 'warning' : 'success' ?>">
                <i class="fas fa-balance-scale-left"></i>
            </div>
            <div class="stat-content">
                <span class="stat-label">ÉCART</span>
                <span class="stat-value"><?= formatNumber($latestMarche['ecart_indic_parallele'] ?? 0, 0) ?></span>
                <span class="stat-change <?= ($latestMarche['ecart_indic_parallele'] ?? 0) > 100 ? 'negative' : 'positive' ?>">CDF</span>
            </div>
        </div>
    </div>
</div>

<?php if ($latestMarche && $latestMarche['ecart_indic_parallele'] > 100): ?>
<div class="alert-box alert-warning">
    <i class="fas fa-exclamation-triangle alert-icon"></i>
    <div class="alert-content">
        <h4>⚠️ Pression sur le taux de change détectée</h4>
        <p>L'écart indicatif/parallèle atteint <?= formatNumber($latestMarche['ecart_indic_parallele']) ?> CDF</p>
    </div>
</div>
<?php endif; ?>

<!-- Row 1: Candlestick-style + Polar -->
<div class="grid-2 mb-30">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-chart-candlestick"></i> Évolution des Cours (30j)</h3>
        </div>
        <div class="chart-container" style="height: 320px;">
            <canvas id="chartCandlestick"></canvas>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-compass"></i> Répartition des Réserves</h3>
        </div>
        <div class="chart-container" style="height: 320px;">
            <canvas id="chartPolarReserves"></canvas>
        </div>
    </div>
</div>

<!-- Row 2: Volumes + Encours -->
<div class="grid-2 mb-30">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-building"></i> Volumes USD par Banque</h3>
        </div>
        <div class="chart-container" style="height: 280px;">
            <canvas id="chartVolumes"></canvas>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-file-invoice-dollar"></i> Encours BCC</h3>
        </div>
        <div style="display: flex; gap: 20px; align-items: center; padding: 20px;">
            <div class="chart-container" style="height: 200px; flex: 1;">
                <canvas id="chartEncours"></canvas>
            </div>
            <div class="encours-legend">
                <?php if ($encours): ?>
                <div class="encours-item">
                    <div class="encours-value"><?= formatNumber($encours['encours_ot_bcc']) ?></div>
                    <div class="encours-label">OT-BCC (Mds)</div>
                </div>
                <div class="encours-item">
                    <div class="encours-value"><?= formatNumber($encours['encours_b_bcc']) ?></div>
                    <div class="encours-label">B-BCC (Mds)</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Area + Table -->
<div class="grid-2">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-mountain"></i> Évolution des Réserves</h3>
        </div>
        <div class="chart-container" style="height: 280px;">
            <canvas id="chartReservesEvol"></canvas>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title"><i class="fas fa-table"></i> Historique des Cours</h3>
        </div>
        <div class="table-container" style="max-height: 300px; overflow-y: auto;">
            <table class="data-table">
                <thead>
                    <tr><th>Date</th><th>Indicatif</th><th>Achat</th><th>Vente</th><th>Écart</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($marcheData as $m): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($m['date_situation'])) ?></td>
                        <td class="font-bold"><?= formatNumber($m['cours_indicatif'], 2) ?></td>
                        <td class="text-success"><?= formatNumber($m['parallele_achat'], 2) ?></td>
                        <td class="text-danger"><?= formatNumber($m['parallele_vente'], 2) ?></td>
                        <td>
                            <span class="inline-badge <?= $m['ecart_indic_parallele'] > 100 ? 'danger' : 'success' ?>">
                                <?= formatNumber($m['ecart_indic_parallele'], 0) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.stats-header { background: linear-gradient(135deg, #001a3d 0%, #003366 100%); border-radius: var(--border-radius); padding: 25px; box-shadow: var(--shadow-lg); }
.stats-row { display: flex; gap: 15px; flex-wrap: wrap; }
.stat-box { flex: 1; min-width: 200px; background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border-radius: 12px; padding: 18px 20px; display: flex; align-items: center; gap: 15px; border: 1px solid rgba(255,255,255,0.1); }
.stat-icon { width: 50px; height: 50px; background: rgba(0,127,255,0.3); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; color: #4da3ff; }
.stat-icon.success { background: rgba(16,185,129,0.3); color: #34d399; }
.stat-icon.danger { background: rgba(239,68,68,0.3); color: #f87171; }
.stat-icon.warning { background: rgba(245,158,11,0.3); color: #fbbf24; }
.stat-content { display: flex; flex-direction: column; }
.stat-label { font-size: 0.7rem; font-weight: 700; color: rgba(255,255,255,0.6); text-transform: uppercase; letter-spacing: 1px; }
.stat-value { font-size: 1.5rem; font-weight: 800; color: white; }
.stat-change { font-size: 0.75rem; font-weight: 600; display: flex; align-items: center; gap: 4px; }
.stat-change.positive { color: #34d399; }
.stat-change.negative { color: #f87171; }
.encours-legend { display: flex; flex-direction: column; gap: 20px; }
.encours-item { text-align: center; padding: 20px; background: linear-gradient(135deg, rgba(0,127,255,0.1), rgba(0,127,255,0.05)); border-radius: 12px; min-width: 140px; }
.encours-value { font-size: 1.8rem; font-weight: 800; color: var(--bcc-primary); }
.encours-label { font-size: 0.85rem; color: var(--text-secondary); margin-top: 5px; }
.inline-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 700; }
.inline-badge.success { background: var(--success-light); color: var(--success); }
.inline-badge.danger { background: var(--danger-light); color: var(--danger); }
</style>

<script>
const evoData = <?= json_encode($evolutionData) ?>;
const resData = <?= json_encode(array_reverse($allReserves)) ?>;

// 1. Multi-line avec zones
new Chart(document.getElementById('chartCandlestick'), {
    type: 'line',
    data: {
        labels: evoData.map(d => new Date(d.date_situation).toLocaleDateString('fr-FR', {day:'2-digit',month:'2-digit'})),
        datasets: [
            { label: 'Indicatif', data: evoData.map(d => d.cours_indicatif), borderColor: '#007FFF', backgroundColor: 'rgba(0,127,255,0.15)', fill: true, tension: 0.3, borderWidth: 3, pointRadius: 4 },
            { label: 'Parallèle Vente', data: evoData.map(d => d.parallele_vente), borderColor: '#ef4444', borderDash: [5,5], fill: false, tension: 0.3, borderWidth: 2 },
            { label: 'Parallèle Achat', data: evoData.map(d => d.parallele_achat), borderColor: '#10b981', fill: false, tension: 0.3, borderWidth: 2 }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: false }, x: { grid: { display: false } } } }
});

// 2. Polar Area - Réserves
<?php if ($reserves): ?>
new Chart(document.getElementById('chartPolarReserves'), {
    type: 'polarArea',
    data: {
        labels: ['Réserves Int.', 'Avoirs Ext.', 'Réserves Banques', 'Avoirs Libres'],
        datasets: [{ 
            data: [<?= $reserves['reserves_internationales_usd'] ?>, <?= $reserves['avoirs_externes_usd'] ?>, <?= $reserves['reserves_banques_cdf'] ?>, <?= $reserves['avoirs_libres_cdf'] ?>],
            backgroundColor: ['rgba(0,127,255,0.7)', 'rgba(139,92,246,0.7)', 'rgba(16,185,129,0.7)', 'rgba(245,158,11,0.7)']
        }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right' } } }
});
<?php endif; ?>

// 3. Volumes horizontal
<?php 
$vLabels = array_map(fn($v) => $v['banque'], $volumes);
$vData = array_map(fn($v) => $v['volume_total_usd'], $volumes);
?>
new Chart(document.getElementById('chartVolumes'), {
    type: 'bar',
    data: { labels: <?= json_encode($vLabels) ?>, datasets: [{ data: <?= json_encode($vData) ?>, backgroundColor: ['#007FFF','#0056b3','#10b981','#8b5cf6','#f59e0b'], borderRadius: 8 }] },
    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});

// 4. Doughnut Encours
<?php if ($encours): ?>
new Chart(document.getElementById('chartEncours'), {
    type: 'doughnut',
    data: { labels: ['OT-BCC', 'B-BCC'], datasets: [{ data: [<?= $encours['encours_ot_bcc'] ?>, <?= $encours['encours_b_bcc'] ?>], backgroundColor: ['#007FFF', '#0056b3'], borderWidth: 0, cutout: '60%' }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});
<?php endif; ?>

// 5. Area Réserves évolution
new Chart(document.getElementById('chartReservesEvol'), {
    type: 'line',
    data: {
        labels: resData.map(d => new Date(d.date_situation).toLocaleDateString('fr-FR', {day:'2-digit',month:'2-digit'})),
        datasets: [
            { label: 'Réserves Int.', data: resData.map(d => d.reserves_internationales_usd), borderColor: '#007FFF', backgroundColor: 'rgba(0,127,255,0.2)', fill: true, tension: 0.4 },
            { label: 'Avoirs Ext.', data: resData.map(d => d.avoirs_externes_usd), borderColor: '#8b5cf6', backgroundColor: 'rgba(139,92,246,0.1)', fill: true, tension: 0.4 }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: false } } }
});
</script>

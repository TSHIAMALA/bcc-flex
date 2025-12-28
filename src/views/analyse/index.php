<?php
/**
 * Vue Analyse & Aide à la Décision
 * Tous les indicateurs sont calculés à partir des données réelles de la BD
 */

$allDates = $conjoncture->getAllDates();
$kpiData = $conjoncture->getKPIJournalier(10);
$latestKPI = $conjoncture->getLatestKPI();
$previousKPI = $conjoncture->getPreviousKPI();
$latestFinances = $financesPubliques->getLatest();
$allFinances = $financesPubliques->getAll(10);

// Récupérer les données supplémentaires pour les calculs
$latestReserves = $marcheChanges->getLatestReserves();
$latestEncours = $marcheChanges->getLatestEncours();
$evolutionFinances = $financesPubliques->getEvolutionData(7);

// ========== CALCULS DES INDICATEURS À PARTIR DES DONNÉES RÉELLES ==========

// 1. Pression sur le Change (basé sur l'écart indicatif/parallèle)
// Plus l'écart est grand, plus la pression est forte (seuil: 150 CDF = 100%)
$ecartChange = $latestKPI['ecart_indic_parallele'] ?? 0;
$pressionChange = min(100, ($ecartChange / 150) * 100);

// 2. Niveau des Réserves (basé sur les réserves internationales)
// Seuil optimal: 10 000 Mio USD = 100%
$reservesInt = $latestKPI['reserves_internationales_usd'] ?? 0;
$niveauReserves = min(100, ($reservesInt / 10000) * 100);

// 3. Équilibre Budgétaire (basé sur le ratio recettes/dépenses)
$recettes = $latestFinances['recettes_totales'] ?? 1;
$depenses = $latestFinances['depenses_totales'] ?? 1;
$ratioRD = $depenses > 0 ? ($recettes / $depenses) * 100 : 100;
$equilibreBudget = min(100, max(0, $ratioRD));

// 4. Liquidité du Marché (basé sur les encours BCC et avoirs libres)
// Combinaison encours OT-BCC + B-BCC + avoirs libres
$encoursTotal = ($latestEncours['encours_ot_bcc'] ?? 0) + ($latestEncours['encours_b_bcc'] ?? 0);
$avoirsLibres = $latestReserves['avoirs_libres_cdf'] ?? 0;
// Score de liquidité: seuil optimal encours = 2000 Mds, avoirs = 500 Mds
$scoreLiquidite = (min(100, ($encoursTotal / 2000) * 50) + min(50, ($avoirsLibres / 500) * 50));
$liquiditeMarche = min(100, max(0, $scoreLiquidite));

// 5. Croissance Économique (basé sur l'évolution des recettes sur 7 jours)
// Comparaison première et dernière valeur de la période
if (count($evolutionFinances) >= 2) {
    $recettesDebut = $evolutionFinances[count($evolutionFinances) - 1]['recettes_totales'] ?? 0;
    $recettesFin = $evolutionFinances[0]['recettes_totales'] ?? 0;
    $variationRecettes = $recettesDebut > 0 ? (($recettesFin - $recettesDebut) / $recettesDebut) * 100 : 0;
    // Score: croissance positive = bonus, négative = malus. Base 50 + variation plafonnée à ±50
    $croissanceEconomique = max(0, min(100, 50 + ($variationRecettes * 5)));
} else {
    $croissanceEconomique = 50; // Valeur neutre si pas assez de données
}

// ========== SCORE GLOBAL ==========
$scoreVigilance = (
    (100 - $pressionChange) * 0.25 +  // 25% - moins de pression = mieux
    $niveauReserves * 0.25 +           // 25% - plus de réserves = mieux
    $equilibreBudget * 0.20 +          // 20% - équilibre budgétaire
    $liquiditeMarche * 0.15 +          // 15% - liquidité du marché
    $croissanceEconomique * 0.15       // 15% - croissance
);

$niveauVigilance = $scoreVigilance > 70 ? 'Favorable' : ($scoreVigilance > 40 ? 'Modéré' : 'Critique');
$couleurVigilance = $scoreVigilance > 70 ? 'success' : ($scoreVigilance > 40 ? 'warning' : 'danger');

// ========== DONNÉES POUR L'AFFICHAGE ==========
$indicateurs = [
    ['nom' => 'Stabilité du Change', 'planifie' => 100, 'realise' => round(100 - $pressionChange, 1), 'couleur' => 'primary', 
     'source' => 'Écart indicatif/parallèle: ' . formatNumber($ecartChange, 0) . ' CDF'],
    ['nom' => 'Niveau des Réserves', 'planifie' => 100, 'realise' => round($niveauReserves, 1), 'couleur' => 'info',
     'source' => 'Réserves internationales: ' . formatNumber($reservesInt, 0) . ' Mio USD'],
    ['nom' => 'Équilibre Budgétaire', 'planifie' => 100, 'realise' => round($equilibreBudget, 1), 'couleur' => 'success',
     'source' => 'Ratio Recettes/Dépenses: ' . formatNumber($ratioRD, 1) . '%'],
    ['nom' => 'Liquidité Marché', 'planifie' => 100, 'realise' => round($liquiditeMarche, 1), 'couleur' => 'warning',
     'source' => 'Encours BCC: ' . formatNumber($encoursTotal, 0) . ' Mds'],
    ['nom' => 'Croissance Économique', 'planifie' => 100, 'realise' => round($croissanceEconomique, 1), 'couleur' => 'purple',
     'source' => 'Évolution recettes 7j: ' . ($variationRecettes >= 0 ? '+' : '') . formatNumber($variationRecettes ?? 0, 1) . '%'],
];
?>

<!-- Header KPI Cards -->
<div class="kpi-header-grid mb-30">
    <div class="kpi-header-card">
        <div class="kpi-header-icon"><i class="fas fa-chart-bar"></i></div>
        <div class="kpi-header-content">
            <span class="kpi-header-label">TOTAL INDICATEURS</span>
            <span class="kpi-header-value">5</span>
        </div>
    </div>
    <div class="kpi-header-card highlight">
        <div class="kpi-header-content">
            <span class="kpi-header-label">SCORE GLOBAL DE VIGILANCE</span>
            <span class="kpi-header-value"><?= formatNumber($scoreVigilance, 1) ?>%</span>
        </div>
        <div class="kpi-header-icon"><i class="fas fa-shield-alt"></i></div>
    </div>
    <div class="kpi-header-card">
        <div class="kpi-header-content">
            <span class="kpi-header-label">NIVEAU DE VIGILANCE</span>
            <span class="kpi-header-value"><?= $niveauVigilance ?></span>
        </div>
        <div class="kpi-header-progress">
            <div class="mini-progress">
                <div class="mini-progress-fill <?= $couleurVigilance ?>" style="width: <?= $scoreVigilance ?>%"></div>
            </div>
        </div>
    </div>
    <div class="kpi-header-card">
        <div class="kpi-header-icon success"><i class="fas fa-calendar-check"></i></div>
        <div class="kpi-header-content">
            <span class="kpi-header-label">JOURS ANALYSÉS</span>
            <span class="kpi-header-value"><?= count($kpiData) ?></span>
        </div>
    </div>
</div>

<!-- Row 1: Table with Progress + Bar Chart -->
<div class="grid-2 mb-30">
    <!-- Tableau des Indicateurs avec barres de progression -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-tasks"></i>
                Taux de Performance par Indicateur
            </h3>
        </div>
        <div class="table-container">
            <table class="data-table progress-table">
                <thead>
                    <tr>
                        <th>Indicateur</th>
                        <th>Objectif</th>
                        <th>Réalisé</th>
                        <th>Taux</th>
                        <th style="width: 150px;">Progression</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($indicateurs as $ind): ?>
                    <?php $taux = ($ind['realise'] / $ind['planifie']) * 100; ?>
                    <tr>
                        <td><strong><?= $ind['nom'] ?></strong></td>
                        <td><?= $ind['planifie'] ?>%</td>
                        <td><?= formatNumber($ind['realise'], 1) ?>%</td>
                        <td>
                            <span class="taux-badge <?= $taux >= 70 ? 'success' : ($taux >= 40 ? 'warning' : 'danger') ?>">
                                <?= formatNumber($taux, 0) ?>%
                            </span>
                        </td>
                        <td>
                            <div class="table-progress">
                                <div class="table-progress-fill <?= $ind['couleur'] ?>" style="width: <?= $taux ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Histogramme des Indicateurs -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-chart-bar"></i>
                Histogramme de Performance
            </h3>
            <div class="chart-legend-inline">
                <span class="legend-dot primary"></span> Objectif
                <span class="legend-dot success"></span> Réalisé
            </div>
        </div>
        <div class="chart-container" style="height: 300px;">
            <canvas id="chartIndicateurs"></canvas>
        </div>
    </div>
</div>

<!-- Row 2: Data Table + Donut -->
<div class="grid-2 mb-30">
    <!-- Liste des Données Journalières -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-list"></i>
                Liste des Données Journalières - Total : <?= count($kpiData) ?>
            </h3>
        </div>
        <div class="table-controls">
            <div class="table-search">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Rechercher..." id="searchInput">
            </div>
        </div>
        <div class="table-container" style="max-height: 350px; overflow-y: auto;">
            <table class="data-table" id="dataTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Cours BCC</th>
                        <th>Réserves</th>
                        <th>Taux Perf.</th>
                        <th>Score</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kpiData as $index => $kpi): 
                        $perf = min(100, max(0, 100 - (($kpi['ecart_indic_parallele'] ?? 0) / 2)));
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($kpi['date_situation'])) ?></td>
                        <td class="font-bold"><?= formatNumber($kpi['cours_indicatif'], 2) ?></td>
                        <td><?= formatNumber($kpi['reserves_internationales_usd'], 0) ?> M</td>
                        <td>
                            <div class="inline-progress">
                                <div class="inline-progress-fill" style="width: <?= $perf ?>%; background: <?= $perf >= 70 ? '#10b981' : ($perf >= 40 ? '#f59e0b' : '#ef4444') ?>;"></div>
                            </div>
                            <span class="inline-perf"><?= formatNumber($perf, 0) ?>%</span>
                        </td>
                        <td>
                            <span class="score-badge <?= $kpi['solde'] >= 0 ? 'success' : 'danger' ?>">
                                <?= $kpi['solde'] >= 0 ? '+' : '' ?><?= formatNumber($kpi['solde'], 0) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="table-pagination">
            <span>Affichage de 1 à <?= count($kpiData) ?> sur <?= count($kpiData) ?> éléments</span>
        </div>
    </div>
    
    <!-- Donut Répartition -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-chart-pie"></i>
                Répartition du Score par Indicateur
            </h3>
        </div>
        <div class="donut-wrapper">
            <div class="chart-container" style="height: 220px;">
                <canvas id="chartDonut"></canvas>
            </div>
            <div class="donut-legend-horizontal">
                <?php 
                $colors = ['#007FFF', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'];
                foreach ($indicateurs as $i => $ind): 
                ?>
                <div class="donut-legend-item">
                    <span class="donut-legend-color" style="background: <?= $colors[$i] ?>;"></span>
                    <span class="donut-legend-label"><?= $ind['nom'] ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Row 3: Analysis Cards -->
<div class="grid-3">
    <div class="analysis-card <?= $pressionChange < 30 ? 'success' : ($pressionChange < 60 ? 'warning' : 'danger') ?>">
        <div class="analysis-icon">
            <i class="fas fa-bolt"></i>
        </div>
        <div class="analysis-content">
            <h4>Pression Change</h4>
            <div class="analysis-value"><?= formatNumber($pressionChange, 0) ?>%</div>
            <p><?= $pressionChange < 30 ? 'Situation stable' : ($pressionChange < 60 ? 'Vigilance requise' : 'Intervention recommandée') ?></p>
        </div>
        <div class="analysis-gauge">
            <svg viewBox="0 0 36 36">
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e0e0e0" stroke-width="3"/>
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="<?= $pressionChange ?>, 100"/>
            </svg>
        </div>
    </div>
    
    <div class="analysis-card <?= $niveauReserves > 70 ? 'success' : ($niveauReserves > 40 ? 'warning' : 'danger') ?>">
        <div class="analysis-icon">
            <i class="fas fa-piggy-bank"></i>
        </div>
        <div class="analysis-content">
            <h4>Niveau Réserves</h4>
            <div class="analysis-value"><?= formatNumber($niveauReserves, 0) ?>%</div>
            <p><?= $niveauReserves > 70 ? 'Réserves solides' : ($niveauReserves > 40 ? 'Niveau acceptable' : 'Renforcement nécessaire') ?></p>
        </div>
        <div class="analysis-gauge">
            <svg viewBox="0 0 36 36">
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e0e0e0" stroke-width="3"/>
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="<?= $niveauReserves ?>, 100"/>
            </svg>
        </div>
    </div>
    
    <div class="analysis-card <?= $equilibreBudget > 70 ? 'success' : ($equilibreBudget > 40 ? 'warning' : 'danger') ?>">
        <div class="analysis-icon">
            <i class="fas fa-balance-scale"></i>
        </div>
        <div class="analysis-content">
            <h4>Équilibre Budget</h4>
            <div class="analysis-value"><?= formatNumber($equilibreBudget, 0) ?>%</div>
            <p><?= $equilibreBudget > 70 ? 'Budget équilibré' : ($equilibreBudget > 40 ? 'Déficit contenu' : 'Consolidation urgente') ?></p>
        </div>
        <div class="analysis-gauge">
            <svg viewBox="0 0 36 36">
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="#e0e0e0" stroke-width="3"/>
                <path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="<?= $equilibreBudget ?>, 100"/>
            </svg>
        </div>
    </div>
</div>

<style>
/* KPI Header Grid */
.kpi-header-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.kpi-header-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 20px 25px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: var(--shadow);
    border-left: 4px solid var(--bcc-primary);
    transition: var(--transition);
}

.kpi-header-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

.kpi-header-card.highlight {
    background: linear-gradient(135deg, #007FFF, #0056b3);
    color: white;
    border-left: none;
}

.kpi-header-card.highlight .kpi-header-label { color: rgba(255,255,255,0.8); }
.kpi-header-card.highlight .kpi-header-value { color: white; }

.kpi-header-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(0,127,255,0.15), rgba(0,127,255,0.05));
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    color: var(--bcc-primary);
}

.kpi-header-icon.success { background: rgba(16,185,129,0.15); color: var(--success); }
.kpi-header-card.highlight .kpi-header-icon { background: rgba(255,255,255,0.2); color: white; }

.kpi-header-content { display: flex; flex-direction: column; }
.kpi-header-label { font-size: 0.7rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 1px; }
.kpi-header-value { font-size: 1.6rem; font-weight: 800; color: var(--text-primary); }

.mini-progress { width: 80px; height: 8px; background: #e0e0e0; border-radius: 8px; overflow: hidden; }
.mini-progress-fill { height: 100%; border-radius: 8px; }
.mini-progress-fill.success { background: var(--success); }
.mini-progress-fill.warning { background: var(--warning); }
.mini-progress-fill.danger { background: var(--danger); }

/* Table Progress */
.progress-table td { padding: 14px 16px; }
.table-progress { width: 100%; height: 10px; background: #f1f5f9; border-radius: 10px; overflow: hidden; }
.table-progress-fill { height: 100%; border-radius: 10px; transition: width 0.8s ease; }
.table-progress-fill.primary { background: linear-gradient(90deg, #007FFF, #4da3ff); }
.table-progress-fill.info { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
.table-progress-fill.success { background: linear-gradient(90deg, #10b981, #34d399); }
.table-progress-fill.warning { background: linear-gradient(90deg, #f59e0b, #fbbf24); }
.table-progress-fill.purple { background: linear-gradient(90deg, #8b5cf6, #a78bfa); }

.taux-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
}
.taux-badge.success { background: var(--success-light); color: var(--success); }
.taux-badge.warning { background: var(--warning-light); color: #b45309; }
.taux-badge.danger { background: var(--danger-light); color: var(--danger); }

/* Chart Legend Inline */
.chart-legend-inline {
    display: flex;
    align-items: center;
    gap: 15px;
    font-size: 0.85rem;
    color: var(--text-secondary);
}
.legend-dot {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 3px;
    margin-right: 5px;
}
.legend-dot.primary { background: #007FFF; }
.legend-dot.success { background: #10b981; }

/* Table Controls */
.table-controls { padding: 15px 0; border-bottom: 1px solid #f1f5f9; margin-bottom: 15px; }
.table-search { display: flex; align-items: center; gap: 10px; background: #f8fafc; padding: 10px 15px; border-radius: 8px; max-width: 250px; }
.table-search i { color: var(--text-secondary); }
.table-search input { border: none; background: transparent; outline: none; font-size: 0.9rem; width: 100%; }

/* Inline Progress */
.inline-progress { width: 60px; height: 6px; background: #e0e0e0; border-radius: 6px; display: inline-block; vertical-align: middle; margin-right: 8px; }
.inline-progress-fill { height: 100%; border-radius: 6px; }
.inline-perf { font-size: 0.8rem; font-weight: 600; }

.score-badge { display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 700; }
.score-badge.success { background: var(--success-light); color: var(--success); }
.score-badge.danger { background: var(--danger-light); color: var(--danger); }

/* Table Pagination */
.table-pagination { padding: 15px 0; border-top: 1px solid #f1f5f9; margin-top: 15px; font-size: 0.85rem; color: var(--text-secondary); }

/* Donut Wrapper & Legend */
.donut-wrapper {
    padding: 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
}

.donut-legend-horizontal {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 15px;
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #f1f5f9;
}

.donut-legend-horizontal .donut-legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    background: #f8fafc;
    border-radius: 20px;
    font-size: 0.8rem;
}

.donut-legend { display: flex; flex-direction: column; gap: 12px; }
.donut-legend-item { display: flex; align-items: center; gap: 10px; }
.donut-legend-color { width: 14px; height: 14px; border-radius: 4px; flex-shrink: 0; }
.donut-legend-label { font-size: 0.85rem; color: var(--text-primary); white-space: nowrap; }

/* Analysis Cards */
.analysis-card {
    background: white;
    border-radius: var(--border-radius);
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: var(--shadow);
    border-top: 4px solid var(--bcc-primary);
    transition: var(--transition);
}
.analysis-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
.analysis-card.success { border-top-color: var(--success); }
.analysis-card.warning { border-top-color: var(--warning); }
.analysis-card.danger { border-top-color: var(--danger); }

.analysis-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    background: var(--info-light);
    color: var(--info);
}
.analysis-card.success .analysis-icon { background: var(--success-light); color: var(--success); }
.analysis-card.warning .analysis-icon { background: var(--warning-light); color: var(--warning); }
.analysis-card.danger .analysis-icon { background: var(--danger-light); color: var(--danger); }

.analysis-content { flex: 1; }
.analysis-content h4 { font-size: 0.9rem; color: var(--text-secondary); margin-bottom: 5px; }
.analysis-value { font-size: 2rem; font-weight: 800; color: var(--text-primary); }
.analysis-content p { font-size: 0.85rem; color: var(--text-secondary); margin-top: 5px; }

.analysis-gauge { width: 60px; height: 60px; }
.analysis-gauge svg { transform: rotate(-90deg); }
.analysis-card.success .analysis-gauge { color: var(--success); }
.analysis-card.warning .analysis-gauge { color: var(--warning); }
.analysis-card.danger .analysis-gauge { color: var(--danger); }

@media (max-width: 1200px) {
    .kpi-header-grid { grid-template-columns: repeat(2, 1fr); }
    .grid-3 { grid-template-columns: 1fr; }
}
</style>

<script>
// Histogramme des Indicateurs
const indicateurs = <?= json_encode($indicateurs) ?>;
new Chart(document.getElementById('chartIndicateurs'), {
    type: 'bar',
    data: {
        labels: indicateurs.map(i => i.nom.split(' ').slice(0,2).join(' ')),
        datasets: [
            { label: 'Objectif', data: indicateurs.map(i => i.planifie), backgroundColor: 'rgba(0,127,255,0.3)', borderColor: '#007FFF', borderWidth: 2, borderRadius: 6 },
            { label: 'Réalisé', data: indicateurs.map(i => i.realise), backgroundColor: '#10b981', borderRadius: 6 }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { 
            y: { beginAtZero: true, max: 100, grid: { color: 'rgba(0,0,0,0.05)' } },
            x: { grid: { display: false } }
        }
    }
});

// Donut Répartition
new Chart(document.getElementById('chartDonut'), {
    type: 'doughnut',
    data: {
        labels: indicateurs.map(i => i.nom),
        datasets: [{
            data: indicateurs.map(i => i.realise),
            backgroundColor: ['#007FFF', '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'],
            borderWidth: 0,
            hoverOffset: 10
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: { legend: { display: false } }
    }
});

// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
    const filter = e.target.value.toLowerCase();
    const rows = document.querySelectorAll('#dataTable tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

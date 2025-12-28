<!-- Page Header -->
<header class="header">
    <div class="header-left">
        <button class="menu-toggle" id="menuToggle" style="display: none; background: none; border: none; font-size: 1.5rem; cursor: pointer;">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header-title">
            <h2><?= $headerTitle ?? 'Tableau de Bord' ?></h2>
            <span><?= $headerSubtitle ?? 'Indicateurs de Conjoncture' ?></span>
        </div>
    </div>
    
    <div class="header-right">
        <div class="date-display">
            <i class="fas fa-calendar-alt"></i>
            <span id="currentDate"><?= date('d/m/Y H:i') ?></span>
        </div>
        <button class="refresh-btn" onclick="location.reload()" title="Actualiser">
            <i class="fas fa-sync-alt"></i>
        </button>
    </div>
</header>

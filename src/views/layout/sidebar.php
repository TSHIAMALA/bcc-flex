<!-- Sidebar Navigation -->
<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <img src="/assets/logo-bcc.png" alt="Logo BCC" style="height: 85px; width: auto;">
            <div>
                <h1>BCC-Flex</h1>
                <span>Conjoncture Économique</span>
            </div>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Tableau de bord</div>
            <a href="/" class="nav-item <?= ($currentPage ?? '') === 'dashboard' ? 'active' : '' ?>">
                <i class="fas fa-chart-line"></i>
                <span>Vue d'ensemble</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Modules</div>
            <a href="/?page=marche" class="nav-item <?= ($currentPage ?? '') === 'marche' ? 'active' : '' ?>">
                <i class="fas fa-exchange-alt"></i>
                <span>Marché des Changes</span>
            </a>
            <a href="/?page=finances" class="nav-item <?= ($currentPage ?? '') === 'finances' ? 'active' : '' ?>">
                <i class="fas fa-wallet"></i>
                <span>Finances Publiques</span>
            </a>
            <a href="/?page=analyse" class="nav-item <?= ($currentPage ?? '') === 'analyse' ? 'active' : '' ?>">
                <i class="fas fa-brain"></i>
                <span>Analyse & Décision</span>
            </a>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Paramètres</div>
            <a href="/?page=alertes" class="nav-item <?= ($currentPage ?? '') === 'alertes' ? 'active' : '' ?>">
                <i class="fas fa-bell"></i>
                <span>Alertes</span>
            </a>
        </div>
    </nav>
</aside>

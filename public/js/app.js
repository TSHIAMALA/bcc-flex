/**
 * BCC-Flex - Application JavaScript
 * Tableau de Bord de Conjoncture Économique
 */

// Configuration globale Chart.js
Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
Chart.defaults.color = '#666';
Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0, 0, 0, 0.8)';
Chart.defaults.plugins.tooltip.padding = 12;
Chart.defaults.plugins.tooltip.cornerRadius = 8;

// Mise à jour de l'heure
function updateDateTime() {
    const now = new Date();
    const options = {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    const dateElement = document.getElementById('currentDate');
    if (dateElement) {
        dateElement.textContent = now.toLocaleDateString('fr-FR', options).replace(',', ' à');
    }
}

// Variables globales pour la navigation mobile
let touchStartX = 0;
let touchEndX = 0;

// Toggle sidebar sur mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.mobile-overlay');

    if (sidebar && overlay) {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');

        // Empêcher le scroll du body quand la sidebar est ouverte
        if (sidebar.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
}

// Fermer la sidebar
function closeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.mobile-overlay');

    if (sidebar && overlay) {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// Gestion des swipe gestures
function handleSwipe() {
    const swipeDistance = touchEndX - touchStartX;
    const sidebar = document.querySelector('.sidebar');

    // Swipe vers la gauche pour fermer (distance > 50px)
    if (swipeDistance < -50 && sidebar && sidebar.classList.contains('active')) {
        closeSidebar();
    }
}

// Optimisation des graphiques pour mobile
function optimizeChartsForMobile() {
    if (window.innerWidth <= 768) {
        // Réduire la taille des polices pour mobile
        Chart.defaults.font.size = 10;
        Chart.defaults.plugins.legend.labels.font = { size: 10 };
        Chart.defaults.plugins.legend.labels.padding = 10;
    } else {
        // Taille normale pour desktop
        Chart.defaults.font.size = 12;
        Chart.defaults.plugins.legend.labels.font = { size: 12 };
        Chart.defaults.plugins.legend.labels.padding = 15;
    }
}

// Exécuter au chargement
document.addEventListener('DOMContentLoaded', function () {
    updateDateTime();
    setInterval(updateDateTime, 60000); // Mise à jour chaque minute

    // Optimiser les graphiques selon la taille d'écran
    optimizeChartsForMobile();

    // Animation des KPI cards
    const kpiCards = document.querySelectorAll('.kpi-card');
    kpiCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        setTimeout(() => {
            card.style.transition = 'all 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });

    // Animation des progress bars
    const progressBars = document.querySelectorAll('.progress-fill');
    progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0';
        setTimeout(() => {
            bar.style.width = width;
        }, 500);
    });

    // Configuration du menu toggle
    const menuToggle = document.getElementById('menuToggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', toggleSidebar);
    }

    // Configuration de l'overlay
    const overlay = document.querySelector('.mobile-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // Fermer la sidebar quand on clique sur un lien de navigation
    const navItems = document.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            if (window.innerWidth <= 992) {
                closeSidebar();
            }
        });
    });

    // Gestion des swipe gestures
    const sidebar = document.querySelector('.sidebar');
    if (sidebar) {
        sidebar.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        sidebar.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });
    }

    // Réoptimiser les graphiques lors du redimensionnement
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            optimizeChartsForMobile();

            // Fermer la sidebar si on passe en mode desktop
            if (window.innerWidth > 992) {
                closeSidebar();
            }
        }, 250);
    });

    // Indicateur de scroll pour les tableaux sur mobile
    const tableContainers = document.querySelectorAll('.table-container');
    tableContainers.forEach(container => {
        container.addEventListener('scroll', function () {
            const scrollIndicator = container.querySelector('::after');
            if (this.scrollLeft > 0) {
                container.style.setProperty('--scroll-opacity', '0');
            } else {
                container.style.setProperty('--scroll-opacity', '0.8');
            }
        });
    });
});

// Fonction de formatage des nombres
function formatNumber(num, decimals = 2) {
    if (num === null || num === undefined) return '—';
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(num);
}

// Notification de rafraîchissement des données
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert-box alert-${type}`;
    notification.style.cssText = `
        position: fixed;
        top: 90px;
        right: 30px;
        z-index: 9999;
        max-width: 350px;
        animation: slideIn 0.3s ease;
    `;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} alert-icon"></i>
        <div class="alert-content">
            <p style="margin: 0;">${message}</p>
        </div>
    `;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideIn 0.3s ease reverse';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Export des données (pour future implémentation)
function exportData(format) {
    showNotification(`Export ${format.toUpperCase()} en cours de développement`, 'info');
}

// Gestion du mode sombre (Dark Mode)
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDark);

    // Mettre à jour l'icône du bouton
    const toggleBtn = document.getElementById('themeToggle');
    if (toggleBtn) {
        toggleBtn.setAttribute('title', isDark ? 'Passer en mode clair' : 'Passer en mode sombre');
    }

    // Notification de changement de thème
    showNotification(`Mode ${isDark ? 'sombre' : 'clair'} activé`, 'success');

    // Mettre à jour les graphiques Chart.js si présents
    updateChartsForTheme(isDark);
}

// Mettre à jour les couleurs des graphiques selon le thème
function updateChartsForTheme(isDark) {
    const textColor = isDark ? '#f1f5f9' : '#666';
    const gridColor = isDark ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';

    Chart.defaults.color = textColor;

    // Mettre à jour tous les graphiques existants
    Chart.helpers.each(Chart.instances, function (chart) {
        if (chart.options.scales) {
            if (chart.options.scales.x) {
                chart.options.scales.x.ticks = chart.options.scales.x.ticks || {};
                chart.options.scales.x.ticks.color = textColor;
                chart.options.scales.x.grid = chart.options.scales.x.grid || {};
                chart.options.scales.x.grid.color = gridColor;
            }
            if (chart.options.scales.y) {
                chart.options.scales.y.ticks = chart.options.scales.y.ticks || {};
                chart.options.scales.y.ticks.color = textColor;
                chart.options.scales.y.grid = chart.options.scales.y.grid || {};
                chart.options.scales.y.grid.color = gridColor;
            }
        }
        chart.update();
    });
}

// Restaurer le mode sombre au chargement (avant le rendu)
(function () {
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
})();

console.log('🏦 BCC-Flex - Tableau de Bord de Conjoncture chargé');
console.log('📱 Navigation mobile optimisée avec swipe gestures');
console.log('🌙 Mode sombre/clair disponible');


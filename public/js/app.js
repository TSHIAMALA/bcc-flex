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

// Exécuter au chargement
document.addEventListener('DOMContentLoaded', function() {
    updateDateTime();
    setInterval(updateDateTime, 60000); // Mise à jour chaque minute
    
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
});

// Toggle sidebar sur mobile
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('active');
}

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

console.log('🏦 BCC-Flex - Tableau de Bord de Conjoncture chargé');

/**
 * MboaLearn - JavaScript Principal
 * Gestion de l'interactivité et des requêtes AJAX
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation
    initAlertes();
    initFormulaires();
    initVideoPlayer();
});

/**
 * Fermer les alertes automatiquement
 */
function initAlertes() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.3s ease';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
}

/**
 * Validation des formulaires
 */
function initFormulaires() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = 'var(--danger)';
                    field.addEventListener('input', function() {
                        this.style.borderColor = '';
                    }, { once: true });
                }
            });
            
            if (!valid) {
                e.preventDefault();
                showNotification('Veuillez remplir tous les champs obligatoires', 'error');
            }
        });
    });
}

/**
 * Lecteur vidéo amélioré
 */
function initVideoPlayer() {
    const video = document.getElementById('video-player');
    if (!video) return;
    
    // Sauvegarder la position de la vidéo
    const videoId = video.src || 'video_' + window.location.pathname;
    const savedTime = localStorage.getItem('video_' + videoId);
    
    if (savedTime) {
        video.currentTime = parseFloat(savedTime);
    }
    
    // Sauvegarder la position toutes les 5 secondes
    setInterval(() => {
        if (!video.paused) {
            localStorage.setItem('video_' + videoId, video.currentTime);
        }
    }, 5000);
    
    // Nettoyage quand la vidéo est terminée
    video.addEventListener('ended', function() {
        localStorage.removeItem('video_' + videoId);
    });
}

/**
 * Afficher une notification
 */
function showNotification(message, type = 'info') {
    const colors = {
        'success': 'var(--success)',
        'error': 'var(--danger)',
        'warning': 'var(--warning)',
        'info': 'var(--primary)'
    };
    
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${colors[type] || colors.info};
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        z-index: 1000;
        animation: slideIn 0.3s ease;
    `;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.opacity = '0';
        notification.style.transition = 'opacity 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * Requête AJAX générique
 */
function ajaxRequest(url, method = 'GET', data = null) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(method, url, true);
        
        if (method === 'POST') {
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        }
        
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    resolve(JSON.parse(xhr.responseText));
                } catch (e) {
                    resolve(xhr.responseText);
                }
            } else {
                reject(new Error('Erreur HTTP: ' + xhr.status));
            }
        };
        
        xhr.onerror = function() {
            reject(new Error('Erreur réseau'));
        };
        
        if (data && method === 'POST') {
            xhr.send(data);
        } else {
            xhr.send();
        }
    });
}

/**
 * Formater une date en français
 */
function formatDate(dateStr) {
    const options = { 
        day: 'numeric', 
        month: 'long', 
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateStr).toLocaleDateString('fr-FR', options);
}

/**
 * Copier du texte dans le presse-papier
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copié !', 'success');
    }).catch(() => {
        showNotification('Erreur lors de la copie', 'error');
    });
}

/**
 * Confirmer une action
 */
function confirmAction(message) {
    return confirm(message);
}

/**
 * Toggle sombre (optionnel)
 */
function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
    const isDark = document.body.classList.contains('dark-mode');
    localStorage.setItem('darkMode', isDark);
}

// Appliquer le mode sombre sauvegardé
if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
}

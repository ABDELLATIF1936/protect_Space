// Configuration
const API_BASE_URL = '/pro3/api';

// Classe principale de l'application
class App {
    constructor() {
        this.user = null;
        this.initializeApp();
    }

    async initializeApp() {
        await this.checkAuthentication();
        this.initializeCommonElements();
        this.setupEventListeners();
    }

    async checkAuthentication() {
        try {
            const response = await fetch(`${API_BASE_URL}/auth.php?action=check`, {
                method: 'GET',
                credentials: 'include'
            });

            if (response.ok) {
                const data = await response.json();
                if (data.authenticated) {
                    this.user = data.user;
                    localStorage.setItem('user', JSON.stringify(this.user));
                } else {
                    this.redirectToLogin();
                }
            } else {
                this.redirectToLogin();
            }
        } catch (error) {
            console.error('Erreur lors de la vérification de l\'authentification:', error);
            this.redirectToLogin();
        }
    }

    redirectToLogin() {
        localStorage.removeItem('user');
        window.location.href = 'login-signup-page.html';
    }

    // Initialisation des éléments communs
    initializeCommonElements() {
        if (!this.user) return;

        // Mise à jour de l'avatar utilisateur
        const avatarElement = document.querySelector('.user-avatar');
        if (avatarElement) {
            const initials = this.user.full_name
                .split(' ')
                .map(n => n[0])
                .join('')
                .toUpperCase();
            avatarElement.textContent = initials;
        }

        // Mise à jour du nom de l'espace de travail
        const workspaceName = document.querySelector('.workspace-name');
        if (workspaceName) {
            workspaceName.textContent = `Espace ${this.user.full_name}`;
        }
    }

    // Configuration des écouteurs d'événements communs
    setupEventListeners() {
        // Gestion de la déconnexion
        const logoutLink = document.getElementById('logout-link');
        if (logoutLink) {
            logoutLink.addEventListener('click', async (e) => {
                e.preventDefault();
                await this.handleLogout();
            });
        }
    }

    // Gestion de la déconnexion
    async handleLogout() {
        try {
            const response = await fetch(`${API_BASE_URL}/auth.php?action=logout`, {
                method: 'POST',
                credentials: 'include'
            });

            if (response.ok) {
                localStorage.removeItem('user');
                window.location.href = 'login-signup-page.html';
            }
        } catch (error) {
            console.error('Erreur lors de la déconnexion:', error);
        }
    }

    // Affichage des messages d'erreur
    showError(message) {
        const errorDiv = document.getElementById('error-message');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 3000);
        } else {
            console.error(message);
        }
    }

    // Affichage des messages de succès
    showSuccess(message) {
        const successDiv = document.getElementById('success-message');
        if (successDiv) {
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            setTimeout(() => {
                successDiv.style.display = 'none';
            }, 3000);
        } else {
            console.log(message);
        }
    }

    // Formatage de la date
    formatDate(dateString) {
        const options = { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return new Date(dateString).toLocaleDateString('fr-FR', options);
    }
}

// Initialisation de l'application
let app = null;
document.addEventListener('DOMContentLoaded', async () => {
    app = new App();
}); 
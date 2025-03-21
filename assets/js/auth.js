// Configuration
const API_BASE_URL = '/pro3/api';

class AuthManager {
    constructor() {
        this.loginForm = document.getElementById('login-form');
        this.signupForm = document.getElementById('signup-form');
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Gestion du formulaire de connexion
        if (this.loginForm) {
            this.loginForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleLogin(e);
            });
        }

        // Gestion du formulaire d'inscription
        if (this.signupForm) {
            this.signupForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleSignup(e);
            });
        }

        // Gestion du basculement entre les formulaires
        document.querySelectorAll('.toggle-form').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleForms();
            });
        });
    }

    async handleLogin(e) {
        const formData = new FormData(e.target);
        const loginData = {
            email: formData.get('email'),
            password: formData.get('password')
        };

        try {
            const response = await fetch(`${API_BASE_URL}/auth.php?action=login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(loginData),
                credentials: 'include'
            });

            const data = await response.json();

            if (response.ok) {
                this.showSuccess('Connexion réussie');
                localStorage.setItem('user', JSON.stringify(data.user));
                window.location.href = 'notion-style-pm-platform.html';
            } else {
                this.showError(data.error || 'Erreur lors de la connexion');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showError('Erreur de connexion au serveur');
        }
    }

    async handleSignup(e) {
        const formData = new FormData(e.target);
        const signupData = {
            full_name: formData.get('full_name'),
            email: formData.get('email'),
            password: formData.get('password'),
            role: formData.get('role')
        };

        try {
            const response = await fetch(`${API_BASE_URL}/auth.php?action=signup`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(signupData),
                credentials: 'include'
            });

            const data = await response.json();

            if (response.ok) {
                this.showSuccess('Inscription réussie');
                localStorage.setItem('user', JSON.stringify(data.user));
                window.location.href = 'notion-style-pm-platform.html';
            } else {
                this.showError(data.error || 'Erreur lors de l\'inscription');
            }
        } catch (error) {
            console.error('Erreur:', error);
            this.showError('Erreur de connexion au serveur');
        }
    }

    toggleForms() {
        this.loginForm.style.display = this.loginForm.style.display === 'none' ? 'block' : 'none';
        this.signupForm.style.display = this.signupForm.style.display === 'none' ? 'block' : 'none';
    }

    showError(message) {
        const errorDiv = document.getElementById('error-message');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 3000);
        }
    }

    showSuccess(message) {
        const successDiv = document.getElementById('success-message');
        if (successDiv) {
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            setTimeout(() => {
                successDiv.style.display = 'none';
            }, 3000);
        }
    }
}

// Initialisation du gestionnaire d'authentification
document.addEventListener('DOMContentLoaded', () => {
    window.authManager = new AuthManager();
}); 
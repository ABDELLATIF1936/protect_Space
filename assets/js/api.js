const API = {
    baseUrl: '/pro3/api',

    async request(endpoint, options = {}) {
        try {
            const defaultOptions = {
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include'
            };

            const response = await fetch(`${this.baseUrl}${endpoint}`, {
                ...defaultOptions,
                ...options,
                headers: {
                    ...defaultOptions.headers,
                    ...options.headers
                }
            });

            const data = await response.json();
            return {
                status: response.status,
                data,
                message: data.message
            };
        } catch (error) {
            console.error('Erreur API:', error);
            return {
                status: 500,
                message: 'Erreur de connexion au serveur'
            };
        }
    },

    // Authentification
    async login(email, password) {
        return this.request('/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });
    },

    async signup(userData) {
        return this.request('/auth/signup', {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    },

    async logout() {
        return this.request('/auth/logout', {
            method: 'POST'
        });
    },

    async checkAuth() {
        try {
            const response = await this.request('/auth/check');
            if (response.status !== 200 && !window.location.pathname.includes('login-signup-page.html')) {
                window.location.href = '/pro3/login-signup-page.html';
            }
            return response;
        } catch (error) {
            if (!window.location.pathname.includes('login-signup-page.html')) {
                window.location.href = '/pro3/login-signup-page.html';
            }
            throw error;
        }
    },

    // Projets
    async getProjects() {
        return this.request('/projects');
    },

    async getProject(id) {
        return this.request(`/projects/${id}`);
    },

    async createProject(projectData) {
        return this.request('/projects', {
            method: 'POST',
            body: JSON.stringify(projectData)
        });
    },

    async updateProject(id, projectData) {
        return this.request(`/projects/${id}`, {
            method: 'PUT',
            body: JSON.stringify(projectData)
        });
    },

    async deleteProject(id) {
        return this.request(`/projects/${id}`, {
            method: 'DELETE'
        });
    },

    // Tâches
    async getProjectTasks(projectId) {
        return this.request(`/projects/${projectId}/tasks`);
    },

    async createTask(taskData) {
        return this.request('/tasks', {
            method: 'POST',
            body: JSON.stringify(taskData)
        });
    },

    async updateTask(id, taskData) {
        return this.request(`/tasks/${id}`, {
            method: 'PUT',
            body: JSON.stringify(taskData)
        });
    },

    async deleteTask(id) {
        return this.request(`/tasks/${id}`, {
            method: 'DELETE'
        });
    },

    // Messages
    async getProjectMessages(projectId) {
        return this.request(`/projects/${projectId}/messages`);
    },

    async createMessage(messageData) {
        return this.request('/messages', {
            method: 'POST',
            body: JSON.stringify(messageData)
        });
    },

    // Documents
    async getProjectDocuments(projectId) {
        return this.request(`/projects/${projectId}/documents`);
    },

    async uploadDocument(formData) {
        return this.request('/documents', {
            method: 'POST',
            headers: {
                // Ne pas définir Content-Type pour permettre au navigateur de gérer le multipart/form-data
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
    },

    async deleteDocument(id) {
        return this.request(`/documents/${id}`, {
            method: 'DELETE'
        });
    }
};

// Fonction utilitaire pour vérifier l'authentification
function checkAuth() {
    API.checkAuth().catch(error => {
        console.error('Erreur de vérification d\'authentification:', error);
    });
}

// Gestionnaire d'erreurs global
window.addEventListener('unhandledrejection', function(event) {
    console.error('Erreur non gérée:', event.reason);
    // Afficher un message d'erreur à l'utilisateur
    alert('Une erreur est survenue. Veuillez réessayer plus tard.');
}); 
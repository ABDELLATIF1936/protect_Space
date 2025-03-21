class ProjectsManager {
    constructor() {
        this.projectsList = document.getElementById('projects-list');
        this.newProjectModal = document.getElementById('new-project-modal');
        this.newProjectForm = document.getElementById('new-project-form');
        this.setupEventListeners();
        this.loadProjects();
    }

    setupEventListeners() {
        // Gestion du formulaire de nouveau projet
        this.newProjectForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handleNewProject(e);
        });

        // Fermeture du modal avec Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideNewProjectModal();
            }
        });
    }

    async loadProjects() {
        try {
            const response = await fetch(`${API_BASE_URL}/projects.php`, {
                method: 'GET',
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Erreur lors du chargement des projets');
            }

            const projects = await response.json();
            this.displayProjects(projects);
        } catch (error) {
            app.showError('Erreur lors du chargement des projets');
            console.error(error);
        }
    }

    displayProjects(projects) {
        if (!this.projectsList) return;

        this.projectsList.innerHTML = projects.map(project => `
            <div class="project-card" data-id="${project.id}">
                <div class="project-header">
                    <h3>${project.title}</h3>
                    <span class="project-status ${project.status}">${this.getStatusLabel(project.status)}</span>
                </div>
                <p class="project-description">${project.description}</p>
                <div class="project-footer">
                    <span class="project-date">Créé le ${app.formatDate(project.created_at)}</span>
                    <div class="project-actions">
                        <button onclick="projectsManager.editProject(${project.id})" class="btn-icon">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="projectsManager.deleteProject(${project.id})" class="btn-icon">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    getStatusLabel(status) {
        const statusLabels = {
            'en_cours': 'En cours',
            'en_pause': 'En pause',
            'termine': 'Terminé'
        };
        return statusLabels[status] || status;
    }

    async handleNewProject(e) {
        const formData = new FormData(e.target);
        const projectData = {
            title: formData.get('title'),
            description: formData.get('description'),
            status: formData.get('status')
        };

        try {
            const response = await fetch(`${API_BASE_URL}/projects.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify(projectData)
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la création du projet');
            }

            app.showSuccess('Projet créé avec succès');
            this.hideNewProjectModal();
            this.loadProjects();
            e.target.reset();
        } catch (error) {
            app.showError('Erreur lors de la création du projet');
            console.error(error);
        }
    }

    async deleteProject(projectId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce projet ?')) {
            return;
        }

        try {
            const response = await fetch(`${API_BASE_URL}/projects.php?id=${projectId}`, {
                method: 'DELETE',
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la suppression du projet');
            }

            app.showSuccess('Projet supprimé avec succès');
            this.loadProjects();
        } catch (error) {
            app.showError('Erreur lors de la suppression du projet');
            console.error(error);
        }
    }

    async editProject(projectId) {
        // À implémenter : logique d'édition de projet
        console.log('Édition du projet:', projectId);
    }

    showNewProjectModal() {
        if (this.newProjectModal) {
            this.newProjectModal.style.display = 'block';
        }
    }

    hideNewProjectModal() {
        if (this.newProjectModal) {
            this.newProjectModal.style.display = 'none';
            this.newProjectForm.reset();
        }
    }
}

// Initialisation du gestionnaire de projets
document.addEventListener('DOMContentLoaded', () => {
    window.projectsManager = new ProjectsManager();
}); 
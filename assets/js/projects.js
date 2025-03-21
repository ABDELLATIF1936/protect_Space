class ProjectsManager {
    constructor() {
        this.projectsList = document.getElementById('projects-list');
        this.newProjectModal = document.getElementById('new-project-modal');
        this.newProjectForm = document.getElementById('new-project-form');
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Gestion du formulaire de nouveau projet
        if (this.newProjectForm) {
            this.newProjectForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                await this.handleNewProject(e);
            });
        }

        // Fermeture du modal avec Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.hideNewProjectModal();
            }
        });

        // Boutons pour ouvrir le modal
        const newProjectButtons = document.querySelectorAll('.new-project-btn');
        newProjectButtons.forEach(button => {
            button.addEventListener('click', () => this.showNewProjectModal());
        });

        // Boutons pour fermer le modal
        const closeButtons = document.querySelectorAll('.close-button, .modal-close');
        closeButtons.forEach(button => {
            button.addEventListener('click', () => this.hideNewProjectModal());
        });

        // Clic en dehors du modal pour le fermer
        this.newProjectModal?.addEventListener('click', (e) => {
            if (e.target === this.newProjectModal) {
                this.hideNewProjectModal();
            }
        });
    }

    showError(message) {
        if (window.app) {
            window.app.showError(message);
        } else {
            console.error(message);
        }
    }

    showSuccess(message) {
        if (window.app) {
            window.app.showSuccess(message);
        } else {
            console.log(message);
        }
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

            const data = await response.json();
            this.displayProjects(Array.isArray(data) ? data : (data.projects || []));
        } catch (error) {
            this.showError('Erreur lors du chargement des projets');
            console.error(error);
        }
    }

    displayProjects(projects) {
        if (!this.projectsList) return;

        if (projects.length === 0) {
            this.projectsList.innerHTML = `
                <div class="no-projects">
                    <p>Aucun projet pour le moment</p>
                    <button class="btn btn-primary new-project-btn">
                        <i class="fas fa-plus"></i> Créer un projet
                    </button>
                </div>
            `;
            
            // Réattacher l'événement au nouveau bouton
            const newButton = this.projectsList.querySelector('.new-project-btn');
            if (newButton) {
                newButton.addEventListener('click', () => this.showNewProjectModal());
            }
            return;
        }

        this.projectsList.innerHTML = projects.map(project => `
            <div class="project-card" data-id="${project.id}">
                <div class="project-header">
                    <h3><a href="project-details.html?id=${project.id}" class="project-title">${project.title}</a></h3>
                    <span class="project-status ${project.status ? project.status.replace(' ', '-') : 'en-cours'}">${this.getStatusLabel(project.status || 'en cours')}</span>
                </div>
                <p class="project-description">${project.description || 'Aucune description'}</p>
                <div class="project-footer">
                    <div class="project-info">
                        <span><i class="fas fa-tasks"></i> ${project.tasks_count || 0} tâches</span>
                        <span> • </span>
                        <span><i class="fas fa-users"></i> ${project.members_count || 1} membres</span>
                    </div>
                    <div class="project-actions">
                        <button onclick="projectsManager.editProject(${project.id})" class="btn-icon" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="projectsManager.deleteProject(${project.id})" class="btn-icon" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');

        // Ajouter les gestionnaires d'événements pour les cartes de projet
        this.projectsList.querySelectorAll('.project-card').forEach(card => {
            card.addEventListener('click', (e) => {
                // Ne pas déclencher la navigation si on clique sur un bouton d'action
                if (!e.target.closest('.project-actions')) {
                    const projectId = card.dataset.id;
                    window.location.href = `project-details.html?id=${projectId}`;
                }
            });
        });
    }

    getStatusLabel(status) {
        const statusLabels = {
            'en cours': 'En cours',
            'en pause': 'En pause',
            'terminé': 'Terminé'
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

            const data = await response.json();

            if (response.ok) {
                this.showSuccess('Projet créé avec succès');
                this.hideNewProjectModal();
                this.loadProjects();
                e.target.reset();
            } else {
                throw new Error(data.error || 'Erreur lors de la création du projet');
            }
        } catch (error) {
            this.showError('Erreur lors de la création du projet');
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

            const data = await response.json();

            if (response.ok) {
                this.showSuccess('Projet supprimé avec succès');
                this.loadProjects();
            } else {
                throw new Error(data.error || 'Erreur lors de la suppression du projet');
            }
        } catch (error) {
            this.showError('Erreur lors de la suppression du projet');
            console.error(error);
        }
    }

    async editProject(projectId) {
        // À implémenter : logique d'édition de projet
        console.log('Édition du projet:', projectId);
    }

    showNewProjectModal() {
        const modal = document.getElementById('new-project-modal');
        if (modal) {
            modal.style.display = 'block';
        }
    }

    hideNewProjectModal() {
        const modal = document.getElementById('new-project-modal');
        if (modal) {
            modal.style.display = 'none';
            if (this.newProjectForm) {
                this.newProjectForm.reset();
            }
        }
    }
}

// Création d'une instance globale de ProjectsManager
window.projectsManager = null;

// Attendre que l'application soit initialisée
const waitForApp = async () => {
    while (!window.app) {
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    window.projectsManager = new ProjectsManager();
    window.projectsManager.loadProjects();
};

document.addEventListener('DOMContentLoaded', waitForApp); 
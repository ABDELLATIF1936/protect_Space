// Configuration de base
const API_BASE_URL = '/pro3/api';

// Fonctions utilitaires
const showError = (message) => {
    const errorDiv = document.getElementById('error-message');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    setTimeout(() => errorDiv.style.display = 'none', 5000);
};

const showSuccess = (message) => {
    const successDiv = document.getElementById('success-message');
    successDiv.textContent = message;
    successDiv.style.display = 'block';
    setTimeout(() => successDiv.style.display = 'none', 5000);
};

// Gestion des projets
const loadProjects = async () => {
    try {
        const response = await fetch(`${API_BASE_URL}/projects.php`);
        const data = await response.json();
        
        if (response.ok) {
            const projectsList = document.getElementById('projects-list');
            if (projectsList) {
                projectsList.innerHTML = data.projects.map(project => `
                    <div class="project-card">
                        <h3>${project.title}</h3>
                        <p>${project.description}</p>
                        <div class="project-meta">
                            <div class="project-status status-${project.status}">${getStatusLabel(project.status)}</div>
                            <a href="project-details.html?id=${project.id}" class="btn btn-primary">Voir détails</a>
                        </div>
                    </div>
                `).join('');
            }
        } else {
            showError(data.message || 'Erreur lors du chargement des projets');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showError('Erreur de connexion au serveur');
    }
};

const getStatusLabel = (status) => {
    const labels = {
        'en-attente': 'En attente',
        'en-cours': 'En cours',
        'termine': 'Terminé'
    };
    return labels[status] || status;
};

const handleNewProject = async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    try {
        const response = await fetch(`${API_BASE_URL}/projects.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                title: formData.get('title'),
                description: formData.get('description'),
                status: formData.get('status')
            })
        });

        const data = await response.json();
        
        if (response.ok) {
            showSuccess('Projet créé avec succès');
            hideNewProjectModal();
            loadProjects(); // Recharger la liste des projets
        } else {
            showError(data.message || 'Erreur lors de la création du projet');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showError('Erreur de connexion au serveur');
    }
};

// Fonctions pour le modal
function showNewProjectModal() {
    document.getElementById('new-project-modal').style.display = 'block';
}

function hideNewProjectModal() {
    document.getElementById('new-project-modal').style.display = 'none';
    document.getElementById('new-project-form').reset();
}

// Gestion de l'authentification
const handleSignup = async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    try {
        const response = await fetch(`${API_BASE_URL}/auth.php?endpoint=signup`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: formData.get('email'),
                password: formData.get('password'),
                full_name: formData.get('full_name'),
                role: formData.get('role')
            })
        });

        const data = await response.json();
        
        if (response.ok) {
            showSuccess('Compte créé avec succès! Redirection vers la connexion...');
            setTimeout(() => {
                document.getElementById('login-form').style.display = 'block';
                document.getElementById('signup-form').style.display = 'none';
            }, 2000);
        } else {
            showError(data.message || 'Erreur lors de la création du compte');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showError('Erreur de connexion au serveur');
    }
};

const handleLogin = async (event) => {
    event.preventDefault();
    const formData = new FormData(event.target);
    
    try {
        const response = await fetch(`${API_BASE_URL}/auth.php?endpoint=login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                email: formData.get('email'),
                password: formData.get('password')
            })
        });

        const data = await response.json();
        
        if (response.ok) {
            showSuccess('Connexion réussie! Redirection...');
            localStorage.setItem('user', JSON.stringify(data.user));
            setTimeout(() => {
                window.location.href = 'projects-view.html';
            }, 1000);
        } else {
            showError(data.message || 'Erreur de connexion');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showError('Erreur de connexion au serveur');
    }
};

// Gestion des tâches
const loadTasks = async (projectId) => {
    try {
        const response = await fetch(`${API_BASE_URL}/tasks.php?project_id=${projectId}`);
        const data = await response.json();
        
        if (response.ok) {
            const tasksList = document.getElementById('tasks-list');
            if (tasksList) {
                tasksList.innerHTML = data.tasks.map(task => `
                    <div class="task-card ${task.completed ? 'completed' : ''}">
                        <h4>${task.title}</h4>
                        <p>${task.description}</p>
                        <div class="task-meta">
                            <span>Assigné à: ${task.assigned_to_name}</span>
                            <span>Date limite: ${task.due_date}</span>
                        </div>
                    </div>
                `).join('');
            }
        } else {
            showError(data.message || 'Erreur lors du chargement des tâches');
        }
    } catch (error) {
        showError('Erreur de connexion au serveur');
        console.error(error);
    }
};

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    // Vérifier l'authentification
    const user = localStorage.getItem('user');
    if (!user && !window.location.pathname.includes('login-signup-page.html')) {
        window.location.href = 'login-signup-page.html';
        return;
    }

    // Formulaires d'authentification
    const loginForm = document.getElementById('login-form');
    const signupForm = document.getElementById('signup-form');
    
    if (loginForm) loginForm.addEventListener('submit', handleLogin);
    if (signupForm) signupForm.addEventListener('submit', handleSignup);
    
    // Gestion des liens de basculement
    document.querySelectorAll('.toggle-form').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            if (loginForm.style.display === 'none') {
                loginForm.style.display = 'block';
                signupForm.style.display = 'none';
            } else {
                loginForm.style.display = 'none';
                signupForm.style.display = 'block';
            }
        });
    });

    // Gestion des projets
    const newProjectForm = document.getElementById('new-project-form');
    if (newProjectForm) {
        newProjectForm.addEventListener('submit', handleNewProject);
    }

    // Chargement des projets sur la page des projets
    if (window.location.pathname.includes('projects-view.html')) {
        loadProjects();
    }

    // Gestion de la déconnexion
    const logoutLink = document.getElementById('logout-link');
    if (logoutLink) {
        logoutLink.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                const response = await fetch(`${API_BASE_URL}/auth.php?endpoint=logout`, {
                    method: 'POST'
                });
                
                if (response.ok) {
                    localStorage.removeItem('user');
                    window.location.href = 'login-signup-page.html';
                }
            } catch (error) {
                console.error('Erreur:', error);
                showError('Erreur lors de la déconnexion');
            }
        });
    }

    // Chargement des tâches sur la page de détails du projet
    if (window.location.pathname.includes('project-details-view.html')) {
        const urlParams = new URLSearchParams(window.location.search);
        const projectId = urlParams.get('id');
        if (projectId) loadTasks(projectId);
    }
}); 
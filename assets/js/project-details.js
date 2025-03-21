class ProjectDetails {
    constructor() {
        const urlParams = new URLSearchParams(window.location.search);
        this.projectId = urlParams.get('id');
        
        if (!this.projectId) {
            window.location.href = 'projects-view.html';
            return;
        }

        this.setupElements();
        this.setupEventListeners();
        this.loadProjectDetails();
        this.loadTasks();
    }

    setupElements() {
        this.projectTitle = document.getElementById('projectTitle');
        this.projectDescription = document.getElementById('projectDescription');
        this.projectStatus = document.getElementById('projectStatus');
        this.taskCount = document.getElementById('taskCount');
        this.memberCount = document.getElementById('memberCount');
        
        this.todoContainer = document.getElementById('todoTasks');
        this.inProgressContainer = document.getElementById('inProgressTasks');
        this.completedContainer = document.getElementById('completedTasks');
        
        this.newTaskModal = document.getElementById('newTaskModal');
        this.newTaskForm = document.getElementById('newTaskForm');
    }

    setupEventListeners() {
        document.getElementById('newTaskBtn').addEventListener('click', () => this.showNewTaskModal());
        document.getElementById('closeTaskModal').addEventListener('click', () => this.hideNewTaskModal());
        
        this.newTaskForm.addEventListener('submit', (e) => this.handleNewTask(e));
        
        // Fermer le modal avec Escape
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.newTaskModal.classList.contains('show')) {
                this.hideNewTaskModal();
            }
        });
    }

    async loadProjectDetails() {
        try {
            const response = await fetch(`${API_BASE_URL}/projects.php?id=${this.projectId}`);
            if (!response.ok) throw new Error('Erreur lors du chargement des détails du projet');
            
            const data = await response.json();
            this.updateProjectDetails(data);
        } catch (error) {
            this.showError('Impossible de charger les détails du projet');
            console.error(error);
        }
    }

    updateProjectDetails(project) {
        this.projectTitle.textContent = project.title;
        this.projectDescription.textContent = project.description || 'Aucune description';
        this.projectStatus.textContent = this.getStatusLabel(project.status);
        this.taskCount.textContent = project.task_count || '0';
        this.memberCount.textContent = project.member_count || '0';
    }

    async loadTasks() {
        try {
            const response = await fetch(`${API_BASE_URL}/tasks.php?project_id=${this.projectId}`);
            if (!response.ok) throw new Error('Erreur lors du chargement des tâches');
            
            const tasks = await response.json();
            this.displayTasks(tasks);
        } catch (error) {
            this.showError('Impossible de charger les tâches');
            console.error(error);
        }
    }

    displayTasks(tasks) {
        // Réinitialiser les conteneurs
        this.todoContainer.innerHTML = '';
        this.inProgressContainer.innerHTML = '';
        this.completedContainer.innerHTML = '';

        if (!tasks.length) {
            const message = document.createElement('div');
            message.className = 'text-center p-3';
            message.textContent = 'Aucune tâche pour le moment. Créez-en une nouvelle !';
            this.todoContainer.appendChild(message);
            return;
        }

        tasks.forEach(task => {
            const taskCard = this.createTaskCard(task);
            switch (task.status) {
                case 'à faire':
                    this.todoContainer.appendChild(taskCard);
                    break;
                case 'en cours':
                    this.inProgressContainer.appendChild(taskCard);
                    break;
                case 'terminé':
                    this.completedContainer.appendChild(taskCard);
                    break;
            }
        });
    }

    createTaskCard(task) {
        const card = document.createElement('div');
        card.className = 'card mb-3 task-card';
        card.dataset.taskId = task.id;

        const priorityClass = {
            'haute': 'bg-danger',
            'moyenne': 'bg-warning',
            'basse': 'bg-info'
        }[task.priority] || 'bg-secondary';

        card.innerHTML = `
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <h5 class="card-title mb-2">${task.title}</h5>
                    <span class="badge ${priorityClass}">${task.priority}</span>
                </div>
                <p class="card-text">${task.description || ''}</p>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted">
                        ${task.assigned_to_name ? `Assignée à: ${task.assigned_to_name}` : 'Non assignée'}
                    </small>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary edit-task">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-task">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                ${task.due_date ? `
                <div class="mt-2">
                    <small class="text-muted">
                        Échéance: ${new Date(task.due_date).toLocaleDateString()}
                    </small>
                </div>
                ` : ''}
            </div>
        `;

        this.setupTaskEventListeners(card, task);
        return card;
    }

    setupTaskEventListeners(card, task) {
        const editBtn = card.querySelector('.edit-task');
        const deleteBtn = card.querySelector('.delete-task');

        editBtn.addEventListener('click', () => this.editTask(task));
        deleteBtn.addEventListener('click', () => this.deleteTask(task.id));
    }

    async handleNewTask(e) {
        e.preventDefault();
        
        const formData = new FormData(e.target);
        const taskData = {
            project_id: this.projectId,
            title: formData.get('title'),
            description: formData.get('description'),
            status: formData.get('status'),
            priority: formData.get('priority'),
            due_date: formData.get('due_date'),
            assigned_to: formData.get('assigned_to')
        };

        try {
            const response = await fetch(`${API_BASE_URL}/tasks.php`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(taskData)
            });

            if (!response.ok) throw new Error('Erreur lors de la création de la tâche');
            
            const result = await response.json();
            this.showSuccess('Tâche créée avec succès');
            this.hideNewTaskModal();
            this.loadTasks();
            e.target.reset();
        } catch (error) {
            this.showError('Impossible de créer la tâche');
            console.error(error);
        }
    }

    async deleteTask(taskId) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) return;

        try {
            const response = await fetch(`${API_BASE_URL}/tasks.php?id=${taskId}`, {
                method: 'DELETE'
            });

            if (!response.ok) throw new Error('Erreur lors de la suppression de la tâche');
            
            this.showSuccess('Tâche supprimée avec succès');
            this.loadTasks();
        } catch (error) {
            this.showError('Impossible de supprimer la tâche');
            console.error(error);
        }
    }

    showNewTaskModal() {
        this.newTaskModal.classList.add('show');
        this.newTaskModal.style.display = 'block';
        document.body.classList.add('modal-open');
    }

    hideNewTaskModal() {
        this.newTaskModal.classList.remove('show');
        this.newTaskModal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    getStatusLabel(status) {
        const labels = {
            'à faire': 'À faire',
            'en cours': 'En cours',
            'terminé': 'Terminé'
        };
        return labels[status] || status;
    }

    showError(message) {
        if (window.app && typeof window.app.showError === 'function') {
            window.app.showError(message);
        } else {
            alert(message);
        }
    }

    showSuccess(message) {
        if (window.app && typeof window.app.showSuccess === 'function') {
            window.app.showSuccess(message);
        } else {
            alert(message);
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new ProjectDetails();
}); 
class DashboardManager {
    constructor() {
        this.setupEventListeners();
        this.loadDashboardData();
    }

    setupEventListeners() {
        // Gestion des tâches
        document.querySelectorAll('.todo-item').forEach(item => {
            const statusCircle = item.querySelector('.status-circle');
            if (statusCircle) {
                statusCircle.addEventListener('click', () => this.toggleTaskStatus(item));
            }
        });

        // Bouton d'ajout de tâche
        const addTaskButton = document.querySelector('.add-button');
        if (addTaskButton) {
            addTaskButton.addEventListener('click', () => this.showAddTaskModal());
        }
    }

    async loadDashboardData() {
        await Promise.all([
            this.loadProjects(),
            this.loadTasks(),
            this.loadTeamMembers(),
            this.loadUpcomingEvents()
        ]);
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
            this.updateProjectsTable(projects);
        } catch (error) {
            app.showError('Erreur lors du chargement des projets');
            console.error(error);
        }
    }

    async loadTasks() {
        try {
            const response = await fetch(`${API_BASE_URL}/tasks.php`, {
                method: 'GET',
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Erreur lors du chargement des tâches');
            }

            const tasks = await response.json();
            this.updateTasksList(tasks);
        } catch (error) {
            app.showError('Erreur lors du chargement des tâches');
            console.error(error);
        }
    }

    async loadTeamMembers() {
        try {
            const response = await fetch(`${API_BASE_URL}/team.php`, {
                method: 'GET',
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Erreur lors du chargement des membres');
            }

            const members = await response.json();
            this.updateTeamGrid(members);
        } catch (error) {
            app.showError('Erreur lors du chargement des membres');
            console.error(error);
        }
    }

    async loadUpcomingEvents() {
        try {
            const response = await fetch(`${API_BASE_URL}/events.php?upcoming=true`, {
                method: 'GET',
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Erreur lors du chargement des événements');
            }

            const events = await response.json();
            this.updateCalendarView(events);
        } catch (error) {
            app.showError('Erreur lors du chargement des événements');
            console.error(error);
        }
    }

    updateProjectsTable(projects) {
        const tbody = document.querySelector('.database-table tbody');
        if (!tbody) return;

        tbody.innerHTML = projects.map(project => `
            <tr>
                <td>${project.title}</td>
                <td>${project.owner}</td>
                <td><span class="tag ${project.category.toLowerCase()}">${project.category}</span></td>
                <td>${app.formatDate(project.due_date)}</td>
                <td>
                    <div class="progress-bar">
                        <div class="progress-bar-inner" style="width: ${project.progress}%"></div>
                    </div>
                </td>
                <td>${project.status}</td>
            </tr>
        `).join('');
    }

    updateTasksList(tasks) {
        const tasksContainer = document.querySelector('.content-block:nth-child(2)');
        if (!tasksContainer) return;

        const tasksList = tasks.map(task => `
            <div class="todo-item ${task.status}">
                <div class="status-circle"></div>
                <div class="task-text">${task.title}</div>
                <div class="assignee">${task.project}</div>
                <div class="due-date ${this.isOverdue(task.due_date) ? 'overdue' : ''}">${app.formatDate(task.due_date)}</div>
            </div>
        `).join('');

        tasksContainer.innerHTML = `
            <div class="block-title">Mes Tâches</div>
            ${tasksList}
            <button class="add-button">+ Ajouter une tâche</button>
        `;
    }

    updateTeamGrid(members) {
        const membersGrid = document.querySelector('.members-grid');
        if (!membersGrid) return;

        membersGrid.innerHTML = members.map(member => `
            <div class="member-card">
                <div class="member-avatar" style="background-color: ${member.color || '#4299e1'};">
                    ${this.getInitials(member.name)}
                </div>
                <div class="member-name">${member.name}</div>
                <div class="member-role">${member.role}</div>
            </div>
        `).join('') + `
            <div class="member-card">
                <div class="member-avatar" style="background-color: #d69e2e;">+</div>
                <div class="member-name">Ajouter un membre</div>
                <div class="member-role"></div>
            </div>
        `;
    }

    updateCalendarView(events) {
        const calendarDays = document.querySelectorAll('.calendar-day');
        calendarDays.forEach(day => {
            day.classList.remove('has-event');
            const events = day.querySelectorAll('.calendar-day-event');
            events.forEach(event => event.remove());
        });

        events.forEach(event => {
            const eventDate = new Date(event.date);
            const dayElement = this.findCalendarDay(eventDate);
            if (dayElement) {
                dayElement.classList.add('has-event');
                const eventElement = document.createElement('div');
                eventElement.className = 'calendar-day-event';
                eventElement.textContent = event.title;
                dayElement.appendChild(eventElement);
            }
        });
    }

    findCalendarDay(date) {
        const dayNumber = date.getDate();
        return Array.from(document.querySelectorAll('.calendar-day')).find(day => {
            const numberElement = day.querySelector('.calendar-day-number');
            return numberElement && parseInt(numberElement.textContent) === dayNumber;
        });
    }

    getInitials(name) {
        return name
            .split(' ')
            .map(n => n[0])
            .join('')
            .toUpperCase();
    }

    isOverdue(date) {
        return new Date(date) < new Date();
    }

    async toggleTaskStatus(taskElement) {
        const taskId = taskElement.dataset.id;
        const newStatus = taskElement.classList.contains('done') ? 'todo' : 'done';

        try {
            const response = await fetch(`${API_BASE_URL}/tasks.php`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'include',
                body: JSON.stringify({
                    id: taskId,
                    status: newStatus
                })
            });

            if (!response.ok) {
                throw new Error('Erreur lors de la mise à jour de la tâche');
            }

            taskElement.className = `todo-item ${newStatus}`;
        } catch (error) {
            app.showError('Erreur lors de la mise à jour de la tâche');
            console.error(error);
        }
    }

    showAddTaskModal() {
        // À implémenter : affichage du modal d'ajout de tâche
        console.log('Affichage du modal d\'ajout de tâche');
    }
}

// Initialisation du gestionnaire de tableau de bord
document.addEventListener('DOMContentLoaded', () => {
    window.dashboardManager = new DashboardManager();
}); 
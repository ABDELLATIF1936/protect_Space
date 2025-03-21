class CalendarManager {
    constructor() {
        this.currentDate = new Date();
        this.init();
    }

    async init() {
        // Attendre que l'application soit initialisée
        await this.waitForApp();
        this.setupEventListeners();
        this.loadEvents();
    }

    waitForApp() {
        return new Promise(resolve => {
            const checkApp = () => {
                if (window.app && window.app.user) {
                    resolve();
                } else {
                    setTimeout(checkApp, 100);
                }
            };
            checkApp();
        });
    }

    setupEventListeners() {
        // Navigation dans le calendrier
        const prevButton = document.querySelector('.action-button:first-child');
        const nextButton = document.querySelector('.action-button:last-child');
        
        if (prevButton) {
            prevButton.addEventListener('click', () => this.navigateMonth(-1));
        }
        if (nextButton) {
            nextButton.addEventListener('click', () => this.navigateMonth(1));
        }

        // Gestion des vues du calendrier
        const viewOptions = document.querySelectorAll('.view-option');
        viewOptions.forEach(option => {
            option.addEventListener('click', (e) => this.changeView(e.target));
        });

        // Bouton d'ajout d'événement
        const addEventButton = document.querySelector('.add-event-button');
        if (addEventButton) {
            addEventButton.addEventListener('click', () => this.showAddEventModal());
        }
    }

    async loadEvents() {
        try {
            const response = await fetch(`${API_BASE_URL}/events.php`, {
                method: 'GET',
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error('Erreur lors du chargement des événements');
            }

            const events = await response.json();
            this.displayEvents(events);
        } catch (error) {
            app.showError('Erreur lors du chargement des événements');
            console.error(error);
        }
    }

    displayEvents(events) {
        // Réinitialiser les événements affichés
        document.querySelectorAll('.date-event').forEach(el => el.remove());

        events.forEach(event => {
            const eventDate = new Date(event.date);
            const cell = this.findDateCell(eventDate);
            
            if (cell) {
                const eventElement = document.createElement('div');
                eventElement.className = `date-event ${event.type}`;
                eventElement.textContent = event.title;
                cell.appendChild(eventElement);
            }
        });
    }

    findDateCell(date) {
        const cells = document.querySelectorAll('.monthly-calendar td');
        return Array.from(cells).find(cell => {
            const dayNumber = cell.querySelector('.date-number');
            return dayNumber && parseInt(dayNumber.textContent) === date.getDate() &&
                   !cell.classList.contains('not-current-month');
        });
    }

    navigateMonth(delta) {
        this.currentDate.setMonth(this.currentDate.getMonth() + delta);
        this.updateCalendarHeader();
        this.loadEvents();
    }

    updateCalendarHeader() {
        const monthName = this.currentDate.toLocaleString('fr-FR', { month: 'long', year: 'numeric' });
        const monthElement = document.querySelector('.month-name');
        if (monthElement) {
            monthElement.textContent = monthName;
        }
    }

    changeView(selectedOption) {
        // Retirer la classe active de toutes les options
        document.querySelectorAll('.view-option').forEach(option => {
            option.classList.remove('active');
        });

        // Ajouter la classe active à l'option sélectionnée
        selectedOption.classList.add('active');

        // À implémenter : logique de changement de vue
        console.log('Changement de vue vers:', selectedOption.textContent);
    }

    showAddEventModal() {
        // À implémenter : affichage du modal d'ajout d'événement
        console.log('Affichage du modal d\'ajout d\'événement');
    }
}

// Initialisation du gestionnaire de calendrier
document.addEventListener('DOMContentLoaded', () => {
    window.calendarManager = new CalendarManager();
}); 
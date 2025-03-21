# ProjectSpace - Plateforme de Gestion de Projets Collaboratifs

ProjectSpace est une application web permettant aux professionnels et étudiants de gérer leurs projets en ligne, assigner des tâches et collaborer efficacement.

## Fonctionnalités

- Gestion des utilisateurs (étudiants, professionnels et administrateurs)
- Création et gestion de projets
- Attribution et suivi des tâches
- Messagerie interne pour la communication
- Partage de documents
- Tableau de bord interactif
- Suivi de l'avancement des projets

## Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache/Nginx)
- Composer (gestionnaire de dépendances PHP)
- Node.js et npm (pour les dépendances front-end)

## Installation

1. Cloner le dépôt :
```bash
git clone https://github.com/votre-username/projectspace.git
cd projectspace
```

2. Configurer la base de données :
- Créer une base de données MySQL
- Importer le fichier `database.sql`
- Copier le fichier `config/database.example.php` vers `config/database.php`
- Modifier les informations de connexion dans `config/database.php`

3. Installer les dépendances :
```bash
composer install
npm install
```

4. Configurer le serveur web :
- Pointer le document root vers le dossier `public`
- Activer le module de réécriture d'URL (mod_rewrite pour Apache)
- Configurer les permissions des dossiers :
```bash
chmod -R 755 .
chmod -R 777 uploads
```

## Structure des dossiers

```
projectspace/
├── api/            # API endpoints
├── assets/         # Fichiers statiques (JS, CSS, images)
├── auth/           # Gestion de l'authentification
├── config/         # Fichiers de configuration
├── models/         # Modèles de données
├── uploads/        # Dossier pour les fichiers uploadés
└── views/          # Templates HTML
```

## Utilisation

1. Inscription/Connexion :
- Accéder à la page d'accueil
- Créer un compte ou se connecter
- Choisir le type de compte (étudiant/professionnel)

2. Gestion des projets :
- Créer un nouveau projet
- Inviter des membres
- Assigner des tâches
- Suivre l'avancement

3. Communication :
- Utiliser la messagerie interne
- Partager des documents
- Commenter les tâches

## Sécurité

- Toutes les entrées utilisateur sont validées et nettoyées
- Les mots de passe sont hashés avec bcrypt
- Protection contre les injections SQL (requêtes préparées)
- Protection CSRF sur les formulaires
- Validation des types de fichiers uploadés

## Contribution

Les contributions sont les bienvenues ! Pour contribuer :

1. Forker le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Pousser vers la branche
5. Créer une Pull Request

## Support

Pour toute question ou problème :
- Ouvrir une issue sur GitHub
- Contacter l'équipe de support

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails. 
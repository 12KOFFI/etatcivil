# Plateforme de Gestion d'État Civil - Côte d'Ivoire

Cette application web permet la gestion numérique des actes d'état civil en Côte d'Ivoire. Elle offre une solution moderne et sécurisée pour la demande et le traitement des actes de naissance, de mariage et de décès.

## Fonctionnalités

- Inscription et authentification des utilisateurs
- Demande d'actes de naissance en ligne
- Demande d'actes de mariage en ligne
- Demande d'actes de décès en ligne
- Demande de duplicata d'actes
- Suivi des demandes
- Interface d'administration
- Génération de documents PDF
- Système de paiement intégré
- Gestion des utilisateurs et des rôles

## Types d'utilisateurs et leurs accès

### 1. Citoyen (Rôle : citoyen)
- Accès à l'espace personnel
- Possibilité de faire des demandes d'actes :
  - Acte de naissance
  - Acte de mariage
  - Acte de décès
  - Duplicata d'actes
- Suivi des demandes personnelles
- Consultation des actes validés
- Gestion des paiements



### 3. Administrateur (Rôle : admin )
- Accès complet à l'application
- Gestion des utilisateurs et des rôles
- Configuration du système
- Gestion des paramètres généraux
- Accès aux logs et aux statistiques
- Gestion des demandes en attente
- Validation des paiements

## Accès par défaut

### Compte Administrateur
- Email : admin@etatcivil.ci
- Mot de passe : password
- Rôle : Administrateur
- Accès : /admin/dashboard.php


### Compte Citoyen (exemple)
- Email : exemple@etatcivil.ci
- Mot de passe : 12345678
- Rôle : Citoyen
- Accès : /citoyen/dashboard.php

## Structure des dossiers

```
etat-civil-ci/
├── admin/              # Interface administrative
├── citoyen/           # Interface citoyen
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── config/
│   ├── database.php
│   └── config.php
├── database/
│   └── schema.sql
├── includes/
│   ├── header.php
│   └── footer.php
├── uploads/
├── index.php
├── login.php
├── register.php
└── README.md
```

## Prérequis

- PHP 7.4 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur web (Apache/Nginx)
- WampServer (pour Windows)
- Composer (gestionnaire de dépendances PHP)

## Installation

1. Clonez le dépôt :

```bash
git clone [URL_DU_REPO]
cd etat-civil-ci
```

2. Configurez votre serveur web pour pointer vers le dossier du projet

3. Créez une base de données MySQL :

```sql
CREATE DATABASE etat_civil_ci;
```

4. Importez le schéma de la base de données :

```bash
mysql -u root -p etat_civil_ci < database/schema.sql
```

5. Configurez les paramètres de connexion à la base de données dans `config/database.php`

6. Assurez-vous que les permissions des dossiers sont correctement configurées :

```bash
chmod 755 -R .
chmod 777 -R uploads/
```

## Configuration

1. Modifiez les paramètres de connexion dans `config/database.php` :

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
define('DB_NAME', 'etat_civil_ci');
```

2. Configurez les paramètres de l'application dans `config/config.php`

## Utilisation

1. Accédez à l'application via votre navigateur :

```
http://localhost/etat-civil-ci
```

2. Créez un compte utilisateur ou connectez-vous avec les identifiants par défaut

3. Commencez à utiliser l'application pour gérer les demandes d'actes d'état civil

## Sécurité

- Tous les mots de passe sont hashés avec l'algorithme bcrypt
- Protection contre les injections SQL avec PDO
- Validation et nettoyage des entrées utilisateur
- Protection CSRF sur les formulaires
- Sessions sécurisées

## Contribution

Les contributions sont les bienvenues ! N'hésitez pas à :

1. Fork le projet
2. Créer une branche pour votre fonctionnalité
3. Commiter vos changements
4. Pousser vers la branche
5. Ouvrir une Pull Request

## Support

Pour toute question ou problème, veuillez ouvrir une issue sur le dépôt GitHub ou contacter l'équipe de support à support@etatcivil.ci

## Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.
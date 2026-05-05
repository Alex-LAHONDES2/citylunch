# CityLunch - Application de commande de repas

Application web développée avec Symfony permettant à des clients de consulter des plats, gérer un panier et simuler des commandes.  
Le projet inclut également une gestion des livreurs, du stock et du suivi des livraisons.

Lien vers le repo https://github.com/Alex-LAHONDES2/citylunch

---

# 1. Technologies utilisées

## Backend
- PHP 8.2+
- Symfony 6/7
- Doctrine ORM

## Frontend
- Twig (templates server-side)

## Bases de données
- MySQL 8 (données métier)
- Redis (sessions utilisateur)

## Outils
- Composer
- Symfony CLI (optionnel mais recommandé)
- phpMyAdmin (gestion MySQL)
- Redis server

---

# 2. Architecture globale

- MVC Symfony
- Sessions stockées dans Redis
- Données persistées dans MySQL
- Aucune API (render HTML uniquement)

---

# 3. Prérequis

Installer :

- PHP 8.2+
- MySQL 8+
- Redis
- Composer
- Symfony CLI (optionnel)

Extensions PHP :
- pdo_mysql
- redis

---

# 4. Installation du projet

```bash
git clone https://github.com/TON-USERNAME/citylunch.git
cd citylunch
composer install
```
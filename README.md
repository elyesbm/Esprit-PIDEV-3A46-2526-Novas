# 👤 Module: Gestion des Utilisateurs (User Management) - NoVas

## 📝 Overview
Le module **Gestion des Utilisateurs** est le noyau de sécurité et d'identité de la plateforme **NoVas**. Il assure non seulement l'authentification et l'autorisation, mais intègre également des technologies avancées comme la reconnaissance faciale et l'authentification multi-facteurs (2FA) pour garantir une expérience utilisateur sécurisée et moderne.

Ce module a été développé dans le cadre du projet **PIDEV – Esprit School of Engineering** (Année Universitaire 2025–2026).

---

## ✨ Features

### 🔐 Authentification Multi-Cloud & Sociale
- **Connexion Classique** : Email et mot de passe sécurisé via Symfony Security.
- **Social Login** : Intégration complète de **Google** et **Facebook** OAuth2 via `KnpUOAuth2ClientBundle`.
- **Reconnaissance Faciale** : Authentification biométrique utilisant `face-api.js` pour une connexion sans mot de passe après enregistrement du vecteur facial.

### 🛡️ Sécurité Avancée
- **Two-Factor Authentication (2FA)** : Protection renforcée via Google Authenticator (TOTP).
- **Codes de Secours** : Génération automatique de codes de backup pour éviter le verrouillage du compte.
- **Réinitialisation du Mot de Passe** : Flux sécurisé par token avec expiration temporelle.
- **Protection des Données** : Utilisation de l'attribut `#[Ignore]` du Serializer pour masquer les champs sensibles (`password`, `secrets`, `biometrics`) lors des exports JSON.

### 👤 Profil & Identité
- **Gestion de Profil** : Mise à jour des informations personnelles, images de profil et coordonnées.
- **Rôles & Permissions** : Système RBAC (Role-Based Access Control) gérant les accès Étudiant et Administrateur.
- **Statut Actif/Inactif** : Possibilité de suspendre des comptes via le panneau d'administration.

---

## 🛠️ Tech Stack

### Backend (Symphony 6.4)
- **Security Bundle** : Gestion des firewalls, authenticators et providers.
- **KnpU OAuth2 Client** : Connecteurs Google et Facebook.
- **Scheb TwoFactorBundle** : Implémentation du 2FA.
- **Doctrine ORM** : Mapping de l'entité [User](cci:2://file:///c:/Users/ASUS/Desktop/projet_web_java_novas_main_final/src/Entity/User.php:13:0-206:1) et gestion des relations OneToMany/OneToOne.

### Frontend
- **Tailwind CSS** : Interfaces de connexion et d'inscription modernes et réponsives.
- **Face-api.js** : Librairie JavaScript pour la détection et reconnaissance des traits faciaux.
- **Lucide Icons** : Iconographie pour une meilleure expérience utilisateur (UX).

---

## 🏗️ Architecture du Module

```text
src/
├── Controller/
│   ├── Front/Auth/        # Login, Register, Social Auth
│   └── User/              # Edition du profil, 2FA settings
├── Entity/
│   └── User.php           # Entité centrale (18 relations gérées)
├── Security/
│   ├── GoogleAuthenticator.php
│   ├── FacebookAuthenticator.php
│   └── FaceAuthenticator.php
└── Service/
    └── UserManager.php    # Logique métier & validations (Unit Tested)

Module: Gestion des Articles (Article Management) - NoVas
📝 Overview
Le module Gestion des Articles est le coeur de la marketplace NoVas. Il permet la creation, la publication, la vente et la mise en valeur des articles, avec recherche, recommandations et statistiques pour une experience fluide et efficace.

Ce module a ete developpe dans le cadre du projet PIDEV – Esprit School of Engineering (Annee Universitaire 2025–2026).

✨ Features
🛍️ Publication & Catalogue
Creation/Modification/Suppression : Gestion complete des articles cote front et admin.
Gestion des Images : Upload securise avec fallback automatique.
Classification : Articles relies a une categorie et a un auteur (etudiant).

🔎 Recherche, Filtres & Recommandations
Recherche Avancee : Filtrage par titre, statut, categorie et type.
Tri & Pagination : Tri par date/prix et pagination dynamique.
Recommandations : Suggestions basees sur achats et consultations.

🛒 Panier & Paiement
Panier Multi-Articles : Quantites, sous-totaux, resume.
Checkout Stripe : Paiement securise et enregistrement des commandes.
Notifications : Envoi SMS (Twilio) apres paiement.

📊 Reporting & Admin
Exports PDF : Articles disponibles et categories.
Stats Marketplace : Articles les plus vus et categories populaires.
Administration : Tableaux de bord et moderation.

🛠️ Tech Stack
Backend (Symfony 6.4)
Doctrine ORM : Entites Article, Categorie, Commande, HistoriqueVue.
Symfony Validator : Contraintes metiers et securite des donnees.
Dompdf : Generation des rapports PDF.
HttpClient : Integration Stripe et SMS.

Frontend
Twig + Tailwind CSS : Interfaces marketplace modernes et responsives.

🏗️ Architecture du Module
src/
├── Controller/
│ ├── Front/MarketplaceController.php # Liste, detail, panier, checkout
│ └── Admin/MarketplaceAdminController.php # CRUD admin, stats, exports
├── Entity/
│ ├── Article.php
│ ├── Categorie.php
│ ├── Commande.php
│ └── HistoriqueVue.php
├── Repository/
│ ├── ArticleRepository.php
│ ├── CommandeRepository.php
│ └── HistoriqueVueRepository.php
└── templates/
├── front/marketplace/ # UI marketplace
└── admin/marketplace/ # UI admin

Module : Gestion des Skills & Learning Paths (Skill Management) – NoVas 🎓
Overview
Le module Gestion des Skills & Learning Paths est le pilier éducatif de la plateforme NoVas. Il permet la création, la gestion et le suivi des compétences (skills), ainsi que la génération et la consultation de parcours d'apprentissage (learning paths). Enrichi par l'intelligence artificielle, l'analyse du marché de l'emploi et un système de playlists, ce module offre une expérience de montée en compétences complète et orientée marché.

Ce module a été développé dans le cadre du projet PIDEV – Esprit School of Engineering (Année Universitaire 2025–2026).

✨ Features
🧠 Gestion des Compétences
Création / Modification / Suppression : Gestion complète des skills côté front et admin.
Classification : Skills organisés par catégorie (Programmation, Data Science, Marketing, Design…) et type de compétence (hard skill / soft skill).
Attribution : Chaque skill est relié à un créateur (étudiant/utilisateur).
🔎 Recherche, Filtres & Catalogue
Recherche Avancée : Filtrage par nom, description, catégorie et type (hard/soft).
Tri & Pagination : Tri alphabétique et pagination dynamique (KnpPaginator).
Catalogue Front : Exploration de tous les skills disponibles avec filtres combinés.
📚 Learning Paths & Playlists
Parcours d'apprentissage : Étapes structurées (post, vidéo, exercice, quiz) reliées à un skill.
Système de Playlists : Regroupement des parcours par skill (1 skill = 1 playlist) avec durée totale et niveaux.
Niveaux : Débutant, Intermédiaire, Avancé — filtrage et tri par niveau.
Détail parcours : Titre, description, durée estimée, type d'étape, URL de ressource.
🤖 Intelligence Artificielle
Tuteur IA (Chatbot) : Assistant conversationnel pour recommander des compétences adaptées au profil et aux objectifs de l'utilisateur.
Génération IA de Learning Paths : Création automatique de parcours complets à partir d'un prompt, avec prévisualisation et édition avant sauvegarde.
Multi-Provider : Support de Google Gemini, xAI Grok, et OpenRouter avec fallback automatique.
📊 Statistiques Marché & Reporting
Analyse du Marché : Nombre d'offres d'emploi associées, score de demande (1–5) et tendance (en hausse / stable / en baisse) via l'API Adzuna.
Commande CLI : app:skill:refresh-market-stats pour rafraîchir les stats marché en batch ou par skill.
Graphiques Admin : Visualisation des tendances marché et du nombre d'offres par compétence.
Tableaux de bord : Stats globales (total skills, total parcours, répartition par tendance).
🛡️ Administration
CRUD Admin complet : Gestion des skills et des learning paths avec validation et tokens CSRF.
Rafraîchissement des stats : Mise à jour individuelle ou globale des données marché.
Modération : Statut actif/inactif pour les parcours d'apprentissage.
🛠️ Tech Stack
Backend (Symfony 6.4)
Doctrine ORM : Entités 

Skill
, 

LearningPath
 avec relations OneToMany / ManyToOne.
Symfony Validator : Contraintes métiers (NotBlank, Length) et sécurité des données.
HttpClient : Intégration API Adzuna (statistiques marché) et API IA (Gemini / Grok / OpenRouter).
Console Command : Commande Symfony pour le batch refresh des stats marché.
KnpPaginator : Pagination côté front.
Frontend
Twig + Tailwind CSS : Interfaces modernes et responsives pour le catalogue, les playlists et le chatbot IA.
🏗️ Architecture du Module
src/
├── Command/
│   └── RefreshSkillMarketStatsCommand.php   # CLI : rafraîchir stats marché
├── Controller/
│   ├── Front/
│   │   ├── SkillController.php              # Catalogue, CRUD front, tuteur IA
│   │   └── LearningPathController.php       # Parcours, playlists, détail
│   └── Admin/
│       ├── SkillAdminController.php          # CRUD admin, stats marché
│       └── LearningPathAdminController.php   # CRUD admin, génération IA
├── Entity/
│   ├── Skill.php                            # Compétence (nom, catégorie, type, stats marché)
│   └── LearningPath.php                     # Parcours (titre, niveau, durée, type étape, URL)
├── Form/
│   ├── SkillType.php                        # Formulaire skill
│   └── LearningPathType.php                 # Formulaire parcours
├── Repository/
│   ├── SkillRepository.php                  # Recherche, filtres, stats tendance, playlists
│   └── LearningPathRepository.php           # Recherche, filtres admin
├── Service/
│   ├── SkillAITutorService.php              # Chatbot IA + génération learning paths
│   └── SkillMarketStatsService.php          # Stats marché via API Adzuna
└── templates/
    ├── front/
    │   ├── skill/                           # index, mes_skills, ajouter, modifier, tuteur_ia
    │   └── learning_path/                   # index, playlists, playlist_detail, detail
    └── admin/
        ├── skill/                           # list, form
        └── learning_path/                   # form, generate (IA)

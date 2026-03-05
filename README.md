# 🌐 NoVas – Network & Values Academic Social Platform

<div align="center">

![Esprit School of Engineering](https://img.shields.io/badge/Esprit-School%20of%20Engineering-blue?style=for-the-badge)
![Academic Year](https://img.shields.io/badge/Academic%20Year-2025--2026-orange?style=for-the-badge)
![Symfony](https://img.shields.io/badge/Symfony-6.4-black?style=for-the-badge&logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.2-purple?style=for-the-badge&logo=php)
![License](https://img.shields.io/badge/License-Academic-green?style=for-the-badge)

</div>

---

## Overview

This project was developed as part of the **PIDEV – 3rd Year Engineering Program** at **Esprit School of Engineering** (Academic Year 2025–2026).

**NoVas** (Network & Values) is a comprehensive academic social platform designed for students and educators. It provides a unified digital environment combining social networking, academic resource sharing, job opportunities, event management, and AI-powered career tools — all tailored for the academic ecosystem.

> 🎓 Developed at **Esprit School of Engineering – Tunisia** | PIDEV 3A | 2025–2026

---

## Features

### 👤 User Management
- Secure registration & login (email/password, facial recognition, Google & Facebook OAuth)
- Two-Factor Authentication (2FA) with backup codes
- Profile management with CV upload and AI analysis
- Role-based access control (Student, Admin)
- Score & analytics dashboard

### 📝 Publications & Social Feed
- Create, edit, delete publications (text, images, YouTube links)
- Threaded comments with audio recording support
- Reaction system (likes, reports)
- AI-powered toxicity & sentiment detection
- SoftDelete for content moderation

### 📰 Articles & Knowledge Sharing
- Rich text article creation (with CKEditor)
- Article categorization & marketplace
- PDF export (DomPDF integration)
- AI-generated descriptions

### 💼 Career & Job Board
- Job offers posting and management
- Smart application tracking
- AI CV scoring: technical skills, soft skills, maturity index, competitiveness score
- AI-powered job offer optimizer
- Personalized career recommendations

### 🎓 Workshops & Ateliers
- Workshop creation with schedule and capacity management
- Online reservation system
- QR code generation for attendance
- AI-generated workshop descriptions
- Rating & review system

### 🛒 Marketplace (Articles)
- Buy/sell academic articles
- Order management
- PDF invoice generation

### 🤖 AI Features (Hugging Face + Gemini)
- CV analysis and scoring via Gemini Pro API
- Toxicity detection on comments/publications (Hugging Face)
- Sentiment analysis
- AI-generated content descriptions
- Voice assistant for health data
- Face recognition login (face-api.js)

### 📊 Analytics Dashboard
- User score evolution charts (Chart.js)
- Score history tracking
- Performance metrics

---

## Tech Stack

### Backend
| Technology | Version | Role |
|------------|---------|------|
| **PHP** | 8.2 | Core language |
| **Symfony** | 6.4 | Web framework |
| **Doctrine ORM** | 3.x | Database ORM |
| **MySQL / MariaDB** | 10.4 | Relational database |
| **KnpU OAuth2** | — | Google & Facebook login |
| **Stof Doctrine Extensions** | — | SoftDelete, Tree, etc. |
| **DomPDF** | — | PDF generation |
| **PHPUnit** | 11.x | Unit testing |
| **Doctrine Doctor** | 1.x | DB performance & security analysis |

### Frontend
| Technology | Role |
|------------|------|
| **Twig** | Server-side templating |
| **Tailwind CSS** | UI Styling (utility-first) |
| **Lucide Icons** | Icon system |
| **Chart.js** | Analytics charts |
| **face-api.js** | Facial recognition login |
| **CKEditor** | Rich text editor |

### AI & External APIs
| Service | Usage |
|---------|-------|
| **Google Gemini Pro** | CV analysis, AI descriptions |
| **Hugging Face** | Toxicity & sentiment analysis |

---

## Architecture

```
NoVas/
├── src/
│   ├── Controller/
│   │   ├── Admin/          # Back-office controllers
│   │   └── Front/          # User-facing controllers
│   ├── Entity/             # Doctrine ORM entities (18 entities)
│   │   ├── User.php
│   │   ├── Publication.php
│   │   ├── Article.php
│   │   ├── Offrejob.php
│   │   ├── Atelier.php
│   │   ├── CvProfile.php
│   │   ├── ScoreHistory.php
│   │   └── ...
│   ├── Repository/         # Custom Doctrine repositories
│   ├── Form/               # Symfony form types
│   ├── Security/           # Authenticators (Standard, Google, Facebook, Face)
│   └── Service/            # Business logic services
│       ├── UserManager.php
│       ├── HuggingFaceImageService.php
│       ├── SentimentAnalysisService.php
│       ├── ToxicityAnalysisService.php
│       └── ...
├── templates/
│   ├── front/              # Student-facing views
│   └── admin/              # Back-office views
├── migrations/             # Database migrations
├── tests/                  # PHPUnit tests
│   └── UserTest.php
├── scripts/                # Python scripts (HF AI)
│   ├── hf_sentiment.py
│   ├── hf_toxicity.py
│   └── hf_image.py
└── public/                 # Web root
```

---

## Contributors

| Name | GitHub | Role |
|------|--------|------|
| **Elyès Ben Moussa** | [@elyesbm](https://github.com/elyesbm) | Team Lead – User Management & AI Features |
| *Team Member 2* | — | Publications & Social Feed |
| *Team Member 3* | — | Career & Job Board |
| *Team Member 4* | — | Workshops & Ateliers |
| *Team Member 5* | — | Marketplace & Orders |

---

## Academic Context

```
Institution : Esprit School of Engineering – Tunisia
Program     : PIDEV – 3ème Année Ingénierie
Academic Year: 2025–2026
```

This project was developed as a collaborative academic project, showcasing full-stack web development competencies, AI integration, software engineering best practices (unit testing, performance analysis, OAuth integration), and modern UX design principles.

---

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- MySQL / MariaDB 10.4+
- Symfony CLI
- Node.js (for assets)
- Python 3.11+ (for AI scripts)

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/elyesbm/projet_web_java_novas.git
cd projet_web_java_novas

# 2. Install PHP dependencies
composer install

# 3. Install Python dependencies (AI scripts)
pip install huggingface_hub

# 4. Configure environment
cp .env .env.local
# Edit .env.local with your database credentials and API keys:
# DATABASE_URL="mysql://root:@127.0.0.1:3306/projet-dev"
# OAUTH_GOOGLE_CLIENT_ID=your_google_client_id
# OAUTH_GOOGLE_CLIENT_SECRET=your_google_secret
# OAUTH_FACEBOOK_CLIENT_ID=your_facebook_app_id
# OAUTH_FACEBOOK_CLIENT_SECRET=your_facebook_secret
# HF_TOKEN=your_huggingface_token
# GEMINI_API_KEY=your_gemini_api_key

# 5. Create database & run migrations
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# 6. Start the server
symfony serve -d

# 7. Open in browser
# http://127.0.0.1:8000
```

### Running Tests

```bash
# Run all unit tests
php bin/phpunit

# Expected output:
# OK (4 tests, 4 assertions)
```

### Performance & Quality Analysis

```bash
# Run Doctrine Doctor analysis via the Symfony Web Profiler
# 1. Start the server and visit any page
# 2. Click the Doctrine Doctor icon in the toolbar
```

---

## Acknowledgments

- **Esprit School of Engineering** for the academic framework and support
- [Symfony](https://symfony.com/) for the powerful PHP framework
- [Hugging Face](https://huggingface.co/) for AI inference APIs
- [Google Gemini](https://ai.google.dev/) for CV analysis capabilities
- [KnpU OAuth2](https://github.com/knpuniversity/oauth2-client-bundle) for social login
- [face-api.js](https://github.com/justadudewhohacks/face-api.js) for browser-side facial recognition
- [Chart.js](https://www.chartjs.org/) for data visualization

---

<div align="center">

**Esprit School of Engineering – Tunisia** | PIDEV 3A | 2025–2026

Made with ❤️ by the NoVas Team

</div>

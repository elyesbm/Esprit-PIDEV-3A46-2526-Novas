# NOVAS – Forum Management Module

## Overview
The Forum Management module is part of the **NOVAS platform**, a management system developed to organize and manage different services within a single application.

This module focuses on managing **forum publications and comments**, allowing users to share posts, interact through comments, and participate in discussions.

This project was developed as part of the **PIDEV – 3rd Year Engineering Program at Esprit School of Engineering (Academic Year 2025–2026).**

## Features
Forum Management Module:
- Create, edit, and delete forum publications
- View and browse forum posts
- Add comments to publications
- Manage comments (edit or delete)
- Facilitate communication and interaction between users

## Tech Stack

### Frontend
- Twig
- HTML
- CSS
- JavaScript

### Backend
- Symfony
- PHP
- MySQL
- Doctrine ORM

## Architecture
The module follows the **MVC architecture** using the Symfony framework.  
It is structured using Controllers, Entities, Repositories, and Twig templates to ensure separation between business logic, data management, and user interface.

## Contributors
- Dhouha Haddadi

## Academic Context
Developed at **Esprit School of Engineering – Tunisia**

PIDEV – 3rd Year Engineering Program  
Academic Year: 2025–2026

## Getting Started
1. Clone the repository
2. Install dependencies using Composer
3. Configure the database connection in `.env`
4. Run database migrations
5. Start the Symfony server

Example:

git clone repository-url  
cd project-folder  
composer install  
php bin/console doctrine:migrations:migrate  
symfony server:start

## Acknowledgments
Special thanks to **Esprit School of Engineering** for providing the academic framework and support for this project.

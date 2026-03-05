# Gestion Atelier – Workshop Management System

# NOVAS – Management Platform

## Overview
NOVAS is a management platform designed to organize and manage different services within a single system. The platform is composed of several modules that allow efficient management of various activities.

This project was developed as part of the **PIDEV – 3rd Year Engineering Program at Esprit School of Engineering (Academic Year 2025–2026).**

The module implemented in this repository focuses on **Workshop and Reservation Management**, which allows users to manage workshops and handle reservations efficiently.

## Features
Workshop & Reservation Management Module:
- Create, update and delete workshops
- Manage workshop reservations
- Display workshop list
- Pagination of workshops
- Statistics such as the most reserved workshops

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
The application follows the **MVC architecture** using the Symfony framework.  
The system is structured into Controllers, Entities, Repositories, and Templates (Twig) to ensure clear separation between business logic, data access, and presentation.

## Contributors
- Mariem Ferchichi

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

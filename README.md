# Egg & Ice Sales Monitoring System

A responsive, lightweight web application built for remote sales monitoring of a retail Egg & Ice business. Designed for business owners/admins monitoring sales from afar and on-site staff recording customer purchases.

---

## Key Features

- **Role-Based Access Control**:
  - **Admin**: Dashboard, remote sales monitoring, daily/weekly/monthly/custom reports, dynamic product & price management, staff account management.
  - **Staff**: Quick point-of-sale interface (New Sale), payment processing, change calculation, personal sales history.
- **Dynamic Product & Variant Architecture**: Supports multiple products and arbitrary variants (e.g., Egg -> Small, Medium, Large; Ice -> Default) without hardcoded rules.
- **Secure Transaction Engine**:
  - Server-side price enforcement (never trusts client prices).
  - MySQL database transactions (`BEGIN` / `COMMIT` / `ROLLBACK`).
  - Historical price lock (saves `unit_price` at the exact moment of sale).
  - Status-based sale cancellations.
- **Responsive UI**: Mobile-friendly design using Tailwind CSS.

---

## Technology Stack

- **Backend**: PHP 8+ (Standard procedural/OOP structured)
- **Database**: MySQL 8+ / MariaDB via PHP PDO
- **Frontend**: HTML5, Tailwind CSS, Vanilla JavaScript (Fetch API)
- **Authentication**: Native PHP Sessions, BCRYPT password hashing (`password_hash()`)

---

## Project Structure

```
egg-ice-system/
├── config/             # Database & configuration settings
├── public/             # Entry points, login, logout
├── admin/              # Admin pages (Dashboard, Products, Staff, Reports, Sales)
├── staff/              # Staff pages (Dashboard, New Sale, Sales)
├── actions/            # Backend POST request processing actions
├── includes/           # Header, Sidebar, Footer, Auth & CSRF guards
├── assets/             # CSS & JavaScript assets
├── database/           # Database schema SQL files
├── memory.md           # Project source-of-truth memory file
└── README.md           # Documentation
```

---

## Installation & Setup Guide

### 1. Database Setup
1. Import `database/database.sql` into MySQL / MariaDB via phpMyAdmin or MySQL CLI.
2. The script will create database `egg_ice_db` along with required tables and initial seed data (Products: Egg, Ice; Default Admin Account).

### 2. Configuration
1. Open `config/database.php`.
2. Update database credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`) to match your XAMPP or production environment settings.

### 3. Default Credentials
- **Admin**: `admin` / `admin123` (Change password upon deployment)
- **Staff**: Created by Admin via Staff Management module.

---

## Development Rule & Memory

All major architecture rules and database structures are recorded in `memory.md`.

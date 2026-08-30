# Egg & Ice Sales Monitoring System - Project Memory

> **SOURCE OF TRUTH**: This file is the absolute source of truth for the Egg & Ice Sales Monitoring System project. All architectural decisions, database schemas, feature rules, and project guidelines are documented here.

---

## 1. Project Summary & Purpose

- **Project Name**: Egg & Ice Sales Monitoring System
- **Target Business**: Small retail business selling Eggs and Ice.
- **Core Problem Solved**: Remote sales monitoring for Admin/Owner located far away, with fast POS sales recording for on-site Staff.
- **Key Architecture**: Web-based PHP 8+, MySQL PDO, Vanilla JS, Tailwind CSS.

### CRITICAL SCOPE BOUNDARY
- **NOT an inventory system**: Absolutely ZERO stock tracking, stock-in/out, or inventory deductions.

---

## 2. Technology Stack & Directory Structure

- **Backend**: PHP 8+ (PDO, prepared statements, BCRYPT password hashing, MySQL transactions)
- **Frontend**: HTML5, Tailwind CSS, Vanilla JS POS Cart Manager
- **Database**: MySQL `egg_ice_db`

```
c:\inventory\
├── config\database.php
├── database\database.sql
├── includes\ (auth.php, admin-auth.php, staff-auth.php, csrf.php, header.php, sidebar.php, footer.php)
├── actions\ (login.php, logout.php, save-product.php, update-product.php, save-sale.php, cancel-sale.php, save-staff.php, update-staff.php)
├── assets\js\sales.js
├── public\ (index.php, login.php, logout.php)
├── admin\ (dashboard.php, products.php, product-create.php, product-edit.php, sales.php, sale-details.php, reports.php, staff.php, staff-create.php, staff-edit.php)
└── staff\ (dashboard.php, new-sale.php, sales.php, sale-details.php)
```

---

## 3. Seeded Accounts & Roles

- **Admin**: `admin` / `admin123` (Full store access, remote sales monitoring, product & staff management, analytics reports)
- **Staff**: `staff` / `staff123` (POS sale recording, personal sales history)

---


## 4. Order Cancellation & Voiding Policy

- **Staff Responsibility**:
  - Staff can cancel an order when necessary (customer changes order, staff error, customer decides not to proceed, etc.).
  - Confirmation modal displayed with "Cancel Order" and "Keep Order" options.
  - Staff selects/provides a cancellation reason.
  - Changes status to `cancelled` and records `cancellation_reason`. Orders are NEVER deleted from the database.
- **Admin Restriction**:
  - Admin cannot cancel, void, delete, or alter completed sale statuses.
  - Backend strictly enforces this rule (`actions/cancel-sale.php` checks `user_role === 'staff'` and rejects admin attempts with `HTTP 403 Forbidden`).
  - Admin interface has NO cancel or void buttons.
- **Revenue Calculations**:
  - Cancelled transactions are strictly excluded from all sales calculations, total revenue, daily/weekly/monthly/custom date range reports, and product unit sales.
  - Cancelled orders remain visible in Sales History with a red `CANCELLED` status badge and cancellation reason.

---

## 5. Status Update: Order Cancellation Security Updated
- System tested and ready for production deployment.


# SalesTrack

A modern, responsive sales monitoring and management panel for tracking transactions, products, inventory, and staff activity. Built with PHP, MySQL, and Tailwind CSS.

## 📋 Features

- **Sales Management**
  - Record new sales transactions with multiple items per sale
  - View detailed sales history with filtering and search
  - Cancel or modify orders (staff & admin roles)
  - Real-time transaction tracking with unique transaction numbers

- **Product & Inventory Management**
  - Create and manage products with multiple variants
  - Track inventory levels per variant
  - Product image uploads with validation
  - Variant-specific pricing and stock management

- **Reports & Analytics**
  - Comprehensive sales reports with date range filtering
  - Revenue tracking (total, daily, weekly, monthly)
  - Transaction volume analytics
  - Product performance breakdown by variant
  - Sales trend charts with monthly comparisons

- **Admin Dashboard**
  - Real-time sales metrics and KPIs
  - Today's sales overview with transaction counts
  - Variant breakdown with top performers
  - Sales trend visualization with charts
  - Full user and product management

- **Staff Management**
  - Role-based access control (Admin / Staff)
  - User account creation and management
  - Staff activity audit logging
  - Secure authentication with bcrypt hashing

- **User Experience**
  - Clean, intuitive admin & staff interfaces
  - Responsive design (mobile, tablet, desktop)
  - Purple brand theme with Tailwind CSS
  - Real-time form validation
  - Comprehensive error handling

## 🛠 Tech Stack

- **Backend:** PHP 8.0+
- **Database:** MySQL 5.7+ / MariaDB or SQLite (local development)
- **Frontend:** Tailwind CSS (CDN), HTML5, Vanilla JavaScript
- **UI Components:** Font Awesome 6.5+ icons, ApexCharts for chart visualizations
- **Security:** bcrypt password hashing, CSRF protection, prepared statements, HTML sanitization

## 🚀 Quick Start

### Requirements

- **PHP 8.0 or higher**
- **MySQL 5.7+** (production) or **SQLite** (local development)
- **Apache/XAMPP/LAMP stack** (for local development)
- **Git**

### Installation

#### 1. Clone the Repository

```bash
git clone https://github.com/yourusername/salestrack.git
cd salestrack
```

#### 2. Create Local Database Configuration

You must manually create `config/database.local.php` with your local credentials. This file is **gitignored** to keep credentials secure.

**For MySQL (XAMPP/LAMP):**

```php
<?php
// config/database.local.php
define('DB_TYPE', 'mysql');
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'salestrack_local');
define('DB_USER', 'root');
define('DB_PASS', '');  // Empty for XAMPP default
```

**For SQLite (easier local testing):**

```php
<?php
// config/database.local.php
define('DB_TYPE', 'sqlite');
define('DB_SQLITE_PATH', __DIR__ . '/database.sqlite');
```

⚠️ **Important:** `config/database.local.php` is in `.gitignore` — it will never be committed to the repository. This is intentional for security.

#### 3. Set Up Local Development Server

**XAMPP (Windows/Mac):**
1. Place the project in `C:\xampp\htdocs\salestrack` or `/Applications/XAMPP/htdocs/salestrack`
2. Start Apache and MySQL from XAMPP Control Panel
3. Access at `http://localhost/salestrack`

**LAMP (Linux):**
1. Place the project in `/var/www/html/salestrack`
2. Ensure MySQL is running: `sudo systemctl start mysql`
3. Access at `http://localhost/salestrack`

#### 4. Import Database Schema

**Option A: phpMyAdmin (GUI)**
1. Open `http://localhost/phpmyadmin`
2. Create a new database named `salestrack_local`
3. Select the database → **Import** tab
4. Upload `database/database.sql`
5. Click **Import**

**Option B: MySQL CLI**

```bash
mysql -u root -p salestrack_local < database/database.sql
```

**Option C: SQLite**
SQLite will auto-create `config/database.sqlite` on first run. No manual import needed.

#### 5. Access the Application

Open `http://localhost/salestrack` and log in with:


## 📁 Project Structure

```
salestrack/
├── admin/                      # Admin-only pages
│   ├── dashboard.php          # KPI metrics and sales overview
│   ├── reports.php            # Sales reports and analytics
│   ├── products.php           # Product list and management
│   ├── product-create.php     # Create new product
│   ├── product-edit.php       # Edit existing product
│   ├── staff.php              # Staff list and management
│   ├── staff-create.php       # Create new staff account
│   └── staff-edit.php         # Edit staff account
│
├── staff/                      # Staff-only pages
│   ├── dashboard.php          # Staff dashboard
│   ├── new-sale.php           # Record new sales
│   ├── sales.php              # View sales with filters
│   ├── sale-details.php       # Sale details and editing
│   ├── sales-history.php      # Extended sales history
│   └── products.php           # View product catalog
│
├── actions/                    # AJAX endpoints & form handlers
│   ├── save-product.php
│   ├── update-product.php
│   ├── delete-product.php
│   ├── save-sale.php
│   ├── cancel-sale.php
│   ├── save-staff.php
│   ├── update-staff.php
│   └── sales-trend.php
│
├── config/                     # Configuration
│   ├── database.php           # Main database config
│   ├── database.local.php     # Local credentials (create manually, gitignored)
│   └── database.sqlite        # SQLite DB file (auto-created)
│
├── database/                   # Database files
│   ├── database.sql           # Schema and seed data
│   └── migrations/            # Migration scripts
│
├── includes/                   # Shared PHP files
│   ├── header.php             # HTML head, navigation
│   ├── footer.php             # Page footer
│   ├── sidebar.php            # Dashboard sidebar
│   ├── auth.php               # Authentication logic
│   ├── admin-auth.php         # Admin role guard
│   ├── staff-auth.php         # Staff role guard
│   ├── functions.php          # Helper functions
│   ├── error-handler.php      # Error handling
│   └── csrf.php               # CSRF protection
│
├── lib/                        # Utility classes
│   ├── DatabaseQueryLogger.php
│   ├── HtmlSanitizer.php
│   ├── ProductImageService.php
│   ├── ProductQueries.php
│   ├── RoleGuard.php
│   ├── SaleLineItemCalculator.php
│   ├── SaleQueries.php
│   ├── UserQueries.php
│   └── UserRepository.php
│
├── assets/                     # Static assets
│   ├── images/                # Product images & UI assets
│   └── css/                   # Custom CSS (if needed)
│
├── uploads/                    # User uploads
│   └── products/              # Product image storage
│
├── logs/                       # Application logs
│   └── error.log              # Error log
│
├── index.php                   # Home/redirect
├── login.php                   # Login page
├── logout.php                  # Logout handler
├── .gitignore                  # Git ignore rules
├── .htaccess                   # Apache rewrites
└── README.md                   # This file
```

### Directory Explanations

| Directory | Purpose |
|-----------|---------|
| `admin/` | Pages restricted to admin users only |
| `staff/` | Pages restricted to staff users only |
| `actions/` | Backend form handlers and AJAX endpoints |
| `config/` | Database config; **create `database.local.php` manually** |
| `includes/` | Shared PHP files (header, footer, auth, helpers) |
| `lib/` | Reusable PHP classes for business logic |
| `database/` | SQL schema and migration files |

## 🔐 Security

✅ **Built-in protections:**
- Passwords hashed with bcrypt (`password_hash()` & `password_verify()`)
- SQL injection prevention via prepared statements (`PDO::prepare()`)
- CSRF tokens on all forms
- HTML entity encoding on output (`htmlspecialchars()`)
- Role-based access control (admin/staff pages)
- Detailed error logging with generic user messages
- Input validation and sanitization

⚠️ **Before going to production:**
- Change default admin & staff passwords immediately
- Create `config/database.local.php` with production database credentials
- Update `.htaccess` for your production server
- Set up HTTPS/SSL certificate
- Review error logs regularly
- Keep PHP and MySQL updated

## 📸 Screenshots

Screenshots coming soon! You can add them later to the `/assets/images/screenshots/` directory and link them here.

## 📄 License

**License:** TBD

Specify a license type (MIT, GPL-3.0, Apache-2.0, etc.) when publishing to GitHub.

## 🤝 Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch (`git checkout -b feature/YourFeature`)
3. Commit changes (`git commit -m 'Add YourFeature'`)
4. Push to branch (`git push origin feature/YourFeature`)
5. Open a Pull Request with detailed description

## ❓ Support & Issues

Found a bug or have a question? Please open an issue on GitHub with:
- A clear description of the problem
- Steps to reproduce (if applicable)
- Expected vs actual behavior
- Your environment (PHP version, OS, browser)

---

**SalesTrack** — Modern Sales & Inventory Management Platform  
Built with ❤️ for business efficiency  
Last Updated: September 4, 2026


- **Admin Account:** Username: `admin` | Password: `admin`
- **Staff Account:** Username: `staff` | Password: `staff`

⚠️ **Change these default passwords immediately in production!**

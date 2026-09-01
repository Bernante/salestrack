# DEPLOYMENT GUIDE: InfinityFree to Production

## Quick Overview
- **Host:** InfinityFree
- **Database:** if0_42783325_salestrack2
- **User:** if0_42783325
- **Method:** SFTP upload + phpMyAdmin database setup

---

## PRE-DEPLOYMENT CHECKLIST

### ✓ Code Preparation
- [ ] All local code tested and working
- [ ] `config/database.local.php` exists locally (NOT committed)
- [ ] `.gitignore` includes `database.local.php`
- [ ] No hardcoded credentials in committed files
- [ ] `uploads/products/` directory exists and is writable

### ✓ Database Preparation
- [ ] InfinityFree database created (if0_42783325_salestrack2)
- [ ] phpMyAdmin access confirmed
- [ ] Database SQL file ready (database/infinityfree-import.sql)

### ✓ InfinityFree Account Setup
- [ ] InfinityFree account active
- [ ] FTP/SFTP credentials obtained
- [ ] File Manager access confirmed
- [ ] PHP version 7.4+ confirmed

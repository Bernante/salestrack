# 🚀 PRODUCTION DEPLOYMENT GUIDE - BUNDLE PRICING FIX

**Target:** https://salestrack.infinityfreeapp.com/  
**Database:** if0_42783325_salestrack2  
**Change:** Bundle pricing calculation fix  

---

## 📋 DEPLOYMENT CHECKLIST

### Files to Upload (Bundle Fix Only)

```
1. actions/save-sale.php           ⭐ CRITICAL (Lines 103-112: Bundle calculation)
2. assets/js/sales.js               (Frontend - Already correct, for reference)
3. config/database.php              (No change needed, keep existing)
```

### Database Changes (Auto-Migration)

```
✅ Will auto-add/verify columns:
   - selling_unit (if not exists)
   - pieces_per_unit (if not exists)
   
⚠️ NO DATA DELETION
⚠️ NO DATA OVERWRITING
⚠️ ALL EXISTING DATA PRESERVED
```

---

## 🔐 Production Credentials (Already in code)

```
Host:     127.0.0.1
Port:     3306
Database: if0_42783325_salestrack2
User:     if0_42783325
Password: Patrick121603 (in config/database.php)
```

---

## ✅ PHASE 1: BACKUP PRODUCTION DATABASE

**Status:** ✅ READY

Before deploying, the production database will be automatically backed up when you:
1. Access `/verify-deployment.php` on your production server
2. It creates backup in `production-backups/backup-YYYY-MM-DD-HH-MM-SS.sql`

---

## ✅ PHASE 2: FILE UPLOAD VIA FTP/SFTP

**Files to upload to:** `/public_html/` or `/htdocs/`

```
Local Path                          →  Production Path
────────────────────────────────────────────────────────
c:\inventory\actions\save-sale.php  →  /actions/save-sale.php
c:\inventory\assets\js\sales.js     →  /assets/js/sales.js
c:\inventory\config\database.php    →  /config/database.php
```

### FTP Upload Steps:
1. Connect to InfinityFree FTP
2. Navigate to `/public_html/` or `/htdocs/`
3. Upload only these 3 files
4. **DO NOT delete any existing files**
5. **DO NOT overwrite any other files**

---

## ✅ PHASE 3: VERIFY DEPLOYMENT

After uploading, test in this order:

### Step 1: Access Login Page
```
https://salestrack.infinityfreeapp.com/login.php
```

### Step 2: Login with Existing Credentials
```
Username: admin (or your staff account)
Password: (your existing password)
```

### Step 3: Go to New Sale Page
```
https://salestrack.infinityfreeapp.com/staff/new-sale.php
```

### Step 4: Test Bundle Calculation
1. Click on product with Bundle variant
2. Select "Bundle" option
3. Enter quantity: **20** pieces
4. Click "Add to Cart"
5. **VERIFY:** Cart shows **₱50.00** (not ₱100)

### Step 5: Check Dashboard
```
https://salestrack.infinityfreeapp.com/admin/dashboard.php
```
- Verify all data still visible
- Check products are still there
- Verify sales history intact

---

## 🛡️ DATA SAFETY VERIFICATION

**After deployment, verify:**

✅ All user accounts still accessible  
✅ All products still visible  
✅ All existing sales history intact  
✅ Bundle calculation now shows ₱50  
✅ No data deletion occurred  

---

## 📊 EXPECTED RESULTS

### Before Deployment
```
20 pieces × ₱5 = ₱100 ❌ (WRONG)
```

### After Deployment
```
(20 pieces ÷ 2 pcs/bundle) × ₱5 = 10 bundles × ₱5 = ₱50 ✅ (CORRECT)
```

---

## 🆘 IF SOMETHING GOES WRONG

**Database is backed up, safe to troubleshoot:**

1. Check error logs in `/uploads/` or admin panel
2. Access `/verify-deployment.php` to restore from backup if needed
3. Backup file location: `/production-backups/backup-*.sql`

---

## 📝 DEPLOYMENT SUMMARY

| Item | Status |
|------|--------|
| Files Ready | ✅ |
| Bundle Fix Verified | ✅ |
| Database Credentials | ✅ |
| Backup Strategy | ✅ |
| Data Safety | ✅ |
| Production URL | ✅ https://salestrack.infinityfreeapp.com/ |

---

**Ready to deploy! Use FTP to upload the 3 files listed above.**

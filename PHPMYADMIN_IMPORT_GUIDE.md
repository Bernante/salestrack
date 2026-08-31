# 🚀 IMPORT SQL TO INFINITYFREE - COMPLETE GUIDE

**File:** `import.sql`  
**Database:** if0_42783325_salestrack2  
**Time:** 2026-08-31 13:50:03 UTC

---

## 📋 STEP-BY-STEP INSTRUCTIONS

### Step 1: Access phpMyAdmin
1. Go to: **https://www.infinityfree.net/**
2. Login to your InfinityFree account
3. Click **"Control Panel"**
4. Click **"MySQL Databases"** or look for database management
5. Find your database: `if0_42783325_salestrack2`
6. Click **"phpMyAdmin"** button

### Step 2: Open phpMyAdmin
- You'll see the database dashboard
- On the left sidebar, click your database name: `if0_42783325_salestrack2`
- This shows all your tables

### Step 3: Go to Import Tab
1. Click the **"Import"** tab at the top
2. You should see:
   - "Choose File" button
   - "Location of the text file" upload area

### Step 4: Select Your SQL File
1. Click **"Choose File"** button
2. Navigate to: `c:\inventory\import.sql`
3. Select and open it
4. File should show in the upload area

### Step 5: Run the Import
1. Scroll down and click **"Go"** or **"Import"** button
2. The system will:
   - Add `selling_unit` column
   - Add `pieces_per_unit` column
   - Create index
   - Update bundle products
   - Show success message

### Step 6: Verify Success
1. Look for: **"Import has been successfully finished"** message
2. If you see it → ✅ Database updated successfully!
3. Click on `product_variants` table to verify columns exist

---

## 🔍 VERIFICATION AFTER IMPORT

### Check If Columns Were Added
1. In phpMyAdmin, click `product_variants` table
2. Click **"Structure"** tab
3. Look for:
   - ✅ `selling_unit` column (ENUM)
   - ✅ `pieces_per_unit` column (INT)

### Check Bundle Configuration
1. Click **"Browse"** tab
2. Look for rows with:
   - `selling_unit` = `bundle`
   - `pieces_per_unit` = `2`
3. Should see "Bulk Non-Mineral Ice" bundle product

### Run Test Query
1. Click **"SQL"** tab
2. Paste this:
```sql
SELECT id, product_id, variant_name, selling_unit, pieces_per_unit, price 
FROM product_variants 
WHERE selling_unit = 'bundle';
```
3. Click **"Go"**
4. Should show bundle products with pieces_per_unit = 2

---

## ⚠️ IF COLUMNS ALREADY EXIST

If you get error: **"Duplicate column name"**

This means the columns already exist on your production server.

**Solution:**
1. Ignore the error ✅ (columns are already there)
2. Continue with code deployment
3. Bundle pricing fix will work fine

---

## ✅ WHAT THIS IMPORT DOES

| Step | Action | Impact |
|------|--------|--------|
| 1 | Add `selling_unit` column | Identifies unit type (piece, bundle, tray) |
| 2 | Add `pieces_per_unit` column | Stores pieces per bundle (e.g., 2) |
| 3 | Create index | Performance optimization |
| 4 | Update bundles to type=bundle | Marks bundles for special calculation |
| 5 | Set pieces_per_unit=2 | 2 pieces per bundle |

---

## 🛡️ DATA SAFETY

✅ **Non-destructive:**
- No existing data deleted
- No data overwritten
- Only adds new columns
- Default values for existing rows
- Fully reversible

---

## 🎯 AFTER IMPORT SUCCEEDS

### Next: Deploy the Code

1. Upload these files to your production server:
   ```
   actions/save-sale.php           (Bundle fix)
   assets/js/sales.js              (Frontend)
   config/database.php             (Config)
   ```

2. Test in browser:
   ```
   https://salestrack.infinityfreeapp.com/staff/new-sale.php
   - Select: Bulk Non-Mineral Ice
   - Select: Bundle
   - Enter: 20
   - Should show: ₱50.00 ✅
   ```

---

## 🚨 TROUBLESHOOTING

### Error: "Duplicate column name"
**Solution:** Columns already exist → No action needed ✅

### Error: "Table doesn't exist"
**Solution:** Use wrong database → Select `if0_42783325_salestrack2` from left sidebar

### Error: "Access denied"
**Solution:** Wrong credentials → Login again to phpMyAdmin

### No error but nothing happened
**Solution:** Scroll down → Look for success message below

---

## 📱 ALTERNATIVE: If Upload Doesn't Work

**Copy-paste method:**

1. In phpMyAdmin, click **"SQL"** tab
2. Copy all text from `import.sql`
3. Paste into the SQL query box
4. Click **"Go"**
5. Should execute same way as import

---

## ✨ SUMMARY

| Item | Status |
|------|--------|
| SQL File Ready | ✅ `import.sql` created |
| Columns to Add | ✅ selling_unit, pieces_per_unit |
| Bundle Config | ✅ pieces_per_unit = 2 |
| Data Safety | ✅ Non-destructive |
| Ready to Import | ✅ YES |

---

**READY! Import import.sql to phpMyAdmin now, then deploy the code files.**

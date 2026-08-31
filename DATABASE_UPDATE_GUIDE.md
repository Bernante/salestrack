# HOW TO UPDATE DATABASE ON INFINITYFREE — Complete Guide

## Your Current Workflow

**What you're doing (Code Updates):**
```bash
git add .
git commit -m "Updated code"
git push origin main
```

This updates your **PHP code files** in htdocs. ✓ Correct!

**But Database needs separate updates** when schema changes.

---

## WHAT NEEDS DATABASE UPDATE?

**Database changes needed:**
- ✓ Adding a new column
- ✓ Removing a column
- ✓ Changing column type
- ✓ Adding a new table

**NO database update needed:**
- ✓ Changing PHP code
- ✓ Changing HTML/CSS/JavaScript
- ✓ Bug fixes

---

## METHOD 1: UPDATE VIA PHPMYADMIN (Easiest)

### Access phpMyAdmin

1. **Login to InfinityFree**
   - https://infinityfree.net
   - Sign In with email/password

2. **Go to cPanel**
   - Click Manage on your domain
   - Find **phpMyAdmin** icon
   - Click it

3. **You're in phpMyAdmin**
   - Left side: Your databases
   - Click: `if0_42783325_salestrack2`

---

### Check If Column Exists

1. **Click the table** (e.g., `sales`)
2. **Click Structure tab**
3. **Look at columns list**
4. **If you see the column → Already exists ✓**
5. **If missing → Need to add it**

---

### Add New Column

**Example: Add `sale_date` to `sales` table**

1. **Open the `sales` table**
2. **Click Structure tab**
3. **Click "Add 1 Column"** (bottom)
4. **Fill form:**
   - Column name: `sale_date`
   - Type: `DATE`
   - Default: `CURRENT_DATE`
5. **Click Save**

**Done!** Column is added.

---

### Remove Column

1. **Click Structure tab**
2. **Find the column**
3. **Click red X (Delete)**
4. **Confirm**

**Done!** Column removed.

---

### Rename Column

1. **Click the pencil icon** next to column
2. **Change the name**
3. **Click Save**

**Done!** Column renamed.

---

## METHOD 2: RUN SQL SCRIPT

### In phpMyAdmin

1. **Click your database**
2. **Click SQL tab** (top)
3. **Paste SQL commands:**

```sql
ALTER TABLE sales ADD COLUMN sale_date DATE NOT NULL DEFAULT CURDATE() AFTER user_id;
ALTER TABLE product_variants ADD COLUMN quantity INT NOT NULL DEFAULT 1;
```

4. **Click Go**

**Done!** Commands executed.

---

## COMMON SQL COMMANDS

**Add Column:**
```sql
ALTER TABLE table_name ADD COLUMN column_name DATA_TYPE;
```

**Remove Column:**
```sql
ALTER TABLE table_name DROP COLUMN column_name;
```

**Rename Column:**
```sql
ALTER TABLE table_name CHANGE old_name new_name DATA_TYPE;
```

**Change Type:**
```sql
ALTER TABLE table_name MODIFY COLUMN column_name NEW_TYPE;
```

---

## YOUR CURRENT STATUS

**What exists on InfinityFree:**
- ✓ All tables created
- ✓ All columns exist
- ✓ Database is ready
- ✓ No updates needed now

**What your code changes do:**
- Recent Egg Size Breakdown removal = Code only (no DB change)
- Quantity management fix = Code only (DB columns already exist)
- Admin dashboard update = Code only (DB columns already exist)

---

## COMPLETE WORKFLOW

**When you add features:**

1. **Make code changes** (local computer)
2. **Make database changes** (if needed)
3. **Test locally**
4. **Push code:** `git push origin main`
5. **Update database:** (phpMyAdmin if needed)
6. **Test online:** https://yourdomain.com

---

## BACKUP DATABASE (Important!)

**Before big changes:**

1. In phpMyAdmin
2. Click your database
3. Click **Export** tab
4. Click **Go**
5. File downloads
6. Save somewhere safe

If something breaks, restore from backup.

---

## SUMMARY

**Code updates:** Use git push ✓
**Database updates:** Use phpMyAdmin
**Both needed:** Do both steps
**Current status:** Ready to deploy! 🚀

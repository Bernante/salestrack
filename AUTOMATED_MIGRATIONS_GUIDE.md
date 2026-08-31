# AUTOMATED DATABASE MIGRATION — Complete Guide

## The New Workflow (No Manual phpMyAdmin!)

### Before (Manual - Old Way)
```bash
git push origin main              # Code updates
# Then manually:
# 1. Login to phpMyAdmin
# 2. Import SQL file
# 3. Run commands
# 4. Verify
```

### After (Automated - New Way)
```bash
# Step 1: Create migration file
echo "ALTER TABLE sales ADD COLUMN notes TEXT;" > database/migrations/2026-08-31-add-notes.sql

# Step 2: Push to GitHub
git add .
git commit -m "Add migration"
git push origin main

# Step 3: Visit URL (automatic!)
https://yourdomain.com/database/migrate.php

# Done! ✓
```

---

## STEP-BY-STEP USAGE

### Step 1: Create Migration File Locally

**Create folder:** `c:\inventory\database\migrations\`

**Create file:** `c:\inventory\database\migrations\2026-08-31-add-notes.sql`

**Add your SQL:**
```sql
ALTER TABLE sales ADD COLUMN notes TEXT NULL AFTER total_amount;
```

**File naming:**
- Format: `YYYY-MM-DD-description.sql`
- Example: `2026-09-15-fix-quantities.sql`
- Must start with date!

---

### Step 2: Test Locally (Optional)

```bash
cd c:\inventory
php database/migrate.php
```

Output:
```
✓ EXECUTED: 2026-08-31-add-notes.sql
✓ All migrations completed successfully!
```

---

### Step 3: Push to GitHub

```bash
git add .
git commit -m "Add migration: add notes column"
git push origin main
```

---

### Step 4: Run Online

**Visit:**
```
https://yourdomain.com/database/migrate.php
```

**Result:**
```
✓ EXECUTED: 2026-08-31-add-notes.sql
✓ All migrations completed successfully!
```

**Done! Database updated automatically!** ✓

---

## EXAMPLES

### Example 1: Add Column
```sql
-- File: 2026-09-01-add-priority.sql
ALTER TABLE sales ADD COLUMN priority VARCHAR(20) DEFAULT 'normal';
```

### Example 2: Multiple Changes
```sql
-- File: 2026-09-02-schema-updates.sql
ALTER TABLE sales ADD COLUMN priority VARCHAR(20);
ALTER TABLE sales ADD COLUMN tags VARCHAR(255);
ALTER TABLE sales ADD INDEX idx_sale_date (sale_date);
```

### Example 3: Remove Column
```sql
-- File: 2026-09-03-remove-old.sql
ALTER TABLE sales DROP COLUMN old_column;
```

---

## SMART FEATURES

✓ **Never Runs Twice** - Tracks which migrations executed
✓ **Safe on Errors** - Marks failed migrations, continues with others
✓ **Auto Tracking** - Creates `_migrations` table
✓ **Easy Verify** - Visit URL anytime to check status

---

## FOLDER STRUCTURE

```
database/
├── migrate.php              (Master runner)
├── migrations/              (Your migration files)
│   ├── 2026-08-31-add-notes.sql
│   ├── 2026-09-01-fix-qty.sql
│   └── ...
├── database.sql
└── import.sql
```

---

## COMMON SQL

### Add Column
```sql
ALTER TABLE table_name ADD COLUMN column_name DATA_TYPE;
```

### Remove Column
```sql
ALTER TABLE table_name DROP COLUMN column_name;
```

### Rename Column
```sql
ALTER TABLE table_name CHANGE old_name new_name DATA_TYPE;
```

### Change Type
```sql
ALTER TABLE table_name MODIFY COLUMN column_name NEW_TYPE;
```

### Add Index
```sql
ALTER TABLE table_name ADD INDEX idx_name (column_name);
```

---

## YOUR WORKFLOW

1. **Make code changes** (PHP files)
2. **Create migration file** (database/migrations/YYYY-MM-DD-*.sql)
3. **git push origin main**
4. **Visit:** https://yourdomain.com/database/migrate.php
5. **Done!** ✓

---

## QUICK START

```bash
# 1. Create migrations folder
mkdir c:\inventory\database\migrations

# 2. Create first migration (optional)
echo "-- Your SQL here" > c:\inventory\database\migrations\2026-08-31-test.sql

# 3. Push to GitHub
git add .
git commit -m "Add migration system"
git push origin main

# 4. Test online
Visit: https://yourdomain.com/database/migrate.php

# Done! Automated DB updates ready! 🎉
```

---

**No more manual phpMyAdmin imports needed!**

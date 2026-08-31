# COMPLETE COMMANDS — With Your Actual Domain

## Your Domain
```
salestrack.infinityfreeapp.com
```

---

## COMMAND 1: Create Migrations Folder (One Time Only)

```bash
mkdir c:\inventory\database\migrations
```

---

## COMMAND 2: Create Migration File

Replace `YYYY-MM-DD` with actual date and `description` with what you're doing.

### Example: Add notes column

```bash
echo ALTER TABLE sales ADD COLUMN notes TEXT; > c:\inventory\database\migrations\2026-08-31-add-notes.sql
```

### Example: Add priority column

```bash
echo ALTER TABLE sales ADD COLUMN priority VARCHAR(20) DEFAULT 'normal'; > c:\inventory\database\migrations\2026-08-31-add-priority.sql
```

### Example: Multiple changes

```bash
(
echo ALTER TABLE sales ADD COLUMN priority VARCHAR(20) DEFAULT 'normal' AFTER total_amount;
echo ALTER TABLE sales ADD COLUMN tags VARCHAR(255);
echo ALTER TABLE sales ADD INDEX idx_priority (priority);
) > c:\inventory\database\migrations\2026-08-31-schema-updates.sql
```

---

## COMMAND 3: Push to GitHub

```bash
cd c:\inventory
git add .
git commit -m "Add migration: your description here"
git push origin main
```

---

## COMMAND 4: Run Migrations Online (Paste in Browser)

```
https://salestrack.infinityfreeapp.com/database/migrate.php
```

---

## COMPLETE WORKFLOW EXAMPLE

**Scenario: You want to add a "notes" column to sales table**

### Step 1: Create migration file
```bash
echo ALTER TABLE sales ADD COLUMN notes TEXT NULL AFTER total_amount; > c:\inventory\database\migrations\2026-08-31-add-notes.sql
```

### Step 2: Push to GitHub
```bash
cd c:\inventory
git add .
git commit -m "Add notes column to sales table"
git push origin main
```

### Step 3: Run migrations online
```
Paste this in your browser address bar:
https://salestrack.infinityfreeapp.com/database/migrate.php

Press Enter
```

### Result
You'll see:
```
=== AUTOMATED DATABASE MIGRATION SYSTEM ===

✓ EXECUTED: 2026-08-31-add-notes.sql

=== MIGRATION SUMMARY ===
Executed: 1
Skipped:  0
Failed:   0

✓ All migrations completed successfully!
```

---

## Test Locally (Optional)

```bash
cd c:\inventory
php database/migrate.php
```

---

## Summary

**Always 3 commands:**

1. Create `.sql` file in `c:\inventory\database\migrations\YYYY-MM-DD-name.sql`
2. `git push origin main`
3. Visit: `https://salestrack.infinityfreeapp.com/database/migrate.php`

Done!

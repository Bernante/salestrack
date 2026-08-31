# AUTOMATED DATABASE UPDATE SYSTEM — Complete Setup

## ✅ YOU NOW HAVE PROFESSIONAL AUTOMATION

No more manual phpMyAdmin imports needed!

---

## How It Works

**Old Way (Manual):**
1. Make code changes
2. Git push
3. Manually login to phpMyAdmin
4. Import SQL file
5. Run commands
6. Verify
= Slow and error-prone

**New Way (Automated):**
1. Make code changes
2. Create migration `.sql` file
3. Git push (updates code AND migration)
4. Visit one URL
5. Done! ✓
= Fast and reliable

---

## Your 3-Step Deployment Process

### EVERY TIME YOU UPDATE:

#### Step 1: Make Changes (Local)
```bash
# Edit PHP files
# If database changes, create:
# c:\inventory\database\migrations\YYYY-MM-DD-description.sql
```

#### Step 2: Push to GitHub
```bash
git add .
git commit -m "Your change"
git push origin main
```

#### Step 3: Update Database (If Schema Changed)
```
Visit: https://yourdomain.com/database/migrate.php
```

---

## Real Example: Add Priority Field

### Step 1: Create Files

**File 1:** Edit `staff/new-sale.php` (add priority dropdown)

**File 2:** Create `database/migrations/2026-09-01-add-priority.sql`
```sql
ALTER TABLE sales ADD COLUMN priority VARCHAR(20) DEFAULT 'normal' AFTER total_amount;
```

### Step 2: Push
```bash
git add .
git commit -m "Add priority field"
git push origin main
```

### Step 3: Run Online
```
Visit: https://yourdomain.com/database/migrate.php
```

**Result:**
```
✓ EXECUTED: 2026-09-01-add-priority.sql
✓ All migrations completed successfully!
```

---

## Migration File Rules

### Format: `YYYY-MM-DD-description.sql`

✓ Correct:
- `2026-09-01-add-priority.sql`
- `2026-09-02-fix-quantities.sql`

✗ Wrong:
- `add-priority.sql` (no date!)
- `2026_09_01.sql` (underscores!)

---

## Common SQL Commands

### Add Column
```sql
ALTER TABLE sales ADD COLUMN priority VARCHAR(20) DEFAULT 'normal';
```

### Remove Column
```sql
ALTER TABLE sales DROP COLUMN priority;
```

### Rename Column
```sql
ALTER TABLE sales CHANGE priority priority_level VARCHAR(20);
```

### Add Index (Speed)
```sql
ALTER TABLE sales ADD INDEX idx_priority (priority);
```

### Multiple Changes
```sql
ALTER TABLE sales ADD COLUMN priority VARCHAR(20);
ALTER TABLE sales ADD COLUMN tags VARCHAR(255);
ALTER TABLE sales ADD INDEX idx_priority (priority);
```

---

## Smart Features

✓ **Never Runs Twice** - Automatic tracking prevents duplicates
✓ **Error Handling** - Failed migrations marked, continues with others
✓ **Auto Tracking** - `_migrations` table records everything
✓ **Works Offline** - Test locally: `php database/migrate.php`

---

## Your Folder Structure

```
database/
├── migrate.php                    ← Master runner
├── migrations/                    ← PUT YOUR .sql FILES HERE
│   ├── 2026-09-01-add-priority.sql
│   └── ...
├── database.sql
└── import.sql
```

---

## Testing Your System

### Test Local
```bash
php c:\inventory\database\migrate.php
```

### Test Online
```
Visit: https://yourdomain.com/database/migrate.php
```

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| "No pending migrations" | Normal! Already executed |
| Migration failed | Check SQL syntax |
| Not running | Check filename format: `YYYY-MM-DD-*.sql` |

---

## Backup Before Big Changes

**In phpMyAdmin (InfinityFree):**
1. Click your database
2. Click Export tab
3. Click Go
4. Save file

If something breaks, restore from backup.

---

## You're All Set! 🎉

✅ Automated code deployment (git push)
✅ Automated database migrations (visit URL)
✅ Version control (GitHub)
✅ Migration tracking (auto table)
✅ Error handling (safe)

---

## Next Update You Make

```bash
# 1. Make changes
# 2. Create migration file (if DB change)
# 3. git push origin main
# 4. Visit: https://yourdomain.com/database/migrate.php
# Done! ✓
```

**No more manual phpMyAdmin imports!** 🚀

# QUICK REFERENCE — Your Deployment System

## 🚀 Three Simple Commands

### Command 1: Push Code Changes
```bash
git add .
git commit -m "Your message"
git push origin main
```
**Result:** Code updates automatically in htdocs ✓

---

### Command 2: Create Migration (If Database Changes)
```bash
# Create file: c:\inventory\database\migrations\2026-09-01-description.sql
# Add SQL inside the file
# Then run Command 1 above
```

---

### Command 3: Run Migrations Online
```bash
# Visit in browser:
https://yourdomain.com/database/migrate.php
```
**Result:** Database updates automatically ✓

---

## Workflow Summary

```
┌─────────────────────────────────────┐
│ 1. Make Code Changes (Local)        │
│    - Edit PHP files                 │
│    - Create migration .sql (if DB)  │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│ 2. Push to GitHub                   │
│    git add .                        │
│    git commit -m "message"          │
│    git push origin main             │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│ 3. Automatic Updates                │
│    Code: Auto in htdocs ✓           │
│    DB: Visit migrate.php URL ✓      │
└─────────────────────────────────────┘
           ↓
┌─────────────────────────────────────┐
│ 4. Test Online                      │
│    Visit: https://yourdomain.com    │
└─────────────────────────────────────┘
```

---

## Examples

### Example 1: Fix Code Only
```bash
# Edit: admin/dashboard.php
git add .
git commit -m "Fix dashboard layout"
git push origin main
# Done! Automatic ✓
```

### Example 2: Code + Database
```bash
# Edit: staff/new-sale.php
# Create: database/migrations/2026-09-01-add-priority.sql
# SQL inside: ALTER TABLE sales ADD COLUMN priority VARCHAR(20);

git add .
git commit -m "Add priority field"
git push origin main

# Then visit:
https://yourdomain.com/database/migrate.php
# Done! Automatic ✓
```

---

## Important Files

| File | Location | Purpose |
|------|----------|---------|
| migrate.php | database/ | Runs all migrations |
| migrations/ | database/ | Store your .sql files here |
| .git/ | root | Tracks changes for git |

---

## Migration File Format

**Create:** `database/migrations/YYYY-MM-DD-description.sql`

**Examples:**
- `2026-09-01-add-priority.sql`
- `2026-09-02-fix-quantities.sql`
- `2026-09-03-add-discount.sql`

**Inside file, add SQL:**
```sql
ALTER TABLE sales ADD COLUMN priority VARCHAR(20);
ALTER TABLE sales ADD INDEX idx_priority (priority);
```

---

## Backup Before Big Changes

```bash
# 1. Go to InfinityFree cPanel
# 2. phpMyAdmin
# 3. Click your database
# 4. Click Export
# 5. Click Go
# 6. Save file
```

If something breaks, restore from backup.

---

## Troubleshooting

| Problem | Solution |
|---------|----------|
| Migrations not running | Check filename format: `YYYY-MM-DD-*.sql` |
| Migration failed | Check SQL syntax in the file |
| No pending migrations | Normal! Already all executed |
| Status shows skipped | Migration already ran, won't run again |

---

## Remember

✓ **Code changes** → `git push origin main`
✓ **Database changes** → Create `.sql` file + `git push` + visit URL
✓ **Both** → Do both steps
✓ **Never manual again** → All automated!

---

## Your Folders

```
inventory/
├── database/
│   ├── migrate.php           ← System runner
│   ├── migrations/           ← Put .sql files here
│   │   ├── (your files)
│   │   └── ...
├── admin/
├── staff/
├── actions/
└── .git/
```

---

## Status

✅ Automated code deployment working
✅ Automated database migrations ready
✅ Version control active
✅ Migration tracking enabled
✅ All systems operational

**Ready to deploy updates!** 🚀

---

## Next Update You Make

1. **Make changes** (code + migrations)
2. **`git push origin main`**
3. **Visit URL** (if DB changed)
4. **Done!**

No more manual work!

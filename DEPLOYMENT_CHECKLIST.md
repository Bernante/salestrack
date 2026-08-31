# SalesTrack GitHub Actions Deployment Checklist

## Current Status
- ✅ GitHub repository: https://github.com/Bernante/salestrack
- ✅ GitHub Actions workflow: `.github/workflows/deploy.yml`
- ✅ Application code: Ready for deployment
- ⏳ **PENDING**: GitHub Secrets configuration

---

## Step 1: Configure GitHub Secrets (CRITICAL)

GitHub Secrets are **encrypted environment variables** used by the workflow. Without them, deployment cannot proceed.

### How to Add GitHub Secrets:

1. Go to: **https://github.com/Bernante/salestrack/settings/secrets/actions**
2. Click **"New repository secret"** for each of the following:

### Secret 1: FTP_SERVER
- **Name**: `FTP_SERVER`
- **Value**: Your InfinityFree FTP host (from InfinityFree Control Panel)
  - Example: `ftp.infinityfree.net` or similar
  - Check InfinityFree > Account > FTP Accounts > FTP Server

### Secret 2: FTP_USERNAME
- **Name**: `FTP_USERNAME`
- **Value**: Your InfinityFree FTP username
  - Usually in format: `if0_XXXXX` (your account number)
  - Check InfinityFree > Account > FTP Accounts > FTP Username

### Secret 3: FTP_PASSWORD
- **Name**: `FTP_PASSWORD`
- **Value**: Your InfinityFree FTP password
  - The password you set or the one provided by InfinityFree

---

## Step 2: Find Your InfinityFree FTP Credentials

1. Log in to **InfinityFree Control Panel**: https://panel.infinityfree.net/
2. Navigate to: **Account** → **FTP Accounts**
3. You should see a table with:
   - FTP Server
   - FTP Username
   - FTP Port
   - Current Path (should be `/htdocs/` or similar)

### Example FTP Account Information:
```
FTP Server: ftp.infinityfree.net
FTP Username: if0_42783325 (or similar)
FTP Port: 21
Current Path: /salestrack.infinityfreeapp.com/htdocs/
```

---

## Step 3: Test the Workflow Manually

After adding the secrets:

1. Go to: **https://github.com/Bernante/salestrack/actions**
2. Click on the **"Deploy SalesTrack to Live Server"** workflow
3. Click **"Run workflow"** button
4. Select **Branch**: `main`
5. Click **"Run workflow"**

The workflow will start running. You should see:
- ✅ Checkout Repository
- ✅ Verify Secrets Configuration
- ✅ Display Deployment Information
- ✅ Sync files via FTP
- ✅ Deployment Complete

---

## Step 4: Verify Deployment Was Successful

After the workflow completes:

1. Visit: **https://salestrack.infinityfreeapp.com**
2. You should see the SalesTrack login page
3. Log in with default credentials:
   - Username: `admin`
   - Password: `admin123`

### If You See an Error:

**Database Connection Error?**
- The database credentials may be incorrect
- Check `config/database.php` for the correct InfinityFree database details

**File Not Found?**
- The files may not have uploaded correctly
- Check the FTP path in the workflow (should be: `/salestrack.infinityfreeapp.com/htdocs/`)

---

## Step 5: Automatic Deployments

Once secrets are configured, the workflow will automatically run on every push to the `main` branch.

### To Deploy Changes:
```bash
git add .
git commit -m "Your changes"
git push origin main
```

The workflow will automatically:
1. Checkout your code
2. Verify secrets are configured
3. Sync all files via FTP to your live server
4. Complete within 1-2 minutes

---

## Troubleshooting

### Workflow Still Not Running?
- Check that secrets are **exactly** named: `FTP_SERVER`, `FTP_USERNAME`, `FTP_PASSWORD`
- Verify the values don't have extra spaces or quotes

### FTP Upload Failed?
- Check FTP credentials in GitHub Secrets are correct
- Verify FTP account is active in InfinityFree Control Panel
- Check that the server path is correct: `/salestrack.infinityfreeapp.com/htdocs/`

### Application Not Loading?
- Database connection error: Update database credentials in `config/database.php`
- Check `salestrack.infinityfreeapp.com/db-setup.php` for database setup

---

## Important Notes

⚠️ **Security Reminder:**
- GitHub Secrets are encrypted and not visible in logs
- Database passwords should ideally be stored as secrets, not hardcoded
- Never commit `.env` files or credential files to git

✅ **Next Steps:**
1. Add the three GitHub Secrets as described above
2. Test the workflow manually
3. Verify your live site is working
4. Make changes and push to `main` for automatic deployment


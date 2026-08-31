# DEPLOYMENT GUIDE — Upload Changes to InfinityFree

## Your Hosting Setup
- **Host:** InfinityFree
- **Database:** MySQL (if0_42783325_salestrack2)
- **Database User:** if0_42783325
- **Access:** cPanel / File Manager

---

## QUICK SUMMARY

You have **3 features to deploy:**

1. ✅ **Date Selection** (sales can be backdated)
   - Already done: `staff/new-sale.php`, `actions/save-sale.php`
   - Files: Database migrations, staff sales history

2. ✅ **Quantity Management Fix** (6 files)
   - `admin/dashboard.php`
   - `admin/product-create.php`
   - `staff/product-create.php`
   - `staff/products.php`
   - `actions/save-product.php`
   - `actions/update-product.php`

3. ✅ **Admin Dashboard Update** (1 file)
   - `admin/dashboard.php` (already in #2)

**Total Files to Upload: 6 modified files**

---

## STEP 1: Prepare Your Files

On your computer (c:\inventory), you have all files ready:

```
admin/dashboard.php ..................... MODIFIED
admin/product-create.php ................ MODIFIED
staff/product-create.php ................ MODIFIED
staff/products.php ...................... MODIFIED
actions/save-product.php ................ MODIFIED
actions/update-product.php .............. MODIFIED
```

Keep these 6 files open and ready to upload.

---

## STEP 2: Login to InfinityFree

1. Go to https://infinityfree.net
2. Click "Sign In" or "Login"
3. Enter your email and password
4. You should see your domain/account

---

## STEP 3: Upload Using File Manager (Easiest)

### **Method A: Direct File Manager**

1. **Go to cPanel**
   - From InfinityFree dashboard
   - Click "Manage" next to your domain
   - Or click "cPanel" button

2. **Open File Manager**
   - Find "File Manager" icon
   - Click it
   - You'll see your files/folders

3. **Navigate to Your App Directory**
   - You should see folders like: `admin`, `staff`, `actions`, `config`, etc.
   - If not, double-click to find them

4. **Upload Modified Files**
   - Right-click in the folder
   - Click "Upload"
   - Select file from your computer
   - Wait for upload to complete
   - Do this for each of the 6 files

---

## STEP 4: Alternative - Upload Using FTP

If File Manager is too slow, use FTP:

### **Using FileZilla (Free)**

1. **Download FileZilla**
   - https://filezilla-project.org/download.php
   - Install it

2. **Get FTP Credentials**
   - Go to InfinityFree → cPanel
   - Look for "FTP Accounts" section
   - Get your FTP username/password
   - Usually: `ftp_username` and a password

3. **Connect in FileZilla**
   - Open FileZilla
   - File → Site Manager
   - New Site
   - Enter:
     - Protocol: FTP
     - Host: `ftp.yourdomain.com`
     - Port: 21
     - Username: your FTP username
     - Password: your FTP password
   - Click Connect

4. **Upload Files**
   - Left side = Your computer
   - Right side = Server
   - Navigate to each folder on right side
   - Drag files from left to right
   - Or right-click → Upload

---

## STEP 5: Verify Files Are Uploaded

1. **Go to File Manager**
2. **Check these paths exist:**
   - `/admin/dashboard.php` ✓
   - `/admin/product-create.php` ✓
   - `/staff/product-create.php` ✓
   - `/staff/products.php` ✓
   - `/actions/save-product.php` ✓
   - `/actions/update-product.php` ✓

3. **If not there, re-upload them**

---

## STEP 6: Test Online

Open your website and test:

1. **Admin Dashboard Test**
   - Login as Admin
   - Go to Dashboard
   - Look at "Recent Transactions" section
   - ✓ Column header should say "Sale Date" (not "Time")
   - ✓ Dates show as: "August 31, 2026"

2. **Product Creation Test**
   - Admin → Products & Prices → Add Product
   - Enter: Name="Test", Variant="Test", Qty=5, Price=100
   - Save
   - Go back to Products
   - ✓ Should display "5 pcs"

3. **Product Edit Test**
   - Edit the product
   - Change Qty to 25
   - Save & Refresh
   - ✓ Should show "25 pcs"

4. **Staff Test**
   - Login as Staff
   - Products & Prices → Add Product
   - Enter Qty=10
   - ✓ Should work same as Admin

5. **Sale Test**
   - Staff → Record New Sale
   - Add product
   - ✓ Quantity should load correctly
   - Complete sale
   - ✓ No errors

---

## IF SOMETHING BREAKS

### **Check Error Log**
- cPanel → Error Logs
- Look for PHP errors
- Screenshot the error
- This tells you what went wrong

### **Restore Previous Version**
- In File Manager, right-click file
- Look for "Restore" or backup option
- Or re-download from your computer and re-upload

### **Clear Browser Cache**
- Press: Ctrl + Shift + Delete
- Select "All time"
- Click "Clear"
- Then refresh website

---

## SUMMARY

✅ 6 files to upload
✅ Use File Manager or FTP
✅ Test each workflow
✅ Done!

Your app now has:
- Staff can record backdated sales
- Quantity field works correctly
- Admin sees sale dates on dashboard

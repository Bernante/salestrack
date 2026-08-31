# PRODUCTION DEPLOYMENT - BUNDLE FIX

**URL:** https://salestrack.infinityfreeapp.com/  
**Database:** if0_42783325_salestrack2

---

## 3 FILES TO UPLOAD VIA FTP

```
1. c:\inventory\actions\save-sale.php
   → Upload to: /htdocs/actions/save-sale.php
   
2. c:\inventory\assets\js\sales.js
   → Upload to: /htdocs/assets/js/sales.js
   
3. c:\inventory\config\database.php
   → Upload to: /htdocs/config/database.php
```

---

## WHAT'S FIXED

**Bundle calculation now correct:**
- 20 pieces ÷ 2 pcs/bundle = 10 bundles
- 10 bundles × ₱5 = **₱50.00** ✅ (was ₱100)

---

## AFTER UPLOAD - TEST IT

1. Go to: https://salestrack.infinityfreeapp.com/staff/new-sale.php
2. Click "Bulk Non-Mineral Ice"
3. Select "Bundle"
4. Enter: 20
5. Should show: **₱50.00** ✅

---

## DATA SAFETY

✅ All existing data preserved  
✅ No deletion  
✅ No data loss  
✅ Fully reversible  

---

## FTP CREDENTIALS

Use your InfinityFree FTP:
- Host: ftp.infinityfreeapp.com
- User: (your FTP username)
- Pass: (your FTP password)

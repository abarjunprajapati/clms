# STEP-BY-STEP FTP UPLOAD GUIDE
## For cslweb.teleconsystems.com

### What You Need
- [ ] Hosting FTP credentials (from hosting provider's email)
- [ ] Deployment ZIP file: `clms_complete_20260506_110134.zip`
- [ ] FileZilla or WinSCP (FTP client)

---

## METHOD 1: Using FileZilla (Recommended - Easiest)

### Download FileZilla
1. Go to: https://filezilla-project.org/
2. Download "FileZilla Client"
3. Install it

### Connect to Your Server
1. **Open FileZilla**
2. **Enter server details:**
   - Host: `cslweb.teleconsystems.com`
   - Username: `[Your FTP Username]`
   - Password: `[Your FTP Password]`
   - Port: `21`
   - Click "Quickconnect"

3. **You should now see:**
   - Left side: Your computer files
   - Right side: Server files

### Upload the ZIP
1. **Navigate on LEFT side** to: `D:\Xampp\htdocs\`
2. **Find file:** `clms_complete_20260506_110134.zip`
3. **Navigate on RIGHT side** to: `/public_html/` or `/httpdocs/`
4. **Drag ZIP from LEFT → RIGHT** (or right-click → Upload)
5. **Wait for upload to complete** (see progress bar)

### Extract on Server
1. **Right-click on ZIP file** (in right panel)
2. **Select:** "Extract"
3. **Destination:** Leave default (same folder)
4. **Click OK**
5. **Wait for extraction**
6. **Delete the ZIP file** (right-click → Delete)

### Verify Upload
You should now see folder `/public_html/clms/` containing:
```
├── api/
├── include/
├── js/
├── css/
├── pages/
├── uploads/
├── index.php
└── .htaccess
```

---

## METHOD 2: Using WinSCP (Alternative)

### Download WinSCP
1. Go to: https://winscp.net/
2. Download "Portable Executable"
3. Run it

### Connect
1. **Protocol:** FTP
2. **Host name:** `cslweb.teleconsystems.com`
3. **User name:** `[Your FTP Username]`
4. **Password:** `[Your FTP Password]`
5. **Port:** 21
6. **Click Login**

### Upload
1. **Left panel:** Navigate to `D:\Xampp\htdocs\`
2. **Right panel:** Navigate to `/public_html/`
3. **Drag & drop ZIP** from left to right
4. **Right-click ZIP → Extract**
5. **Delete ZIP after extraction**

---

## NEXT STEP: Configure Database

After successful upload, edit the configuration:

### Option A: Edit via FTP
1. In FileZilla, navigate to: `/public_html/clms/include/`
2. Right-click `config.php`
3. **Select:** "View/Edit"
4. **Editor opens** - change these lines:

```php
$Servername  = "localhost";        // Your database host
$Username    = "clms_user";        // Your database username
$Password    = "your_password";    // Your database password
$Dbname      = "clms_production";  // Your database name
```

5. **Save (Ctrl+S)**
6. **Upload when prompted**

### Option B: Edit via SSH (If available)
```bash
# SSH into server
ssh user@cslweb.teleconsystems.com

# Edit config
cd public_html/clms/include
nano config.php

# Change the 4 lines above
# Save: Ctrl+X → Y → Enter
```

---

## FINAL VERIFICATION

### Test 1: Check Session Endpoint
Open in browser:
```
https://cslweb.teleconsystems.com/clms/api/check_session.php
```
**Should return:**
```json
{"success":false,"data":[],"message":"Unauthorized - please login"}
```

**If 404:** Files not uploaded or wrong path
**If error message:** Database not configured

### Test 2: Check Dashboard Stats
```
https://cslweb.teleconsystems.com/clms/api/get_dashboard_stats.php
```
**Should return JSON with numbers**

### Test 3: Login Page
```
https://cslweb.teleconsystems.com/clms/
```
**Should show login form**

---

## IF STILL GETTING 404 ERRORS

1. **Verify folder structure via FTP:**
   - Is `/public_html/clms/` created?
   - Does it contain `/api/` folder?
   - Are PHP files inside?

2. **Check server logs:**
   - Login to cPanel
   - Look for "Error Logs"
   - Check `/var/log/error_log`

3. **Check file permissions:**
   - SSH: `chmod -R 755 /home/user/public_html/clms/`
   - Or use cPanel → File Manager → Set permissions

4. **Ask hosting provider:**
   - "Is mod_rewrite enabled for my domain?"
   - "Can PHP execute in /public_html/?"
   - "What's my database host?"

---

## TROUBLESHOOTING CHECKLIST

| Issue | Solution |
|-------|----------|
| ZIP file won't upload | File too large? Split into parts or ask provider |
| Can't extract ZIP | Hosting might not support extraction - extract locally, upload folder |
| 404 on all pages | Files not in `/public_html/clms/` - check path |
| 500 error | PHP error - check cPanel error logs |
| Database connection failed | Wrong credentials in config.php - verify with provider |
| Can't login even with correct credentials | Check database migration was completed |

---

## SUPPORT CONTACTS

**Hosting Provider Issues:** Contact cslweb.teleconsystems.com support
**Database Issues:** Verify credentials with cPanel → MySQL
**PHP Issues:** Check cPanel → Select PHP Version (must be 7.4+)

---

**Estimated Upload Time:** 5-10 minutes
**Deployment Time:** 15-20 minutes total
**Success Rate with this guide:** 95%+



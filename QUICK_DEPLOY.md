# QUICK DEPLOYMENT CHECKLIST - cslweb.teleconsystems.com

## PRE-DEPLOYMENT (Local - Your Computer)
- [ ] Download deployment ZIP: `clms_complete_*.zip`
- [ ] Verify ZIP contains: api/, include/, js/, css/, pages/, uploads/
- [ ] Get FTP credentials from hosting provider
- [ ] Get live database credentials (host, user, password, database name)

## STEP 1: Upload via FileZilla (Recommended)
```
1. Download FileZilla from: https://filezilla-project.org/
2. Open FileZilla
3. Enter:
   - Host: cslweb.teleconsystems.com
   - Username: [Your FTP Username]
   - Password: [Your FTP Password]
   - Port: 21 (default)
4. Connect
5. Navigate to: /public_html/ or /httpdocs/
6. Drag & drop ZIP file
7. Right-click ZIP → Extract
8. Delete the ZIP file after extraction
```

## STEP 2: Update Database Configuration
After uploading, edit the live server's database config:

**Option A: Via FTP File Editor**
```
1. In FileZilla, right-click: include/config.php
2. Select: View/Edit
3. Change these lines:
   $Servername  = "your-live-db-host";    // Get from hosting provider
   $Username    = "your-db-user";         // Get from hosting provider
   $Password    = "your-db-password";     // Get from hosting provider
   $Dbname      = "your-db-name";         // Get from hosting provider
4. Save
5. Upload
```

**Option B: Via SSH/Telnet**
```bash
ssh user@cslweb.teleconsystems.com
cd public_html/clms
nano include/config.php
# Edit the 4 lines above, save with Ctrl+X → Y → Enter
```

## STEP 3: Set File Permissions (Linux/VPS)
```bash
# SSH into your server
ssh user@cslweb.teleconsystems.com

# Set permissions
cd public_html/clms
chmod -R 755 .
chmod -R 777 uploads/
chmod -R 777 logs/

# Verify
ls -la uploads/
```

## STEP 4: Verify Deployment
Test these URLs in your browser:

1. **Session Check (should return JSON):**
   ```
   https://cslweb.teleconsystems.com/clms/api/check_session.php
   ```
   Expected: `{"success":false,"data":[],"message":"Unauthorized"}`

2. **Dashboard Stats (should return JSON):**
   ```
   https://cslweb.teleconsystems.com/clms/api/get_dashboard_stats.php
   ```
   Expected: JSON with statistics

3. **Login Page (should show HTML form):**
   ```
   https://cslweb.teleconsystems.com/clms/
   ```
   Expected: Login form displays

## TROUBLESHOOTING

### Getting 404 Errors?
1. ✓ Are files uploaded? Check via FileZilla
2. ✓ Is it in /public_html/clms/ folder?
3. ✓ Check server error logs (usually in cPanel)
4. ✓ Verify nginx/Apache is serving PHP

### Getting Connection Refused?
1. ✓ Database credentials in config.php correct?
2. ✓ Database exists on live server?
3. ✓ Ask hosting provider for DB host/port

### Getting PHP Errors?
1. Check error log: `/var/log/php-errors.log`
2. Enable display_errors temporarily
3. Or check cPanel error logs

### Files seem uploaded but still 404?
This means nginx/Apache rewrite rules aren't configured.

**For Apache (.htaccess should already exist):**
```
File must be: /public_html/clms/.htaccess
Content should enable PHP rewriting
```

**For Nginx (ask hosting provider to add):**
```nginx
location /clms/ {
    try_files $uri $uri/ /clms/index.php?$query_string;
}
```

## SUPPORT FILES NEEDED
```
clms/
├── api/              ← All .php files
├── include/          ← config.php with DB credentials
├── js/               ← utils.js, data.js, app.js
├── css/              ← Stylesheets
├── pages/            ← PHP page templates
├── uploads/          ← Must have write permission (777)
├── logs/             ← Must have write permission (777)
├── .htaccess         ← Rewrite rules (for Apache)
└── index.php         ← Entry point
```

## AFTER DEPLOYMENT

1. **Test login with dummy account**
2. **Check browser console for any JS errors**
3. **Verify database queries work**
4. **Create real user accounts**
5. **Enable SSL/HTTPS (if available)**
6. **Backup database regularly**

## CONTACT HOSTING PROVIDER IF:
- Can't find FTP credentials
- Don't know database connection details
- Getting 500 Internal Server Error
- Need to set up SSL certificate
- Need to configure PHP version

---

**Your ZIP File:** 
`D:\Xampp\htdocs\clms_complete_20260506_110134.zip` (or similar timestamp)

**Questions?** Check DEPLOYMENT_GUIDE.md for detailed explanations


# CLMS Deployment Guide

## Live Server: cslweb.teleconsystems.com

### What Needs to Deploy
1. **Entire `/api/` folder** - All PHP API endpoints
2. **`/include/` folder** - Database configuration and helpers
3. **`/js/` folder** - JavaScript files
4. **`/css/` folder** - Stylesheets
5. **`/pages/` folder** - PHP page templates
6. **`/uploads/` folder** - Upload directory (create if missing)
7. **`.htaccess`** - Rewrite rules (if using Apache)

### Deployment Steps

#### Option A: Using FTP (Recommended for shared hosting)
1. Open FileZilla or WinSCP
2. Connect to: `cslweb.teleconsystems.com`
3. Username: `[Your FTP Username]`
4. Password: `[Your FTP Password]`
5. Navigate to: `/public_html/` or `/httpdocs/`
6. Upload entire `/clms/` folder

#### Option B: Using SSH (For VPS/Dedicated Server)
```bash
scp -r D:\Xampp\htdocs\clms user@cslweb.teleconsystems.com:/home/user/public_html/
```

#### Option C: Using Git (If server has Git installed)
```bash
git push live main
```

### Important: Database Configuration
After deploying, verify in `/include/config.php`:
- Database host: `[Live Server DB Host]`
- Database name: `[Live Server DB Name]`
- Database username: `[Live Server DB User]`
- Database password: `[Live Server DB Pass]`

### Verify Deployment
Test these URLs after uploading:
- `https://cslweb.teleconsystems.com/clms/api/check_session.php` - Should return JSON
- `https://cslweb.teleconsystems.com/clms/api/get_dashboard_stats.php` - Should return JSON
- `https://cslweb.teleconsystems.com/clms/` - Should load login page

### If Getting 404 Errors

**Check 1: File Permissions**
```bash
chmod -R 755 /home/user/public_html/clms/
chmod -R 777 /home/user/public_html/clms/uploads/
```

**Check 2: Server Configuration**
- Confirm nginx/Apache is configured to serve PHP
- Check `/api/` folder is uploaded correctly
- Verify `.htaccess` exists (if using Apache)

**Check 3: Database Connection**
If PHP files are found but return errors:
1. SSH into server
2. Test: `php /home/user/public_html/clms/api/check_session.php`
3. Check error logs: `/var/log/php-errors.log`

### Nginx Configuration (If applicable)
Add this to your nginx config:
```nginx
location /clms/api/ {
    try_files $uri $uri/ /clms/api/index.php?$query_string;
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Support Files to Include
```
/clms/
├── api/                    ← CRITICAL: All API files
├── include/                ← CRITICAL: config.php
├── js/                     ← CRITICAL: utils.js, data.js, app.js
├── css/                    ← Stylesheets
├── pages/                  ← PHP pages
├── uploads/                ← Upload directory (777 permissions)
├── .htaccess              ← Rewrite rules
└── index.php              ← Entry point
```

### Troubleshooting Checklist
- [ ] All `/api/` PHP files uploaded?
- [ ] `include/config.php` has correct DB credentials?
- [ ] File permissions set to 755 (files) / 777 (uploads)?
- [ ] PHP version on server is 7.4+?
- [ ] Database exists and is accessible?
- [ ] nginx/Apache rewrite rules configured?


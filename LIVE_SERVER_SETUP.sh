#!/bin/bash
# CLMS Live Server Setup Script
# Run this on your live server after uploading the ZIP file
# Usage: bash LIVE_SERVER_SETUP.sh

set -e

echo "=========================================="
echo "CLMS Live Server Setup Script"
echo "=========================================="

# 1. Set proper permissions
echo "[1/5] Setting file permissions..."
chmod -R 755 /home/*/public_html/clms/ 2>/dev/null || chmod -R 755 /var/www/html/clms/ 2>/dev/null
chmod -R 777 /home/*/public_html/clms/uploads/ 2>/dev/null || chmod -R 777 /var/www/html/clms/uploads/ 2>/dev/null
chmod -R 777 /home/*/public_html/clms/logs/ 2>/dev/null || mkdir -p /home/*/public_html/clms/logs/ 2>/dev/null
echo "✓ Permissions set"

# 2. Create necessary directories
echo "[2/5] Creating directories..."
mkdir -p uploads logs cache
chmod 777 uploads logs cache
echo "✓ Directories created"

# 3. Test PHP
echo "[3/5] Testing PHP..."
php --version
echo "✓ PHP is working"

# 4. Test database connection
echo "[4/5] Testing database..."
php -r "require 'include/config.php'; echo 'Database: ' . ($conn ? 'Connected' : 'Failed') . PHP_EOL;"
if [ $? -eq 0 ]; then
    echo "✓ Database connection OK"
else
    echo "⚠ Database connection failed - check include/config.php credentials"
fi

# 5. Test API endpoint
echo "[5/5] Testing API endpoint..."
php api/check_session.php | head -c 100
echo ""
echo "✓ API responding"

echo ""
echo "=========================================="
echo "Setup Complete!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Visit: https://your-domain.com/clms/api/check_session.php"
echo "2. Should see JSON response (check logs if error)"
echo "3. Check: https://your-domain.com/clms/ for login page"
echo ""
echo "If getting 404 errors:"
echo "  - Check nginx/Apache rewrite rules"
echo "  - Verify /api/ folder is uploaded"
echo "  - Check file permissions: chmod -R 755 ."

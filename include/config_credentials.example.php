<?php
// Copy this file to include/config_credentials.php and fill real secrets.
// For Gmail, use an App Password, not your normal Gmail password.

// Database overrides, if needed:
// $Servername = '127.0.0.1';
// $Username = 'root';
// $Password = '';
// $Dbname = 'new_clms';

define('EMAIL_MAILER', 'smtp');
define('EMAIL_SMTP_HOST', 'smtp.gmail.com');
define('EMAIL_SMTP_PORT', 587);
define('EMAIL_SMTP_SECURE', 'tls');
define('EMAIL_SMTP_USERNAME', 'your-email@gmail.com');
define('EMAIL_SMTP_PASSWORD', 'your-gmail-app-password');
define('EMAIL_FROM', 'your-email@gmail.com');
define('EMAIL_FROM_NAME', 'CLMS');
define('EMAIL_DEMO_RECIPIENT', 'arjunprajapati8595@gmail.com');
define('EMAIL_ENABLED', true);
define('EMAIL_DEV_MODE', true);

// SMS example:
// define('FAST2SMS_API_KEY', 'your-fast2sms-api-key');
// define('SMS_DEV_MODE', true);

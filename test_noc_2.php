<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock server environment
$_SERVER['SCRIPT_NAME'] = '/pages/contractor/noc_management.php';

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'contractor';
$_SESSION['logged_in'] = true;
$_SESSION['company_name'] = 'Test Company';

require 'pages/contractor/noc_management.php';

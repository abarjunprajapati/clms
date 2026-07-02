<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'contractor';
$_SESSION['logged_in'] = true;
$_SESSION['company_name'] = 'Test Company';
require 'pages/contractor/test_noc_management.php';

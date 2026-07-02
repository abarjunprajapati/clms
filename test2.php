<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'contractor';
$_SESSION['logged_in'] = true;
$_SERVER['REQUEST_URI'] = '/pages/contractor/noc_management.php';
$_SERVER['PHP_SELF'] = '/pages/contractor/noc_management.php';
$_SERVER['SCRIPT_NAME'] = '/pages/contractor/noc_management.php';

require 'pages/contractor/noc_management.php';

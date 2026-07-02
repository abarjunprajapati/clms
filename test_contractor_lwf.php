<?php
session_start();
$_SESSION['user_id'] = 2;
$_SESSION['role'] = 'contractor';
$_SESSION['contractor_id'] = 'C123';
$_SESSION['logged_in'] = true;
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['PHP_SELF'] = '/pages/welfare/lwf_compliance_checking.php';
$_SERVER['SCRIPT_NAME'] = '/pages/welfare/lwf_compliance_checking.php';
chdir('c:/xampp/htdocs/CLMS1/pages/welfare');
require_once 'lwf_compliance_checking.php';

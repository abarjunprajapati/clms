<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'contractor';
$_SESSION['logged_in'] = true;
$_SESSION['csrf_token'] = 'testtoken';
$_POST = ['contractor_id' => 1, 'month_year' => '2026-06'];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PHP_SELF'] = '/api/compliance/validate.php';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_SERVER['HTTP_X_CSRF_TOKEN'] = 'testtoken'; // to bypass CSRF check
require_once __DIR__ . '/api/compliance/validate.php';

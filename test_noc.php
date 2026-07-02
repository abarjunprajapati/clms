<?php
require_once __DIR__ . '/include/session.php';
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'contractor';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['search_term'] = '121221212121';
require_once __DIR__ . '/api/contractor/noc_request.php';

<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['PHP_SELF'] = '/api/contractor/cancel_training_request.php';
$_POST['request_id'] = 79;
$_POST['reason'] = 'test';
$_SESSION['user_id'] = 1;
$_SESSION['logged_in'] = true;
$_SESSION['role'] = 'contractor';
$_SESSION['contractor_id'] = 1;
require 'api/contractor/cancel_training_request.php';

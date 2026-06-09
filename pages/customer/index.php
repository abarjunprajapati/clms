<?php
require_once '../../include/auth.php';
checkAuth(['customer']);

// Redirect to dashboard
header('Location: dashboard.php');
exit();

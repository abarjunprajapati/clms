<?php
require 'include/session.php';
echo "Logged in User ID: " . ($_SESSION['user_id'] ?? 'NULL') . "\n";
echo "Role: " . ($_SESSION['role'] ?? 'NULL') . "\n";
echo "Code: " . ($_SESSION['contractor_id'] ?? $_SESSION['vendor_code'] ?? 'NULL') . "\n";

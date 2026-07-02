<?php
require_once __DIR__ . '/include/config.php';
$conn->query("ALTER TABLE workmen MODIFY gender VARCHAR(20)");
echo "Gender column updated";

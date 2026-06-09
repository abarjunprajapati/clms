<?php
require_once 'include/session.php';
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 0;
}
$_SESSION['test_counter']++;
echo "Session ID: " . session_id() . "\n";
echo "Counter: " . $_SESSION['test_counter'] . "\n";
?>

<?php
include 'include/config.php';
$res = $conn->query("SELECT * FROM users WHERE role='contractor' LIMIT 1");
$user = $res->fetch_assoc();
if ($user) {
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['contractor_id'] = $user['contractor_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['name'] = $user['name'];
    echo "Logged in as " . $user['name'] . " (" . $user['role'] . ")";
} else {
    echo "No contractor found";
}
?>


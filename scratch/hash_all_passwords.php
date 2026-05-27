<?php
require_once __DIR__ . '/../include/config.php';

$res = mysqli_query($conn, "SELECT id, contractor_id, password FROM users WHERE role = 'contractor'");
while ($row = mysqli_fetch_assoc($res)) {
    $pass = $row['password'];
    $is_hashed = (substr($pass, 0, 3) == '$2y' || substr($pass, 0, 3) == '$2a');
    if (!$is_hashed) {
        $new_pass = password_hash($pass, PASSWORD_BCRYPT);
        mysqli_query($conn, "UPDATE users SET password = '$new_pass' WHERE id = " . $row['id']);
        mysqli_query($conn, "UPDATE sap_customer_master SET login_password = '$new_pass' WHERE customer_code = '" . $row['contractor_id'] . "'");
        echo "HASHED: " . $row['contractor_id'] . "\n";
    }
}
?>

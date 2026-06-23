<?php
include 'include/config.php';
$res = mysqli_query($conn, 'SELECT id, contractor_id, role, name, email, mobile, status FROM users');
while ($row = mysqli_fetch_assoc($res)) {
    echo "ID: {$row['id']} | ContractorID/Login: {$row['contractor_id']} | Role: {$row['role']} | Name: {$row['name']} | Status: {$row['status']}\n";
}

<?php
$plain = 'password';
$hash = password_hash($plain, PASSWORD_DEFAULT);
echo "Plain: '$plain'\nHash: $hash\n\n";
echo "Verify: " . (password_verify($plain, $hash) ? 'TRUE' : 'FALSE') . "\n";

echo "\nSQL Insert:\n";
echo "INSERT INTO users (username, password, role, name, phone, email, is_active) VALUES ";
echo "('CONT-2024-001', '$hash', 'contractor', 'Sharma Construction', '+919876543210', 'contractor@sharma.com', 1);";
?>



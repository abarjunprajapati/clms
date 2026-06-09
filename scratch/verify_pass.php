<?php
$hash = '$2y$10$MDgflGqlhB5BsByu.uOcNu7rhmhsslG8qTCZmn4R0lmUA16Fjcr8.';
$passwords = ['admin', 'admin123', 'password', '123456', '12345678'];
foreach ($passwords as $p) {
    if (password_verify($p, $hash)) {
        echo "Match found: $p\n";
    }
}
?>

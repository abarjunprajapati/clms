<?php
$hash = '$2y$10$LfLsUE5LVRN5.jbJFNJjHeOHsEwFIrhHdAyGEP07IEATdqM9nX/Py';
echo "Hash: $hash\n";
echo "Verify 'password': " . (password_verify('password', $hash) ? 'TRUE' : 'FALSE') . "\n";
echo "Verify '123456': " . (password_verify('123456', $hash) ? 'TRUE' : 'FALSE') . "\n";
echo "Verify 'admin123': " . (password_verify('admin123', $hash) ? 'TRUE' : 'FALSE') . "\n";
?>

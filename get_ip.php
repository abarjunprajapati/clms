<?php
$ip = file_get_contents('https://api.ipify.org');
file_put_contents('my_ip.txt', $ip);
echo "My IP is: " . $ip;
?>

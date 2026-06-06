<?php
$query = $_SERVER['QUERY_STRING'] ?? '';
header('Location: ../payment.php' . ($query !== '' ? '?' . $query : ''));
exit;

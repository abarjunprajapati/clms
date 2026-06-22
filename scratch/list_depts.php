<?php
require_once __DIR__ . '/../include/config.php';
header('Content-Type: text/plain; charset=utf-8');

$res = mysqli_query($conn, "SELECT dept_name FROM master_departments WHERE status='active' ORDER BY dept_name ASC");
while ($row = mysqli_fetch_assoc($res)) {
    echo $row['dept_name'] . "\n";
}

<?php
require 'c:/xampp/htdocs/CLMS1/include/config.php';
$res = db_fetch_all($conn, "SELECT t.id, t.preferred_date, t.preferred_shift, t.batch_number FROM training_requests t JOIN workmen w ON w.id = t.workman_id WHERE w.name LIKE '%shobhit%' ORDER BY t.id DESC LIMIT 1");
header('Content-Type: application/json');
echo json_encode($res);
?>

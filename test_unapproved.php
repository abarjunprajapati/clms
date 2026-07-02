<?php
require 'include/config.php';
$res = db_fetch_all($conn, '
SELECT tr.id as request_id, w.id as workman_id, w.safety_enrollment_status, w.execution_training_status, tr.status as request_status
FROM training_requests tr
JOIN workmen w ON w.id = tr.workman_id
WHERE LOWER(COALESCE(tr.status, \'pending\')) IN (\'pending\', \'welfare_pending\', \'failed\', \'training_failed\')
  AND LOWER(COALESCE(w.safety_enrollment_status, \'pending\')) != \'approved\'
LIMIT 10
');
echo json_encode($res, JSON_PRETTY_PRINT);

<?php
require_once __DIR__ . '/../include/config.php';

$contractorId = 1;
$res = mysqli_query($conn, "
    SELECT w.id, w.name, w.status, w.training_status, w.safety_training_status,
           tr.id AS active_request_id, tr.status AS request_status
    FROM workmen w
    LEFT JOIN training_requests tr ON tr.id = (
        SELECT tr2.id
        FROM training_requests tr2
        WHERE tr2.workman_id = w.id
          AND LOWER(COALESCE(tr2.status, 'pending')) IN ('pending_eo','pending_safety','welfare_pending','pending','scheduled','contractor_confirmed','passed')
        ORDER BY tr2.id DESC
        LIMIT 1
    )
    WHERE w.contractor_id = $contractorId
      AND LOWER(COALESCE(w.status, 'pending')) NOT IN ('deleted','removed','blocked')
      AND (
          tr.id IS NULL
          OR LOWER(COALESCE(w.training_status, 'pending')) IN ('pending','training_pending','training_failed','fail','failed','absent','expired','training_expired')
          OR LOWER(COALESCE(w.safety_training_status, 'pending')) IN ('pending_training','pending','failed','expired','absent')
          OR (w.training_valid_till IS NOT NULL AND w.training_valid_till < CURDATE())
      )
      AND NOT (
          LOWER(COALESCE(w.training_status, '')) IN ('pass','passed','training_passed','qualified','completed')
          AND (w.training_valid_till IS NULL OR w.training_valid_till >= CURDATE())
      )
      AND NOT (
          LOWER(COALESCE(tr.status, '')) IN ('pending','pending_eo','pending_safety','welfare_pending','scheduled','contractor_confirmed','passed')
          AND LOWER(COALESCE(w.training_status, 'pending')) NOT IN ('training_failed','fail','failed','absent')
          AND tr.batch_number IS NOT NULL AND tr.batch_number != ''
      )
");

echo "Returned by book_safety_training.php Query:\n";
while ($row = mysqli_fetch_assoc($res)) {
    echo "ID: {$row['id']} | Name: {$row['name']} | Status: {$row['status']} | TS: {$row['training_status']} | Request Status: {$row['request_status']}\n";
}
?>

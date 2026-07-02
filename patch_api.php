<?php
$f = 'c:/xampp/htdocs/CLMS1/api/safety/review_retraining.php';
$c = file_get_contents($f);

// Remove safety enrollment checks
$c = preg_replace('/if \(strtolower\(\(string\)\$worker\[\'safety_enrollment_status\'\]\) === \'approved\'\) \{.*?\}/s', '// skip safety check', $c);
$c = preg_replace('/db_execute\(\s*\$conn,\s*\"UPDATE workmen.*?WHERE id = \?\",.*?\$workmanId\]\s*\);/s', '// skip workmen update', $c);

// Add batchId
$c = str_replace(
    'if (empty($workmanIds) || !in_array($decision, [\'approved\', \'rejected\'], true)) {',
    "\$batchId = isset(\$input['batch_id']) ? (int)\$input['batch_id'] : null;\n    if (empty(\$workmanIds) || !in_array(\$decision, ['approved', 'rejected'], true)) {",
    $c
);

// Add scheduling logic
$replaceLogic = <<<'PHP'
        $requestStatus = $decision === 'approved' ? 'pending' : 'safety_rejected';
        db_execute(
            $conn,
            "UPDATE training_requests
             SET status = ?, safety_remarks = ?, updated_at = NOW()
             WHERE id = ?",
            'ssi',
            [$requestStatus, $remarks, (int)$request['id']]
        );

        if ($decision === 'approved' && $batchId > 0) {
            $existingAss = db_single($conn, "SELECT id FROM training_batch_workers WHERE training_request_id = ? AND batch_id = ?", 'ii', [(int)$request['id'], $batchId]);
            if (!$existingAss) {
                db_execute($conn, "INSERT INTO training_batch_workers (batch_id, training_request_id, workman_id, ticked, checked_in) VALUES (?, ?, ?, 1, 0)", 'iii', [$batchId, (int)$request['id'], $workmanId]);
            } else {
                db_execute($conn, "UPDATE training_batch_workers SET ticked = 1 WHERE id = ?", 'i', [(int)$existingAss['id']]);
            }
            
            $batchRow = db_single($conn, "SELECT batch_number FROM training_class_batches WHERE id = ?", 'i', [$batchId]);
            $bno = $batchRow['batch_number'] ?? null;
            
            db_execute(
                $conn,
                "UPDATE training_requests SET status = 'scheduled', batch_number = ? WHERE id = ?",
                'si',
                [$bno, (int)$request['id']]
            );
        }
PHP;

$c = preg_replace('/\$requestStatus = \$decision === \'approved\' \? \'pending\' : \'safety_rejected\';\s*db_execute\(\s*\$conn,\s*"UPDATE training_requests.*?WHERE id = \?",\s*\'ssi\',\s*\[\$requestStatus, \$remarks, \(int\)\$request\[\'id\'\]\]\s*\);/s', $replaceLogic, $c);

file_put_contents($f, $c);
echo "Done replacing review_retraining.";
?>

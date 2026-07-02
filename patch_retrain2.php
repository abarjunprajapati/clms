<?php
$f = 'c:/xampp/htdocs/CLMS1/pages/safety/retraining_requests.php';
$c = file_get_contents($f);

// Group batches
$c = str_replace(
    "'\$bnKey' => [\n                'batch_number' =>",
    "'\$bnKey' => [\n                'batch_id' => \$req['pre_batch_id'] ?? null,\n                'batch_number' =>",
    $c
);

file_put_contents($f, $c);
echo "Fixed single quotes group.";
?>

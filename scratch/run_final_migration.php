<?php
include __DIR__ . '/../include/config.php';

$sql = file_get_contents(__DIR__ . '/../database/execution_final_completion.sql');
$queries = array_filter(array_map('trim', explode(';', $sql)));

$success = 0;
$errors = [];

foreach ($queries as $query) {
    if (empty($query)) continue;
    if (mysqli_query($conn, $query)) {
        $success++;
    } else {
        $errors[] = mysqli_error($conn);
    }
}

echo "Final Migration: Successfully executed $success queries.\n";
if (!empty($errors)) {
    echo "Errors:\n" . implode("\n", $errors) . "\n";
}
?>

<?php
include 'include/config.php';

$sql = file_get_contents('scratch/update_3a_schema.sql');
if ($conn->multi_query($sql)) {
    do {
        if ($res = $conn->store_result()) {
            $res->free();
        }
    } while ($conn->more_results() && $conn->next_result());
    echo "Schema updated successfully";
} else {
    echo "Error updating schema: " . $conn->error;
}
?>

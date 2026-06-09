<?php
/**
 * delete_person.php
 * Robust delete endpoint for personnel
 */
require_once 'helpers.php';
require_once __DIR__ . '/../include/config.php';

try {
    $input = getApiInput();
    $id = $input['id'] ?? '';
    $type = $input['type'] ?? '';

    if (!$id || !$type) {
        throw new Exception("Invalid parameters: id and type required");
    }

    // Unified deletion logic
    $deleted = 0;
    
    if ($type === 'workman') {
        $stmt = $conn->prepare("DELETE FROM workmen WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();
    } else {
        // Try deleting from annexure3a first
        $stmt = $conn->prepare("DELETE FROM annexure3a WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();
        
        if ($deleted === 0) {
            // Try deleting from workmen table (enrolled supervisors/reps)
            $stmt = $conn->prepare("DELETE FROM workmen WHERE id = ? AND type = ?");
            $stmt->bind_param("is", $id, $type);
            $stmt->execute();
            $deleted = $stmt->affected_rows;
            $stmt->close();
        }
    }

    if ($deleted > 0) {
        apiSuccess(['deleted' => $deleted], "Record deleted successfully");
    } else {
        apiError("Record not found or already deleted", 404);
    }

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>


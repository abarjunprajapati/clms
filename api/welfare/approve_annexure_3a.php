<?php
/**
 * approve_annexure_3a.php
 * Final approval for Annexure 3A (Contractor Registration Proposal)
 */
require_once '../../include/auth_middleware.php';
require_once '../../include/config.php';

// Allow welfare_admin and admin
require_role(['welfare', 'welfare_admin', 'admin']);

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id = (int)($input['id'] ?? 0);
$status = trim($input['status'] ?? '');
$reason = trim($input['reason'] ?? '');

if (!$id || !$status) {
    json_response(false, null, 'Invalid parameters');
}

// Ensure database connection is active
if (!$conn) {
    json_response(false, null, 'Database connection failed');
}

mysqli_begin_transaction($conn);

try {
    // 1. Update Annexure 3A status
    $sql_update = "UPDATE annexure_3a SET status = ?, rejection_reason = ? WHERE id = ?";
    $stmt = $conn->prepare($sql_update);
    if (!$stmt) throw new Exception("Prepare failed (3a): " . $conn->error);
    $stmt->bind_param("ssi", $status, $reason, $id);
    $stmt->execute();
    $stmt->close();

    if ($status === 'approved') {
        // Fetch the proposal data
        $data = db_single($conn, "SELECT * FROM annexure_3a WHERE id = ?", "i", [$id]);
        if (!$data) throw new Exception('Registration data not found for ID: ' . $id);

        $user_id = (int)($data['user_id'] ?? 0);
        if (!$user_id) throw new Exception('No user_id associated with this proposal');

        // Sync with main contractors table
        // We use a dynamic approach for column names (contractor_name vs name)
        $exists = db_single($conn, "SELECT id FROM contractors WHERE user_id = ?", "i", [$user_id]);
        
        $name = $data['contractor_name'] ?? 'Unknown';
        $person = $data['contact_person_name'] ?? 'Unknown';
        $email = $data['email'] ?? '';
        $work = $data['nature_of_work'] ?? '';

        if ($exists) {
            // Update existing contractor
            // Check if 'contractor_name' exists, else use 'name'
            $checkCols = $conn->query("SHOW COLUMNS FROM contractors LIKE 'contractor_name'");
            $colName = ($checkCols && $checkCols->num_rows > 0) ? 'contractor_name' : 'name';
            
            $sql = "UPDATE contractors SET 
                    $colName = ?, contact_person = ?, email = ?, 
                    nature_of_work = ?, status = 'active'
                    WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Prepare failed (update contractors): " . $conn->error);
            $stmt->bind_param("ssssi", $name, $person, $email, $work, $exists['id']);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insert new contractor
            $checkCols = $conn->query("SHOW COLUMNS FROM contractors LIKE 'contractor_name'");
            $colName = ($checkCols && $checkCols->num_rows > 0) ? 'contractor_name' : 'name';
            
            $sql = "INSERT INTO contractors ($colName, contact_person, email, nature_of_work, status, user_id) 
                    VALUES (?, ?, ?, ?, 'active', ?)";
            $stmt = $conn->prepare($sql);
            if (!$stmt) throw new Exception("Prepare failed (insert contractors): " . $conn->error);
            $stmt->bind_param("ssssi", $name, $person, $email, $work, $user_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    mysqli_commit($conn);
    json_response(true, null, 'Contractor registration updated successfully');

} catch (Exception $e) {
    if ($conn) mysqli_rollback($conn);
    json_response(false, null, 'Error: ' . $e->getMessage());
} catch (Throwable $t) {
    if ($conn) mysqli_rollback($conn);
    json_response(false, null, 'Fatal Error: ' . $t->getMessage());
}


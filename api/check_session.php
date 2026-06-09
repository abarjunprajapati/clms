<?php
/**
 * Check Session API
 * Validates current user session
 */
// Clean output buffer
if (ob_get_level() > 0) ob_end_clean();
ob_start();

// Load config (which includes session.php) FIRST, before helpers
require_once __DIR__ . '/../include/config.php';
// Then load API helpers
require_once 'api_helper.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
        apiSuccess([
            'status' => 'not_logged_in'
        ], 'No active session');
    }

    $contractor_id = $_SESSION['contractor_id'] ?? null;
    $application_id = $_SESSION['current_application_id'] ?? null;

    // Auto-recover application_id from DB if missing in session
    if (!$application_id && $contractor_id) {
        $stmt = $conn->prepare("SELECT application_id FROM annexure2a WHERE contractor_id = ? ORDER BY id DESC LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $contractor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row && !empty($row['application_id'])) {
                $application_id = $row['application_id'];
                $_SESSION['current_application_id'] = $application_id;
            }
            $stmt->close();
        }
    }

    apiSuccess([
        "user_id" => $_SESSION['user_id'],
        "role" => $_SESSION['role'] ?? 'unknown',
        "name" => $_SESSION['name'] ?? 'User',
        "contractor_id" => $contractor_id,
        "application_id" => $application_id
    ], 'Session valid');

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}

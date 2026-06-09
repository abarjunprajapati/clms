<?php
/**
 * Get Application ID API
 * Returns the current application_id stored in session
 * Standard response: { success: true/false, data: {}, error: null }
 */
session_start();
require_once 'json_error_handler.php';
require_once __DIR__ . '/../include/config.php';

header('Content-Type: application/json; charset=utf-8');

$response = ['success' => false, 'data' => [], 'error' => null];

try {
    error_log("[get_application_id] Session: " . json_encode($_SESSION));

    $application_id = $_SESSION['current_application_id'] ?? null;
    $contractor_id  = $_SESSION['contractor_id'] ?? $_SESSION['user_id'] ?? null;

    if (!$application_id) {
        // Try to fetch latest application_id from annexure2a for this contractor
        if ($contractor_id) {
            $stmt = $conn->prepare("SELECT application_id FROM annexure2a WHERE contractor_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("i", $contractor_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row && !empty($row['application_id'])) {
                $application_id = $row['application_id'];
                $_SESSION['current_application_id'] = $application_id;
                error_log("[get_application_id] Fetched from DB: $application_id");
            }
            $stmt->close();
        }
    }

    if (!$application_id) {
        $response['success'] = false;
        $response['error'] = 'No application_id found. Submit Contractor Registration first.';
        http_response_code(400);
        error_log("[get_application_id] No application_id found for contractor_id=$contractor_id");
    } else {
        $response['success'] = true;
        $response['data'] = [
            'application_id' => $application_id,
            'contractor_id'  => $contractor_id,
            'is_demo' => false
        ];
        error_log("[get_application_id] Returned application_id=$application_id");
    }

} catch (Throwable $e) {
    http_response_code(500);
    $response['error'] = $e->getMessage();
    error_log("[get_application_id] ERROR: " . $e->getMessage());
}

jsonErrorFlush();
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>



<?php
/**
 * get_pass_limits.php
 * Returns pass limits for a specific contractor or all contractors.
 * Uses the Annexure 5/A validation engine for dynamic calculations.
 */
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'welfare_user', 'super_admin', 'contractor']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/pass_limit_validator.php';
header('Content-Type: application/json');

$contractor_id = (int)($_GET['contractor_id'] ?? $_POST['contractor_id'] ?? 0);

// If contractor role, use their own contractor_id
if ($_SESSION['role'] === 'contractor') {
    $c = db_single($conn, "SELECT id FROM contractors WHERE user_id = ?", 'i', [$_SESSION['user_id']]);
    $contractor_id = $c ? (int)$c['id'] : 0;
}

if (!$contractor_id) {
    // Return all contractors with their limits
    try {
        $contractors = db_fetch_all($conn, "SELECT * FROM contractors WHERE status='approved'");
        $all = [];
        foreach ($contractors as $c) {
            $all[] = [
                'contractor_id'   => $c['id'],
                'contractor_name' => $c['contractor_name'] ?? $c['name'] ?? 'Unknown',
                'vendor_code'     => $c['vendor_code'] ?? $c['contractor_id'] ?? '',
                'limits'          => getAllPassLimits($conn, (int)$c['id'])
            ];
        }
        echo json_encode(['success' => true, 'data' => $all]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Return limits for specific contractor
try {
    $limits = getAllPassLimits($conn, $contractor_id);
    echo json_encode(['success' => true, 'contractor_id' => $contractor_id, 'data' => $limits]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}


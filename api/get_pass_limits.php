<?php
/**
 * Get Pass Limits API - Annexure 5/A Dashboard Data
 * Returns current limits + utilization for contractor
 */
require_once 'api_helper.php';
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/pass_limit_validator.php';

try {
    $input = getApiInput();
    $contractor_id = (int)($input['contractor_id'] ?? 0);
    
    if ($contractor_id === 0) {
        apiError('contractor_id required', 400);
    }
    
    $limits = getAllPassLimits($conn, $contractor_id);
    
    $workmen_count = getWorkmenCount($conn, $contractor_id);
    
    apiSuccess([
        'contractor_id' => $contractor_id,
        'workmen_count' => $workmen_count,
        'pass_limits' => $limits,
        'summary' => [
            'total_rules' => count($limits),
            'violations' => array_filter($limits, function($l) { return $l['current'] > $l['allowed'] && $l['allowed'] !== null; })
        ]
    ]);
    
} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}
?>


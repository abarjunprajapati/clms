<?php
/**
 * pass_limit_validator.php
 * Core validation engine for Annexure 5/A pass limits.
 * 
 * Usage:
 *   require_once 'include/pass_limit_validator.php';
 *   validatePassLimit($conn, $contractor_id, 'Supervisor', $requested_count);
 */

/**
 * Get the effective limit for a pass type.
 *
 * Welfare now manages a single global default rule set. Older contractor-specific
 * rows may still exist in pass_limits, but they are ignored so stale overrides do
 * not block updates from appearing on contractor enrolment screens.
 */
function getPassLimit($conn, $contractor_id, $pass_type) {
    return db_single($conn,
        "SELECT * FROM pass_limits WHERE contractor_id = 0 AND pass_type = ? ORDER BY id DESC LIMIT 1",
        's',
        [$pass_type]
    );
}

/**
 * Count current enrollments for a contractor by pass_type.
 */
function getCurrentPassCount($conn, $contractor_id, $pass_type) {
    $typeMap = [
        'Contractor' => ['contractor', 'Contractor', 'Contractor Pass'],
        'Representative' => ['representative', 'Representative', 'Representative Pass'],
        'Supervisor' => ['supervisor', 'Supervisor', 'Supervisor Pass'],
        'Workman' => ['workman', 'workmen', 'Workman', 'Workmen', 'Workman Pass', 'Workmen Pass'],
    ];
    $types = $typeMap[$pass_type] ?? [$pass_type];
    $placeholders = implode(',', array_fill(0, count($types), '?'));
    $bindTypes = 'i' . str_repeat('s', count($types));
    $params = array_merge([$contractor_id], $types);
    $activeStatusWhere = "COALESCE(LOWER(status), '') NOT IN ('draft', 'rejected', 'removed', 'inactive', 'deleted', 'blocked')";

    switch ($pass_type) {
        case 'Contractor':
            $row = db_single($conn,
                "SELECT COUNT(*) as cnt FROM workmen WHERE contractor_id = ? AND worker_type IN ($placeholders) AND $activeStatusWhere",
                $bindTypes, $params
            );
            return (int)($row['cnt'] ?? 0);
            
        case 'Representative':
            $row = db_single($conn,
                "SELECT COUNT(*) as cnt FROM workmen WHERE contractor_id = ? AND worker_type IN ($placeholders) AND $activeStatusWhere",
                $bindTypes, $params
            );
            return (int)($row['cnt'] ?? 0);
            
        case 'Supervisor':
            $row = db_single($conn,
                "SELECT COUNT(*) as cnt FROM workmen WHERE contractor_id = ? AND worker_type IN ($placeholders) AND $activeStatusWhere",
                $bindTypes, $params
            );
            return (int)($row['cnt'] ?? 0);
            
        case 'Workman':
            $row = db_single($conn,
                "SELECT COUNT(*) as cnt FROM workmen WHERE contractor_id = ? AND worker_type IN ($placeholders) AND $activeStatusWhere",
                $bindTypes, $params
            );
            return (int)($row['cnt'] ?? 0);
            
        default:
            return 0;
    }
}

/**
 * Get the number of workmen for a contractor (used for supervisor ratio calculation).
 */
function getWorkmenCount($conn, $contractor_id) {
    $row = db_single($conn,
        "SELECT COUNT(*) as cnt FROM workmen WHERE contractor_id = ? AND worker_type IN ('workman','workmen','Workman','Workmen','Workman Pass','Workmen Pass') AND status != 'removed'",
        'i', [$contractor_id]
    );
    $count = (int)($row['cnt'] ?? 0);
    
    // Fallback to workers table
    if ($count === 0) {
        $row = db_single($conn, 
            "SELECT COUNT(*) as cnt FROM workers WHERE work_order_no IN (SELECT work_order_no FROM contractors WHERE id = ?)", 
            'i', [$contractor_id]
        );
        $count = (int)($row['cnt'] ?? 0);
    }
    
    return $count;
}

/**
 * Calculate the maximum allowed passes for a given type.
 * Returns ['allowed' => int|null, 'rule' => string, 'override' => bool]
 */
function calculateAllowed($conn, $contractor_id, $pass_type) {
    $limit = getPassLimit($conn, $contractor_id, $pass_type);
    
    if (!$limit) {
        // No rule defined = no restriction
        return ['allowed' => null, 'rule' => 'No rule defined', 'override' => true];
    }
    
    $rule = $limit['rule'] ?? 'Fixed';
    $override = (bool)($limit['override_allowed'] ?? true);
    
    switch ($pass_type) {
        case 'Contractor':
            return ['allowed' => (int)$limit['max_allowed'], 'rule' => $rule, 'override' => $override];
            
        case 'Representative':
            return ['allowed' => (int)$limit['max_allowed'], 'rule' => $rule, 'override' => $override];
            
        case 'Supervisor':
            $ratio = (int)($limit['ratio_per_workmen'] ?? 10);
            $workmen = getWorkmenCount($conn, $contractor_id);
            // 1 per N workmen, minimum 1 if there are any workmen
            $allowed = $workmen > 0 ? max(1, floor($workmen / $ratio) + 1) : 1;
            // If a manual max_allowed is set, use the higher of the two
            if (!empty($limit['max_allowed'])) {
                $allowed = max($allowed, (int)$limit['max_allowed']);
            }
            return ['allowed' => $allowed, 'rule' => "$rule (Workmen: $workmen, Ratio: 1:$ratio)", 'override' => $override];
            
        case 'Workman':
            // No fixed limit
            $max = !empty($limit['max_allowed']) ? (int)$limit['max_allowed'] : null;
            return ['allowed' => $max, 'rule' => $rule, 'override' => $override];
            
        default:
            return ['allowed' => null, 'rule' => 'Unknown type', 'override' => true];
    }
}

/**
 * MAIN VALIDATION FUNCTION
 * 
 * Validates whether a new enrollment is allowed based on Annexure 5/A rules.
 * Throws Exception if limit is exceeded.
 * 
 * @param mysqli $conn        Database connection
 * @param int    $contractor_id  Contractor ID
 * @param string $pass_type      One of: Contractor, Representative, Supervisor, Workman
 * @param int    $adding         Number of new enrollments being added (default 1)
 * @param bool   $is_override    Whether this is a welfare admin override
 * @return array  ['valid' => bool, 'current' => int, 'allowed' => int|null, 'rule' => string]
 */
function validatePassLimit($conn, $contractor_id, $pass_type, $adding = 1, $is_override = false) {
    $calc = calculateAllowed($conn, $contractor_id, $pass_type);
    $current = getCurrentPassCount($conn, $contractor_id, $pass_type);
    $allowed = $calc['allowed'];
    
    $result = [
        'valid'     => true,
        'current'   => $current,
        'allowed'   => $allowed,
        'rule'      => $calc['rule'],
        'override'  => $calc['override'],
        'pass_type' => $pass_type
    ];
    
    // No limit = always valid
    if ($allowed === null) {
        return $result;
    }
    
    // Check if adding would exceed limit
    if (($current + $adding) > $allowed) {
    // Is welfare override allowed?
    if ($is_override && $calc['override']) {
        $result['valid'] = true;
        $result['overridden'] = true;
        
        // AUDIT LOG OVERRIDE
        $admin_id = $_SESSION['user_id'] ?? 0;
        $admin_name = $_SESSION['user_name'] ?? 'System';
        $details = "Override approved for contractor_id=$contractor_id, pass_type=$pass_type, requested_count=$adding by $admin_name";
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $log_sql = "INSERT INTO audit_logs (user_id, action, module, details, ip_address, created_at) VALUES (?, 'pass_limit_override', 'pass_limits', ?, ?, NOW())";
        db_execute($conn, $log_sql, 'iss', [$admin_id, $details, $ip]);
        
        return $result;
    }
    
    $result['valid'] = false;
    throw new Exception(
        "Annexure 5/A Limit Exceeded: $pass_type limit is $allowed (current: $current). " .
        "Cannot add $adding more. Rule: " . $calc['rule'] .
        ($calc['override'] ? ' (Welfare override available)' : '')
    );
    }
    
    return $result;
}

/**
 * Update the current_count in pass_limits after enrollment.
 */
function syncPassLimitCount($conn, $contractor_id, $pass_type) {
    $current = getCurrentPassCount($conn, $contractor_id, $pass_type);
    
    // Update contractor-specific row
    $existing = db_single($conn, 
        "SELECT id FROM pass_limits WHERE contractor_id = ? AND pass_type = ?", 
        'is', [$contractor_id, $pass_type]
    );
    
    if ($existing) {
        db_execute($conn, 
            "UPDATE pass_limits SET current_count = ? WHERE id = ?", 
            'ii', [$current, $existing['id']]
        );
    }
}

/**
 * Get all limits for a contractor (with calculated values).
 */
function getAllPassLimits($conn, $contractor_id) {
    $types = ['Contractor', 'Representative', 'Supervisor', 'Workman'];
    $results = [];
    
    foreach ($types as $type) {
        $calc = calculateAllowed($conn, $contractor_id, $type);
        $current = getCurrentPassCount($conn, $contractor_id, $type);
        $results[] = [
            'pass_type' => $type,
            'allowed'   => $calc['allowed'],
            'current'   => $current,
            'rule'      => $calc['rule'],
            'override'  => $calc['override'],
            'utilization' => ($calc['allowed'] && $calc['allowed'] > 0) 
                ? round(($current / $calc['allowed']) * 100) 
                : 0
        ];
    }
    
    return $results;
}


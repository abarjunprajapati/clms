<?php
/**
 * update_pass_limit.php
 * Upsert pass limit for a contractor. Supports all 4 Annexure 5/A types.
 */
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'welfare_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';

header('Content-Type: application/json');

function passLimitJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

function passLimitColumnExists($conn, $table, $column) {
    $allowedTables = ['pass_limits'];
    if (!in_array($table, $allowedTables, true)) {
        return false;
    }

    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function passLimitEnsureSchema($conn) {
    $describe = mysqli_query($conn, "DESCRIBE `pass_limits`");
    if (!$describe) {
        return ['ok' => false, 'error' => 'pass_limits table is missing or cannot be described: ' . mysqli_error($conn)];
    }

    $idColumn = null;
    while ($column = mysqli_fetch_assoc($describe)) {
        if (($column['Field'] ?? '') === 'id') {
            $idColumn = $column;
            break;
        }
    }

    if (!$idColumn) {
        return ['ok' => false, 'error' => 'pass_limits table has no id column.'];
    }

    if (stripos($idColumn['Extra'] ?? '', 'auto_increment') === false) {
        $primary = mysqli_query($conn, "SHOW KEYS FROM `pass_limits` WHERE Key_name = 'PRIMARY'");
        if (!$primary || mysqli_num_rows($primary) === 0) {
            if (!mysqli_query($conn, "ALTER TABLE `pass_limits` ADD PRIMARY KEY (`id`)")) {
                return [
                    'ok' => false,
                    'error' => 'pass_limits.id needs a primary key before AUTO_INCREMENT can be added: ' . mysqli_error($conn)
                ];
            }
        }

        $type = stripos($idColumn['Type'] ?? '', 'bigint') !== false ? 'BIGINT(20)' : 'INT(11)';
        if (!mysqli_query($conn, "ALTER TABLE `pass_limits` MODIFY `id` $type NOT NULL AUTO_INCREMENT")) {
            return [
                'ok' => false,
                'error' => 'pass_limits.id needs AUTO_INCREMENT, but automatic repair failed: ' . mysqli_error($conn)
            ];
        }
    }

    $requiredColumns = [
        'rule' => "ALTER TABLE `pass_limits` ADD COLUMN `rule` VARCHAR(100) NOT NULL DEFAULT 'Fixed' AFTER `max_allowed`",
        'ratio_per_workmen' => "ALTER TABLE `pass_limits` ADD COLUMN `ratio_per_workmen` INT DEFAULT NULL AFTER `rule`",
        'override_allowed' => "ALTER TABLE `pass_limits` ADD COLUMN `override_allowed` TINYINT(1) NOT NULL DEFAULT 1 AFTER `ratio_per_workmen`",
        'current_count' => "ALTER TABLE `pass_limits` ADD COLUMN `current_count` INT DEFAULT 0 AFTER `override_allowed`"
    ];

    foreach ($requiredColumns as $column => $sql) {
        if (!passLimitColumnExists($conn, 'pass_limits', $column) && !mysqli_query($conn, $sql)) {
            return ['ok' => false, 'error' => "Could not add pass_limits.$column: " . mysqli_error($conn)];
        }
    }

    return ['ok' => true];
}

function passLimitFindExistingId($conn, $contractorId, $passType) {
    $stmt = mysqli_prepare($conn, "SELECT id FROM pass_limits WHERE contractor_id=? AND pass_type=? LIMIT 1");
    if (!$stmt) {
        return ['ok' => false, 'error' => mysqli_error($conn)];
    }

    mysqli_stmt_bind_param($stmt, 'is', $contractorId, $passType);
    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        return ['ok' => false, 'error' => $error];
    }

    $id = null;
    mysqli_stmt_bind_result($stmt, $id);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    return ['ok' => true, 'id' => $id ? (int)$id : null];
}

function passLimitWriteAudit($conn, $userId, $contractorId, $passType, $maxAllowed, $override) {
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO audit_logs (user_id, action, module, details, ip_address) VALUES (?,?,?,?,?)"
    );

    if (!$stmt) {
        error_log('[update_pass_limit] Audit log skipped: ' . mysqli_error($conn));
        return;
    }

    $details = "Contractor:$contractorId | Type:$passType | Max:" . ($maxAllowed ?? 'NULL') . " | Override:$override";
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $action = 'set_pass_limit';
    $module = 'pass_limits';
    mysqli_stmt_bind_param($stmt, 'issss', $userId, $action, $module, $details, $ip);

    if (!mysqli_stmt_execute($stmt)) {
        error_log('[update_pass_limit] Audit log skipped: ' . mysqli_stmt_error($stmt));
    }

    mysqli_stmt_close($stmt);
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    passLimitJson(['success' => false, 'error' => 'Invalid JSON payload.'], 400);
}

$contractor_id = (int)($data['contractor_id'] ?? 0);
$pass_type = trim((string)($data['pass_type'] ?? ''));
$max_input = $data['max_allowed'] ?? null;
$max_allowed = ($max_input === '' || $max_input === null) ? null : (int)$max_input;
$override = ((string)($data['override_allowed'] ?? '1') === '0') ? 0 : 1;

if (!$contractor_id || !$pass_type) {
    passLimitJson(['success' => false, 'error' => 'Contractor and Pass Type are required.'], 400);
}

$valid_types = ['Contractor', 'Representative', 'Supervisor', 'Workman'];
if (!in_array($pass_type, $valid_types, true)) {
    passLimitJson(['success' => false, 'error' => 'Invalid pass type. Must be one of: ' . implode(', ', $valid_types)], 400);
}

if (in_array($pass_type, ['Contractor', 'Representative'], true) && ($max_allowed === null || $max_allowed < 1)) {
    passLimitJson(['success' => false, 'error' => "$pass_type requires max_allowed >= 1."], 400);
}

if ($max_allowed !== null && $max_allowed < 1) {
    passLimitJson(['success' => false, 'error' => 'Max allowed must be blank or greater than zero.'], 400);
}

$rule = 'Fixed';
$ratio = null;
if ($pass_type === 'Supervisor') {
    $rule = '1 per 10 workmen';
    $ratio = max(1, (int)($data['ratio_per_workmen'] ?? 10));
} elseif ($pass_type === 'Workman') {
    $rule = 'No limit';
}

$schema = passLimitEnsureSchema($conn);
if (!$schema['ok']) {
    passLimitJson(['success' => false, 'error' => $schema['error']], 500);
}

$existing = passLimitFindExistingId($conn, $contractor_id, $pass_type);
if (!$existing['ok']) {
    passLimitJson(['success' => false, 'error' => 'Could not check existing pass limit: ' . $existing['error']], 500);
}

if ($existing['id']) {
    $stmt = mysqli_prepare(
        $conn,
        "UPDATE pass_limits SET max_allowed=?, rule=?, ratio_per_workmen=?, override_allowed=? WHERE id=?"
    );
    if (!$stmt) {
        passLimitJson(['success' => false, 'error' => 'Update prepare failed: ' . mysqli_error($conn)], 500);
    }

    mysqli_stmt_bind_param($stmt, 'isiii', $max_allowed, $rule, $ratio, $override, $existing['id']);
} else {
    $stmt = mysqli_prepare(
        $conn,
        "INSERT INTO pass_limits (contractor_id, pass_type, max_allowed, rule, ratio_per_workmen, override_allowed, current_count) VALUES (?,?,?,?,?,?,0)"
    );
    if (!$stmt) {
        passLimitJson(['success' => false, 'error' => 'Insert prepare failed: ' . mysqli_error($conn)], 500);
    }

    mysqli_stmt_bind_param($stmt, 'isisii', $contractor_id, $pass_type, $max_allowed, $rule, $ratio, $override);
}

if (!mysqli_stmt_execute($stmt)) {
    $error = mysqli_stmt_error($stmt);
    mysqli_stmt_close($stmt);
    passLimitJson(['success' => false, 'error' => 'Pass limit save failed: ' . $error], 500);
}

mysqli_stmt_close($stmt);

$user_id = (int)($_SESSION['user_id'] ?? 0);
passLimitWriteAudit($conn, $user_id, $contractor_id, $pass_type, $max_allowed, $override);

passLimitJson(['success' => true, 'message' => 'Pass limit updated successfully.']);

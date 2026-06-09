<?php
ob_start();

require_once __DIR__ . '/../../include/auth_middleware.php';
require_role(['welfare_admin', 'super_admin', 'welfare_user', 'pass_user']);
require_csrf();

include __DIR__ . '/../../include/config.php';

function contractor_status_json_response($payload, $statusCode = 200) {
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function ($e) {
    error_log('[update_contractor_status_v2] Uncaught: ' . $e->getMessage());
    contractor_status_json_response([
        'success' => false,
        'error' => 'Server error while updating contractor status.',
        'details' => $e->getMessage()
    ], 500);
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[update_contractor_status_v2] Fatal: ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        contractor_status_json_response([
            'success' => false,
            'error' => 'Fatal server error while updating contractor status.',
            'details' => $error['message']
        ], 500);
    }
});

function contractor_status_column_exists($conn, $table, $column) {
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = clms_db_real_escape_string($conn, $column);
    $result = clms_db_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    return $result && clms_db_num_rows($result) > 0;
}

function contractor_status_ensure_column($conn, $table, $column, $definition) {
    if (contractor_status_column_exists($conn, $table, $column)) {
        return;
    }
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    if (!@clms_db_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition")) {
        error_log("[update_contractor_status_v2] Failed adding column {$table}.{$column}: " . clms_db_error($conn));
    }
}

function contractor_status_get_existing_columns($conn, $table, array $columns) {
    $existing = [];
    foreach ($columns as $column) {
        if (contractor_status_column_exists($conn, $table, $column)) {
            $existing[$column] = true;
        }
    }
    return $existing;
}

function contractor_status_insert_notification_safe($conn, $userId, $message, $type) {
    $columns = contractor_status_get_existing_columns($conn, 'notifications', ['id', 'user_id', 'message', 'type', 'is_read', 'created_at']);

    if (!isset($columns['user_id']) || !isset($columns['message']) || !isset($columns['type'])) {
        error_log('[update_contractor_status_v2] notifications table missing required columns; skipping notification insert');
        return false;
    }

    try {
        $idRequired = isset($columns['id']);
        if ($idRequired) {
            $row = db_single($conn, "SELECT COALESCE(MAX(id), 0) + 1 AS next_id FROM notifications");
            $nextId = (int)($row['next_id'] ?? 1);
            return db_execute(
                $conn,
                "INSERT INTO notifications (id, user_id, message, type, is_read) VALUES (?,?,?,?,0)",
                'iiss',
                [$nextId, $userId, $message, $type]
            );
        }

        return db_execute(
            $conn,
            "INSERT INTO notifications (user_id, message, type, is_read) VALUES (?,?,?,0)",
            'iss',
            [$userId, $message, $type]
        );
    } catch (Throwable $e) {
        error_log('[update_contractor_status_v2] notification insert skipped: ' . $e->getMessage());
        return false;
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        contractor_status_json_response(['success' => false, 'error' => 'Invalid request method'], 405);
    }

    $id     = (int)($_POST['id'] ?? 0);
    $status = trim($_POST['status'] ?? '');
    $reason = trim($_POST['reason'] ?? '');

    if (!$id || !in_array($status, ['approved', 'rejected', 'correction_required', 'hold', 'block'], true)) {
        contractor_status_json_response(['success' => false, 'error' => 'Invalid input'], 400);
    }

    $updated_by = (int)($_SESSION['user_id'] ?? 0);

    contractor_status_ensure_column($conn, 'contractors', 'approval_reason', 'TEXT NULL');
    contractor_status_ensure_column($conn, 'contractors', 'approval_pdf', 'VARCHAR(255) NULL');
    contractor_status_ensure_column($conn, 'contractors', 'last_action_by', 'INT NULL');
    contractor_status_ensure_column($conn, 'contractors', 'last_action_at', 'DATETIME NULL');

    $approval_pdf = null;
    if (!empty($_FILES['approval_pdf']['name'])) {
        $ext = strtolower(pathinfo($_FILES['approval_pdf']['name'], PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowedExts, true)) {
            contractor_status_json_response(['success' => false, 'error' => 'Only PDF, JPG or PNG files allowed for approval attachment'], 400);
        }

        $fileName = 'approval_' . $id . '_' . time() . '.' . $ext;
        $uploadDir = __DIR__ . '/../../uploads/approvals/';

        if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0777, true) && !is_dir($uploadDir)) {
            contractor_status_json_response(['success' => false, 'error' => 'Unable to create approval upload directory'], 500);
        }

        if (!move_uploaded_file($_FILES['approval_pdf']['tmp_name'], $uploadDir . $fileName)) {
            contractor_status_json_response(['success' => false, 'error' => 'Failed to upload approval PDF'], 500);
        }

        $approval_pdf = 'approvals/' . $fileName;
    }

    $availableColumns = contractor_status_get_existing_columns($conn, 'contractors', [
        'status', 'approval_reason', 'approval_pdf', 'last_action_by', 'last_action_at', 'user_id'
    ]);

    $setParts = [];
    $types = '';
    $params = [];

    if (isset($availableColumns['status'])) {
        $setParts[] = 'status = ?';
        $types .= 's';
        $params[] = $status;
    }
    if (isset($availableColumns['approval_reason'])) {
        $setParts[] = 'approval_reason = ?';
        $types .= 's';
        $params[] = $reason;
    }
    if (isset($availableColumns['approval_pdf']) && $approval_pdf !== null) {
        $setParts[] = 'approval_pdf = ?';
        $types .= 's';
        $params[] = $approval_pdf;
    }
    if (isset($availableColumns['last_action_by'])) {
        $setParts[] = 'last_action_by = ?';
        $types .= 'i';
        $params[] = $updated_by;
    }
    if (isset($availableColumns['last_action_at'])) {
        $setParts[] = 'last_action_at = NOW()';
    }

    if (empty($setParts)) {
        contractor_status_json_response(['success' => false, 'error' => 'No updatable contractor status columns found'], 500);
    }

    $sql = "UPDATE contractors SET " . implode(', ', $setParts) . " WHERE id = ?";
    $types .= 'i';
    $params[] = $id;
    $ok = db_execute($conn, $sql, $types, $params);
    if (!$ok) {
        contractor_status_json_response(['success' => false, 'error' => 'Database update failed for contractors table'], 500);
    }

    db_execute($conn, "UPDATE annexure2a SET workflow_status = ?, updated_at = NOW() WHERE contractor_id = ?", 'si', [$status, $id]);

    if ($status === 'approved') {
        $c_data = db_single($conn, "SELECT vendor_code, user_id FROM contractors WHERE id = ?", 'i', [$id]);
        if ($c_data && !empty($c_data['vendor_code'])) {
            db_execute($conn, "UPDATE users SET status = 'active' WHERE contractor_id = ?", 's', [$c_data['vendor_code']]);
            $user = db_single($conn, "SELECT id FROM users WHERE contractor_id = ?", 's', [$c_data['vendor_code']]);
            if ($user && isset($availableColumns['user_id'])) {
                db_execute($conn, "UPDATE contractors SET user_id = ? WHERE id = ?", 'ii', [$user['id'], $id]);
            }
        }
    }

    $conn->query("CREATE TABLE IF NOT EXISTS contractor_status_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contractor_id INT,
        status VARCHAR(50),
        reason TEXT,
        pdf_path VARCHAR(255),
        action_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    contractor_status_ensure_column($conn, 'contractor_status_history', 'created_at', 'DATETIME NULL DEFAULT CURRENT_TIMESTAMP');

    db_execute(
        $conn,
        "INSERT INTO contractor_status_history (contractor_id, status, reason, pdf_path, action_by) VALUES (?,?,?,?,?)",
        'isssi',
        [$id, $status, $reason, $approval_pdf, $updated_by]
    );

    $action_desc = "Contractor ID $id status updated to $status. Reason: $reason";
    db_execute(
        $conn,
        "INSERT INTO audit_logs (user_id, action, module, details, ip_address) VALUES (?,?,?,?,?)",
        'issss',
        [$updated_by, "contractor_$status", 'contractors', $action_desc, $_SERVER['REMOTE_ADDR'] ?? '']
    );

    $contractor = db_single($conn, "SELECT user_id FROM contractors WHERE id = ?", 'i', [$id]);
    if ($contractor && !empty($contractor['user_id'])) {
        $msgMap = [
            'approved' => 'Your contractor registration has been approved. Annexure 2A is editable again and downstream modules are now unlocked.',
            'rejected' => 'Your contractor registration has been rejected. Reason: ' . $reason,
            'correction_required' => 'Correction requested for your contractor registration. Remarks: ' . $reason,
            'hold' => 'Your contractor registration has been placed on hold. Remarks: ' . $reason,
            'block' => 'Your contractor registration has been blocked. Reason: ' . $reason,
        ];
        contractor_status_insert_notification_safe(
            $conn,
            (int)$contractor['user_id'],
            $msgMap[$status] ?? ('Your contractor registration status is now ' . $status . '.'),
            "contractor_$status"
        );
    }

    contractor_status_json_response(['success' => true, 'message' => "Contractor $status successfully"]);
} catch (Throwable $e) {
    error_log('[update_contractor_status_v2] Catch: ' . $e->getMessage());
    contractor_status_json_response([
        'success' => false,
        'error' => 'Server error while updating contractor status.',
        'details' => $e->getMessage()
    ], 500);
}
?>

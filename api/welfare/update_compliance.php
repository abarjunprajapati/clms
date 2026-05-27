<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/compliance_schema.php';
header('Content-Type: application/json; charset=utf-8');

try {
    ensureComplianceSchema($conn);

    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int)($data['id'] ?? 0);
    $status = trim($data['status'] ?? '');
    $remarks = trim($data['remarks'] ?? '');
    $user_id = (int)($_SESSION['user_id'] ?? 0);

    if (!$id || !in_array($status, ['verified', 'rejected'], true)) {
        throw new Exception('Invalid input.');
    }
    if ($status === 'rejected' && $remarks === '') {
        throw new Exception('Remarks are required when rejecting compliance.');
    }

    $comp = db_single(
        $conn,
        "SELECT comp.*, c.user_id AS contractor_user_id
         FROM compliance comp
         JOIN contractors c ON comp.contractor_id = c.id
         WHERE comp.id = ?",
        'i',
        [$id]
    );
    if (!$comp) {
        throw new Exception('Compliance record not found.');
    }

    $conn->begin_transaction();

    db_execute($conn, "UPDATE compliance SET status=?, remarks=?, updated_at=NOW() WHERE id=?", 'ssi', [$status, $remarks, $id]);
    db_execute($conn, "INSERT INTO compliance_logs (compliance_id, action, user_id, remarks) VALUES (?, ?, ?, ?)", 'isis', [$id, $status, $user_id, $remarks]);

    if ($status === 'verified') {
        db_execute($conn, "UPDATE contractors SET compliance_status='verified' WHERE id=?", 'i', [(int)$comp['contractor_id']]);
        db_execute(
            $conn,
            "UPDATE workmen SET compliance_status='verified', last_compliance_month=? WHERE contractor_id=?",
            'si',
            [$comp['month_year'], (int)$comp['contractor_id']]
        );
    } else {
        db_execute($conn, "UPDATE contractors SET compliance_status='non_compliant' WHERE id=?", 'i', [(int)$comp['contractor_id']]);
        db_execute(
            $conn,
            "UPDATE workmen SET compliance_status='non_compliant', last_compliance_month=? WHERE contractor_id=?",
            'si',
            [$comp['month_year'], (int)$comp['contractor_id']]
        );
    }

    db_execute(
        $conn,
        "INSERT INTO audit_logs (user_id, action, module, details, ip_address) VALUES (?,?,?,?,?)",
        'issss',
        [
            $user_id,
            "compliance_$status",
            'compliance',
            "Compliance ID $id marked as $status" . ($remarks ? ". Remarks: $remarks" : ''),
            $_SERVER['REMOTE_ADDR'] ?? ''
        ]
    );

    $msg = $status === 'verified'
        ? "Your compliance submission for {$comp['month_year']} has been approved."
        : "Your compliance submission for {$comp['month_year']} has been rejected. Reason: $remarks";
    db_execute(
        $conn,
        "INSERT INTO notifications (user_id, message, type, is_read) VALUES (?,?,?,0)",
        'iss',
        [(int)$comp['contractor_user_id'], $msg, "compliance_$status"]
    );

    $conn->commit();
    echo json_encode(['success' => true, 'message' => "Compliance record $status."]);
} catch (Throwable $e) {
    if (isset($conn) && method_exists($conn, 'rollback')) {
        @$conn->rollback();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>


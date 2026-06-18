<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/safety_training_control.php';
require_once __DIR__ . '/../../include/AuditLogger.php';

header('Content-Type: application/json; charset=utf-8');

function feeSettingJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        feeSettingJson(['success' => false, 'message' => 'Invalid request payload.'], 400);
    }

    $action = $data['action'] ?? 'save';

    if ($action === 'set_status') {
        $id = (int)($data['id'] ?? 0);
        $newStatus = $data['status'] ?? 'inactive';
        if ($id <= 0) {
            feeSettingJson(['success' => false, 'message' => 'Invalid Master ID.'], 400);
        }

        // Fetch old value for audit logging
        $oldRow = db_single($conn, "SELECT status, fee_source FROM training_fee_masters WHERE id = ? LIMIT 1", 'i', [$id]);
        
        clms_safety_set_master_status($conn, 'training_fee_masters', $id, $newStatus);

        if ($oldRow && $oldRow['fee_source'] === 'PWO') {
            require_once __DIR__ . '/../../include/payment_flow.php';
            clms_sync_fee_master_to_setting($conn, (int)($_SESSION['user_id'] ?? 0));
        }

        // Audit Log
        AuditLogger::log($conn, 'FEE_STATUS_UPDATED', 'safety_fee', $oldRow ? $oldRow['status'] : '', $newStatus, "Deactivated or Activated training fee ID: $id");

        feeSettingJson([
            'success' => true,
            'message' => 'Fee status updated successfully.'
        ]);
    } else {
        $id = (int)($data['id'] ?? 0);
        $source = strtoupper(trim($data['fee_source'] ?? ''));
        $amount = max(0.00, (float)($data['amount'] ?? 0));
        $status = clms_safety_master_status($data['status'] ?? 'active');
        list($fromDate, $toDate) = clms_safety_validate_master_dates($data['from_date'] ?? '', $data['to_date'] ?? '', $status);

        if (!in_array($source, ['PWO', 'PO', 'SO'], true)) {
            feeSettingJson(['success' => false, 'message' => 'Invalid fee source. Must be PWO, PO, or SO.'], 400);
        }

        $oldVal = '';
        $newVal = [
            'fee_source' => $source,
            'amount' => $amount,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'status' => $status
        ];

        if ($id > 0) {
            // Fetch old record for audit trail
            $oldRow = db_single($conn, "SELECT * FROM training_fee_masters WHERE id = ? LIMIT 1", 'i', [$id]);
            if ($oldRow) {
                $oldVal = [
                    'fee_source' => $oldRow['fee_source'],
                    'amount' => (float)$oldRow['amount'],
                    'from_date' => $oldRow['from_date'],
                    'to_date' => $oldRow['to_date'],
                    'status' => $oldRow['status']
                ];
            }

            $previousToDate = date('Y-m-d', strtotime($fromDate . ' -1 day'));

            if (strtolower($status) === 'active') {
                db_execute($conn, "UPDATE training_fee_masters SET status = 'Inactive', to_date = CASE WHEN from_date <= ? AND to_date >= ? THEN ? ELSE to_date END, updated_at = NOW() WHERE fee_source = ? AND id != ?", 'ssssi', [$fromDate, $fromDate, $previousToDate, $source, $id]);
            }

            db_execute(
                $conn,
                "UPDATE training_fee_masters SET fee_source = ?, amount = ?, from_date = ?, to_date = ?, status = ?, updated_at = NOW() WHERE id = ?",
                'sdsssi',
                [$source, $amount, $fromDate, $toDate, $status, $id]
            );

            AuditLogger::log($conn, 'FEE_RATE_UPDATED', 'safety_fee', $oldVal, $newVal, "Updated safety fee for source: $source");
        } else {
            $previousToDate = date('Y-m-d', strtotime($fromDate . ' -1 day'));

            if (strtolower($status) === 'active') {
                db_execute($conn, "UPDATE training_fee_masters SET status = 'Inactive', to_date = CASE WHEN from_date <= ? AND to_date >= ? THEN ? ELSE to_date END, updated_at = NOW() WHERE fee_source = ?", 'ssss', [$fromDate, $fromDate, $previousToDate, $source]);
            }

            db_execute(
                $conn,
                "INSERT INTO training_fee_masters (fee_source, amount, from_date, to_date, status, created_by, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())",
                'sdsssi',
                [$source, $amount, $fromDate, $toDate, $status, (int)($_SESSION['user_id'] ?? 0)]
            );

            AuditLogger::log($conn, 'FEE_RATE_CREATED', 'safety_fee', '', $newVal, "Created or Upserted safety fee for source: $source");
        }

        if ($source === 'PWO') {
            require_once __DIR__ . '/../../include/payment_flow.php';
            clms_sync_fee_master_to_setting($conn, (int)($_SESSION['user_id'] ?? 0));
        }

        feeSettingJson([
            'success' => true,
            'message' => 'Safety fee saved successfully.'
        ]);
    }

} catch (InvalidArgumentException $e) {
    feeSettingJson(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('[UPDATE_FEE_SETTING] ' . $e->getMessage());
    feeSettingJson(['success' => false, 'message' => 'Server error while updating safety fee setting.'], 500);
}

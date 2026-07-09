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
    $safeColumn = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$safeTable` LIKE '$safeColumn'");
    return $result && mysqli_num_rows($result) > 0;
}

function contractor_status_ensure_column($conn, $table, $column, $definition) {
    if (contractor_status_column_exists($conn, $table, $column)) {
        return;
    }
    $safeTable = str_replace('`', '``', $table);
    $safeColumn = str_replace('`', '``', $column);
    if (!@mysqli_query($conn, "ALTER TABLE `$safeTable` ADD COLUMN `$safeColumn` $definition")) {
        error_log("[update_contractor_status_v2] Failed adding column {$table}.{$column}: " . mysqli_error($conn));
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

    // Process pending edit request if it exists for this contractor
    $edit_request = db_single($conn, "SELECT * FROM contractor_edit_requests WHERE contractor_id = ? AND status = 'pending' ORDER BY id DESC LIMIT 1", 'i', [$id]);
    $is_edit_request = false;
    if ($edit_request) {
        $is_edit_request = true;
        if ($status === 'approved') {
            $data = json_decode($edit_request['requested_data_json'], true);
            if (is_array($data)) {
                // Update contractors table with requested values
                db_execute($conn, "UPDATE contractors SET 
                    mobile=?, vendor_mob2=?, email=?, address=?, work_awarding_department=?, 
                    epf_registered=?, epf_code=?, esi_registered=?, esi_code=?, epf_esi_exemption_reason=?,
                    wage_category=?, wage_declaration=?, ecp_covered=?, ecp_details_json=?, license_details_json=?,
                    ecp_number=?, ecp_valid_from=?, ecp_valid_to=?, workers_ecp=?, workers_proposed_to_be_engaged=?, 
                    workers_proposed=?, worker_category=?, license_no=?, license_issued=?, issued_date=?, 
                    expiry_date=?, license_file=?, labour_license_appl_no=?, labour_identification_no=?, contact_person=?, 
                    remarks=? WHERE id=?",
                    'ssssssssssssssssssiiissssssssssi',
                    [
                        $data['mobile'] ?? null, $data['vendor_mob2'] ?? null, $data['email'] ?? null, $data['address'] ?? null, $data['work_awarding_department'] ?? null,
                        $data['epf_registered'] ?? 'NO', $data['epf_code'] ?? null, $data['esi_registered'] ?? 'NO', $data['esi_code'] ?? null, $data['epf_esi_exemption_reason'] ?? null,
                        $data['wage_category'] ?? null, $data['wage_declaration'] ?? null, $data['ecp_covered'] ?? 'NO', $data['ecp_details_json'] ?? null, $data['license_details_json'] ?? null,
                        $data['ecp_number'] ?? null, $data['ecp_valid_from'] ?? null, $data['ecp_valid_to'] ?? null, intval($data['workers_ecp'] ?? 0), intval($data['workers_proposed_to_be_engaged'] ?? 0),
                        intval($data['workers_proposed_to_be_engaged'] ?? 0), $data['worker_category'] ?? null, $data['license_no'] ?? null, $data['license_issued'] ?? null, $data['issued_date'] ?? null,
                        $data['expiry_date'] ?? null, $data['license_file'] ?? null, $data['labour_license_appl_no'] ?? null, $data['labour_identification_no'] ?? null, $data['contact_person'] ?? null,
                        $data['remarks'] ?? null, $id
                    ]
                );

                // Update annexure2a table with requested values
                db_execute($conn, "UPDATE annexure2a SET 
                    mobile=?, vendor_mob2=?, email=?, office_address=?, 
                    epf_registered=?, epf_code=?, esi_registered=?, esic_code=?, epf_esi_exemption_reason=?,
                    project_name=?, wage_category=?, wage_declaration=?, ecp_covered=?, ecp_details_json=?, license_details_json=?,
                    ecp_number=?, ecp_valid_from=?, ecp_valid_to=?, 
                    workers_ecp=?, workers_proposed_to_be_engaged=?, worker_category=?, 
                    license_no=?, license_issued=?, issued_date=?, expiry_date=?, 
                    klwf_registration_no=?, labour_license_appl_no=?, labour_identification_no=?, contact_person=?, remarks=?
                    WHERE contractor_id=?",
                    'ssssssssssssssssssiissssssssssi',
                    [
                        $data['mobile'] ?? null, $data['vendor_mob2'] ?? null, $data['email'] ?? null, $data['address'] ?? null,
                        $data['epf_registered'] ?? 'NO', $data['epf_code'] ?? null, $data['esi_registered'] ?? 'NO', $data['esi_code'] ?? null, $data['epf_esi_exemption_reason'] ?? null,
                        $data['work_awarding_department'] ?? null, $data['wage_category'] ?? null, $data['wage_declaration'] ?? null, $data['ecp_covered'] ?? 'NO', $data['ecp_details_json'] ?? null, $data['license_details_json'] ?? null,
                        $data['ecp_number'] ?? null, $data['ecp_valid_from'] ?? null, $data['ecp_valid_to'] ?? null,
                        intval($data['workers_ecp'] ?? 0), intval($data['workers_proposed_to_be_engaged'] ?? 0), $data['worker_category'] ?? null,
                        $data['license_no'] ?? null, $data['license_issued'] ?? null, $data['issued_date'] ?? null, $data['expiry_date'] ?? null,
                        $data['license_issued'] ?? null, $data['labour_license_appl_no'] ?? null, $data['labour_identification_no'] ?? null, $data['contact_person'] ?? null, $data['remarks'] ?? null,
                        $id
                    ]
                );

                // Sync PO/PWO/SO selections if present in data
                if (isset($data['selected_pos'])) {
                    $conn->query("DELETE FROM contractor_po_selection WHERE contractor_id = $id");
                    $pos = json_decode($data['selected_pos'], true);
                    if (is_array($pos)) {
                        foreach ($pos as $po) {
                            db_execute($conn, "INSERT INTO contractor_po_selection (contractor_id, po_number) VALUES (?,?)", 'is', [$id, $po]);
                        }
                    }
                    $pos_str = is_array($pos) ? implode(',', array_filter($pos)) : '';
                    db_execute($conn, "UPDATE contractors SET po_number = ? WHERE id = ?", 'si', [$pos_str, $id]);
                }
                if (isset($data['selected_pwos'])) {
                    $conn->query("DELETE FROM contractor_pwo_selection WHERE contractor_id = $id");
                    $pwos = json_decode($data['selected_pwos'], true);
                    if (is_array($pwos)) {
                        foreach ($pwos as $pwo) {
                            db_execute($conn, "INSERT INTO contractor_pwo_selection (contractor_id, pwo_number) VALUES (?,?)", 'is', [$id, $pwo]);
                        }
                    }
                    $pwos_str = is_array($pwos) ? implode(',', array_filter($pwos)) : '';
                    db_execute($conn, "UPDATE contractors SET pwo_number = ? WHERE id = ?", 'si', [$pwos_str, $id]);
                }
                if (isset($data['selected_sales'])) {
                    $conn->query("DELETE FROM contractor_so_selection WHERE contractor_id = $id");
                    $sos = json_decode($data['selected_sales'], true);
                    if (is_array($sos)) {
                        foreach ($sos as $so) {
                            db_execute($conn, "INSERT INTO contractor_so_selection (contractor_id, sale_order_no) VALUES (?,?)", 'is', [$id, $so]);
                        }
                    }
                    $sos_str = is_array($sos) ? implode(',', array_filter($sos)) : '';
                    db_execute($conn, "UPDATE contractors SET sales_order_number = ? WHERE id = ?", 'si', [$sos_str, $id]);
                }
            }

            db_execute($conn, "UPDATE contractor_edit_requests SET status = 'approved', action_by = ?, action_at = NOW(), remarks = ? WHERE id = ?", 'isi', [$updated_by, $reason, $edit_request['id']]);
        } else {
            // Rejected or other action
            db_execute($conn, "UPDATE contractor_edit_requests SET status = 'rejected', action_by = ?, action_at = NOW(), remarks = ? WHERE id = ?", 'isi', [$updated_by, $reason, $edit_request['id']]);
            
            // Set the target status to approved so contractor status remains approved
            $status = 'approved';
        }
    }

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

    if (!$is_edit_request) {
        db_execute(
            $conn,
            "INSERT INTO contractor_status_history (contractor_id, status, reason, pdf_path, action_by) VALUES (?,?,?,?,?)",
            'isssi',
            [$id, $status, $reason, $approval_pdf, $updated_by]
        );
    }

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

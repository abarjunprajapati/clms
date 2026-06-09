<?php
require_once __DIR__ . '/../../include/auth.php';
include __DIR__ . '/../../include/config.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_POST['id']) || !isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$id = intval($_POST['id']);
$status = $_POST['status'];
$reason = trim($_POST['reason'] ?? '');

// Fetch contractor_id for this annexure row so documents can be linked correctly
$contractor_id = null;
$cstmt = $conn->prepare("SELECT contractor_id FROM annexure2a WHERE id = ? LIMIT 1");
if ($cstmt) {
    $cstmt->bind_param('i', $id);
    $cstmt->execute();
    $cres = $cstmt->get_result();
    $crow = $cres ? $cres->fetch_assoc() : null;
    $cstmt->close();
    if ($crow) $contractor_id = intval($crow['contractor_id']);
}

$allowed = ['approved', 'rejected', 'correction_required', 'hold', 'block'];
if (!in_array($status, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

$uploaded_path = null;
if (!empty($_FILES['approval_pdf']['tmp_name']) && $_FILES['approval_pdf']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../../uploads/contractor_docs/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $orig = basename($_FILES['approval_pdf']['name']);
    $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $orig);
    $filename = 'approval_' . $id . '_' . time() . '_' . $safe;
    $target = $upload_dir . $filename;
    if (move_uploaded_file($_FILES['approval_pdf']['tmp_name'], $target)) {
        $uploaded_path = '../../uploads/contractor_docs/' . $filename; // path stored in DB for UI
        $uploaded_basename = $filename;
    }
}

try {
    $stmt = $conn->prepare("UPDATE annexure2a SET workflow_status = ?, remarks = ?, updated_at = NOW() WHERE id = ?");
    if ($stmt === false) throw new Exception($conn->error);
    $stmt->bind_param('ssi', $status, $reason, $id);
    $stmt->execute();

    // Record a history entry for this action (create table if missing)
    if ($contractor_id) {
        $create_history_sql = "CREATE TABLE IF NOT EXISTS contractor_annexure2a_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            annexure2a_id INT NULL,
            contractor_id INT NULL,
            status VARCHAR(50) NULL,
            reason TEXT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        @clms_db_query($conn, $create_history_sql);

        $hstmt = $conn->prepare("INSERT INTO contractor_annexure2a_history (annexure2a_id, contractor_id, status, reason) VALUES (?, ?, ?, ?)");
        if ($hstmt) {
            $hstmt->bind_param('iiss', $id, $contractor_id, $status, $reason);
            $hstmt->execute();
            $hstmt->close();
        }

        // Update central application_workflow table to reflect this action
        $app_no_row = $conn->prepare("SELECT application_no FROM contractors WHERE id = ? LIMIT 1");
        if ($app_no_row) {
            $app_no_row->bind_param('i', $contractor_id);
            $app_no_row->execute();
            $res = $app_no_row->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $app_no_row->close();
            if ($row && !empty($row['application_no'])) {
                $application_no = $row['application_no'];
                $wf = $conn->prepare("INSERT INTO application_workflow (application_id, contractor_id, current_stage, overall_status) VALUES (?, ?, '2a_review', ?) ON DUPLICATE KEY UPDATE current_stage = '2a_review', overall_status = ?, updated_at = NOW()");
                if ($wf) {
                    $wf->bind_param('siss', $application_no, $contractor_id, $status, $status);
                    $wf->execute();
                    $wf->close();
                }
            }
        }
    }

    // If an approval PDF was uploaded, record it in contractor_documents so UI can show it
    if ($uploaded_path) {
        // Determine doc_type for approval
        $doc_type = 'welfare_approval_letter';

        $check = $conn->prepare("SELECT id FROM contractor_documents WHERE contractor_id = ? AND doc_type = ?");
        if ($check) {
            $check->bind_param('is', $contractor_id, $doc_type);
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();
            $check->close();

            if ($existing) {
                $upd = $conn->prepare("UPDATE contractor_documents SET file_path = ?, original_name = ?, status = 'approved', remarks = ?, uploaded_at = NOW(), updated_at = NOW() WHERE id = ?");
                if ($upd) {
                    $upd->bind_param('sssi', $uploaded_path, $_FILES['approval_pdf']['name'], $reason, $existing['id']);
                    $upd->execute();
                    $upd->close();
                }
            } else {
                $ins = $conn->prepare("INSERT INTO contractor_documents (contractor_id, doc_type, file_path, original_name, status, remarks, uploaded_at) VALUES (?, ?, ?, ?, 'approved', ?, NOW())");
                if ($ins) {
                    $ins->bind_param('issss', $contractor_id, $doc_type, $uploaded_path, $_FILES['approval_pdf']['name'], $reason);
                    $ins->execute();
                    $ins->close();
                }
            }
        }
    }

    // Also update central contractors.status so contractor-side UI reflects change
    if ($contractor_id) {
        $updated_by = $_SESSION['user_id'] ?? 0;
        $upd = $conn->prepare("UPDATE contractors SET status = ?, approval_reason = ?, last_action_by = ?, last_action_at = NOW() WHERE id = ?");
        if ($upd) {
            $upd->bind_param('ssii', $status, $reason, $updated_by, $contractor_id);
            $upd->execute();
            $upd->close();
        }

        // If approved, try to activate linked user account (by vendor_code)
        if ($status === 'approved') {
            $cdata = $conn->prepare("SELECT vendor_code, user_id FROM contractors WHERE id = ? LIMIT 1");
            if ($cdata) {
                $cdata->bind_param('i', $contractor_id);
                $cdata->execute();
                $cres = $cdata->get_result();
                $crow = $cres ? $cres->fetch_assoc() : null;
                $cdata->close();
                if ($crow && !empty($crow['vendor_code'])) {
                    $vc = $crow['vendor_code'];
                    $conn->query("UPDATE users SET status='active' WHERE contractor_id = '" . $conn->real_escape_string($vc) . "'");
                    // link user_id to contractor if user exists
                    $urow = db_single($conn, "SELECT id FROM users WHERE contractor_id = ?", 's', [$vc]);
                    if ($urow) {
                        db_execute($conn, "UPDATE contractors SET user_id = ? WHERE id = ?", 'ii', [$urow['id'], $contractor_id]);
                    }
                }
            }
        }

        // Record contractor status history
        $conn->query("CREATE TABLE IF NOT EXISTS contractor_status_history (id INT AUTO_INCREMENT PRIMARY KEY, contractor_id INT, status VARCHAR(50), reason TEXT, pdf_path VARCHAR(255), action_by INT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
        $h = $conn->prepare("INSERT INTO contractor_status_history (contractor_id, status, reason, action_by) VALUES (?, ?, ?, ?)");
        if ($h) {
            $h->bind_param('issi', $contractor_id, $status, $reason, $updated_by);
            $h->execute();
            $h->close();
        }
    }

    $status_messages = [
        'approved' => 'Contractor approved successfully',
        'rejected' => 'Contractor rejected successfully',
        'correction_required' => 'Correction requested from contractor',
        'hold' => 'Contractor application placed on hold',
        'block' => 'Contractor blocked'
    ];
    $msg = $status_messages[$status] ?? 'Action completed successfully';

    echo json_encode([
        'success' => true,
        'message' => $msg
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

exit;

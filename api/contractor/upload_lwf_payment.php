<?php
// api/contractor/upload_lwf_payment.php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/compliance_schema.php';

header('Content-Type: application/json');

function respondJSON($success, $message, $data = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['contractor', 'welfare_user', 'welfare_admin', 'welfare', 'admin', 'super_admin'])) {
    respondJSON(false, 'Unauthorized access.');
}

ensureComplianceSchema($conn);

$c_id       = (int)($_POST['contractor_id'] ?? 0);
$half       = trim($_POST['period_half'] ?? '');
$year       = (int)($_POST['period_year'] ?? 0);
$paidAmt    = (float)($_POST['paid_amount'] ?? 0);
$dueAmt     = (float)($_POST['due_amount'] ?? 0);
$declaration = (int)($_POST['declaration'] ?? 0);

if (!$c_id || !in_array($half, ['first', 'second']) || !$year || $paidAmt <= 0) {
    respondJSON(false, 'Please fill all required fields correctly.');
}

if (!isset($_FILES['payment_proof']) || $_FILES['payment_proof']['error'] !== 0) {
    respondJSON(false, 'Please upload a payment proof file.');
}

// Save file
$file      = $_FILES['payment_proof'];
$ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed   = ['pdf', 'jpg', 'jpeg', 'png'];
if (!in_array($ext, $allowed)) {
    respondJSON(false, 'Only PDF, JPG, PNG files are allowed.');
}

$uploadDir = __DIR__ . '/../../uploads/compliance/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

$filename  = 'lwf_' . $c_id . '_' . $half . '_' . $year . '_' . time() . '.' . $ext;
$dest      = $uploadDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    respondJSON(false, 'File upload failed. Please try again.');
}

$status = ($paidAmt >= $dueAmt) ? 'complied' : 'mismatch';

// Upsert compliance_lwf record
$existing = db_single($conn, "SELECT id FROM compliance_lwf WHERE contractor_id = ? AND period_half = ? AND period_year = ?", 'isi', [$c_id, $half, $year]);

if ($existing) {
    $conn->query("UPDATE compliance_lwf SET 
        paid_amount = " . $conn->real_escape_string($paidAmt) . ",
        due_amount = " . $conn->real_escape_string($dueAmt) . ",
        payment_proof_path = '" . $conn->real_escape_string($filename) . "',
        declaration_accepted = " . $declaration . ",
        status = '" . $conn->real_escape_string($status) . "',
        uploaded_at = NOW()
        WHERE id = " . (int)$existing['id']);
} else {
    // Get worker count and rate
    $latestRate = db_single($conn, "SELECT * FROM lwf_rate_master ORDER BY effective_from DESC LIMIT 1");
    $empC  = (float)($latestRate['employee_contribution'] ?? 45);
    $emplC = (float)($latestRate['employer_contribution'] ?? 45);

    $conn->query("INSERT INTO compliance_lwf 
        (contractor_id, period_half, period_year, employee_contribution, employer_contribution, due_amount, paid_amount, payment_proof_path, declaration_accepted, status, uploaded_at)
        VALUES (
            $c_id,
            '" . $conn->real_escape_string($half) . "',
            $year,
            $empC, $emplC,
            " . $conn->real_escape_string($dueAmt) . ",
            " . $conn->real_escape_string($paidAmt) . ",
            '" . $conn->real_escape_string($filename) . "',
            $declaration,
            '" . $conn->real_escape_string($status) . "',
            NOW()
        )");
}

respondJSON(true, 'Payment proof uploaded successfully.', ['status' => $status]);
?>

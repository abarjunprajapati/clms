<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/compliance_schema.php';

header('Content-Type: application/json; charset=utf-8');

function certificate_response($success, $message, $data = []) {
    echo json_encode(['success' => $success, 'message' => $message] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}

function certificate_clean($value) {
    return trim((string)($value ?? ''));
}

function certificate_error(&$errors, $field, $message) {
    $errors[] = ['field' => $field, 'message' => $message];
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        certificate_response(false, 'Only POST requests are allowed');
    }

    ensureComplianceSchema($conn);

    $userId = (int)($_SESSION['user_id'] ?? 0);
    if (!$userId) {
        certificate_response(false, 'User not authenticated');
    }

    $contractor = db_single($conn, "SELECT id FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [$userId]);
    if (!$contractor) {
        certificate_response(false, 'Contractor registration not found');
    }
    $contractorId = (int)$contractor['id'];

    $headerFields = [
        'vendor_code' => 'VENDOR CODE',
        'project' => 'PROJECT',
        'contractor_name' => 'NAME OF CONTRACTOR',
        'work_order_no' => 'WORK ORDER NO.',
        'certificate_date' => 'DATE',
        'period_from' => 'PERIOD OF WORK FROM',
        'period_to' => 'PERIOD OF WORK TO',
        'total_workmen' => 'TOTAL NUMBER OF WORKMEN',
    ];

    $data = [];
    $errors = [];
    foreach ($headerFields as $field => $label) {
        $data[$field] = certificate_clean($_POST[$field] ?? '');
        if ($data[$field] === '') {
            certificate_error($errors, $field, $label . ' is required.');
        }
    }

    if ($data['total_workmen'] !== '' && !preg_match('/^[0-9]+$/', $data['total_workmen'])) {
        certificate_error($errors, 'total_workmen', 'TOTAL NUMBER OF WORKMEN must be a number.');
    }

    $rows = [
        'epfo_registered' => 'Whether establishment is registered under EPFO (Auto generated) If YES EPF Code, If NO Reason',
        'pf_contribution_due' => 'PF contributions remitted within prescribed due dates',
        'esic_registered' => 'Whether establishment is registered under ESIC (Auto generated) If YES ESIC Code, If NO Reason',
        'esic_contribution_due' => 'ESIC contributions remitted within prescribed due dates',
        'emp_comp_policy' => 'Whether Emp.Comp. Policy exist (In case not covered under ESIC) If YES, Policy No, If NO Reason Policy Valid from to',
        'gratuity_maternity' => 'Whether Payment of Gratuity/Maternity (Wherever applicable) processed',
        'wages_paid_time' => 'Whether wages paid within time limit as notified by Central/State Govt. as per Skilled/Semi Skilled/Unskilled',
        'wage_slip_issued' => 'Whether Wage Slip issued to all workers',
        'bank_digital_payment' => 'Whether payment made through Bank/Digital mode',
        'wages_by_7th' => 'Payment of wages by 7th of each month',
        'attendance_records' => 'Attendance records maintained',
        'bonus_paid' => 'Whether statutory Bonus paid to all workers',
        'labour_license' => 'Valid Labour License obtained (If Number of workers 50 and above) If YES License No.',
        'ppe_provided' => 'PPE provided to all workers',
        'safety_training' => 'Safety Training/Toobox Talk conducted regularly',
        'accident_register' => 'Accident Register maintained',
        'welfare_facilities' => 'Whether compliance relating to drinking water, washing facilities, toilets, rest rooms, First Aid, canteen facilities (If Number of workers 100 and above) provided',
        'annual_medical' => 'Whether Annual Medical Checkup carried out for workers of 40 yrs age and above',
        'inter_state_migrant' => 'Whether any Inter State Migrant engaged',
        'migrant_requirements' => 'If Yes, Whether statutory requirements for Migrant workers provided (Annual Travel Allowances)',
        'appointment_letters' => 'Appointment Letters issued to all workers',
        'service_records' => 'Service records maintained properly',
        'industrial_dispute' => 'Any illegal strike/lockout/Industrial Dispute pending',
        'klwf_registration' => 'KLWF Registration No: (If no, reason)',
        'klwf_deposit' => 'Deposit of deductions paid on or before 10th January and 10th July of every year',
    ];

    $answers = $_POST['answers'] ?? [];
    $details = $_POST['details'] ?? [];
    $formRows = [];

    foreach ($rows as $key => $label) {
        $answer = strtoupper(certificate_clean($answers[$key] ?? ''));
        if (!in_array($answer, ['YES', 'NO'], true)) {
            certificate_error($errors, 'answers[' . $key . ']', $label . ': YES or NO is required.');
        }
        $formRows[$key] = [
            'label' => $label,
            'answer' => $answer,
            'details' => isset($details[$key]) && is_array($details[$key]) ? array_map('certificate_clean', $details[$key]) : [],
        ];
    }

    if (($formRows['epfo_registered']['answer'] ?? '') === 'YES' && certificate_clean($details['epfo_registered']['epf_code'] ?? '') === '') {
        certificate_error($errors, 'details[epfo_registered][epf_code]', 'EPF Code is required when EPFO is YES.');
    }
    if (($formRows['epfo_registered']['answer'] ?? '') === 'NO' && certificate_clean($details['epfo_registered']['reason'] ?? '') === '') {
        certificate_error($errors, 'details[epfo_registered][reason]', 'Reason is required when EPFO is NO.');
    }
    if (($formRows['esic_registered']['answer'] ?? '') === 'YES' && certificate_clean($details['esic_registered']['esic_code'] ?? '') === '') {
        certificate_error($errors, 'details[esic_registered][esic_code]', 'ESIC Code is required when ESIC is YES.');
    }
    if (($formRows['esic_registered']['answer'] ?? '') === 'NO' && certificate_clean($details['esic_registered']['reason'] ?? '') === '') {
        certificate_error($errors, 'details[esic_registered][reason]', 'Reason is required when ESIC is NO.');
    }
    if (($formRows['emp_comp_policy']['answer'] ?? '') === 'YES') {
        foreach (['policy_no' => 'Policy No', 'valid_from' => 'Policy Valid from', 'valid_to' => 'Policy Valid to'] as $field => $label) {
            if (certificate_clean($details['emp_comp_policy'][$field] ?? '') === '') {
                certificate_error($errors, 'details[emp_comp_policy][' . $field . ']', $label . ' is required when Emp.Comp. Policy is YES.');
            }
        }
    }
    if (($formRows['emp_comp_policy']['answer'] ?? '') === 'NO' && certificate_clean($details['emp_comp_policy']['reason'] ?? '') === '') {
        certificate_error($errors, 'details[emp_comp_policy][reason]', 'Reason is required when Emp.Comp. Policy is NO.');
    }
    if (($formRows['labour_license']['answer'] ?? '') === 'YES' && certificate_clean($details['labour_license']['license_no'] ?? '') === '') {
        certificate_error($errors, 'details[labour_license][license_no]', 'License No. is required when Valid Labour License is YES.');
    }
    if (($formRows['klwf_registration']['answer'] ?? '') === 'YES' && certificate_clean($details['klwf_registration']['registration_no'] ?? '') === '') {
        certificate_error($errors, 'details[klwf_registration][registration_no]', 'KLWF Registration No. is required when KLWF Registration is YES.');
    }
    if (($formRows['klwf_registration']['answer'] ?? '') === 'NO' && certificate_clean($details['klwf_registration']['reason'] ?? '') === '') {
        certificate_error($errors, 'details[klwf_registration][reason]', 'Reason is required when KLWF Registration is NO.');
    }

    $declarationAccepted = isset($_POST['declaration_accepted']) ? 1 : 0;
    if (!$declarationAccepted) {
        certificate_error($errors, 'declaration_accepted', 'Contractor certification is required.');
    }

    if (!empty($errors)) {
        certificate_response(false, 'Validation failed', ['errors' => $errors]);
    }

    $payload = json_encode([
        'header' => $data,
        'rows' => $formRows,
        'declaration_accepted' => $declarationAccepted,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    db_execute(
        $conn,
        "INSERT INTO compliance_certificates (contractor_id, certificate_date, period_from, period_to, total_workmen, form_data, created_by, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
        'isssisi',
        [$contractorId, $data['certificate_date'], $data['period_from'], $data['period_to'], (int)$data['total_workmen'], $payload, $userId]
    );

    certificate_response(true, 'Certificate of Compliance submitted successfully');
} catch (Throwable $e) {
    error_log('[save_compliance_certificate] ' . $e->getMessage());
    certificate_response(false, 'Server error while saving Certificate of Compliance.', ['error' => $e->getMessage()]);
}
?>

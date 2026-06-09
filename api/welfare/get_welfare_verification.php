<?php
session_start();
require_once __DIR__ . '/../json_error_handler.php';
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../workflow_helpers.php';

header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'data' => [
        'sapContractor' => new stdClass(),
        'verification' => new stdClass(),
        'checklist' => [],
        'documents' => [],
        'remarks' => []
    ]
];

try {
    workflow_ensure_tables($conn);

    $app_id = trim($_GET['application_id'] ?? '');
    if ($app_id === '') {
        http_response_code(400);
        throw new Exception('application_id parameter required');
    }

    workflow_seed_application($conn, $app_id);

    $stmt = $conn->prepare("
        SELECT
            w.application_id,
            w.contractor_id,
            w.current_stage,
            w.welfare_status,
            w.pio_status,
            w.final_status,
            w.overall_status,
            w.remarks AS workflow_remarks,
            w.updated_at,
            a.contractor_name,
            a.contractor_id AS contractor_code,
            a.category_work,
            a.created_at,
            a.status AS legacy_status
        FROM application_workflow w
        LEFT JOIN annexure2a a ON a.application_id = w.application_id
        WHERE w.application_id = ?
        LIMIT 1
    ");
    if (!$stmt) throw new Exception('Main query prepare failed: ' . $conn->error);
    $stmt->bind_param('s', $app_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception('Workflow record not found');
    }

    $response['success'] = true;
    $response['data']['sapContractor'] = [
        'name' => $row['contractor_name'] ?? 'Contractor',
        'code' => $row['contractor_code'] ?? '',
        'project' => $row['category_work'] ?? 'Contract Work',
        'work_order' => $row['application_id'],
        'contract_value' => 'N/A',
        'status' => $row['overall_status'] ?? 'pending'
    ];
    $response['data']['verification'] = [
        'id' => $row['application_id'],
        'contractor_id' => $row['contractor_id'] ?? '',
        'application_id' => $row['application_id'],
        'status' => $row['welfare_status'] ?? 'pending',
        'current_stage' => $row['current_stage'] ?? 'welfare',
        'pio_status' => $row['pio_status'] ?? 'pending',
        'final_status' => $row['final_status'] ?? 'pending',
        'overall_status' => $row['overall_status'] ?? 'pending',
        'remarks' => $row['workflow_remarks'] ?? '',
        'verified_by' => '',
        'verified_date' => $row['updated_at'] ?? ''
    ];

    $tables = [];
    $res = $conn->query('SHOW TABLES');
    while ($res && ($tableRow = $res->fetch_array())) {
        $tables[$tableRow[0]] = true;
    }

    if (isset($tables['verification_checklist'])) {
        $stmt = $conn->prepare("SELECT * FROM verification_checklist WHERE application_id = ? ORDER BY id ASC");
        $stmt->bind_param('s', $app_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($item = $result->fetch_assoc()) $response['data']['checklist'][] = $item;
        $stmt->close();
    }

    if (isset($tables['documents'])) {
        $stmt = $conn->prepare("SELECT * FROM documents WHERE application_id = ? ORDER BY id DESC");
        $stmt->bind_param('s', $app_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($doc = $result->fetch_assoc()) $response['data']['documents'][] = $doc;
        $stmt->close();
    }

    if (isset($tables['remarks_history'])) {
        $stmt = $conn->prepare("SELECT * FROM remarks_history WHERE application_id = ? ORDER BY id DESC");
        $stmt->bind_param('s', $app_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($remark = $result->fetch_assoc()) $response['data']['remarks'][] = $remark;
        $stmt->close();
    }

    if (count($response['data']['checklist']) === 0) {
        $response['data']['checklist'] = [
            ['id' => 'wf-1', 'item_name' => 'Application data verified', 'is_done' => $row['welfare_status'] === 'approved'],
            ['id' => 'wf-2', 'item_name' => 'Workflow record available', 'is_done' => true]
        ];
    }
} catch (Throwable $e) {
    $response['success'] = false;
    $response['data'] = null;
    $response['error'] = $e->getMessage();
}

jsonErrorFlush();
echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>


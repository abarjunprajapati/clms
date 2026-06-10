<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/gate_pass_document_master.php';

header('Content-Type: application/json; charset=utf-8');

function gateDocJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) gateDocJson(['success' => false, 'message' => 'Invalid request payload.'], 400);

    $id = clms_upsert_gate_pass_document_master($conn, $data);
    gateDocJson(['success' => true, 'message' => 'Gate pass document master saved successfully.', 'id' => $id]);
} catch (InvalidArgumentException $e) {
    gateDocJson(['success' => false, 'message' => $e->getMessage()], 400);
} catch (Throwable $e) {
    error_log('[UPDATE_GATE_PASS_DOCUMENT_MASTER] ' . $e->getMessage());
    gateDocJson(['success' => false, 'message' => 'Gate pass document master update failed on server.'], 500);
}

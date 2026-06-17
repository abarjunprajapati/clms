<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/payment_flow.php';

header('Content-Type: application/json; charset=utf-8');

function manageQrJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $data = $_POST;
    }
    
    $action = trim((string)($data['action'] ?? ''));
    $qrId = (int)($data['qr_id'] ?? 0);
    $userId = (int)($_SESSION['user_id'] ?? 0);

    if ($qrId <= 0) {
        manageQrJson(['success' => false, 'message' => 'Invalid QR ID.'], 400);
    }

    if ($action === 'activate') {
        $ok = clms_activate_qr($conn, $qrId, $userId);
        if ($ok) {
            manageQrJson(['success' => true, 'message' => 'QR code activated successfully.']);
        } else {
            manageQrJson(['success' => false, 'message' => 'Failed to activate QR code.'], 500);
        }
    } elseif ($action === 'delete') {
        $ok = clms_delete_qr_history($conn, $qrId);
        if ($ok) {
            manageQrJson(['success' => true, 'message' => 'QR code history deleted successfully.']);
        } else {
            manageQrJson(['success' => false, 'message' => 'Failed to delete QR code. Active QR cannot be deleted.'], 400);
        }
    } else {
        manageQrJson(['success' => false, 'message' => 'Invalid action.'], 400);
    }
} catch (Throwable $e) {
    error_log('[MANAGE_QR_HISTORY] ' . $e->getMessage());
    manageQrJson(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
}

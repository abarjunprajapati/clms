<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/payment_flow.php';

header('Content-Type: application/json; charset=utf-8');

function paymentSettingsJson($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    clms_ensure_payment_flow($conn);
    $userId = (int)($_SESSION['user_id'] ?? 0);
    $savedQrPath = clms_payment_setting($conn, 'payment_demo_qr_path', '');
    $provider = trim((string)($_POST['payment_gateway_provider'] ?? 'demo_qr'));
    $provider = $provider !== '' ? $provider : 'demo_qr';
    clms_set_payment_setting($conn, 'payment_gateway_provider', $provider, $userId);
    clms_set_payment_setting($conn, 'payment_demo_merchant_name', $_POST['payment_demo_merchant_name'] ?? 'CLMS Safety Training', $userId);
    clms_set_payment_setting($conn, 'payment_demo_upi_id', $_POST['payment_demo_upi_id'] ?? 'clms-demo@upi', $userId);
    clms_set_payment_setting($conn, 'training_fee_per_worker', $_POST['training_fee_per_worker'] ?? '500', $userId);
    clms_set_payment_setting($conn, 'training_payment_gst_percent', $_POST['training_payment_gst_percent'] ?? '18', $userId);
    clms_set_payment_setting($conn, 'training_payment_link_valid_hours', $_POST['training_payment_link_valid_hours'] ?? '72', $userId);

    if (!empty($_FILES['payment_qr']) && ($_FILES['payment_qr']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['payment_qr']['name'] ?? '', PATHINFO_EXTENSION));
        if (!in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true)) {
            paymentSettingsJson(['success' => false, 'message' => 'QR image must be PNG/JPG/JPEG/WEBP.'], 400);
        }
        $dir = __DIR__ . '/../../uploads/payment_qr/';
        if (!is_dir($dir) && !@mkdir($dir, 0777, true)) {
            paymentSettingsJson(['success' => false, 'message' => 'QR upload folder create nahi ho pa raha. uploads/payment_qr permission check karein.'], 500);
        }
        if (!is_writable($dir)) {
            @chmod($dir, 0777);
        }
        if (!is_writable($dir)) {
            paymentSettingsJson(['success' => false, 'message' => 'QR upload folder writable nahi hai. uploads/payment_qr permission check karein.'], 500);
        }
        $filename = 'demo_qr_' . date('YmdHis') . '_' . random_int(1000, 9999) . '.' . $ext;
        if (!move_uploaded_file($_FILES['payment_qr']['tmp_name'], $dir . $filename)) {
            paymentSettingsJson(['success' => false, 'message' => 'QR upload failed.'], 500);
        }
        $savedQrPath = 'uploads/payment_qr/' . $filename;
        clms_set_payment_setting($conn, 'payment_demo_qr_path', $savedQrPath, $userId);
    }

    $demo = clms_demo_payment_details($conn);
    paymentSettingsJson([
        'success' => true,
        'message' => 'Payment gateway settings saved.',
        'qr_path' => $savedQrPath,
        'qr_url' => $demo['qr_url'] ?? '',
    ]);
} catch (Throwable $e) {
    error_log('[UPDATE_PAYMENT_GATEWAY] ' . $e->getMessage());
    paymentSettingsJson(['success' => false, 'message' => 'Payment settings update failed.'], 500);
}

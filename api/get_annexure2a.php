<?php
require_once __DIR__ . '/../include/auth_middleware.php';

require_role(['contractor', 'welfare', 'admin']);
header('Content-Type: application/json');

$user_id = $currentUserId;
error_log("Annexure2A user_id=" . $user_id);


$input = json_decode(file_get_contents('php://input'), true) ?: $_GET;
$app_id = trim($input['application_id'] ?? $_GET['app_id'] ?? '');
$ref_id = trim($input['ref_id'] ?? $_GET['ref'] ?? '');
error_log("GET Annexure2A app_id=" . ($app_id ?? 'null'));

// Priority fetch: application_id (id) > ref_id > contractor_id fallback
$sql = null;
$params = null;
$types = null;

if (!empty($app_id)) {
    $sql = "SELECT * FROM annexure2a WHERE id = ?";
    $params = [$app_id];
    $types = 's';
    error_log("Using application_id=$app_id");
} elseif (!empty($ref_id)) {
    $sql = "SELECT * FROM annexure2a WHERE ref_id = ?";
    $params = [$ref_id];
    $types = 's';
    error_log("Using ref_id=$ref_id");
} else {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'contractor' && !empty($_SESSION['contractor_id'])) {
        $sql = "SELECT * FROM annexure2a WHERE contractor_id = ? ORDER BY id DESC LIMIT 1";
        $params = [$_SESSION['contractor_id']];
        $types = 'i';
        error_log("Fallback to contractor_id=" . $_SESSION['contractor_id']);
    } else {
        $sql = "SELECT * FROM annexure2a ORDER BY id DESC LIMIT 1";
        $params = [];
        $types = '';
        error_log("Fallback to global latest");
    }
}

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => 'Prepare failed: ' . $conn->error]);
    exit;
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
error_log("Annexure2A GET DEBUG: fetched record id=" . $row['id'] . ", ref_id=" . $row['ref_id']);
    // Format dates
    $row['created_date'] = date('d M Y', strtotime($row['created_at']));
    $row['updated_date'] = $row['updated_at'] ? date('d M Y H:i', strtotime($row['updated_at'])) : null;
    
    echo json_encode([
        'success' => true,
        'data' => $row,
        'application_id' => $row['id'],
        'message' => 'Application loaded'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'data' => null,
        'application_id' => null
    ]);
}

$stmt->close();
?>



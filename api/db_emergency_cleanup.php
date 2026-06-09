<?php
/**
 * Temporary emergency endpoint for clearing stuck MySQL sleeping connections.
 * Upload, run once, then delete this file after live DB access is restored.
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../include/config.php';

if (($conn->driver ?? 'mysql') === 'sqlsrv') {
    echo json_encode([
        'success' => false,
        'message' => 'This emergency cleanup endpoint is MySQL-only and is not used for SQL Server mode.'
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$expectedToken = getenv('CLMS_DB_CLEANUP_TOKEN') ?: 'clms-db-fix-2026';
if (!hash_equals($expectedToken, (string)$token)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forbidden']);
    exit;
}

$minSleep = max(1, (int)($_GET['min_sleep'] ?? $_POST['min_sleep'] ?? 1));
$limit = min(200, max(1, (int)($_GET['limit'] ?? $_POST['limit'] ?? 100)));

$profiles = [
    ['host' => 'localhost', 'user' => 'sachin', 'pass' => 'a', 'db' => 'new_clms'],
    ['host' => '127.0.0.1', 'user' => 'sachin', 'pass' => 'a', 'db' => 'new_clms'],
];

$conn = false;
$lastError = '';
$used = null;
foreach ($profiles as $profile) {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = mysqli_init();
    if ($conn) {
        @mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    }
    $connected = $conn ? @mysqli_real_connect($conn, $profile['host'], $profile['user'], $profile['pass'], $profile['db']) : false;
    if ($connected && $conn) {
        $used = $profile;
        break;
    }
    $lastError = $conn ? clms_db_connect_error() : 'mysqli_init failed';
    if ($conn instanceof mysqli) {
        @clms_db_close($conn);
    }
    $conn = false;
}

if (!$conn) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to connect for cleanup',
        'error' => $lastError
    ], JSON_UNESCAPED_SLASHES);
    exit;
}

$killed = [];
$errors = [];
$processes = [];
$currentId = mysqli_thread_id($conn);
$user = clms_db_real_escape_string($conn, $used['user']);
$db = clms_db_real_escape_string($conn, $used['db']);

$result = @clms_db_query($conn, "
    SELECT ID, USER, HOST, DB, COMMAND, TIME, STATE, INFO
    FROM information_schema.PROCESSLIST
    WHERE USER = '{$user}'
      AND ID <> {$currentId}
      AND (DB = '{$db}' OR DB IS NULL)
      AND COMMAND = 'Sleep'
      AND TIME >= {$minSleep}
    ORDER BY TIME DESC
    LIMIT {$limit}
");

if ($result) {
    while ($row = clms_db_fetch_assoc($result)) {
        $id = (int)$row['ID'];
        $processes[] = [
            'id' => $id,
            'host' => $row['HOST'],
            'db' => $row['DB'],
            'time' => (int)$row['TIME'],
            'state' => $row['STATE'],
        ];
        if (@clms_db_query($conn, "KILL {$id}")) {
            $killed[] = $id;
        } else {
            $errors[] = "KILL {$id}: " . clms_db_error($conn);
        }
    }
} else {
    $errors[] = clms_db_error($conn);
}

$status = @clms_db_query($conn, "SHOW STATUS WHERE Variable_name IN ('Threads_connected','Threads_running','Max_used_connections')");
$statusRows = [];
if ($status) {
    while ($row = clms_db_fetch_assoc($status)) {
        $statusRows[$row['Variable_name']] = $row['Value'];
    }
}

$variables = @clms_db_query($conn, "SHOW VARIABLES WHERE Variable_name IN ('max_connections','wait_timeout','interactive_timeout')");
$variableRows = [];
if ($variables) {
    while ($row = clms_db_fetch_assoc($variables)) {
        $variableRows[$row['Variable_name']] = $row['Value'];
    }
}

clms_db_close($conn);

echo json_encode([
    'success' => true,
    'message' => 'Cleanup attempted. Delete api/db_emergency_cleanup.php after recovery.',
    'connected_with' => $used['host'],
    'min_sleep' => $minSleep,
    'killed_count' => count($killed),
    'killed' => $killed,
    'matched_processes' => $processes,
    'errors' => $errors,
    'status' => $statusRows,
    'variables' => $variableRows
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>

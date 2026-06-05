<?php
session_start();
header('Content-Type: application/json');
include __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/labour_license_threshold.php';

set_exception_handler(function($e) {
    if (!headers_sent()) header('Content-Type: application/json', true, 500);
    error_log('[get_contractor_selections] Exception: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
    exit;
});
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

try {
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, ['admin', 'welfare_admin', 'welfare_user', 'super_admin', 'pass_user'])) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $contractor_id = intval($_GET['id'] ?? 0);
    if (!$contractor_id) {
        echo json_encode(['success' => false, 'message' => 'ID missing']);
        exit;
    }

    // Full contractor data
    $contractor = db_single($conn, "SELECT * FROM contractors WHERE id = ?", 'i', [$contractor_id]);

    // PO / PWO / Sales selections
    $pos   = db_fetch_all($conn, "SELECT po_number  FROM contractor_po_selection  WHERE contractor_id = ?", 'i', [$contractor_id]);
    $pwos  = db_fetch_all($conn, "SELECT pwo_number FROM contractor_pwo_selection WHERE contractor_id = ?", 'i', [$contractor_id]);
    $sales = db_fetch_all($conn, "SELECT sale_order_no FROM contractor_so_selection WHERE contractor_id = ?", 'i', [$contractor_id]);

    // Documents — primary table
    $docs1 = db_fetch_all($conn, "SELECT doc_type, file_path, original_name FROM contractor_documents WHERE contractor_id = ?", 'i', [$contractor_id]);

    // Documents — legacy via annexure2a; handle schema differences on live server
    $docs2 = [];
    $docColumns = [];
    $colRes = $conn->query("SHOW COLUMNS FROM documents");
    if ($colRes) {
        while ($row = $colRes->fetch_assoc()) {
            $docColumns[] = $row['Field'];
        }
    }

    $hasFilePath = in_array('file_path', $docColumns);
    $docTypeCol = in_array('doc_type', $docColumns) ? 'doc_type' : (in_array('document_type', $docColumns) ? 'document_type' : null);
    $docNameCol = in_array('doc_name', $docColumns) ? 'doc_name' : (in_array('original_name', $docColumns) ? 'original_name' : (in_array('document_name', $docColumns) ? 'document_name' : null));
    $joinApplicationCol = in_array('application_id', $docColumns) ? 'application_id' : (in_array('application_no', $docColumns) ? 'application_no' : null);
    $hasContractorCol = in_array('contractor_id', $docColumns);

    if ($hasFilePath && ($joinApplicationCol || $hasContractorCol)) {
        $typeExpr = $docTypeCol ? "d.$docTypeCol as doc_type" : "NULL as doc_type";
        $nameExpr = $docNameCol ? "d.$docNameCol as original_name" : "'' as original_name";

        if ($joinApplicationCol) {
            $docs2_sql = "SELECT $typeExpr, d.file_path, $nameExpr FROM documents d JOIN annexure2a a ON d.$joinApplicationCol = a.application_id WHERE a.contractor_id = ?";
        } else {
            $docs2_sql = "SELECT $typeExpr, d.file_path, $nameExpr FROM documents d WHERE d.contractor_id = ?";
        }
        $docs2 = db_fetch_all($conn, $docs2_sql, 'i', [$contractor_id]);
    }

// License file stored directly on contractors table
$license_docs = [];
if ($contractor && !empty($contractor['license_file'])) {
    $license_docs[] = [
        'doc_type'      => 'Labour Licence Certificate',
        'file_path'     => 'contractors/' . $contractor['license_file'],
        'original_name' => basename($contractor['license_file']),
    ];
}

// Merge & deduplicate by doc_type
$all_docs = array_merge($docs1, $docs2, $license_docs);
$seen = [];
$final_docs = [];
foreach ($all_docs as $d) {
    $key = strtolower(trim($d['doc_type'] ?? ''));
    if (!isset($seen[$key])) {
        $seen[$key] = true;
        $final_docs[] = $d;
    }
}

$threshold = clms_get_labour_license_threshold($conn);

echo json_encode([
    'success'    => true,
    'contractor' => $contractor,
    'pos'        => array_column($pos, 'po_number'),
    'pwos'       => array_column($pwos, 'pwo_number'),
    'sales'      => array_column($sales, 'sale_order_no'),
    'docs'       => $final_docs,
    'threshold'  => $threshold,
]);
} catch (Throwable $e) {
    if (!headers_sent()) header('Content-Type: application/json', true, 500);
    error_log('[get_contractor_selections] Catch: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error', 'error' => $e->getMessage()]);
}

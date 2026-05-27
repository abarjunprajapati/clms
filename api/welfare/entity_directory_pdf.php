<?php
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth_middleware.php';

require_role(['welfare_admin', 'super_admin', 'welfare_user', 'pass_user']);

$type = strtolower(trim($_GET['type'] ?? ''));
$id = (int)($_GET['id'] ?? 0);

if (!in_array($type, ['contractor', 'customer'], true) || $id <= 0) {
    http_response_code(400);
    exit('Invalid request');
}

function entityPdfStatus($type, $row) {
    if ($type === 'customer') {
        if (strtoupper((string)($row['status'] ?? '')) === 'INACTIVE') return 'REJECTED';
        return ((int)($row['is_password_created'] ?? 0)) ? 'APPROVED' : 'PENDING';
    }
    $status = strtolower((string)($row['status'] ?? 'pending'));
    if (in_array($status, ['approved', 'active'], true)) return 'APPROVED';
    if (in_array($status, ['rejected', 'blocked'], true)) return 'REJECTED';
    return 'PENDING';
}

function h($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

if ($type === 'contractor') {
    $row = db_single($conn, "SELECT * FROM contractors WHERE id = ?", 'i', [$id]);
    $title = 'Contractor Details';
    $code = $row['vendor_code'] ?? '';
    $displayName = $row['contractor_name'] ?: ($row['vendor_name'] ?? '');
} else {
    $row = db_single($conn, "SELECT * FROM sap_customer_master WHERE id = ?", 'i', [$id]);
    $title = 'Customer Details';
    $code = $row['customer_code'] ?? '';
    $displayName = $row['customer_name'] ?? '';
}

if (!$row) {
    http_response_code(404);
    exit('Record not found');
}

$status = entityPdfStatus($type, $row);
$rowsHtml = '';
foreach ($row as $key => $value) {
    if (in_array($key, ['login_password', 'reset_token'], true)) continue;
    $label = ucwords(str_replace('_', ' ', $key));
    $rowsHtml .= '<tr><th>' . h($label) . '</th><td>' . nl2br(h($value ?: '-')) . '</td></tr>';
}

$html = '<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>' . h($title) . '</title>
<style>
body{font-family:Arial,sans-serif;color:#172033;margin:28px;font-size:12px}
.header{border-bottom:3px solid #334155;padding-bottom:12px;margin-bottom:18px}
h1{font-size:22px;margin:0 0 6px}
.sub{color:#64748b;font-size:12px}
.badge{display:inline-block;padding:5px 10px;border-radius:16px;font-weight:bold;font-size:11px;background:#eef2ff;color:#3730a3}
.summary{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin:16px 0}
.box{border:1px solid #dbe3ef;border-radius:8px;padding:10px;background:#f8fafc}
.label{font-size:10px;text-transform:uppercase;color:#64748b;font-weight:bold;margin-bottom:5px}
.value{font-size:13px;font-weight:bold}
table{width:100%;border-collapse:collapse;margin-top:14px}
th,td{border:1px solid #dbe3ef;padding:8px;text-align:left;vertical-align:top}
th{width:30%;background:#f1f5f9;color:#334155}
.footer{margin-top:20px;color:#64748b;font-size:10px;text-align:center}
@media print{.print-actions{display:none} body{margin:16px}}
.print-actions{margin-bottom:16px;text-align:right}
.print-actions button{border:0;background:#2563eb;color:#fff;border-radius:6px;padding:8px 12px;font-weight:bold;cursor:pointer}
</style>
</head>
<body>
<div class="print-actions"><button onclick="window.print()">Download / Print PDF</button></div>
<div class="header">
  <h1>' . h($title) . '</h1>
  <div class="sub">Generated on ' . date('d M Y H:i') . '</div>
</div>
<div class="summary">
  <div class="box"><div class="label">Code</div><div class="value">' . h($code ?: '-') . '</div></div>
  <div class="box"><div class="label">Name</div><div class="value">' . h($displayName ?: '-') . '</div></div>
  <div class="box"><div class="label">Status</div><div class="value"><span class="badge">' . h($status) . '</span></div></div>
</div>
<table>' . $rowsHtml . '</table>
<div class="footer">Contractor Labour Management System</div>
<script>setTimeout(function(){ window.print(); }, 500);</script>
</body>
</html>';

header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $type . '_' . $code) . '.html"');
echo $html;
exit;
?>

<?php
session_start();
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['contractor', 'customer', 'welfare_user', 'welfare_admin', 'welfare', 'admin', 'super_admin']);

$compliance_id = isset($_GET['compliance_id']) ? intval($_GET['compliance_id']) : 0;
if (!$compliance_id) {
    die("Invalid compliance ID.");
}

$comp = db_single($conn, "SELECT * FROM compliance WHERE id = ?", 'i', [$compliance_id]);
if (!$comp) {
    die("Record not found.");
}

$errors = explode("\n", $comp['validation_errors']);

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="epf_compliance_report_'.$comp['month_year'].'.xls"');
header('Cache-Control: max-age=0');
?>
<table border="1">
    <thead>
        <tr>
            <th colspan="1" style="font-size:16px; font-weight:bold; background:#ef4444; color:#fff;">EPF Compliance Report - <?= $comp['month_year'] ?></th>
        </tr>
        <tr>
            <th>Discrepancy Details</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($errors as $e): if(trim($e)==='') continue; ?>
        <tr>
            <td><?= htmlspecialchars($e) ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

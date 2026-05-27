<?php
require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/auth_middleware.php';

require_role(['contractor', 'pass_officer', 'acc', 'admin']);

require '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = intval($_GET['id'] ?? 0);

$sql = "SELECT g.*, w.name, w.role as trade, w.tempId
        FROM gate_passes g
        JOIN workmen w ON g.workman_id = w.id
        WHERE g.id = ? LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) die("Pass not found");

// Role-based access: contractor can only download their own passes
if ($currentRole === 'contractor') {
    $check = db_single($conn,
        "SELECT g.id FROM gate_passes g
         JOIN workmen w ON g.workman_id = w.id
         WHERE g.id = ? AND w.contractor_id = ? LIMIT 1",
        'ii', [$id, $currentUserId]
    );
    if (!$check) {
        http_response_code(403);
        die('Access denied: This pass does not belong to you.');
    }
}

$html = '
<h2 style="text-align:center;">Permanent Gate Pass</h2>
<hr>
<table width="100%" cellpadding="8">
<tr><td><strong>Pass No:</strong></td><td>'.$row['pass_ref'].'</td></tr>
<tr><td><strong>Name:</strong></td><td>'.$row['name'].'</td></tr>
<tr><td><strong>Trade:</strong></td><td>'.$row['trade'].'</td></tr>
<tr><td><strong>Temp ID:</strong></td><td>'.$row['tempId'].'</td></tr>
<tr><td><strong>Gate:</strong></td><td>'.$row['gate_location'].'</td></tr>
<tr><td><strong>Shift:</strong></td><td>'.$row['shift_type'].'</td></tr>
<tr><td><strong>Valid From:</strong></td><td>'.$row['valid_from'].'</td></tr>
<tr><td><strong>Valid To:</strong></td><td>'.$row['valid_to'].'</td></tr>
<tr><td><strong>Status:</strong></td><td>'.$row['status'].'</td></tr>
</table>
<hr>
<p style="text-align:center;">Government Contractor Portal</p>
';

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream("GatePass-".$row['pass_ref'].".pdf", ["Attachment" => true]);
exit;



<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['safety_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/safety_training_control.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Safety Officer';
clms_safety_ensure_control_schema($conn);

function renderContent() {
    global $conn;
    $batchId = (int)($_GET['batch_id'] ?? 0);
    $batch = $batchId
        ? db_single($conn, "SELECT * FROM training_class_batches WHERE id = ? LIMIT 1", 'i', array($batchId))
        : db_single($conn, "SELECT * FROM training_class_batches ORDER BY created_at DESC, id DESC LIMIT 1");
    $batches = db_fetch_all($conn, "SELECT id, batch_number, training_date FROM training_class_batches ORDER BY created_at DESC, id DESC LIMIT 50");
    $workers = array();
    if ($batch) {
        $nameParts = array();
        foreach (array('contractor_name', 'vendor_name', 'name') as $col) if (clms_safety_column_exists($conn, 'contractors', $col)) $nameParts[] = "c.`$col`";
        $nameParts[] = "CONCAT('Contractor #', w.contractor_id)";
        $contractorName = "COALESCE(" . implode(', ', $nameParts) . ")";
        $codeParts = array();
        foreach (array('contractor_code', 'vendor_code', 'vendor_id') as $col) if (clms_safety_column_exists($conn, 'contractors', $col)) $codeParts[] = "c.`$col`";
        $codeParts[] = "CONCAT('C-', w.contractor_id)";
        $contractorCode = "COALESCE(" . implode(', ', $codeParts) . ")";
        $workers = db_fetch_all($conn, "
            SELECT tbw.token_number, tbw.attempt_no, w.name, w.aadhaar, w.safety_language, $contractorCode contractor_code, $contractorName contractor_name, tr.requested_date
            FROM training_batch_workers tbw
            JOIN workmen w ON w.id = tbw.workman_id
            JOIN training_requests tr ON tr.id = tbw.training_request_id
            LEFT JOIN contractors c ON c.id = w.contractor_id
            WHERE tbw.batch_id = ?
              AND tbw.ticked = 1
            ORDER BY COALESCE(tr.requested_date, DATE(tr.created_at)) ASC, tbw.id ASC
        ", 'i', array((int)$batch['id']));
    }
?>
<div class="content-header"><div><h2 class="page-title"><i class="fas fa-file-lines"></i> Training Batch Report</h2><p class="page-subtitle">Batch header and attendee list with signature space.</p></div></div>
<section class="card glass">
  <div class="card-body">
    <form method="get" class="report-select"><select class="form-control" name="batch_id" onchange="this.form.submit()"><option value="">Latest Batch</option><?php foreach($batches as $b): ?><option value="<?= (int)$b['id'] ?>" <?= $batch && (int)$batch['id']===(int)$b['id']?'selected':'' ?>><?= htmlspecialchars($b['batch_number']) ?> - <?= date('d M Y', strtotime($b['training_date'])) ?></option><?php endforeach; ?></select><button type="button" class="btn btn-outline" onclick="exportCsv()">XL</button><button type="button" class="btn btn-primary" onclick="window.print()">PDF / Print</button></form>
    <?php if ($batch): ?>
    <div class="report-box" id="reportBox">
      <div class="report-head"><h3><?= htmlspecialchars($batch['batch_number']) ?></h3><p>Token: <?= htmlspecialchars($batch['batch_token']) ?> | Date: <?= date('d M Y', strtotime($batch['training_date'])) ?> | <?= htmlspecialchars($batch['venue_name']) ?> | <?= htmlspecialchars($batch['language_name']) ?> | <?= htmlspecialchars($batch['session_name']) ?> | Trainer: <?= htmlspecialchars($batch['instructor_name'] ?: '-') ?></p></div>
      <table class="data-table" id="reportTable"><thead><tr><th>S.No</th><th>Token</th><th>Enrollment Dt</th><th>Aadhaar</th><th>Name</th><th>Contractor Code</th><th>Contractor Name</th><th>Language</th><th>Attempt</th><th>Signature</th></tr></thead><tbody>
      <?php foreach($workers as $idx=>$w): ?><tr><td><?= $idx+1 ?></td><td><?= htmlspecialchars($w['token_number'] ?: str_pad((string)($idx + 1), 6, '0', STR_PAD_LEFT)) ?></td><td><?= !empty($w['requested_date']) ? date('d M Y', strtotime($w['requested_date'])) : '-' ?></td><td><?= htmlspecialchars($w['aadhaar'] ?? '') ?></td><td><?= htmlspecialchars($w['name'] ?? '') ?></td><td><?= htmlspecialchars($w['contractor_code'] ?? '') ?></td><td><?= htmlspecialchars($w['contractor_name'] ?? '') ?></td><td><?= htmlspecialchars($w['safety_language'] ?? $batch['language_name']) ?></td><td><?= (int)$w['attempt_no'] ?></td><td style="min-width:140px"></td></tr><?php endforeach; ?>
      </tbody></table>
    </div>
    <?php else: ?><div style="padding:30px;text-align:center;color:var(--text-muted)">No batch created yet.</div><?php endif; ?>
  </div>
</section>
<style>.report-select{display:flex;gap:10px;margin-bottom:16px}.form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px}.report-head{padding:12px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;margin-bottom:10px}.report-head h3{margin:0}.report-head p{margin:4px 0 0;color:#64748b;font-size:12px}@media print{body *{visibility:hidden}.report-box,.report-box *{visibility:visible}.report-box{position:absolute;left:0;top:0;width:100%}}</style>
<script>function exportCsv(){var t=document.getElementById('reportTable');if(!t)return;var rows=[].slice.call(t.querySelectorAll('tr')).map(function(r){return [].slice.call(r.children).map(function(c){return '"'+c.innerText.replace(/"/g,'""').trim()+'"'}).join(',')});var b=new Blob([rows.join('\n')],{type:'text/csv'});var u=URL.createObjectURL(b);var a=document.createElement('a');a.href=u;a.download='training-batch-report.csv';a.click();URL.revokeObjectURL(u);}</script>
<?php }
renderLayout('Training Batch Report', 'renderContent', $role, $name);
?>

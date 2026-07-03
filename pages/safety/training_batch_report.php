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
        $workerCreatedExpr = clms_safety_column_exists($conn, 'workmen', 'created_at') ? 'w.created_at' : 'tr.created_at';
        $workers = db_fetch_all($conn, "
            SELECT tbw.token_number, tbw.training_token, tbw.attempt_no, w.name, w.aadhaar, w.safety_language, 
                   $contractorCode contractor_code, $contractorName contractor_name, tr.requested_date, 
                   $workerCreatedExpr AS enrolment_date, tsw.attendance_status, tsw.result, tsw.remarks, tsw.total_score
            FROM training_batch_workers tbw
            JOIN workmen w ON w.id = tbw.workman_id
            JOIN training_requests tr ON tr.id = tbw.training_request_id
            LEFT JOIN contractors c ON c.id = w.contractor_id
            LEFT JOIN training_session_workers tsw ON tsw.training_request_id = tbw.training_request_id
            WHERE tbw.batch_id = ?
              AND tbw.ticked = 1
            ORDER BY COALESCE(DATE($workerCreatedExpr), tr.requested_date, DATE(tr.created_at)) ASC, tbw.id ASC
        ", 'i', array((int)$batch['id']));
    }
    $capacity = $batch ? max(1, (int)$batch['capacity']) : 0;
    $emergencySeats = $batch ? max(0, (int)($batch['emergency_seats'] ?? 0)) : 0;
    $regularSeats = max(0, $capacity - $emergencySeats);
?>
<div class="content-header"><div><h2 class="page-title"><i class="fas fa-file-lines"></i> Training Batch Report</h2><p class="page-subtitle">Batch header and attendee list with signature space.</p></div></div>
<section class="card glass">
  <div class="card-body">
    <form method="get" class="report-select"><select class="form-control" name="batch_id" onchange="this.form.submit()"><option value="">Latest Batch</option><?php foreach($batches as $b): ?><option value="<?= (int)$b['id'] ?>" <?= $batch && (int)$batch['id']===(int)$b['id']?'selected':'' ?>><?= htmlspecialchars($b['batch_number']) ?> - <?= date('d M Y', strtotime($b['training_date'])) ?></option><?php endforeach; ?></select><button type="button" class="btn btn-outline" onclick="exportCsv()"><i class="fas fa-file-excel"></i> XL</button><button type="button" class="btn btn-primary" onclick="window.print()"><i class="fas fa-print"></i> PDF / Print</button></form>
    <?php if ($batch): ?>
    <div class="report-box" id="reportBox">
      <div class="report-head">
        <h3>Safety Training Attendance Sheet</h3>
        <div class="report-meta">
          <div class="report-meta-card"><span>Batch No</span><strong><?= htmlspecialchars($batch['batch_number']) ?></strong></div>
          <div class="report-meta-card"><span>Training Dt</span><strong><?= date('d M Y', strtotime($batch['training_date'])) ?></strong></div>
          <div class="report-meta-card"><span>Location</span><strong><?= htmlspecialchars($batch['venue_name']) ?></strong></div>
          <div class="report-meta-card"><span>Slots</span><strong><?= $capacity ?> seats</strong><small><?= $regularSeats ?> regular + <?= $emergencySeats ?> emergency</small></div>
          <div class="report-meta-card"><span>Language</span><strong><?= htmlspecialchars($batch['language_name']) ?></strong></div>
          <div class="report-meta-card"><span>Session</span><strong><?= htmlspecialchars($batch['session_name']) ?></strong></div>
          <div class="report-meta-card"><span>Time</span><strong><?= htmlspecialchars(substr((string)($batch['time_from'] ?: ($batch['session_name'] === 'AN' ? '14:00' : '09:00')), 0, 5)) ?> - <?= htmlspecialchars(substr((string)($batch['time_to'] ?: ''), 0, 5) ?: '-') ?></strong></div>
          <div class="report-meta-card"><span>Training Type</span><strong><?= htmlspecialchars($batch['training_type']) ?></strong></div>
          <div class="report-meta-card"><span>Trainer</span><strong><?= htmlspecialchars($batch['instructor_name'] ?: '-') ?></strong></div>
        </div>
      </div>
      <table class="data-table report-table" id="reportTable"><thead><tr><th>S.No</th><th>Token No</th><th>Batch Dt</th><th>Name of Worker</th><th>Contractor Name</th><th>Aadhaar No</th><th>Contractor Code</th><th>Language</th><th>Attempt</th><th>Status</th><th>Marks</th><th>Signature</th></tr></thead><tbody>
      <?php foreach($workers as $idx=>$w): 
        $status = 'Pending';
        if (strtolower($w['attendance_status'] ?? '') === 'absent') {
            $status = 'Absent';
        } elseif (strtolower($w['result'] ?? '') === 'pass') {
            $status = 'Pass';
        } elseif (strtolower($w['result'] ?? '') === 'fail') {
            $status = 'Fail';
        } elseif (!empty($w['result'])) {
            $status = ucfirst($w['result']);
        }
      ?><tr><td><?= $idx+1 ?></td><td><?= htmlspecialchars($w['token_number'] ?: str_pad((string)($idx + 1), 6, '0', STR_PAD_LEFT)) ?></td><td><?= date('d M Y', strtotime($batch['training_date'])) ?></td><td><?= htmlspecialchars($w['name'] ?? '') ?></td><td><?= htmlspecialchars($w['contractor_name'] ?? '') ?></td><td><?= htmlspecialchars($w['aadhaar'] ?? '') ?></td><td><?= htmlspecialchars($w['contractor_code'] ?? '') ?></td><td><?= htmlspecialchars($w['safety_language'] ?? $batch['language_name']) ?></td><td><?= (int)$w['attempt_no'] ?></td><td><strong><?= $status ?></strong></td><td><?= $w['total_score'] !== null ? (int)$w['total_score'] : '-' ?></td><td class="signature-cell"></td></tr><?php endforeach; ?>
      </tbody></table>
    </div>
    <?php else: ?><div style="padding:30px;text-align:center;color:var(--text-muted)">No batch created yet.</div><?php endif; ?>
  </div>
</section>
<style>
.report-select{display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap}
.report-select select{min-width:280px;flex:1}
.form-control{height:38px;border:1px solid #cbd5e1;border-radius:8px;padding:0 10px}
.report-head{padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;margin-bottom:10px}
.report-head h3{margin:0 0 12px;color:#0f172a}
.report-meta{display:grid;grid-template-columns:repeat(3,minmax(170px,1fr));gap:8px}
.report-meta-card{background:#fff;border:1px solid #e5e7eb;border-radius:6px;padding:9px}
.report-meta-card span{display:block;font-size:10px;font-weight:800;color:#64748b;text-transform:uppercase;margin-bottom:4px}
.report-meta-card strong{display:block;font-size:13px;color:#111827}
.report-meta-card small{display:block;margin-top:2px;font-size:10px;color:#64748b}
.report-table th,.report-table td{vertical-align:top}
.signature-cell{min-width:150px;height:34px}
@media(max-width:820px){
  .report-meta{grid-template-columns:1fr}
  .report-select .btn{flex:1}
}
@media print {
  /* Hide UI Layout components */
  .sidebar, .topbar, .top-bar, .header, .content-header, .report-select, .btn, button, footer, 
  .layout-sidebar, .layout-header, .layout-footer, .nav-container, .brand-logo, #sidebar {
    display: none !important;
    visibility: hidden !important;
  }
  
  /* Reset content wrapper and card shadows */
  body, html {
    background: #fff !important;
    margin: 0 !important;
    padding: 0 !important;
    font-family: Arial, sans-serif !important;
  }
  .content-wrapper, .main-content, .card, .glass {
    margin: 0 !important;
    padding: 0 !important;
    border: none !important;
    box-shadow: none !important;
    background: transparent !important;
    width: 100% !important;
    display: block !important;
  }
  
  /* Report structure */
  .report-box {
    width: 100% !important;
    display: block !important;
    margin: 0 !important;
    padding: 0 !important;
  }
  .report-head {
    border: 1px solid #ccc !important;
    background: #e2e8f0 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    margin-bottom: 20px !important;
    padding: 12px !important;
    border-radius: 4px !important;
  }
  .report-head h3 {
    color: #111827 !important;
    margin: 0 0 10px 0 !important;
    font-size: 16px !important;
  }
  .report-meta {
    display: grid !important;
    grid-template-columns: repeat(3, 1fr) !important;
    gap: 8px !important;
  }
  .report-meta-card {
    border: 1px solid #ccc !important;
    background: #fff !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
    padding: 6px !important;
    border-radius: 3px !important;
  }
  .report-meta-card span {
    font-size: 8px !important;
    color: #475569 !important;
  }
  .report-meta-card strong {
    font-size: 11px !important;
    color: #000 !important;
  }
  .report-meta-card small {
    font-size: 8px !important;
    color: #475569 !important;
  }
  .report-table {
    width: 100% !important;
    border-collapse: collapse !important;
    margin-top: 15px !important;
  }
  .report-table th, .report-table td {
    border: 1px solid #ccc !important;
    padding: 6px 8px !important;
    font-size: 10px !important;
    color: #000 !important;
    text-align: left !important;
  }
  .report-table th {
    background: #e2e8f0 !important;
    color: #000 !important;
    font-weight: 700 !important;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
  }
  .signature-cell {
    min-width: 130px !important;
    height: 40px !important;
  }
}
</style>
<script>
function csvCell(value) {
  return '"' + String(value || '').replace(/"/g, '""').trim() + '"';
}
function exportCsv() {
  var t = document.getElementById('reportTable');
  if (!t) return;
  var meta = [].slice.call(document.querySelectorAll('.report-meta-card')).map(function(card) {
    var label = card.querySelector('span')?.innerText || '';
    var value = card.querySelector('strong')?.innerText || '';
    var small = card.querySelector('small')?.innerText || '';
    return [csvCell(label), csvCell(value + (small ? ' | ' + small : ''))].join(',');
  });
  
  var rows = [csvCell('Safety Training Attendance Sheet'), ''];
  rows = rows.concat(meta, ['']);
  
  rows = rows.concat([].slice.call(t.querySelectorAll('tr')).map(function(r) {
    return [].slice.call(r.children).map(function(c) {
      return csvCell(c.innerText);
    }).join(',');
  }));
  
  // Use UTF-8 BOM '\ufeff' so Excel opens it correctly with UTF-8 character encoding
  var b = new Blob(["\ufeff", rows.join('\r\n')], {type: 'text/csv;charset=utf-8;'});
  var u = URL.createObjectURL(b);
  var a = document.createElement('a');
  a.href = u;
  a.download = 'training-batch-report.csv';
  a.click();
  URL.revokeObjectURL(u);
}
</script>
<?php }
renderLayout('Training Batch Report', 'renderContent', $role, $name);
?>

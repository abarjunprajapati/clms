<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];
clms_get_portal_contractor($conn);

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;

    // Fetch from gate_pass_request_workers joined with gate_pass_requests
    $passes = $c_id ? db_fetch_all($conn,
        "SELECT 
            gprw.id,
            gpr.status as status,
            gpr.request_no,
            gpr.pass_type,
            gpr.from_date as valid_from,
            gpr.to_date as valid_to,
            gpr.created_at,
            w.id as worker_id,
            w.name as worker_name,
            w.trade,
            w.aadhaar,
            w.temp_id,
            w.status as workman_status,
            COALESCE(gprw.gatepass_no, '') as pass_number,
            gpr.rejection_reason,
            EXISTS (
                SELECT 1
                FROM permanent_gate_passes pgp
                WHERE pgp.worker_id = w.id
                  AND LOWER(COALESCE(pgp.status, '')) = 'active'
            ) AS has_permanent_pass,
            (
                SELECT COUNT(*)
                FROM documents d
                WHERE d.workman_id = w.id
                  AND COALESCE(d.status, 'pending') IN ('rejected', 'reupload_required')
            ) AS rejected_doc_count
         FROM gate_pass_request_workers gprw
         JOIN gate_pass_requests gpr ON gprw.request_id = gpr.id
         JOIN workmen w ON gprw.workman_id = w.id
         WHERE w.contractor_id = ?
         ORDER BY gpr.created_at DESC",
        'i', [$c_id]) : [];

    // Fallback/Union with gate_passes if any exist (for future proofing)
    $issued_passes = $c_id ? db_fetch_all($conn,
        "SELECT 
            gp.id,
            gp.status,
            gp.pass_number as request_no,
            gp.pass_type,
            gp.valid_from,
            gp.valid_to,
            gp.created_at,
            w.id as worker_id,
            w.name as worker_name,
            w.trade,
            w.aadhaar,
            w.temp_id,
            w.status as workman_status,
            gp.pass_number,
            EXISTS (
                SELECT 1
                FROM permanent_gate_passes pgp
                WHERE pgp.worker_id = w.id
                  AND LOWER(COALESCE(pgp.status, '')) = 'active'
            ) AS has_permanent_pass,
            '' as rejection_reason
         FROM gate_passes gp
         JOIN workmen w ON gp.workman_id = w.id
         WHERE w.contractor_id = ?
         ORDER BY gp.created_at DESC",
        'i', [$c_id]) : [];

    if (!empty($issued_passes)) {
        $passes = array_merge($passes, $issued_passes);
        // Sort by created_at desc
        usort($passes, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
    }

    foreach ($passes as &$pass) {
        $rawStatus = strtolower((string)($pass['status'] ?? 'pending'));
        $workmanStatus = strtolower((string)($pass['workman_status'] ?? ''));
        if (
            in_array($rawStatus, ['issued', 'permanent_issued', 'permanent_active'], true) ||
            in_array($workmanStatus, ['permanent_active', 'permanent_issued'], true) ||
            (int)($pass['has_permanent_pass'] ?? 0) > 0
        ) {
            $rawStatus = 'active';
        }
        if ((int)($pass['rejected_doc_count'] ?? 0) > 0 && !in_array($rawStatus, ['approved', 'active', 'issued'], true)) {
            $rawStatus = 'reupload_required';
        }
        $pass['status'] = $rawStatus;
    }
    unset($pass);

    $status_filter = $_GET['status'] ?? 'all';
    if ($status_filter !== 'all') {
        $passes = array_filter($passes, function($p) use ($status_filter) { return ($p['status'] ?? '') === $status_filter; });
    }
    ?>

    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-satellite-dish" style="color:#3b82f6;margin-right:10px;"></i> Pass Status Tracking</h2>
        <!-- <p class="page-subtitle">Monitor the status of all your gate pass applications in real time.</p> -->
      </div>
    </div>

    <!-- Status Filter Tabs -->
    <div class="filter-tabs">
      <?php
      $filters = ['all'=>'All Requests','pending'=>'Pending','reupload_required'=>'Re-upload','approved'=>'Approved','active'=>'Active','rejected'=>'Rejected','expired'=>'Expired'];
      foreach ($filters as $val => $label):
        $count = $val === 'all' ? count($passes) : count(array_filter($passes, function($p) use ($val) { return ($p['status']??'') === $val; }));
      ?>
      <a href="?status=<?= $val ?>" class="filter-tab <?= $status_filter === $val ? 'active' : '' ?>">
        <?= $label ?> <span class="tab-count"><?= $count ?></span>
      </a>
      <?php endforeach; ?>
    </div>

    <?php if (empty($passes)): ?>
    <div class="card glass">
      <div class="card-body" style="text-align:center;padding:60px 0;">
        <i class="fas fa-id-card" style="font-size:60px;opacity:.15;display:block;margin-bottom:16px;"></i>
        <p style="font-weight:600;font-size:16px;margin-bottom:8px;">No Gate Pass Requests</p>
        <p style="color:var(--text-muted);margin-bottom:20px;">Apply for your first gate pass to get started.</p>
        <a href="gatepass-6a.php" class="btn btn-primary"><i class="fas fa-plus-circle"></i> Apply for Gate Pass</a>
      </div>
    </div>
    <?php else: ?>

    <div class="pass-cards">
    <?php foreach ($passes as $gp):
      $st = $gp['status'] ?? 'pending';
      if ((int)($gp['rejected_doc_count'] ?? 0) > 0 && !in_array($st, ['approved', 'active', 'issued'], true)) {
        $st = 'reupload_required';
      }
      $colors = ['active'=>'#10b981','pending'=>'#f59e0b','reupload_required'=>'#ef4444','rejected'=>'#ef4444','expired'=>'#6b7280','approved'=>'#3b82f6'];
      $icons  = ['active'=>'fa-check-circle','pending'=>'fa-clock','reupload_required'=>'fa-file-circle-exclamation','rejected'=>'fa-times-circle','expired'=>'fa-calendar-times','approved'=>'fa-thumbs-up'];
      $color = $colors[$st] ?? '#6b7280';
      $icon  = $icons[$st]  ?? 'fa-question-circle';
    ?>
    <div class="pass-card glass">
      <div class="pass-card-header" style="border-left:4px solid <?= $color ?>;">
        <div>
          <div class="pass-worker-name"><?= htmlspecialchars($gp['worker_name'] ?? '—') ?></div>
          <div class="pass-meta"><?= htmlspecialchars($gp['trade'] ?? '') ?> | Aadhaar: <?= htmlspecialchars($gp['aadhaar'] ?? '—') ?></div>
          <?php if (!empty($gp['temp_id'])): ?>
          <div class="pass-meta">Temp ID: <code><?= htmlspecialchars($gp['temp_id']) ?></code></div>
          <?php endif; ?>
        </div>
        <div class="pass-status-badge" style="background:<?= $color ?>20;color:<?= $color ?>;border:1px solid <?= $color ?>40;">
          <i class="fas <?= $icon ?>"></i> <?= strtoupper($st) ?>
        </div>
      </div>

      <!-- Timeline -->
      <div class="pass-timeline">
        <?php
        $timeline_steps = [
          'submitted' => ['label'=>'Application Submitted', 'icon'=>'fa-file-alt'],
          'under_review' => ['label'=>'Under Review (Welfare)', 'icon'=>'fa-search'],
          'documents_verified' => ['label'=>'Documents Verified', 'icon'=>'fa-file-check'],
          'approved' => ['label'=>'Pass Approved', 'icon'=>'fa-thumbs-up'],
          'active' => ['label'=>'Pass Issued & Active', 'icon'=>'fa-id-card'],
        ];
        $order = array_keys($timeline_steps);
        $current_idx = 0;
        if ($st === 'pending') $current_idx = 1;
        if ($st === 'reupload_required') $current_idx = 1;
        if ($st === 'approved') $current_idx = 3;
        if ($st === 'active') $current_idx = 4;
        
        // If it's a request record, it's at least 'submitted'
        if ($current_idx === 0) $current_idx = 0; 

        foreach ($timeline_steps as $key => $ts):
          $idx = array_search($key, $order);
          $cls = $idx <= $current_idx ? 'done' : 'future';
          if ($idx === $current_idx) $cls = 'current';
          if (($st === 'rejected' || $st === 'reupload_required') && $idx === 1) $cls = 'rejected';
        ?>
        <div class="tl-step <?= $cls ?>">
          <div class="tl-dot"><i class="fas <?= $ts['icon'] ?>"></i></div>
          <div class="tl-label"><?= $ts['label'] ?></div>
        </div>
        <?php if ($idx < count($timeline_steps) - 1): ?>
        <div class="tl-line <?= $idx < $current_idx ? 'done' : '' ?>"></div>
        <?php endif; ?>
        <?php endforeach; ?>
      </div>

      <div class="pass-card-footer">
        <div class="pass-info-grid">
          <div><span class="pil">Pass Type</span><span class="piv"><?= htmlspecialchars($gp['pass_type'] ?? '—') ?></span></div>
          <div><span class="pil">Valid From</span><span class="piv"><?= $gp['valid_from'] ? date('d M Y', strtotime($gp['valid_from'])) : '—' ?></span></div>
          <div><span class="pil">Valid To</span><span class="piv"><?= $gp['valid_to'] ? date('d M Y', strtotime($gp['valid_to'])) : '—' ?></span></div>
          <div><span class="pil">Applied</span><span class="piv"><?= $gp['created_at'] ? date('d M Y', strtotime($gp['created_at'])) : '—' ?></span></div>
        </div>
        <?php if ($st === 'active' || $st === 'approved'): ?>
        <div style="margin-top:12px;">
          <a href="../../api/welfare/download_pass.php?id=<?= $gp['worker_id'] ?>&type=perm&action=print" class="btn btn-sm btn-primary" target="_blank">
            <i class="fas fa-download"></i> Download Pass PDF
          </a>
        </div>
        <?php endif; ?>
        <?php if ($st === 'rejected' || $st === 'reupload_required'): ?>
        <div class="rejection-note">
          <i class="fas fa-info-circle"></i>
          <?= htmlspecialchars($gp['rejection_reason'] ?? 'Some documents were rejected. Please re-upload corrected documents.') ?>
          <a href="gatepass-reupload.php" class="btn btn-sm btn-danger" style="margin-left:8px;">Re-upload Documents</a>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <style>
    .filter-tabs { display:flex;gap:4px;margin-bottom:20px;background:var(--card-bg);border:1px solid var(--border-color);border-radius:12px;padding:6px; }
    .filter-tab { padding:8px 14px;border-radius:8px;text-decoration:none;font-size:13px;font-weight:600;color:var(--text-muted);display:flex;align-items:center;gap:6px;transition:.2s; }
    .filter-tab:hover { background:rgba(148,163,184,.1); }
    .filter-tab.active { background:#6366f1;color:white; }
    .tab-count { background:rgba(255,255,255,.2);padding:1px 7px;border-radius:999px;font-size:11px; }
    .filter-tab.active .tab-count { background:rgba(255,255,255,.25); }

    .pass-cards { display:flex;flex-direction:column;gap:16px; }
    .pass-card { border-radius:16px;overflow:hidden; }
    .pass-card-header { display:flex;justify-content:space-between;align-items:flex-start;padding:16px 20px; }
    .pass-worker-name { font-size:16px;font-weight:700; }
    .pass-meta { font-size:12px;color:var(--text-muted);margin-top:2px; }
    .pass-status-badge { padding:6px 14px;border-radius:999px;font-size:12px;font-weight:700;display:flex;align-items:center;gap:6px;white-space:nowrap; }

    .pass-timeline { display:flex;align-items:center;padding:16px 20px;gap:0;overflow-x:auto;border-top:1px solid var(--border-color);border-bottom:1px solid var(--border-color); }
    .tl-step { display:flex;flex-direction:column;align-items:center;gap:4px;min-width:80px; }
    .tl-dot { width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;border:2px solid; }
    .tl-step.done .tl-dot    { background:#10b981;border-color:#10b981;color:white; }
    .tl-step.current .tl-dot { background:#6366f1;border-color:#6366f1;color:white;box-shadow:0 0 0 5px rgba(99,102,241,.15); }
    .tl-step.rejected .tl-dot { background:#ef4444;border-color:#ef4444;color:white; }
    .tl-step.future .tl-dot  { background:rgba(148,163,184,.1);border-color:rgba(148,163,184,.3);color:var(--text-muted); }
    .tl-label { font-size:10px;font-weight:600;text-align:center;color:var(--text-muted);line-height:1.3; }
    .tl-step.done .tl-label { color:#10b981; }
    .tl-step.current .tl-label { color:#6366f1; }
    .tl-step.rejected .tl-label { color:#ef4444; }
    .tl-line { flex:1;height:2px;background:rgba(148,163,184,.2);margin:0 4px;margin-bottom:14px; }
    .tl-line.done { background:#10b981; }

    .pass-card-footer { padding:14px 20px; }
    .pass-info-grid { display:grid;grid-template-columns:repeat(4,1fr);gap:12px; }
    .pil { display:block;font-size:11px;color:var(--text-muted);margin-bottom:2px; }
    .piv { display:block;font-size:13px;font-weight:600; }
    .rejection-note { margin-top:10px;padding:10px 14px;background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:8px;font-size:13px;color:#ef4444;display:flex;align-items:center;gap:8px;flex-wrap:wrap; }
    </style>
    <?php
}

renderLayout("Pass Status Tracking", 'renderContent', $role, $name);

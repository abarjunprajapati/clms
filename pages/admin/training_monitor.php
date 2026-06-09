<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    // Summary
    $totalTests = db_count($conn, "SELECT COUNT(*) c FROM training_results");
    $passed = db_count($conn, "SELECT COUNT(*) c FROM training_results WHERE result='pass'");
    $failed = db_count($conn, "SELECT COUNT(*) c FROM training_results WHERE result='fail'");
    $passRate = ($totalTests > 0) ? round(($passed / $totalTests) * 100) : 0;
    
    $results = db_fetch_all($conn, "SELECT tr.*, w.name as workman_name, c.contractor_name 
                                    FROM training_results tr 
                                    JOIN workmen w ON tr.workman_id = w.id 
                                    LEFT JOIN contractors c ON w.contractor_id = c.id
                                    ORDER BY tr.updated_at DESC LIMIT 100");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-graduation-cap" style="color:#10b981;margin-right:10px;"></i> Safety Training Oversight</h2>
        <!-- <p class="page-subtitle">Monitoring training results, pass rates, and certification logs.</p> -->
      </div>
    </div>

    <!-- Stats Row -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#6366f1;"><?= $totalTests ?></div>
        <div style="font-size:11px;opacity:0.6;">Tests Conducted</div>
      </div>
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#10b981;"><?= $passRate ?>%</div>
        <div style="font-size:11px;opacity:0.6;">Overall Pass Rate</div>
      </div>
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#059669;"><?= $passed ?></div>
        <div style="font-size:11px;opacity:0.6;">Passed Workers</div>
      </div>
      <div class="card glass" style="text-align:center;padding:16px;">
        <div style="font-size:24px;font-weight:800;color:#ef4444;"><?= $failed ?></div>
        <div style="font-size:11px;opacity:0.6;">Failed Attempts</div>
      </div>
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title">Training Result Logs</div>
      </div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman Name</th>
              <th>Contractor</th>
              <th>Score</th>
              <th>Result</th>
              <th>Certificate #</th>
              <th>Certified At</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($results as $r): ?>
            <tr>
              <td><strong><?= htmlspecialchars($r['workman_name']) ?></strong></td>
              <td><small><?= htmlspecialchars($r['contractor_name'] ?? 'N/A') ?></small></td>
              <td>
                <div style="display:flex; align-items:center; gap:8px;">
                    <div style="flex:1; height:6px; width:40px; background:#e2e8f0; border-radius:3px;">
                        <div style="width:<?= $r['total_score'] ?>%; height:100%; background:<?= $r['result']=='pass' ? '#10b981' : '#ef4444' ?>; border-radius:3px;"></div>
                    </div>
                    <small><?= $r['total_score'] ?? 0 ?>%</small>
                </div>
              </td>
              <td>
                <span class="badge <?= ($r['result'] == 'pass') ? 'badge-success' : 'badge-danger' ?>">
                    <?= strtoupper($r['result']) ?>
                </span>
              </td>
              <td><code style="font-size:11px;"><?= htmlspecialchars($r['certificate_no'] ?? '-') ?></code></td>
              <td><small><?= date('d M Y', strtotime($r['created_at'])) ?></small></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php
}

renderLayout("Training Monitor", 'renderContent', $_SESSION['role'], $_SESSION['name']);

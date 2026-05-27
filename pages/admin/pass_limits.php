<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    $contractors = db_fetch_all($conn, "SELECT c.id, c.contractor_name, c.vendor_code, COALESCE(p.max_allowed,0) as max_allowed, COALESCE(p.current_count,0) as current_count FROM contractors c LEFT JOIN pass_limits p ON c.id = p.contractor_id ORDER BY c.contractor_name");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-sliders-h" style="color:#6366f1;margin-right:10px;"></i> Pass Limit Configuration </h2>
        <!-- <p class="page-subtitle">Define maximum worker limits per contractor and monitor utilization.</p> -->
      </div>
    </div>

    <div class="card glass">
      <div class="card-header"><div class="card-title">Contractor Pass Limits</div></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table">
          <thead><tr><th>Contractor</th><th>Vendor Code</th><th>Max Allowed</th><th>Current Active</th><th>Utilization</th><th>Action</th></tr></thead>
          <tbody>
          <?php foreach($contractors as $c):
            $util = ($c['max_allowed'] > 0) ? ($c['current_count'] / $c['max_allowed']) * 100 : 0;
            $color = ($util > 90) ? 'var(--danger)' : (($util > 70) ? 'var(--warning)' : 'var(--success)');
          ?>
          <tr>
            <td><strong><?= htmlspecialchars($c['contractor_name']) ?></strong></td>
            <td><code><?= htmlspecialchars($c['vendor_code'] ?? '-') ?></code></td>
            <td><input type="number" class="form-control" style="width:100px" value="<?= $c['max_allowed'] ?>" id="limit_<?= $c['id'] ?>"></td>
            <td><?= $c['current_count'] ?></td>
            <td>
              <div style="width:100%;height:8px;background:rgba(0,0,0,0.05);border-radius:4px;">
                <div style="width:<?= min(100,$util) ?>%;height:100%;background:<?= $color ?>;border-radius:4px;transition:0.3s;"></div>
              </div>
              <small><?= round($util,1) ?>% Used</small>
            </td>
            <td><button class="btn btn-sm btn-primary" onclick="updateLimit(<?= $c['id'] ?>)"><i class="fas fa-save"></i> Save</button></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div id="pl-toast" class="um-toast" style="display:none;"></div>
    <style>.um-toast{position:fixed;bottom:30px;right:30px;z-index:99999;padding:14px 24px;border-radius:12px;font-size:14px;font-weight:600;color:#fff;display:flex;align-items:center;gap:10px;box-shadow:0 8px 24px rgba(0,0,0,0.2);}.um-toast.success{background:#10b981;}.um-toast.error{background:#ef4444;}</style>
    <script>
    async function updateLimit(id) {
      const val = document.getElementById('limit_' + id).value;
      try {
        const res = await fetch('../../api/admin/update_pass_limit.php', {
          method:'POST', headers:{'Content-Type':'application/json'},
          body: JSON.stringify({contractor_id:id, max_allowed:parseInt(val)})
        });
        const data = await res.json();
        const t = document.getElementById('pl-toast');
        t.className = 'um-toast ' + (data.success?'success':'error');
        t.innerHTML = '<i class="fas fa-'+(data.success?'check-circle':'exclamation-circle')+'"></i> ' + data.message;
        t.style.display = 'flex';
        setTimeout(()=>t.style.display='none',3000);
      } catch(e) { alert('Error: '+e.message); }
    }
    </script>
    <?php
}

renderLayout("Pass Limits Control", 'renderContent', $_SESSION['role'], $_SESSION['name']);

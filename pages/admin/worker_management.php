<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    $workers = db_fetch_all($conn, "SELECT w.*, c.contractor_name FROM workmen w JOIN contractors c ON w.contractor_id = c.id ORDER BY w.name ASC LIMIT 100");
    $totalW = db_count($conn, "SELECT COUNT(*) c FROM workmen");
    $activeW = db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE status IN ('active','trained','verified','acc_generated','permanent_issued')");
    $blockedW = db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE status='blocked'");
    $tempW = db_count($conn, "SELECT COUNT(*) c FROM workmen WHERE status='temp'");
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-user-clock" style="color:#6366f1;margin-right:10px;"></i> Worker Lifecycle Control</h2>
        <!-- <p class="page-subtitle">Block/Unblock workers with approval safeguards and cascading pass cancellation.</p> -->
      </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;">
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#6366f1;"><?= $totalW ?></div><div style="font-size:12px;opacity:0.6;">Total Workers</div></div>
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#10b981;"><?= $activeW ?></div><div style="font-size:12px;opacity:0.6;">Active</div></div>
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#ef4444;"><?= $blockedW ?></div><div style="font-size:12px;opacity:0.6;">Blocked</div></div>
      <div class="card glass" style="padding:18px;text-align:center;"><div style="font-size:28px;font-weight:800;color:#f59e0b;"><?= $tempW ?></div><div style="font-size:12px;opacity:0.6;">Temporary</div></div>
    </div>

    <div class="card glass">
      <div class="card-header"><div class="card-title"><i class="fas fa-list"></i> All Registered Workmen</div></div>
      <div class="card-body" style="padding:0;">
        <table class="data-table" id="workersTable">
          <thead><tr><th>ID</th><th>Name</th><th>Contractor</th><th>Trade</th><th>Status</th><th style="width:200px;">Action</th></tr></thead>
          <tbody>
          <?php foreach($workers as $w):
            $st = $w['status'];
            $cls = 'badge-info';
            if(in_array($st,['active','trained','verified','acc_generated','permanent_issued'])) $cls='badge-success';
            if($st=='blocked') $cls='badge-danger';
            if($st=='temp') $cls='badge-warning';
          ?>
          <tr>
            <td><code><?= htmlspecialchars($w['temp_id'] ?? $w['id']) ?></code></td>
            <td><strong><?= htmlspecialchars($w['name']) ?></strong></td>
            <td><?= htmlspecialchars($w['contractor_name']) ?></td>
            <td><?= htmlspecialchars($w['trade'] ?? '-') ?></td>
            <td><span class="badge <?= $cls ?>"><?= strtoupper($st) ?></span></td>
            <td style="display:flex;gap:6px;">
              <?php if($st == 'blocked'): ?>
              <button class="btn btn-sm btn-success" onclick="updateWorkerStatus(<?= $w['id'] ?>, 'unblock', '<?= addslashes($w['name']) ?>')"><i class="fas fa-lock-open"></i> Unblock</button>
              <?php else: ?>
              <button class="btn btn-sm btn-danger" onclick="updateWorkerStatus(<?= $w['id'] ?>, 'block', '<?= addslashes($w['name']) ?>')"><i class="fas fa-ban"></i> Block</button>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div id="wm-toast" class="um-toast" style="display:none;"></div>
    <style>.um-toast{position:fixed;bottom:30px;right:30px;z-index:99999;padding:14px 24px;border-radius:12px;font-size:14px;font-weight:600;color:#fff;display:flex;align-items:center;gap:10px;box-shadow:0 8px 24px rgba(0,0,0,0.2);}.um-toast.success{background:#10b981;}.um-toast.error{background:#ef4444;}</style>

    <script>
    async function updateWorkerStatus(id, action, name) {
      const reason = prompt((action==='block'?'⚠️ Block':'✅ Unblock') + ' worker "' + name + '"?\nEnter reason:');
      if (!reason) return;

      try {
        let res = await fetch('../../api/admin/update_worker_status.php', {
          method:'POST', headers:{'Content-Type':'application/json'},
          body: JSON.stringify({worker_id:id, action, reason, confirm_override:false})
        });
        let data = await res.json();

        if (data.requires_override) {
          if (!confirm('⚠️ APPROVAL SAFEGUARD\n\n' + data.message)) return;
          res = await fetch('../../api/admin/update_worker_status.php', {
            method:'POST', headers:{'Content-Type':'application/json'},
            body: JSON.stringify({worker_id:id, action, reason, confirm_override:true})
          });
          data = await res.json();
        }

        showToast(data.message, data.success?'success':'error');
        if(data.success) setTimeout(() => location.reload(), 1000);
      } catch(e) { showToast('Network error','error'); }
    }

    function showToast(msg,type){const t=document.getElementById('wm-toast');t.className='um-toast '+type;t.innerHTML='<i class="fas fa-'+(type==='success'?'check-circle':'exclamation-circle')+'"></i> '+msg;t.style.display='flex';setTimeout(()=>t.style.display='none',3500);}
    </script>
    <?php
}

renderLayout("Worker Management", 'renderContent', $_SESSION['role'], $_SESSION['name']);

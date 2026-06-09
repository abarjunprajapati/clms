<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;
    $workers = db_fetch_all($conn, "SELECT w.*, c.contractor_name, wb.reason as block_reason, wb.blocked_at, wb.status as block_status 
                                    FROM workmen w 
                                    JOIN contractors c ON w.contractor_id = c.id 
                                    LEFT JOIN worker_blocks wb ON w.id = wb.workman_id AND wb.status='active'
                                    ORDER BY w.name ASC");
    ?>
    <div class="content-header">
      <h2 class="page-title">Worker Lifecycle Control</h2>
      <!-- <p class="page-subtitle">Permanently block or unblock workmen for disciplinary or security reasons.</p> -->
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-users-slash"></i> Worker Registry & Block Control</div>
      </div>
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman Name</th>
              <th>Contractor</th>
              <th>Status</th>
              <th>Block Reason</th>
              <th>Blocked At</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($workers as $w): 
              $is_blocked = ((int)($w['is_blocked'] ?? 0) === 1 || $w['block_status'] == 'active');
            ?>
            <tr class="<?= $is_blocked ? 'bg-danger-subtle' : '' ?>">
              <td>
                <strong><?= htmlspecialchars($w['name'] ?? 'Unknown') ?></strong>
                <div style="font-size:11px;color:var(--gray-500)">Aadhar: <?= htmlspecialchars($w['aadhaar'] ?? 'N/A') ?></div>
              </td>
              <td><?= htmlspecialchars($w['contractor_name'] ?? 'N/A') ?></td>
              <td>
                <span class="badge <?= $is_blocked ? 'badge-danger' : 'badge-success' ?>">
                  <?= $is_blocked ? 'BLOCKED' : 'ACTIVE' ?>
                </span>
              </td>
              <td style="max-width:200px;font-size:12px"><?= htmlspecialchars($w['block_reason'] ?: '-') ?></td>
              <td><?= $w['blocked_at'] ? date('d M Y', strtotime($w['blocked_at'])) : '-' ?></td>
              <td>
                <?php if($is_blocked): ?>
                <button class="btn btn-sm btn-success" onclick="updateBlock(<?= $w['id'] ?>, 'unblock')">Unblock</button>
                <?php else: ?>
                <button class="btn btn-sm btn-danger" onclick="updateBlock(<?= $w['id'] ?>, 'block')">Block</button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <script>
    async function updateBlock(workman_id, action) {
        let reason = '';
        if (action === 'block') {
            reason = prompt('Enter reason for blocking this worker:');
            if (reason === null) return;
            if (!reason.trim()) {
                alert('Reason is required for blocking');
                return;
            }
        } else {
            if (!confirm('Are you sure you want to unblock this worker?')) return;
        }

        try {
            const res = await fetch('../../api/welfare/update_worker_lifecycle.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': window.CLMS_CSRF_TOKEN || ''
                },
                body: JSON.stringify({workman_id, action, reason})
            });
            const raw = await res.text();
            let data = {};
            try {
                data = raw ? JSON.parse(raw) : {};
            } catch (err) {
                data = { success: false, error: raw ? raw.replace(/<[^>]*>/g, ' ').trim() : 'Server returned an empty response.' };
            }
            if (data.success) {
                alert('Worker status updated successfully');
                location.reload();
            } else {
                alert(data.error || data.message || 'Operation failed');
            }
        } catch (e) {
            alert('Error updating status');
        }
    }
    </script>
    <?php
}

renderLayout("Worker Lifecycle Control", 'renderContent', $role, $name);

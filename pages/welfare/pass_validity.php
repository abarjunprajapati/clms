<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['pass_user', 'super_admin', 'welfare_user', 'welfare_admin']);

include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = get_normalized_role();
$name = $_SESSION['name'] ?? 'Pass Issuing Officer';

function passValidityColumnExists($table, $column) {
    global $conn;
    $table = str_replace('`', '``', $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function renderContent() {
    global $conn;
    
    $hasTempValidTo = passValidityColumnExists('workmen', 'temp_valid_to');
    $hasValidTo = passValidityColumnExists('workmen', 'valid_to');
    $hasAccNumber = passValidityColumnExists('workmen', 'acc_number');
    $hasContractorName = passValidityColumnExists('contractors', 'contractor_name');
    $hasContractorDisplayName = passValidityColumnExists('contractors', 'name');

    $expiryExpr = $hasTempValidTo && $hasValidTo ? "COALESCE(w.temp_valid_to, w.valid_to)" : ($hasValidTo ? "w.valid_to" : ($hasTempValidTo ? "w.temp_valid_to" : "NULL"));
    $accExpr = $hasAccNumber ? "w.acc_number" : "''";
    $contractorExpr = $hasContractorName && $hasContractorDisplayName ? "COALESCE(c.contractor_name, c.name, 'N/A')" : ($hasContractorName ? "COALESCE(c.contractor_name, 'N/A')" : ($hasContractorDisplayName ? "COALESCE(c.name, 'N/A')" : "'N/A'"));
    $queryError = '';
    $expiring = [];

    if ($expiryExpr === 'NULL') {
        $queryError = 'Pass validity columns are missing in workmen table.';
    } else {
        // Find all passes nearing expiry or expired. LEFT JOIN keeps worker rows visible even if contractor master is missing.
        $query = "SELECT w.id, w.name, w.status, $accExpr AS acc_number, $expiryExpr AS expiry_date,
                         $contractorExpr AS contractor_name
                  FROM workmen w
                  LEFT JOIN contractors c ON w.contractor_id = c.id
                  WHERE
                    (w.status = 'temporary_issued' AND $expiryExpr <= DATE_ADD(CURDATE(), INTERVAL 3 DAY))
                    OR
                    (w.status IN ('permanent_active', 'permanent_issued', 'acc_generated') AND $expiryExpr <= DATE_ADD(CURDATE(), INTERVAL 7 DAY))
                  ORDER BY $expiryExpr ASC";
        $result = mysqli_query($conn, $query);
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $expiring[] = $row;
            }
        } else {
            $queryError = mysqli_error($conn);
        }
    }
    ?>
    <div class="content-header">
      <h2 class="page-title">Pass Validity Management</h2>
      <!-- <p class="page-subtitle">Track expiry dates and extend gate passes as per welfare rules.</p> -->
    </div>

    <?php if($queryError): ?>
      <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i>
        <div>Pass validity data load nahi ho pa raha hai: <?= htmlspecialchars($queryError) ?></div>
      </div>
    <?php endif; ?>

    <div class="card glass">
      <div class="card-body" style="padding:0">
        <?php if(empty($expiring)): ?>
          <div class="text-center" style="padding:40px;">No expiring passes found at the moment.</div>
        <?php else: ?>
        <table class="validity-table">
          <thead>
            <tr>
              <th>Workman</th>
              <th>Contractor</th>
              <th>Current Validity</th>
              <th>Days Left</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($expiring as $e): 
                $is_temp = ($e['status'] === 'temporary_issued');
                $expiry_date = $e['expiry_date'];
                if (!$expiry_date) continue;
                $days_left = (int)((strtotime($expiry_date) - strtotime('today')) / (60 * 60 * 24));
                $threshold = $is_temp ? 3 : 7;
            ?>
            <tr>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($e['name']) ?></div>
                <div style="font-size:11px; opacity:0.6"><?= htmlspecialchars($e['acc_number'] ?: 'Temporary') ?></div>
              </td>
              <td><?= htmlspecialchars($e['contractor_name']) ?></td>
              <td>
                <span class="<?= $days_left < 0 ? 'text-danger' : ($days_left <= $threshold ? 'text-warning' : '') ?>">
                  <?= date('d M Y', strtotime($expiry_date)) ?>
                </span>
              </td>
              <td>
                <?php if($days_left < 0): ?>
                  <span class="badge badge-danger">Expired (<?= abs($days_left) ?> days ago)</span>
                <?php elseif($days_left == 0): ?>
                  <span class="badge badge-warning">Expires Today</span>
                <?php elseif($days_left <= $threshold): ?>
                  <span class="badge badge-warning">Expiring Soon (<?= $days_left ?> Days)</span>
                <?php else: ?>
                  <span class="badge badge-success">Active (<?= $days_left ?> Days)</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if($days_left <= $threshold): ?>
                <button class="btn btn-sm btn-primary btn-extend" 
                        data-id="<?= $e['id'] ?>" 
                        data-name="<?= htmlspecialchars($e['name']) ?>" 
                        data-current="<?= $expiry_date ?>">
                  <i class="fas fa-calendar-plus"></i> Extend
                </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>
    </div>

    <!-- Extension Modal -->
    <div id="extendModal" class="modal-backdrop" style="display:none">
      <div class="card glass" style="width:400px; margin: 100px auto; padding:20px;">
        <h3 id="extendName">Extend Pass</h3>
        <p style="opacity:0.6; font-size:13px;">Current Expiry: <span id="currentExpiry"></span></p>
        <form id="extendForm">
          <input type="hidden" name="workman_id" id="extendId">
          <div class="form-group mt-3">
            <label class="form-label">New Expiry Date</label>
            <input type="date" name="new_valid_to" id="newExpiry" class="form-control" required>
          </div>
          <div style="display:flex; justify-content:flex-end; gap:12px; margin-top:24px;">
            <button type="button" class="btn btn-outline-secondary" onclick="closeModal()">Cancel</button>
            <button type="submit" class="btn btn-primary">Save Extension</button>
          </div>
        </form>
      </div>
    </div>

    <style>
      .modal-backdrop { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; backdrop-filter:blur(4px); }
      .validity-table { width:100%; border-collapse:collapse; }
      .validity-table th, .validity-table td { padding:14px 16px; border-bottom:1px solid #e2e8f0; text-align:left; vertical-align:middle; }
      .validity-table th { color:#64748b; font-size:12px; font-weight:800; text-transform:uppercase; }
    </style>

    <script>
      const modal = document.getElementById('extendModal');
      const form = document.getElementById('extendForm');

      document.querySelectorAll('.btn-extend').forEach(btn => {
        btn.addEventListener('click', () => {
          document.getElementById('extendId').value = btn.dataset.id;
          document.getElementById('extendName').textContent = 'Extend Pass: ' + btn.dataset.name;
          document.getElementById('currentExpiry').textContent = btn.dataset.current;
          
          // Default extension +7 days from current
          const current = new Date(btn.dataset.current);
          current.setDate(current.getDate() + 7);
          document.getElementById('newExpiry').value = current.toISOString().split('T')[0];
          
          modal.style.display = 'block';
        });
      });

      function closeModal() {
        modal.style.display = 'none';
      }

      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const data = {
          workman_id: document.getElementById('extendId').value,
          new_valid_to: document.getElementById('newExpiry').value
        };

        try {
          const res = await fetch('../../api/welfare/extend_pass.php', {
            method: 'POST',
            body: JSON.stringify(data),
            headers: { 'Content-Type': 'application/json' }
          });
          const result = await res.json();
          if (result.success) {
            alert(result.message || 'Pass extended successfully!');
            location.reload();
          } else {
            alert('Error: ' + (result.message || result.error || 'Unknown error'));
          }
        } catch (err) {
          alert('API Error: ' + err.message);
        }
      });
    </script>
    <?php
}

renderLayout("Pass Validity Management", 'renderContent', $role, $name);

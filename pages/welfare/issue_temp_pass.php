<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['pass_user', 'super_admin', 'welfare_user', 'welfare_admin']);

include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/temporary_pass_validity.php';

$workman_id = $_GET['id'] ?? 0;

$role = get_normalized_role();
$name = $_SESSION['name'] ?? 'Pass Issuing Officer';

function renderContent() {
    global $conn, $workman_id;
    $tempValidityDays = clms_get_temporary_pass_validity_days($conn);
    
    if (!$workman_id) {
        $issuedPasses = db_fetch_all($conn, "SELECT w.*, c.contractor_name
                                            FROM workmen w
                                            JOIN contractors c ON w.contractor_id = c.id
                                            WHERE (
                                                w.status = 'temporary_issued'
                                                OR COALESCE(w.temp_pass_status, 0) = 1
                                                OR COALESCE(w.temp_pass_no, '') != ''
                                            )
                                            ORDER BY COALESCE(w.temp_valid_to, w.valid_to, w.updated_at) DESC");
        // Show Search UI if no ID is provided
        ?>
        <div class="content-header">
          <h2 class="page-title">Temporary Gate Pass Issue</h2>
        </div>
        <div class="card glass">
          <div class="card-body">
            <form method="GET" action="issue_temp_pass.php">
              <div class="form-group">
                <label class="form-label">Search by Temp ID, Aadhaar, or Name</label>
                <div style="display:flex; gap:10px;">
                  <input type="text" name="search" class="form-control" placeholder="Enter Temp ID, Aadhaar, or Name..." required value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                  <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                </div>
              </div>
            </form>
            
            <?php if (!empty($_GET['search'])): ?>
            <div style="margin-top:20px;">
              <?php
                $search = "%" . $_GET['search'] . "%";
                $res = db_fetch_all($conn, "SELECT w.*, c.contractor_name FROM workmen w JOIN contractors c ON w.contractor_id = c.id WHERE w.name LIKE ? OR w.aadhaar LIKE ? OR w.temp_id LIKE ?", 'sss', [$search, $search, $search]);
              ?>
              <table class="data-table mt-3">
                <thead>
                  <tr>
                    <th>Workman Name</th>
                    <th>Temp ID</th>
                    <th>Aadhaar</th>
                    <th>Contractor</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach($res as $r): ?>
                  <tr>
                    <td><?= htmlspecialchars($r['name']) ?></td>
                    <td><?= htmlspecialchars($r['temp_id']) ?></td>
                    <td><?= htmlspecialchars($r['aadhaar']) ?></td>
                    <td><?= htmlspecialchars($r['contractor_name']) ?></td>
                    <td>
                      <a href="issue_temp_pass.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-success"><i class="fas fa-check"></i> Select</a>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php if(empty($res)): ?>
                  <tr><td colspan="5" class="text-center">No workmen found matching your search.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="card glass mt-4">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-id-card"></i> Issued Temporary ID Cards</div>
          </div>
          <div class="card-body" style="padding:0">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Workman</th>
                  <th>Temp Pass No</th>
                  <th>Contractor</th>
                  <th>Valid From</th>
                  <th>Valid To</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($issuedPasses as $pass):
                  $validFrom = $pass['temp_valid_from'] ?? $pass['valid_from'] ?? null;
                  $validTo = $pass['temp_valid_to'] ?? $pass['valid_to'] ?? null;
                  $daysLeft = $validTo ? ceil((strtotime($validTo) - time()) / 86400) : null;
                ?>
                <tr>
                  <td>
                    <div style="font-weight:600"><?= htmlspecialchars($pass['name'] ?? 'Unknown') ?></div>
                    <div style="font-size:11px;opacity:0.65">ID: <?= htmlspecialchars($pass['id'] ?? '') ?> | <?= htmlspecialchars(ucfirst($pass['worker_type'] ?? 'Workmen')) ?></div>
                  </td>
                  <td><code><?= htmlspecialchars($pass['temp_pass_no'] ?? 'TEMP-PENDING') ?></code></td>
                  <td><?= htmlspecialchars($pass['contractor_name'] ?? 'N/A') ?></td>
                  <td><?= $validFrom ? date('d M Y', strtotime($validFrom)) : 'N/A' ?></td>
                  <td><?= $validTo ? date('d M Y', strtotime($validTo)) : 'N/A' ?></td>
                  <td>
                    <?php if($daysLeft !== null && $daysLeft < 0): ?>
                      <span class="badge badge-danger">Expired</span>
                    <?php elseif($daysLeft !== null && $daysLeft <= 3): ?>
                      <span class="badge badge-warning"><?= $daysLeft ?> Days Left</span>
                    <?php else: ?>
                      <span class="badge badge-success">Active</span>
                    <?php endif; ?>
                  </td>
                  <td style="display:flex;gap:8px;flex-wrap:wrap">
                    <a href="../../api/welfare/download_pass.php?id=<?= (int)$pass['id'] ?>&type=temp" target="_blank" class="btn btn-sm btn-outline">
                      <i class="fas fa-eye"></i> View
                    </a>
                    <a href="../../api/welfare/download_pass.php?id=<?= (int)$pass['id'] ?>&type=temp&action=print" target="_blank" class="btn btn-sm btn-primary">
                      <i class="fas fa-print"></i> Print
                    </a>
                    <?php if(empty($pass['acc_number'])): ?>
                    <a href="acc_generation.php" class="btn btn-sm btn-success">
                      <i class="fas fa-microchip"></i> Generate ACC
                    </a>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php if(empty($issuedPasses)): ?>
                <tr>
                  <td colspan="7" style="text-align:center;padding:40px;color:var(--gray-500)">
                    <i class="fas fa-id-card" style="font-size:42px;opacity:0.3"></i><br>
                    No temporary ID cards issued yet.
                  </td>
                </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
        <?php
        return;
    }
    
    $workman = db_single($conn, "SELECT w.*, c.contractor_name 
                                FROM workmen w 
                                JOIN contractors c ON w.contractor_id = c.id 
                                WHERE w.id = ?", "i", [$workman_id]);
    
    if (!$workman) {
        echo "<div class='alert alert-danger'>Workman not found.</div>";
        return;
    }
    
    if ($workman['pass_issuer_verified'] != 1) {
        echo "<div class='alert alert-warning'>Documents must be verified before issuing a pass. <a href='verify_documents.php?id=$workman_id'>Verify now</a></div>";
        return;
    }
    
    ?>
    <div class="content-header">
      <div style="display:flex; align-items:center; gap:16px;">
        <a href="verify_documents.php?id=<?= $workman_id ?>" class="btn btn-icon btn-outline-secondary"><i class="fas fa-arrow-left"></i></a>
        <div>
          <h2 class="page-title">Issue Temporary Gate Pass</h2>
          <p class="page-subtitle">Configure validity and issue the initial gate pass for <strong><?= htmlspecialchars($workman['name']) ?></strong></p>
        </div>
      </div>
    </div>

    <div class="grid grid-3">
      <div class="col-span-2">
        <div class="card glass">
          <div class="card-header">
            <div class="card-title">Pass Configuration</div>
          </div>
          <div class="card-body">
            <form id="issueTempPassForm">
              <input type="hidden" name="workman_id" value="<?= $workman_id ?>">
              <input type="hidden" name="pass_type" value="temporary">
              
              <div class="grid grid-2">
                <div class="form-group">
                  <label class="form-label">Valid From</label>
                  <input type="date" name="valid_from" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group">
                  <label class="form-label">Valid To</label>
                  <input type="date" name="valid_to" class="form-control" value="<?= htmlspecialchars(date('Y-m-d', strtotime('+' . ($tempValidityDays - 1) . ' days'))) ?>" required>
                </div>
              </div>

              <div class="form-group mt-3">
                <label class="form-label">Duration (Days)</label>
                <input type="number" id="duration" class="form-control" readonly value="<?= (int)$tempValidityDays ?>">
                <p style="font-size:11px; opacity:0.6; margin-top:4px;">Auto-calculated based on From/To dates. Current master validity: <?= (int)$tempValidityDays ?> days.</p>
              </div>

              <div class="form-group mt-3">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="3" placeholder="Additional instructions for gate security"></textarea>
              </div>

              <div class="mt-4" style="display:flex; justify-content:flex-end; gap:12px;">
                <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-print"></i> Issue Temporary Pass</button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div>
        <div class="card glass">
          <div class="card-header">
            <div class="card-title">Worker Summary</div>
          </div>
          <div class="card-body">
            <div style="display:flex; gap:12px; margin-bottom:16px;">
              <div class="user-avatar" style="width:48px; height:48px; font-size:20px;"><?= strtoupper(substr($workman['name'], 0, 2)) ?></div>
              <div>
                <h4 style="margin:0"><?= htmlspecialchars($workman['name']) ?></h4>
                <p style="opacity:0.6; font-size:12px;"><?= htmlspecialchars($workman['contractor_name']) ?></p>
              </div>
            </div>
            <div class="badge badge-success mb-3" style="width:100%; display:block; text-align:center;">
              <i class="fas fa-check-double"></i> All Documents Verified
            </div>
            <div style="font-size:13px;">
              <p><strong>Training:</strong> <span class="text-success">Passed</span></p>
              <p><strong>Type:</strong> <?= ucfirst($workman['worker_type']) ?></p>
            </div>
          </div>
        </div>

        <div class="alert alert-info mt-4" style="font-size:13px;">
          <i class="fas fa-info-circle"></i> After temporary pass issuance, you can proceed to generate the Permanent ACC number.
        </div>
      </div>
    </div>

    <script>
      const form = document.getElementById('issueTempPassForm');
      const fromInput = form.querySelector('input[name="valid_from"]');
      const toInput = form.querySelector('input[name="valid_to"]');
      const durationInput = document.getElementById('duration');
      const maxTempValidityDays = <?= (int)$tempValidityDays ?>;

      function calculateDuration() {
        const from = new Date(fromInput.value);
        const to = new Date(toInput.value);
        if (to >= from) {
          const diff = Math.ceil((to - from) / (1000 * 60 * 60 * 24)) + 1;
          durationInput.value = diff;
        } else {
          durationInput.value = 0;
        }
      }

      fromInput.addEventListener('change', calculateDuration);
      toInput.addEventListener('change', calculateDuration);

      form.addEventListener('submit', async (e) => {
        e.preventDefault();
        calculateDuration();
        if (parseInt(durationInput.value || '0', 10) > maxTempValidityDays) {
          alert(`Temporary pass validity cannot exceed ${maxTempValidityDays} days.`);
          return;
        }
        if (!confirm('Issue temporary gate pass?')) return;

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
          const res = await fetch('../../api/welfare/issue_pass.php', {
            method: 'POST',
            body: JSON.stringify(data),
            headers: { 'Content-Type': 'application/json' }
          });
          const raw = await res.text();
          let result = {};
          try {
            result = raw ? JSON.parse(raw) : {};
          } catch (parseError) {
            result = { success: false, message: raw ? raw.replace(/<[^>]*>/g, ' ').trim() : 'Server returned an empty response.' };
          }
          if (!res.ok && !result.message) result.message = 'Temporary pass issue failed on the server.';
          if (result.success) {
            alert(result.message || 'Temporary pass issued successfully!');
            window.location.href = 'acc_generation.php';
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

renderLayout("Issue Temporary Pass", 'renderContent', $role, $name);

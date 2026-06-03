<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['pass_user', 'super_admin', 'welfare_user', 'welfare_admin']);

include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = get_normalized_role();
$name = $_SESSION['name'] ?? 'Pass Issuing Officer';

function renderContent() {
    global $conn;
    
    // Fetch workmen who are ready for ACC generation (Temporary Issued, No ACC)
    $queryReady = "SELECT w.*, c.contractor_name 
                   FROM workmen w 
                   JOIN contractors c ON w.contractor_id = c.id 
                   WHERE (w.status = 'temporary_issued' OR COALESCE(w.temp_pass_status, 0) = 1 OR COALESCE(w.temp_pass_no, '') != '')
                     AND (w.acc_number IS NULL OR w.acc_number = '')
                   ORDER BY w.updated_at ASC";
    $ready_for_acc = db_fetch_all($conn, $queryReady);

    // Fetch workmen whose ACC is generated but not yet active.
    // Some workers can still have status "verified" after ACC creation, so use the ACC number as the source of truth.
    $query = "SELECT w.*, c.contractor_name 
              FROM workmen w 
              JOIN contractors c ON w.contractor_id = c.id 
              WHERE COALESCE(w.acc_number, '') != ''
                AND w.status <> 'permanent_active'
                AND COALESCE(w.biometric_status, 'pending') <> 'completed'
              ORDER BY w.updated_at DESC";
    $pending_biometric = db_fetch_all($conn, $query);
    
    // Also show recently generated ACCs
    $recent_acc = db_fetch_all($conn, "SELECT w.*, c.contractor_name 
                                       FROM workmen w 
                                       JOIN contractors c ON w.contractor_id = c.id 
                                       WHERE COALESCE(w.acc_number, '') != ''
                                       ORDER BY w.updated_at DESC LIMIT 10");
    ?>
    <div class="content-header">
      <h2 class="page-title">ACC Number Generation & Management</h2>
      <!-- <p class="page-subtitle">Unique identification for permanent passes linked with SAP system.</p> -->
    </div>

    <div class="grid grid-3">
      <div class="col-span-2">
      
        <!-- Section 1: Ready For ACC Generation -->
        <div class="card glass mb-4">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-id-card-alt text-primary"></i> Ready For ACC Generation</div>
          </div>
          <div class="card-body" style="padding:0">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Workman</th>
                  <th>Contractor</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($ready_for_acc as $rfa): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($rfa['name']) ?></strong></td>
                  <td><?= htmlspecialchars($rfa['contractor_name']) ?></td>
                  <td><span class="badge badge-info">TEMP PASS ACTIVE</span></td>
                  <td>
                    <button onclick="generateWorkerACC(<?= $rfa['id'] ?>)" class="btn btn-sm btn-primary">
                      <i class="fas fa-cog"></i> Generate ACC
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Section 2: Awaiting Biometric -->
        <div class="card glass">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-fingerprint text-warning"></i> Awaiting Biometric Enrollment</div>
          </div>
          <div class="card-body" style="padding:0">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Workman</th>
                  <th>ACC Number</th>
                  <th>Contractor</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($pending_biometric as $pb): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($pb['name']) ?></strong></td>
                  <td><code><?= htmlspecialchars($pb['acc_number']) ?></code></td>
                  <td><?= htmlspecialchars($pb['contractor_name']) ?></td>
                  <td>
                    <button onclick="issuePermanentPass(<?= $pb['id'] ?>)" class="btn btn-sm btn-success">
                      <i class="fas fa-id-card-clip"></i> Issue Permanent Pass
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Section 3: Recently Generated ACCs -->
        <div class="card glass mt-4">
          <div class="card-header">
            <div class="card-title"><i class="fas fa-history text-info"></i> Recently Generated ACCs</div>
          </div>
          <div class="card-body" style="padding:0">
            <table class="data-table">
              <thead>
                <tr>
                  <th>Workman</th>
                  <th>ACC Number</th>
                  <th>Date Generated</th>
                  <th>Status</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach($recent_acc as $ra): ?>
                <tr>
                  <td><?= htmlspecialchars($ra['name']) ?></td>
                  <td><code><?= htmlspecialchars($ra['acc_number']) ?></code></td>
                  <td><?= date('d M Y', strtotime($ra['updated_at'])) ?></td>
                  <?php
                    $isActive = $ra['status'] == 'permanent_active';
                    $statusLabel = $isActive ? 'PERMANENT ACTIVE' : strtoupper(str_replace('_', ' ', $ra['status']));
                  ?>
                  <td><span class="badge badge-<?= $isActive ? 'success' : 'warning' ?>"><?= htmlspecialchars($statusLabel) ?></span></td>
                  <td>
                    <?php if ($isActive): ?>
                      <a href="../../api/welfare/download_pass.php?id=<?= (int)$ra['id'] ?>&type=perm&action=download" target="_blank" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-file-download"></i> Download
                      </a>
                    <?php else: ?>
                      <button onclick="issuePermanentPass(<?= (int)$ra['id'] ?>)" class="btn btn-sm btn-success">
                        <i class="fas fa-id-card-clip"></i> Issue Permanent
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div>
        <div class="card glass">
          <div class="card-header">
            <div class="card-title">ACC Generation Rules</div>
          </div>
          <div class="card-body" style="font-size:13px; line-height:1.6;">
            <p><i class="fas fa-info-circle text-primary"></i> <strong>Format:</strong> <code>ACC-YYYY-XXXXX</code></p>
            <p><i class="fas fa-info-circle text-primary"></i> <strong>Series:</strong> Yearly sequential increment.</p>
            <hr style="opacity:0.1; margin:12px 0;">
            <p><strong>Step-by-Step Flow:</strong></p>
            <ul style="padding-left:20px; opacity:0.8;">
              <li>Final document verification.</li>
              <li>Temporary pass issuance.</li>
              <li>ACC number generation.</li>
              <li>Biometric enrollment.</li>
              <li>Pass activation.</li>
            </ul>
          </div>
        </div>

        <div class="alert alert-info mt-4">
          <i class="fas fa-sync"></i> ACC numbers are automatically synchronized with the SAP system once activated.
        </div>
      </div>
    </div>

    <script>
      async function parsePassApiResponse(res, fallbackMessage) {
        const raw = await res.text();
        let result = {};
        try {
          result = raw ? JSON.parse(raw) : {};
        } catch (parseError) {
          result = { success: false, message: raw ? raw.replace(/<[^>]*>/g, ' ').trim() : 'Server returned an empty response.' };
        }
        if (!res.ok && !result.message) result.message = fallbackMessage;
        return result;
      }

      async function issuePermanentPass(id) {
        if (!confirm('Issue permanent pass and activate ACC for this workman?')) return;
        
        try {
          const res = await fetch('../../api/welfare/complete_biometric.php', {
            method: 'POST',
            body: JSON.stringify({ workman_id: id }),
            headers: { 
              'Content-Type': 'application/json',
              'X-CSRF-Token': window.CLMS_CSRF_TOKEN || ''
            }
          });
          const result = await parsePassApiResponse(res, 'Permanent pass issue failed on the server. Please check api_errors.log.');
          if (result.success) {
            alert(result.message || 'Permanent pass issued successfully.');
            location.reload();
          } else {
            alert('Error: ' + (result.message || result.error || 'Unknown error'));
          }
        } catch (err) {
          alert('API Error: ' + err.message);
        }
      }

      async function generateWorkerACC(id) {
        if (!confirm('Generate ACC number for this workman?')) return;
        
        try {
          const res = await fetch('../../api/welfare/generate_worker_acc.php', {
            method: 'POST',
            body: JSON.stringify({ workman_id: id }),
            headers: { 
              'Content-Type': 'application/json',
              'X-CSRF-Token': window.CLMS_CSRF_TOKEN || ''
            }
          });
          const result = await parsePassApiResponse(res, 'ACC generation failed on the server. Please check api_errors.log.');
          if (result.success) {
            alert(result.message || 'ACC Generated successfully!');
            location.reload();
          } else {
            alert('Error: ' + (result.message || result.error || 'Unknown error'));
          }
        } catch (err) {
          alert('API Error: ' + err.message);
        }
      }
    </script>
    <?php
}

renderLayout("ACC Number Generation", 'renderContent', $role, $name);

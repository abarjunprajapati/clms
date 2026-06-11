<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin', 'pass_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare User';

function renderContent() {
    global $conn, $role;
    $app_id = $_GET['id'] ?? 0;
    
    // Fetch Application
    $app = db_single($conn, "SELECT a.*, c.contractor_name, c.status as contractor_status 
                             FROM applications a 
                             JOIN contractors c ON a.contractor_id = c.id 
                             WHERE a.id=?", 'i', [$app_id]);
                             
    if (!$app) {
        echo "<div class='content-header'><h2>Application Not Found</h2></div>";
        return;
    }
    
    $normalizedRole = get_normalized_role();
    $is_execution = ($normalizedRole === 'welfare');

    // MOCK data fetches for tabs (In a real system we fetch from respective tables)
    $training_status = 'pass'; // MOCK
    $documents = [
        ['type' => 'medical', 'status' => 'pending'],
        ['type' => 'police', 'status' => 'pending'],
        ['type' => 'insurance', 'status' => 'pending']
    ];
    $all_verified = false; // MOCK
    
    ?>
    <style>
      .tab-nav { display:flex; gap:10px; border-bottom:1px solid #e2e8f0; margin-bottom:20px; }
      .tab-btn { padding:10px 20px; border:none; background:none; border-bottom:2px solid transparent; cursor:pointer; font-weight:600; color:var(--gray-500); }
      .tab-btn.active { border-bottom-color:var(--primary); color:var(--primary); }
      .tab-pane { display:none; }
      .tab-pane.active { display:block; animation: fadeIn 0.3s ease; }
      @keyframes fadeIn { from { opacity:0; } to { opacity:1; } }
      
      .action-panel { background:var(--gray-50); border-radius:8px; padding:20px; margin-top:20px; border:1px solid #e2e8f0; }
    </style>

    <div class="content-header">
      <h2 class="page-title">Application Processing : #<?= htmlspecialchars($app['id']) ?></h2>
      <p class="page-subtitle">Contractor: <?= htmlspecialchars($app['contractor_name']) ?> | Status: <span class="badge badge-warning"><?= ucfirst($app['current_status']) ?></span></p>
    </div>

    <div class="card glass">
      <div class="card-body">
          <div class="tab-nav">
              <button class="tab-btn active" onclick="openTab('enrollment')">1. Enrollment (4A)</button>
              <button class="tab-btn" onclick="openTab('training')">2. Training</button>
              <button class="tab-btn" onclick="openTab('documents')">3. Documents (6A)</button>
              <button class="tab-btn" onclick="openTab('final')">4. Final Decision</button>
          </div>
          
          <!-- TAB 1: ENROLLMENT -->
          <div id="enrollment" class="tab-pane active">
              <h4>Enrollment Data (Read-only)</h4>
              <p>Workmen Details...</p>
              
              <?php if($is_execution): ?>
              <div class="action-panel">
                  <h5>Decision Gate</h5>
                  <?php if($app['contractor_status'] !== 'approved'): ?>
                      <div class="alert alert-danger">Contractor is not approved. Action blocked.</div>
                  <?php else: ?>
                      <form id="form_enrollment" onsubmit="submitDecision(event, 'enrollment')">
                          <input type="hidden" name="action_type" value="verify_enrollment">
                          <label>Verify Status</label>
                          <select name="decision" class="form-input" required onchange="toggleReject('enrollment', this.value)">
                              <option value="">Select...</option>
                              <option value="allow">Verify & Allow Next Step</option>
                              <option value="reject">Reject</option>
                          </select>
                          <div id="enrollment_reject_box" style="display:none;margin-top:10px;">
                              <label>Reject Reason (Required)</label>
                              <textarea name="reason" class="form-input"></textarea>
                          </div>
                          <button type="submit" class="btn btn-primary" style="margin-top:10px">Submit Decision</button>
                      </form>
                  <?php endif; ?>
              </div>
              <?php endif; ?>
          </div>

          <!-- TAB 2: TRAINING -->
          <div id="training" class="tab-pane">
              <h4>Training Result (Read-only)</h4>
              <p>Safety Training Status: <strong class="text-success"><?= strtoupper($training_status) ?></strong></p>
              
              <?php if($is_execution): ?>
              <div class="action-panel">
                  <h5>Decision Gate (No Override Allowed)</h5>
                  <form id="form_training" onsubmit="submitDecision(event, 'training')">
                      <input type="hidden" name="action_type" value="verify_training">
                      <select name="decision" class="form-input" required onchange="toggleReject('training', this.value)">
                          <option value="">Select...</option>
                          <option value="allow">Acknowledge & Allow Next Step</option>
                          <option value="block">Block</option>
                      </select>
                      <div id="training_reject_box" style="display:none;margin-top:10px;">
                          <label>Block Reason</label>
                          <textarea name="reason" class="form-input"></textarea>
                      </div>
                      <button type="submit" class="btn btn-primary" style="margin-top:10px">Submit Decision</button>
                  </form>
              </div>
              <?php endif; ?>
          </div>

          <!-- TAB 3: DOCUMENTS -->
          <div id="documents" class="tab-pane">
              <h4>Document Checklist</h4>
              <form id="form_documents" onsubmit="submitDecision(event, 'documents')">
                  <input type="hidden" name="action_type" value="verify_documents">
                  <table class="data-table">
                      <thead>
                          <tr><th>Document Type</th><th>Status</th><th>Action</th></tr>
                      </thead>
                      <tbody>
                          <?php foreach($documents as $doc): ?>
                          <tr>
                              <td><?= ucfirst($doc['type']) ?></td>
                              <td><span class="badge badge-warning"><?= ucfirst($doc['status']) ?></span></td>
                              <td>
                                  <?php if($is_execution): ?>
                                  <select name="doc_<?= $doc['type'] ?>" class="form-input" style="width:120px">
                                      <option value="pending">Pending</option>
                                      <option value="verified">Verified</option>
                                      <option value="rejected">Rejected</option>
                                  </select>
                                  <?php endif; ?>
                              </td>
                          </tr>
                          <?php endforeach; ?>
                      </tbody>
                  </table>
                  
                  <?php if($is_execution): ?>
                  <div class="action-panel">
                      <h5>Application-Level Decision</h5>
                      <p class="text-muted" style="font-size:12px">Partial rejections will set application to 'reupload_pending'.</p>
                      <select name="decision" class="form-input" required onchange="toggleReject('documents', this.value)">
                          <option value="">Select...</option>
                          <option value="approve">Approve Documents</option>
                          <option value="reject">Reject Application</option>
                      </select>
                      <div id="documents_reject_box" style="display:none;margin-top:10px;">
                          <label>Reject Reason</label>
                          <textarea name="reason" class="form-input"></textarea>
                      </div>
                      <button type="submit" class="btn btn-primary" style="margin-top:10px">Submit Final Docs Decision</button>
                  </div>
                  <?php endif; ?>
              </form>
          </div>

          <!-- TAB 4: FINAL DECISION -->
          <div id="final" class="tab-pane">
              <h4>Final Validation & Forward</h4>
              <div style="background:var(--gray-50);padding:20px;border-radius:8px">
                  <p><strong>Training Status:</strong> <?= $training_status == 'pass' ? '✅ Pass' : '❌ Fail/Pending' ?></p>
                  <p><strong>All Documents Verified:</strong> <?= $all_verified ? '✅ Yes' : '❌ No' ?></p>
              </div>
              
              <?php if($is_execution): ?>
              <div class="action-panel">
                  <h5>Forward to Pass Issuer</h5>
                  <?php if(!$all_verified || $training_status !== 'pass'): ?>
                      <div class="alert alert-danger">Cannot forward. Documents or training incomplete.</div>
                  <?php else: ?>
                      <form id="form_final" onsubmit="submitDecision(event, 'final')">
                          <input type="hidden" name="action_type" value="forward_pass">
                          <label><input type="checkbox" required> I confirm all details are strictly verified</label>
                          <br>
                          <button type="submit" class="btn btn-success" style="margin-top:15px">Forward to Pass Issuer</button>
                      </form>
                  <?php endif; ?>
              </div>
              <?php endif; ?>
          </div>

      </div>
    </div>

    <script>
    function openTab(tabId) {
        document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
        document.getElementById(tabId).classList.add('active');
        event.currentTarget.classList.add('active');
    }
    
    function toggleReject(tab, value) {
        const box = document.getElementById(tab + '_reject_box');
        if(!box) return;
        if(value === 'reject' || value === 'block') {
            box.style.display = 'block';
            box.querySelector('textarea').required = true;
        } else {
            box.style.display = 'none';
            box.querySelector('textarea').required = false;
        }
    }

    async function submitDecision(e, tab) {
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        data.application_id = <?= $app_id ?>;
        
        try {
            const res = await fetch('../../api/welfare/process_application.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if(result.success) {
                alert('Action completed successfully.');
                location.reload();
            } else {
                alert('Error: ' + result.error);
            }
        } catch(err) {
            alert('Request failed');
        }
    }
    </script>
    <?php
}

renderLayout("Application Processing", 'renderContent', $role, $name);


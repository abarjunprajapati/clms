<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/layout.php';
require_once '../../include/gate_pass_document_master.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = (int)($_SESSION['user_id'] ?? 0);
clms_get_portal_contractor($conn);

function gatePassColumnExists($conn, $table, $column) {
    $table = mysqli_real_escape_string($conn, $table);
    $column = mysqli_real_escape_string($conn, $column);
    $result = mysqli_query($conn, "SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && mysqli_num_rows($result) > 0;
}

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id, contractor_name FROM contractors WHERE user_id = ? ORDER BY id DESC LIMIT 1", 'i', [$user_id]);
    $contractorId = (int)($contractor['id'] ?? 0);
    $documents = clms_get_gate_pass_documents_for_form($conn);
    $roleTypeExpr = gatePassColumnExists($conn, 'workmen', 'role_type') ? "COALESCE(w.role_type, '')" : "''";
    $safetyEnrollmentExpr = gatePassColumnExists($conn, 'workmen', 'safety_enrollment_status') ? "COALESCE(w.safety_enrollment_status, 'approved')" : "'approved'";

    $workers = $contractorId ? db_fetch_all(
        $conn,
        "SELECT
            w.id, w.name, w.aadhaar, w.temp_id, w.worker_type, $roleTypeExpr AS role_type, w.trade, w.skill,
            w.training_valid_till, $safetyEnrollmentExpr AS safety_enrollment_status,
            COALESCE(w.training_status, '') AS training_status,
            COALESCE(w.safety_training_status, '') AS safety_training_status,
            (
                SELECT gpr.status
                FROM gate_pass_request_workers gprw
                JOIN gate_pass_requests gpr ON gpr.id = gprw.request_id
                WHERE gprw.workman_id = w.id
                  AND LOWER(COALESCE(gpr.status, 'pending')) IN ('draft','pending','submitted','reupload_required')
                ORDER BY gpr.id DESC LIMIT 1
            ) AS gate_pass_request_status
         FROM workmen w
         WHERE w.contractor_id = ?
           AND LOWER($safetyEnrollmentExpr) = 'approved'
           AND (
                LOWER(COALESCE(w.training_status, '')) IN ('pass','passed','training_passed','qualified','completed')
                OR LOWER(COALESCE(w.safety_training_status, '')) IN ('1','training_passed','passed','pass','qualified','completed')
           )
           AND (w.training_valid_till IS NULL OR w.training_valid_till >= CURDATE())
         ORDER BY w.name ASC",
        'i',
        [$contractorId]
    ) : [];

    $requests = $contractorId ? db_fetch_all(
        $conn,
        "SELECT gpr.request_no, gpr.status, gpr.created_at, w.name AS worker_name, w.temp_id
         FROM gate_pass_requests gpr
         JOIN gate_pass_request_workers gprw ON gprw.request_id = gpr.id
         JOIN workmen w ON w.id = gprw.workman_id
         WHERE gpr.contractor_id = ?
           AND LOWER(COALESCE(gpr.status, '')) <> 'draft'
         ORDER BY gpr.id DESC
         LIMIT 10",
        'i',
        [$contractorId]
    ) : [];
    $availableWorkerCount = count(array_filter($workers, function($worker) {
        return trim((string)($worker['gate_pass_request_status'] ?? '')) === '';
    }));
?>
<div class="content-header">
  <div>
    <h2 class="page-title"><i class="fas fa-id-badge" style="color:#2563eb;margin-right:10px;"></i>Gate Pass Creation Request</h2>
    <p class="page-subtitle">Select an eligible employee, upload supporting documents, and submit for approval.</p>
  </div>
  <a href="pass_status.php" class="btn btn-outline"><i class="fas fa-list-check"></i> Request Tracker</a>
</div>

<?php if (!$contractorId): ?>
  <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i><div>Complete contractor registration first.</div></div>
<?php return; endif; ?>

<div class="flow-steps" aria-label="Gate Pass request progress">
  <div class="flow-step active" data-step-indicator="1"><span>1</span><div><strong>Select Employee</strong><small>Safety-cleared employees</small></div></div>
  <div class="flow-line"></div>
  <div class="flow-step" data-step-indicator="2"><span>2</span><div><strong>Upload Documents</strong><small>Required supporting files</small></div></div>
  <div class="flow-line"></div>
  <div class="flow-step" data-step-indicator="3"><span>3</span><div><strong>Approval Process</strong><small>Starts after final submit</small></div></div>
</div>

<section id="employeeStep" class="workflow-panel">
  <div class="panel-heading">
    <div>
      <h3>Select Employee</h3>
      <p>Only enrollment-approved employees with completed Safety Training are displayed.</p>
    </div>
    <div class="eligible-count"><?= $availableWorkerCount ?> available</div>
  </div>
  <div class="search-strip">
    <div class="search-field"><i class="fas fa-fingerprint"></i><input type="search" id="aadhaarSearch" placeholder="Search Aadhaar No."></div>
    <div class="search-field"><i class="fas fa-user"></i><input type="search" id="nameSearch" placeholder="Search Employee Name"></div>
    <button type="button" class="btn btn-primary" id="searchEmployees"><i class="fas fa-search"></i> Search</button>
    <button type="button" class="btn btn-outline" id="resetSearch"><i class="fas fa-rotate-left"></i> Reset</button>
  </div>

  <div class="table-wrap">
    <table class="data-table employee-table">
      <thead><tr><th>Select</th><th>S.No.</th><th>Aadhaar No.</th><th>Employee Name</th><th>Category</th><th>Status</th><th>Action</th></tr></thead>
      <tbody id="employeeRows">
      <?php if (!$workers): ?>
        <tr><td colspan="7"><div class="empty-state"><i class="fas fa-user-shield"></i><strong>No eligible employees found</strong><span>Enrollment approval and Safety Training completion are required.</span></div></td></tr>
      <?php endif; ?>
      <?php foreach ($workers as $workerIndex => $worker):
        $category = $worker['worker_type'] ?: ($worker['role_type'] ?: ($worker['skill'] ?: 'Worker'));
        $hasRequest = trim((string)$worker['gate_pass_request_status']) !== '';
      ?>
        <tr data-name="<?= htmlspecialchars(strtolower((string)$worker['name'])) ?>" data-aadhaar="<?= htmlspecialchars(strtolower((string)$worker['aadhaar'])) ?>">
          <td><input type="checkbox" class="worker-checkbox" value="<?= (int)$worker['id'] ?>" <?= $hasRequest ? 'disabled' : '' ?>></td>
          <td><?= $workerIndex + 1 ?></td>
          <td><?= htmlspecialchars($worker['aadhaar'] ?: '-') ?></td>
          <td><strong><?= htmlspecialchars($worker['name']) ?></strong><small><?= htmlspecialchars($worker['temp_id'] ?: 'No Temp ID') ?></small></td>
          <td><?= htmlspecialchars($category) ?></td>
          <td>
            <?php if ($hasRequest): ?>
              <span class="status-pill warning"><?= htmlspecialchars(strtoupper(str_replace('_', ' ', $worker['gate_pass_request_status']))) ?></span>
            <?php else: ?>
              <span class="status-pill success"><i class="fas fa-check"></i> Approved</span>
            <?php endif; ?>
          </td>
          <td>
          <?php if ($hasRequest): ?>
            <a class="btn btn-sm btn-outline" href="pass_status.php"><i class="fas fa-eye"></i> View Status</a>
          <?php else: ?>
            <button type="button" class="btn btn-sm btn-primary select-worker" data-worker='<?= htmlspecialchars(json_encode([
              'id' => (int)$worker['id'],
              'name' => $worker['name'],
              'aadhaar' => $worker['aadhaar'],
              'category' => $category,
              'temp_id' => $worker['temp_id'],
            ]), ENT_QUOTES, 'UTF-8') ?>'><i class="fas fa-arrow-right"></i> Submit</button>
          <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<section id="documentStep" class="workflow-panel hidden">
  <div class="panel-heading">
    <div>
      <h3>Gate Pass Supporting Document Upload</h3>
      <p>Upload all mandatory documents before submitting the request.</p>
    </div>
    <span class="status-pill neutral" id="documentProgress">0 / <?= count($documents) ?> uploaded</span>
  </div>

  <div class="employee-summary">
    <div><span>Employee Name</span><strong id="selectedName">-</strong></div>
    <div><span>Aadhaar No.</span><strong id="selectedAadhaar">-</strong></div>
    <div><span>Category</span><strong id="selectedCategory">-</strong></div>
    <div><span>Temp ID</span><strong id="selectedTempId">-</strong></div>
  </div>

  <form id="documentForm" enctype="multipart/form-data">
    <input type="hidden" name="request_id" id="requestId">
    <input type="hidden" name="workman_id" id="selectedWorkerId">
    <input type="hidden" name="action" value="submit">
    <div class="document-list">
      <?php foreach ($documents as $index => $doc): ?>
      <div class="document-row">
        <div class="document-number"><?= $index + 1 ?></div>
        <div class="document-info"><strong><?= htmlspecialchars($doc['label']) ?></strong><small><?= htmlspecialchars($doc['hint']) ?></small></div>
        <button type="button" class="btn btn-sm btn-outline download-format" data-document="<?= htmlspecialchars($doc['label']) ?>"><i class="fas fa-download"></i> Download</button>
        <label class="btn btn-sm btn-outline upload-control" for="gate-doc-<?= htmlspecialchars($doc['id']) ?>"><i class="fas fa-upload"></i> Choose File</label>
        <input class="gate-doc-input" type="file" id="gate-doc-<?= htmlspecialchars($doc['id']) ?>" name="<?= htmlspecialchars($doc['key']) ?>" accept=".pdf,.jpg,.jpeg,.png" <?= $doc['required'] ? 'required' : '' ?>>
        <span class="file-state" data-file-state>Pending</span>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="form-group remarks-field"><label class="form-label">Remarks</label><textarea class="form-control" name="remarks" rows="2" placeholder="Optional remarks for the approving officer"></textarea></div>
    <div class="form-actions">
      <button type="button" class="btn btn-outline" id="backToEmployees"><i class="fas fa-arrow-left"></i> Back</button>
      <button type="button" class="btn btn-outline" id="saveGatePassDraft"><i class="fas fa-floppy-disk"></i> Save Draft</button>
      <button type="submit" class="btn btn-primary" id="submitGatePass"><i class="fas fa-paper-plane"></i> Submit Gate Pass Request</button>
    </div>
  </form>
</section>

<section class="workflow-panel request-panel">
  <div class="panel-heading"><div><h3>Recent Gate Pass Requests</h3><p>Submitted requests and current approval status.</p></div></div>
  <div class="table-wrap">
    <table class="data-table"><thead><tr><th>Request No.</th><th>Employee</th><th>Temp ID</th><th>Status</th><th>Submitted</th></tr></thead>
      <tbody>
      <?php if (!$requests): ?><tr><td colspan="5" class="muted-cell">No submitted gate pass requests yet.</td></tr><?php endif; ?>
      <?php foreach ($requests as $request): ?>
        <tr><td><strong><?= htmlspecialchars($request['request_no']) ?></strong></td><td><?= htmlspecialchars($request['worker_name']) ?></td><td><?= htmlspecialchars($request['temp_id'] ?: '-') ?></td><td><span class="status-pill warning"><?= htmlspecialchars(strtoupper(str_replace('_', ' ', $request['status']))) ?></span></td><td><?= !empty($request['created_at']) ? date('d M Y, h:i A', strtotime($request['created_at'])) : '-' ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>

<style>
.hidden{display:none!important}.flow-steps{display:flex;align-items:center;margin:0 0 18px;padding:14px 16px;border:1px solid var(--border-color);background:var(--card-bg,#fff);border-radius:8px}.flow-step{display:flex;align-items:center;gap:9px;color:var(--text-muted);min-width:180px}.flow-step>span{width:30px;height:30px;border-radius:50%;display:grid;place-items:center;background:#e2e8f0;color:#475569;font-weight:800}.flow-step div{display:flex;flex-direction:column}.flow-step strong{font-size:13px}.flow-step small{font-size:11px}.flow-step.active{color:#1d4ed8}.flow-step.active>span{background:#2563eb;color:#fff}.flow-step.done>span{background:#16a34a;color:#fff}.flow-line{height:1px;background:var(--border-color);flex:1;margin:0 12px}.workflow-panel{border:1px solid var(--border-color);background:var(--card-bg,#fff);border-radius:8px;margin-bottom:18px;overflow:hidden}.panel-heading{padding:15px 18px;border-bottom:1px solid var(--border-color);display:flex;align-items:center;justify-content:space-between;gap:12px}.panel-heading h3{font-size:16px;margin:0}.panel-heading p{font-size:12px;color:var(--text-muted);margin:3px 0 0}.eligible-count{font-weight:800;color:#1d4ed8;background:#eff6ff;padding:6px 9px;border-radius:6px}.search-strip{display:flex;gap:10px;padding:12px 18px;border-bottom:1px solid var(--border-color)}.search-field{position:relative;flex:1}.search-field i{position:absolute;left:12px;top:11px;color:#64748b}.search-field input{width:100%;padding:9px 12px 9px 34px;border:1px solid var(--border-color);border-radius:6px;background:var(--input-bg,#fff);color:var(--text-primary)}.table-wrap{overflow:auto}.employee-table td:first-child,.employee-table th:first-child{text-align:center;width:60px}.worker-checkbox{width:16px;height:16px;cursor:pointer}.data-table td small{display:block;color:var(--text-muted);font-size:11px;margin-top:3px}.status-pill{display:inline-flex;align-items:center;gap:5px;padding:4px 7px;border-radius:5px;font-size:10px;font-weight:800;white-space:nowrap}.status-pill.success{background:#dcfce7;color:#166534}.status-pill.warning{background:#fef3c7;color:#92400e}.status-pill.neutral{background:#e2e8f0;color:#475569}.employee-summary{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:var(--border-color);border-bottom:1px solid var(--border-color)}.employee-summary>div{background:var(--card-bg,#fff);padding:12px 16px}.employee-summary span{display:block;font-size:10px;text-transform:uppercase;color:var(--text-muted);font-weight:800;margin-bottom:3px}.employee-summary strong{font-size:13px}.document-list{padding:14px 18px}.document-row{display:grid;grid-template-columns:28px minmax(220px,1fr) 105px 110px 130px;gap:10px;align-items:center;padding:10px 0;border-bottom:1px solid var(--border-color)}.document-number{font-weight:800;color:#64748b}.document-info{display:flex;flex-direction:column}.document-info strong{font-size:13px}.document-info small{font-size:11px;color:var(--text-muted);margin-top:3px}.gate-doc-input{position:absolute;width:1px;height:1px;opacity:0}.file-state{font-size:11px;color:#64748b;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.file-state.uploaded{color:#15803d;font-weight:700}.remarks-field{padding:0 18px}.form-actions{display:flex;justify-content:flex-end;gap:10px;padding:14px 18px;border-top:1px solid var(--border-color)}.empty-state{display:flex;flex-direction:column;align-items:center;gap:5px;padding:30px;color:var(--text-muted)}.empty-state i{font-size:28px}.muted-cell{text-align:center;color:var(--text-muted);padding:24px!important}.request-panel{margin-top:8px}@media(max-width:900px){.flow-step{min-width:0}.flow-step small{display:none}.employee-summary{grid-template-columns:1fr 1fr}.document-row{grid-template-columns:28px 1fr 90px}.document-row .status-pill{display:none}.upload-control{grid-column:2}.file-state{grid-column:3}.panel-heading{align-items:flex-start}.search-strip{flex-direction:column}}
</style>

<script>
const employeeStep = document.getElementById('employeeStep');
const documentStep = document.getElementById('documentStep');
const documentForm = document.getElementById('documentForm');
const aadhaarSearch = document.getElementById('aadhaarSearch');
const nameSearch = document.getElementById('nameSearch');

function fireMessage(icon, title, text) {
  if (typeof Swal !== 'undefined' && Swal.fire) return Swal.fire({icon, title, text, confirmButtonColor:'#1d4ed8'});
  alert(title + ': ' + text);
  return Promise.resolve();
}

function setStep(step) {
  document.querySelectorAll('[data-step-indicator]').forEach(item => {
    const value = Number(item.dataset.stepIndicator);
    item.classList.toggle('active', value === step);
    item.classList.toggle('done', value < step);
  });
}

async function selectEmployee(worker, button = null) {
  if (button) button.disabled = true;
  try {
    const body = new FormData();
    body.append('action', 'prepare');
    body.append('workman_id', worker.id);
    const response = await fetch('../../api/save_gate_pass_request.php', {method:'POST', body});
    const result = await response.json();
    if (!result.success) throw new Error(result.message || 'Unable to prepare Gate Pass request.');
    document.getElementById('requestId').value = result.data?.request_id || result.request_id || '';
    document.getElementById('selectedWorkerId').value = worker.id;
    document.getElementById('selectedName').textContent = worker.name || '-';
    document.getElementById('selectedAadhaar').textContent = worker.aadhaar || '-';
    document.getElementById('selectedCategory').textContent = worker.category || '-';
    document.getElementById('selectedTempId').textContent = worker.temp_id || '-';
    employeeStep.classList.add('hidden');
    documentStep.classList.remove('hidden');
    setStep(2);
    window.scrollTo({top:0, behavior:'smooth'});
  } catch (error) {
    fireMessage('error', 'Gate Pass Request', error.message);
  } finally {
    if (button) button.disabled = false;
  }
}

document.querySelectorAll('.select-worker').forEach(button => {
  button.addEventListener('click', e => selectEmployee(JSON.parse(e.currentTarget.dataset.worker), e.currentTarget));
});
document.querySelectorAll('.worker-checkbox').forEach(checkbox => {
  checkbox.addEventListener('change', e => {
    document.querySelectorAll('.worker-checkbox').forEach(other => {
      if (other !== e.currentTarget) other.checked = false;
    });
    const button = e.currentTarget.closest('tr')?.querySelector('.select-worker');
    if (button) button.click();
  });
});
function filterEmployees() {
  const aadhaar = aadhaarSearch.value.trim().toLowerCase();
  const name = nameSearch.value.trim().toLowerCase();
  document.querySelectorAll('#employeeRows tr[data-name]').forEach(row => {
    row.classList.toggle('hidden', !row.dataset.aadhaar.includes(aadhaar) || !row.dataset.name.includes(name));
  });
}
document.getElementById('searchEmployees')?.addEventListener('click', filterEmployees);
[aadhaarSearch, nameSearch].forEach(input => input?.addEventListener('keydown', event => {
  if (event.key === 'Enter') {
    event.preventDefault();
    filterEmployees();
  }
}));
document.getElementById('resetSearch')?.addEventListener('click', () => {
  aadhaarSearch.value = '';
  nameSearch.value = '';
  filterEmployees();
});
document.getElementById('backToEmployees')?.addEventListener('click', () => {
  documentStep.classList.add('hidden');
  employeeStep.classList.remove('hidden');
  setStep(1);
});

function updateDocumentProgress() {
  const inputs = Array.from(document.querySelectorAll('.gate-doc-input'));
  const uploaded = inputs.filter(input => input.files.length > 0).length;
  document.getElementById('documentProgress').textContent = `${uploaded} / ${inputs.length} uploaded`;
}
document.querySelectorAll('.gate-doc-input').forEach(input => {
  input.addEventListener('change', () => {
    const state = input.closest('.document-row').querySelector('[data-file-state]');
    state.textContent = input.files[0]?.name || 'Pending';
    state.classList.toggle('uploaded', Boolean(input.files[0]));
    updateDocumentProgress();
  });
});
document.querySelectorAll('.download-format').forEach(button => {
  button.addEventListener('click', event => {
    const documentName = event.currentTarget.dataset.document || 'Gate Pass Document';
    const content = `GATE PASS SUPPORTING DOCUMENT FORMAT\n\nDocument: ${documentName}\nEmployee Name: ____________________\nAadhaar No.: ______________________\nDate: _____________________________\n\nDeclaration / Details:\n\n\nSignature: ________________________\n`;
    const blob = new Blob([content], {type:'text/plain'});
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = documentName.replace(/[^a-z0-9]+/gi, '_').toLowerCase() + '_format.txt';
    link.click();
    URL.revokeObjectURL(link.href);
  });
});

async function saveGatePass(action) {
  const button = action === 'save_draft' ? document.getElementById('saveGatePassDraft') : document.getElementById('submitGatePass');
  const original = button.innerHTML;
  button.disabled = true;
  button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving';
  try {
    const formData = new FormData(documentForm);
    formData.set('action', action);
    const response = await fetch('../../api/save_gate_pass_request.php', {method:'POST', body:formData});
    const result = await response.json();
    if (!result.success) throw new Error(result.message || 'Gate Pass request could not be saved.');
    if (action === 'save_draft') {
      await fireMessage('success', 'Draft Saved', 'Uploaded documents have been saved. You can continue and submit later.');
      return;
    }
    setStep(3);
    await fireMessage('success', 'Gate Pass Request Submitted', 'Approval process has been initiated.');
    location.reload();
  } catch (error) {
    fireMessage('error', action === 'save_draft' ? 'Draft Save Failed' : 'Submission Failed', error.message);
  } finally {
    button.disabled = false;
    button.innerHTML = original;
  }
}
document.getElementById('saveGatePassDraft')?.addEventListener('click', () => saveGatePass('save_draft'));

documentForm?.addEventListener('submit', async event => {
  event.preventDefault();
  if (!documentForm.reportValidity()) return;
  saveGatePass('submit');
});
</script>
<?php
}

renderLayout("Gate Pass Creation Request", 'renderContent', $role, $name);

<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['execution_officer', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Execution Officer';
$userId = $_SESSION['user_id'];

// Get Officer ID
$officerRes = db_single($conn, "SELECT id FROM execution_officers WHERE employee_code = (SELECT contractor_id FROM users WHERE id = ?)", 'i', [$userId]);
$officerId = $officerRes['id'] ?? 0;

function renderContent() {
    global $conn, $officerId;

    // Data for dropdowns
    $contractors = db_fetch_all($conn, "SELECT c.id, c.contractor_name FROM contractors c 
                                       JOIN execution_officer_contractors eoc ON c.id = eoc.contractor_id 
                                       WHERE eoc.execution_officer_id = ?", 'i', [$officerId]);

    $prefilledWorkmanId = $_GET['workman_id'] ?? '';
    $prefilledContractorId = $_GET['contractor_id'] ?? '';
    $prefilledType = $_GET['type'] ?? '';
    $prefilledSeverity = $_GET['severity'] ?? 'medium';
    $showModal = !empty($prefilledType) ? 'true' : 'false';

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-bullhorn" style="color:#ef4444;margin-right:8px"></i>Escalation Management</h2>
            <!-- <p class="page-subtitle">Forward critical issues to Safety, Welfare, or Admin departments for final action.</p> -->
        </div>
        <div style="display:flex;gap:8px">
            <button class="btn btn-danger" onclick="showEscalationModal()"><i class="fas fa-plus"></i> Raise Escalation</button>
        </div>
    </div>

    <div class="card glass">
        <div class="card-header"><div class="card-title">My Active Escalations</div></div>
        <div class="card-body" style="padding:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Target Dept</th>
                        <th>Type</th>
                        <th>Contractor / Workman</th>
                        <th>Reason</th>
                        <th>Severity</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $list = db_fetch_all($conn, "SELECT e.*, w.name as workman_name, c.contractor_name 
                                                FROM execution_escalations e 
                                                LEFT JOIN workmen w ON e.workman_id = w.id 
                                                LEFT JOIN contractors c ON e.contractor_id = c.id 
                                                WHERE e.execution_officer_id = ?
                                                ORDER BY e.created_at DESC", 'i', [$officerId]);
                    
                    if(empty($list)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:30px;color:#64748b">No active escalations.</td></tr>
                    <?php else: foreach($list as $e): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($e['created_at'])) ?></td>
                            <td><span class="badge badge-primary"><?= strtoupper($e['escalated_to']) ?></span></td>
                            <td><?= htmlspecialchars($e['escalation_type']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($e['contractor_name'] ?? '-') ?></strong><br>
                                <small><?= htmlspecialchars($e['workman_name'] ?? 'General') ?></small>
                            </td>
                            <td style="max-width:250px; white-space:normal"><?= htmlspecialchars($e['remarks']) ?></td>
                            <td><span class="badge badge-<?= $e['severity'] === 'critical' || $e['severity'] === 'high' ? 'danger' : 'warning' ?>"><?= strtoupper($e['severity']) ?></span></td>
                            <td>
                                <span class="badge badge-<?= $e['status'] === 'open' ? 'warning' : ($e['status'] === 'in_progress' ? 'info' : 'success') ?>">
                                    <?= strtoupper(str_replace('_', ' ', $e['status'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for New Escalation -->
    <div id="escModal" class="modal">
        <div class="modal-content" style="max-width:600px">
            <div class="modal-header">
                <h3 class="modal-title">Raise New Escalation</h3>
                <span class="close" onclick="closeEscModal()">&times;</span>
            </div>
            <form id="escForm" onsubmit="saveEscalation(event)">
                <div class="modal-body">
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
                        <div class="form-group">
                            <label>Target Department</label>
                            <select name="escalated_to" class="form-control" required>
                                <option value="welfare">Welfare Section</option>
                                <option value="safety">Safety Section</option>
                                <option value="admin">System Admin</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Escalation Type</label>
                            <select name="escalation_type" class="form-control" required>
                                <option value="Safety Violation">Safety Violation</option>
                                <option value="Attendance Ghosting">Attendance Ghosting</option>
                                <option value="Unauthorized Access">Unauthorized Access</option>
                                <option value="Manpower Shortage">Manpower Shortage</option>
                                <option value="Conduct Issue">Conduct Issue</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px">
                        <div class="form-group">
                            <label>Contractor</label>
                            <select name="contractor_id" class="form-control" onchange="loadWorkersEsc(this.value)" required>
                                <option value="">Select Contractor</option>
                                <?php foreach($contractors as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['contractor_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Severity</label>
                            <select name="severity" class="form-control" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Workman (Optional)</label>
                        <select name="workman_id" id="workman_select_esc" class="form-control">
                            <option value="">None / General</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Remarks / Action Recommended</label>
                        <textarea name="remarks" class="form-control" rows="4" required placeholder="Describe the issue and recommended action..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeEscModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Submit Escalation</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        if (<?= $showModal ?>) {
            const contractorId = '<?= $prefilledContractorId ?>';
            if (contractorId) {
                document.querySelector('[name="contractor_id"]').value = contractorId;
                loadWorkersEsc(contractorId, '<?= $prefilledWorkmanId ?>');
            }
            document.querySelector('[name="escalation_type"]').value = '<?= $prefilledType ?>';
            document.querySelector('[name="severity"]').value = '<?= $prefilledSeverity ?>';
            showEscalationModal();
        }
    });

    function showEscalationModal() {
        document.getElementById('escModal').style.display = 'block';
    }

    function closeEscModal() {
        document.getElementById('escModal').style.display = 'none';
    }

    async function loadWorkersEsc(contractorId, preselectedId = '') {
        if (!contractorId) return;
        const select = document.getElementById('workman_select_esc');
        select.innerHTML = '<option value="">Loading...</option>';
        
        try {
            const response = await fetch(`../../api/execution/get_assigned_data.php?type=workers&contractor_id=${contractorId}`);
            const res = await response.json();
            if (res.status) {
                select.innerHTML = '<option value="">None / General</option>';
                res.data.forEach(w => {
                    const selected = w.id == preselectedId ? 'selected' : '';
                    select.innerHTML += `<option value="${w.id}" ${selected}>${w.name}</option>`;
                });
            }
        } catch (e) {
            select.innerHTML = '<option value="">Error loading workers</option>';
        }
    }

    async function saveEscalation(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch('../../api/execution/save_escalation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const res = await response.json();
            if (res.status) {
                alert('Escalation submitted successfully!');
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        } catch (err) {
            alert('Failed to submit escalation.');
        }
    }
    </script>
    <style>
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
    .modal-content { background: #fff; margin: 5% auto; padding: 0; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; animation: slideDown 0.3s ease; }
    .modal-header { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .modal-body { padding: 20px; }
    .modal-footer { padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; }
    @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    </style>
    <?php
}

renderLayout("Escalation Management", 'renderContent', $role, $name);
?>

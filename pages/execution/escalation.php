<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['execution_officer', 'execution', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/execution_context.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Execution Officer';
$userId = $_SESSION['user_id'];

// Get or create execution officer context for this login
$officerId = clms_execution_get_officer_id($conn, $userId);

function renderContent() {
    global $conn, $officerId;

    // Fetch assigned contractors for dropdown
    $contractors = db_fetch_all($conn, "SELECT c.id, c.contractor_name FROM contractors c 
                                       JOIN execution_officer_contractors eoc ON c.id = eoc.contractor_id 
                                       WHERE eoc.execution_officer_id = ?", 'i', [$officerId]);

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-bullhorn" style="color:#ef4444;margin-right:8px"></i>Escalation Management</h2>
            <p class="page-subtitle">Forward critical issues to Safety, Welfare, or Admin departments for final action.</p>
        </div>
        <div style="display:flex;gap:8px">
            <button class="btn btn-danger" onclick="showEscalationModal()"><i class="fas fa-plus"></i> Raise Escalation</button>
        </div>
    </div>

    <div class="card glass">
        <div class="card-header"><div class="card-title">Active Escalations</div></div>
        <div class="card-body" style="padding:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Target Dept</th>
                        <th>Type</th>
                        <th>Contractor / Workman</th>
                        <th>Reason</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $list = db_fetch_all($conn, "SELECT a.*, w.name as workman_name, c.contractor_name 
                                                FROM execution_actions a 
                                                LEFT JOIN workmen w ON a.workman_id = w.id 
                                                LEFT JOIN contractors c ON a.contractor_id = c.id 
                                                WHERE a.execution_officer_id = ? AND a.action_type = 'escalation'
                                                ORDER BY a.created_at DESC", 'i', [$officerId]);
                    
                    if(empty($list)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:30px;color:#64748b">No active escalations.</td></tr>
                    <?php else: foreach($list as $a): ?>
                        <tr>
                            <td><?= date('d M Y', strtotime($a['created_at'])) ?></td>
                            <td><span class="badge badge-primary">Welfare / Safety</span></td>
                            <td><?= htmlspecialchars($a['action_type']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($a['contractor_name'] ?? '-') ?></strong><br>
                                <small><?= htmlspecialchars($a['workman_name'] ?? 'General') ?></small>
                            </td>
                            <td><?= htmlspecialchars($a['action_reason']) ?></td>
                            <td><span class="badge badge-warning">PENDING</span></td>
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
                <input type="hidden" name="action_type" value="escalation">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Target Department</label>
                        <select name="target_dept" class="form-control" required>
                            <option value="welfare">Welfare Section</option>
                            <option value="safety">Safety Section</option>
                            <option value="admin">System Admin</option>
                        </select>
                    </div>

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
                        <label>Workman (Optional)</label>
                        <select name="workman_id" id="workman_select_esc" class="form-control">
                            <option value="">None / General</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Escalation Reason / Action Recommended</label>
                        <textarea name="action_reason" class="form-control" rows="4" required placeholder="Describe the reason for escalation and what action is recommended (e.g. recommend block, disciplinary action)..."></textarea>
                    </div>

                    <div class="alert alert-warning" style="margin-top:10px; font-size:12px">
                        <i class="fas fa-info-circle"></i> This escalation will notify the respective department for final approval/action.
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
    function showEscalationModal() {
        document.getElementById('escModal').style.display = 'block';
    }

    function closeEscModal() {
        document.getElementById('escModal').style.display = 'none';
    }

    async function loadWorkersEsc(contractorId) {
        if (!contractorId) return;
        const select = document.getElementById('workman_select_esc');
        select.innerHTML = '<option value="">Loading...</option>';
        
        try {
            const response = await fetch(`../../api/execution/get_assigned_data.php?type=workers&contractor_id=${contractorId}`);
            const res = await response.json();
            if (res.status) {
                select.innerHTML = '<option value="">None / General</option>';
                res.data.forEach(w => {
                    select.innerHTML += `<option value="${w.id}">${w.name}</option>`;
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
            const response = await fetch('../../api/execution/save_action.php', {
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
    .modal-content { background: #fff; margin: 5% auto; padding: 0; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; }
    .modal-header { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .modal-body { padding: 20px; }
    .modal-footer { padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; }
    </style>
    <?php
}

renderLayout("Escalation Management", 'renderContent', $role, $name);
?>


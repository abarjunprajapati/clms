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

    // Fetch assigned work orders for dropdown
    $workOrders = db_fetch_all($conn, "SELECT wo.id, wo.work_order_no FROM work_orders wo 
                                      JOIN execution_officer_workorders eow ON wo.id = eow.work_order_id 
                                      WHERE eow.execution_officer_id = ?", 'i', [$officerId]);

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-edit" style="color:#3b82f6;margin-right:8px"></i>Observation Log</h2>
            <!-- <p class="page-subtitle">Record daily field observations, safety issues, and deployment discrepancies.</p> -->
        </div>
        <div style="display:flex;gap:8px">
            <button class="btn btn-primary" onclick="showObservationModal()"><i class="fas fa-plus"></i> New Observation</button>
        </div>
    </div>

    <div class="card glass">
        <div class="card-header"><div class="card-title">My Observations</div></div>
        <div class="card-body" style="padding:0">
            <table class="data-table" id="observationsTable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Workman</th>
                        <th>Contractor</th>
                        <th>Type</th>
                        <th>Severity</th>
                        <th>Observation</th>
                        <th>Action Required</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $list = db_fetch_all($conn, "SELECT o.*, w.name as workman_name, c.contractor_name 
                                                FROM execution_observations o 
                                                LEFT JOIN workmen w ON o.workman_id = w.id 
                                                LEFT JOIN contractors c ON o.contractor_id = c.id 
                                                WHERE o.execution_officer_id = ? 
                                                ORDER BY o.created_at DESC", 'i', [$officerId]);
                    
                    if(empty($list)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:30px;color:#64748b">No observations found.</td></tr>
                    <?php else: foreach($list as $o): ?>
                        <tr>
                            <td><?= date('d M Y, H:i', strtotime($o['created_at'])) ?></td>
                            <td><strong><?= htmlspecialchars($o['workman_name'] ?? 'General') ?></strong></td>
                            <td><?= htmlspecialchars($o['contractor_name'] ?? 'N/A') ?></td>
                            <td><span class="badge badge-info"><?= htmlspecialchars($o['observation_type']) ?></span></td>
                            <td><span class="badge badge-<?= $o['severity'] === 'high' ? 'danger' : ($o['severity'] === 'medium' ? 'warning' : 'info') ?>"><?= strtoupper($o['severity']) ?></span></td>
                            <td style="max-width:300px; white-space:normal"><?= htmlspecialchars($o['remarks']) ?></td>
                            <td><?= $o['action_required'] ? '<span class="badge badge-danger">YES</span>' : '<span class="badge badge-success">NO</span>' ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal for New Observation -->
    <div id="obsModal" class="modal">
        <div class="modal-content" style="max-width:600px">
            <div class="modal-header">
                <h3 class="modal-title">New Observation</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="obsForm" onsubmit="saveObservation(event)">
                <div class="modal-body">
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label>Contractor</label>
                            <select name="contractor_id" class="form-control" onchange="loadWorkers(this.value)" required>
                                <option value="">Select Contractor</option>
                                <?php foreach($contractors as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['contractor_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Work Order</label>
                            <select name="work_order_id" class="form-control" required>
                                <option value="">Select Work Order</option>
                                <?php foreach($workOrders as $wo): ?>
                                    <option value="<?= $wo['id'] ?>"><?= htmlspecialchars($wo['work_order_no']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Workman (Optional)</label>
                        <select name="workman_id" id="workman_select" class="form-control">
                            <option value="">General Observation</option>
                        </select>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
                        <div class="form-group">
                            <label>Observation Type</label>
                            <select name="observation_type" class="form-control" required>
                                <option value="Safety Violation">Safety Violation</option>
                                <option value="Attendance Issue">Attendance Issue</option>
                                <option value="Unauthorized Entry">Unauthorized Entry</option>
                                <option value="Performance Issue">Performance Issue</option>
                                <option value="Deployment Mismatch">Deployment Mismatch</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Severity</label>
                            <select name="severity" class="form-control" required>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Remarks / Description</label>
                        <textarea name="remarks" class="form-control" rows="4" required placeholder="Describe the observation in detail..."></textarea>
                    </div>

                    <div class="form-group" style="display:flex; align-items:center; gap:8px">
                        <input type="checkbox" name="action_required" id="action_req" value="1">
                        <label for="action_req" style="margin:0">Immediate Action Required?</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Observation</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showObservationModal() {
        document.getElementById('obsModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('obsModal').style.display = 'none';
    }

    async function loadWorkers(contractorId) {
        if (!contractorId) return;
        const select = document.getElementById('workman_select');
        select.innerHTML = '<option value="">Loading...</option>';
        
        try {
            const response = await fetch(`../../api/execution/get_assigned_data.php?type=workers&contractor_id=${contractorId}`);
            const res = await response.json();
            if (res.status) {
                select.innerHTML = '<option value="">General Observation</option>';
                res.data.forEach(w => {
                    select.innerHTML += `<option value="${w.id}">${w.name} (${w.aadhaar})</option>`;
                });
            }
        } catch (e) {
            select.innerHTML = '<option value="">Error loading workers</option>';
        }
    }

    async function saveObservation(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData.entries());
        data.action_required = document.getElementById('action_req').checked ? 1 : 0;

        try {
            const response = await fetch('../../api/execution/save_observation.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const res = await response.json();
            if (res.status) {
                alert('Observation saved successfully!');
                location.reload();
            } else {
                alert('Error: ' + res.message);
            }
        } catch (err) {
            alert('Failed to save observation.');
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

renderLayout("Observation Log", 'renderContent', $role, $name);
?>


<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;

    // Fetch all Execution Officers
    $officers = db_fetch_all($conn, "SELECT eo.*, u.name as user_name 
                                    FROM execution_officers eo 
                                    LEFT JOIN users u ON eo.employee_code = u.contractor_id 
                                    ORDER BY eo.name ASC");

    // Fetch Departments for dropdown
    $depts = db_fetch_all($conn, "SELECT * FROM master_departments ORDER BY dept_name ASC");

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-users-cog" style="color:#6366f1;margin-right:10px;"></i> Execution Officer Mapping</h2>
            <!-- <p class="page-subtitle">Map Execution Officers to Departments, Contractors, and Work Orders.</p> -->
        </div>
        <div class="action-buttons">
            <button class="btn btn-primary" onclick="showMappingModal()"><i class="fas fa-link"></i> New Mapping</button>
        </div>
    </div>

    <div class="card glass">
        <div class="card-header"><div class="card-title">Execution Officers & Assignments</div></div>
        <div class="card-body" style="padding:0">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Officer Name</th>
                        <th>Emp Code</th>
                        <th>Assigned Depts</th>
                        <th>Active Contractors</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($officers)): ?>
                        <tr><td colspan="5" style="text-align:center;padding:20px;color:#64748b">No officers found.</td></tr>
                    <?php else: foreach($officers as $eo): 
                        // Fetch assigned departments
                        $eoDepts = db_fetch_all($conn, "SELECT d.dept_name FROM master_departments d 
                                                      JOIN execution_officer_departments eod ON d.id = eod.department_id 
                                                      WHERE eod.execution_officer_id = ?", 'i', [$eo['id']]);
                        
                        // Fetch assigned contractors count
                        $contractorCount = db_count($conn, "SELECT COUNT(*) FROM execution_officer_contractors WHERE execution_officer_id = ?", 'i', [$eo['id']]);
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($eo['name']) ?></strong><br>
                                <small style="opacity:0.6"><?= htmlspecialchars($eo['email']) ?></small>
                            </td>
                            <td><code><?= htmlspecialchars($eo['employee_code']) ?></code></td>
                            <td>
                                <?php if(empty($eoDepts)): ?><span style="opacity:0.4">None</span>
                                <?php else: foreach($eoDepts as $d): ?>
                                    <span class="badge badge-info" style="font-size:10px; margin-bottom:2px"><?= htmlspecialchars($d['dept_name']) ?></span>
                                <?php endforeach; endif; ?>
                            </td>
                            <td><span class="badge badge-success"><?= $contractorCount ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-outline" onclick="manageMapping(<?= $eo['id'] ?>, '<?= htmlspecialchars(addslashes($eo['name'])) ?>')">Manage</button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mapping Modal -->
    <div id="mappingModal" class="modal">
        <div class="modal-content" style="max-width:700px">
            <div class="modal-header">
                <h3 class="modal-title">Manage Assignments: <span id="targetOfficerName"></span></h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div class="modal-body">
                <input type="hidden" id="targetOfficerId">
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <!-- Department Mapping -->
                    <div>
                        <h5 style="margin-bottom:10px; font-size:14px; border-bottom:1px solid #eee; padding-bottom:5px">Departments</h5>
                        <div id="deptList" style="max-height:300px; overflow-y:auto; padding:5px">
                            <?php foreach($depts as $d): ?>
                                <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px">
                                    <input type="checkbox" class="dept-check" value="<?= $d['id'] ?>" id="dept-<?= $d['id'] ?>">
                                    <label for="dept-<?= $d['id'] ?>" style="font-size:13px; margin:0"><?= htmlspecialchars($d['dept_name']) ?></label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Contractor Mapping (Dynamic) -->
                    <div>
                        <h5 style="margin-bottom:10px; font-size:14px; border-bottom:1px solid #eee; padding-bottom:5px">Contractors</h5>
                        <div id="contractorMappingList">
                             <p style="font-size:12px; color:#64748b">Select a department to view contractors.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal()">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveAssignments()">Save Assignments</button>
            </div>
        </div>
    </div>

    <script>
    function showMappingModal() {
        // Feature to add new officer record if needed
        alert('Please use User Management to create Execution Officer first.');
    }

    function manageMapping(id, name) {
        document.getElementById('targetOfficerId').value = id;
        document.getElementById('targetOfficerName').textContent = name;
        
        // Load current mappings
        fetchAssignments(id);
        
        document.getElementById('mappingModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('mappingModal').style.display = 'none';
    }

    async function fetchAssignments(officerId) {
        // Reset checks
        document.querySelectorAll('.dept-check').forEach(c => c.checked = false);
        
        const res = await fetch(`../../api/admin/get_officer_assignments.php?id=${officerId}`);
        const data = await res.json();
        
        if (data.status) {
            data.depts.forEach(did => {
                const el = document.getElementById('dept-' + did);
                if (el) el.checked = true;
            });
            // Update contractor list based on selected depts (simplified for now)
            loadContractorsForMapping();
        }
    }

    async function loadContractorsForMapping() {
        const officerId = document.getElementById('targetOfficerId').value;
        const container = document.getElementById('contractorMappingList');
        container.innerHTML = '<p style="font-size:12px">Loading contractors...</p>';

        const res = await fetch(`../../api/admin/get_contractors_for_mapping.php?officer_id=${officerId}`);
        const data = await res.json();
        
        if (data.status) {
            container.innerHTML = '';
            data.contractors.forEach(c => {
                container.innerHTML += `
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px">
                        <input type="checkbox" class="contractor-check" value="${c.id}" ${c.assigned ? 'checked' : ''} id="cont-${c.id}">
                        <label for="cont-${c.id}" style="font-size:12px; margin:0">${c.contractor_name}</label>
                    </div>
                `;
            });
        }
    }

    async function saveAssignments() {
        const officerId = document.getElementById('targetOfficerId').value;
        const depts = Array.from(document.querySelectorAll('.dept-check:checked')).map(c => c.value);
        const contractors = Array.from(document.querySelectorAll('.contractor-check:checked')).map(c => c.value);

        const res = await fetch(`../../api/admin/save_officer_assignments.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ officer_id: officerId, depts, contractors })
        });
        
        const data = await res.json();
        if (data.status) {
            alert('Assignments updated successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    }
    </script>
    <style>
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); }
    .modal-content { background: #fff; margin: 2% auto; padding: 0; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
    .modal-header { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
    .modal-body { padding: 20px; }
    .modal-footer { padding: 16px 20px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px; }
    </style>
    <?php
}

renderLayout("Execution Officer Management", 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>

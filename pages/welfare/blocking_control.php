<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'welfare_user', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    // Fetch counts for tabs
    $activeCount = db_count($conn, "SELECT COUNT(*) FROM contractors WHERE status != 'blocked'");
    $blockedCount = db_count($conn, "SELECT COUNT(*) FROM contractors WHERE status = 'blocked'");
    $pendingSyncCount = db_count($conn, "SELECT COUNT(*) FROM (
        SELECT entity_id FROM sap_sync_queue WHERE sync_status = 'pending' AND entity_type = 'CONTRACTOR'
        UNION
        SELECT entity_id FROM attendance_sync_queue WHERE status = 'pending' AND entity_type = 'CONTRACTOR'
    ) as pending");

    ?>
    <div class="content-header">
        <h2 class="page-title">Contractor Control & Lifecycle</h2>
        <!-- <p class="page-subtitle">Enterprise-grade blocking and activation with SAP & Attendance synchronization.</p> -->
    </div>

    <!-- TABS -->
    <div class="tabs-container" style="margin-bottom: 20px;">
        <button class="tab-btn active" onclick="switchTab('active')">
            <i class="fas fa-check-circle"></i> Active Firms <span class="badge badge-success"><?= $activeCount ?></span>
        </button>
        <button class="tab-btn" onclick="switchTab('worker_blocking')">
            <i class="fas fa-user-slash"></i> Worker Blocking <span class="badge badge-warning">Rep/Sup/Workman</span>
        </button>
        <button class="tab-btn" onclick="switchTab('blocked')">
            <i class="fas fa-ban"></i> Blocked Firms <span class="badge badge-danger"><?= $blockedCount ?></span>
        </button>
        <button class="tab-btn" onclick="switchTab('pending')">
            <i class="fas fa-sync"></i> Pending Sync <span class="badge badge-warning"><?= $pendingSyncCount ?></span>
        </button>
    </div>

    <!-- ACTIVE CONTRACTORS TAB -->
    <div id="tab-active" class="tab-content active">
        <div class="card glass">
            <div class="card-header"><div class="card-title">Active / Operational Firms</div></div>
            <div class="card-body" style="padding:0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>SAP Code</th>
                            <th>Firm Name</th>
                            <th>Total Workers</th>
                            <th>Active Workers</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $activeContractors = db_fetch_all($conn, "
                            SELECT c.*, 
                            (SELECT COUNT(*) FROM workmen WHERE contractor_id = c.id) as total_workers,
                            (SELECT COUNT(*) FROM workmen WHERE contractor_id = c.id AND status = 'permanent_active' AND is_blocked = 0) as active_workers
                            FROM contractors c WHERE c.status != 'blocked' ORDER BY c.contractor_name ASC");
                        foreach ($activeContractors as $c):
                        ?>
                        <tr>
                            <td><code><?= $c['vendor_code'] ?></code></td>
                            <td><strong><?= htmlspecialchars($c['contractor_name']) ?></strong></td>
                            <td><?= $c['total_workers'] ?></td>
                            <td><span class="text-success"><?= $c['active_workers'] ?></span></td>
                            <td><span class="badge badge-success"><?= strtoupper($c['status']) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="openBlockModal(<?= $c['id'] ?>, '<?= htmlspecialchars($c['contractor_name']) ?>', '<?= $c['vendor_code'] ?>')">
                                    <i class="fas fa-user-slash"></i> Block Firm
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- WORKER BLOCKING TAB -->
    <div id="tab-worker_blocking" class="tab-content" style="display:none;">
        <div class="card glass mb-4">
            <div class="card-header"><div class="card-title">Block Individuals (Rep / Sup / Workman)</div></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Search Worker (Aadhaar / ACC / Name)</label>
                        <input type="text" id="workerSearchInput" class="form-control" placeholder="Search for individual to block...">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button class="btn btn-primary w-100" onclick="searchWorkerToBlock()"><i class="fas fa-search"></i> Search Worker</button>
                    </div>
                </div>
                <div id="workerSearchResult" class="mt-4"></div>
            </div>
        </div>
    </div>

    <!-- BLOCKED CONTRACTORS TAB -->
    <div id="tab-blocked" class="tab-content" style="display:none;">
        <div class="card glass">
            <div class="card-header"><div class="card-title">Disciplinary / Security Blocked Firms</div></div>
            <div class="card-body" style="padding:0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>SAP Code</th>
                            <th>Firm Name</th>
                            <th>Block Date</th>
                            <th>Blocked By</th>
                            <th>Reason</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $blockedContractors = db_fetch_all($conn, "
                            SELECT c.*, u.name as blocked_by_name
                            FROM contractors c 
                            LEFT JOIN users u ON c.blocked_by = u.id
                            WHERE c.status = 'blocked' ORDER BY c.blocked_at DESC");
                        foreach ($blockedContractors as $c):
                        ?>
                        <tr>
                            <td><code><?= $c['vendor_code'] ?></code></td>
                            <td><strong><?= htmlspecialchars($c['contractor_name']) ?></strong></td>
                            <td><?= $c['blocked_at'] ? date('d M Y H:i', strtotime($c['blocked_at'])) : '-' ?></td>
                            <td><?= htmlspecialchars($c['blocked_by_name'] ?? 'System') ?></td>
                            <td><span class="text-danger"><?= htmlspecialchars($c['block_reason']) ?></span></td>
                            <td>
                                <button class="btn btn-sm btn-success" onclick="unblockFirm(<?= $c['id'] ?>, '<?= htmlspecialchars($c['contractor_name']) ?>')">
                                    <i class="fas fa-check"></i> Activate Firm
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- PENDING SYNC TAB -->
    <div id="tab-pending" class="tab-content" style="display:none;">
        <div class="card glass">
            <div class="card-header"><div class="card-title">Integration Health - Pending Syncs</div></div>
            <div class="card-body" style="padding:0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Firm Name</th>
                            <th>Action</th>
                            <th>Queue Date</th>
                            <th>Sync Status</th>
                            <th>Retry</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $pendingSyncs = db_fetch_all($conn, "
                            SELECT 'SAP' as system, c.contractor_name, q.action, q.created_at, q.sync_status AS status, q.id as q_id
                            FROM sap_sync_queue q
                            JOIN contractors c ON q.entity_id = c.id
                            WHERE q.sync_status = 'pending' AND q.entity_type = 'CONTRACTOR'
                            UNION
                            SELECT 'Attendance' as system, c.contractor_name, q.action, q.created_at, q.status, q.id as q_id
                            FROM attendance_sync_queue q
                            JOIN contractors c ON q.entity_id = c.id
                            WHERE q.status = 'pending' AND q.entity_type = 'CONTRACTOR'
                        ");
                        foreach ($pendingSyncs as $p):
                        ?>
                        <tr>
                            <td><span class="badge badge-info"><?= $p['system'] ?></span></td>
                            <td><?= htmlspecialchars($p['contractor_name']) ?></td>
                            <td><?= strtoupper($p['action']) ?></td>
                            <td><?= date('d M Y H:i', strtotime($p['created_at'])) ?></td>
                            <td><span class="badge badge-warning">PENDING</span></td>
                            <td><button class="btn btn-sm btn-outline-primary" onclick="retrySync(<?= $p['q_id'] ?>, '<?= $p['system'] ?>')"><i class="fas fa-redo"></i> Retry</button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- BLOCK MODAL -->
    <div id="blockModal" class="modal">
        <div class="modal-content" style="max-width:500px;">
            <div class="modal-header">
                <h3 class="modal-title">Block Contractor</h3>
                <span class="close-modal" onclick="closeBlockModal()">&times;</span>
            </div>
            <form id="blockForm" onsubmit="submitBlock(event)">
                <input type="hidden" id="block_contractor_id" name="contractor_id">
                <div style="margin-bottom:15px;">
                    <label>Contractor:</label>
                    <div id="modal_contractor_name" style="font-weight:bold; font-size:16px;"></div>
                    <div id="modal_vendor_code" style="color:var(--gray-500); font-family:monospace;"></div>
                </div>
                <div class="form-group">
                    <label>Blocking Reason <span class="text-danger">*</span></label>
                    <select class="form-control" name="reason" required>
                        <option value="">-- Select Reason --</option>
                        <option value="Emergency">Emergency</option>
                        <option value="Disciplinary">Discipline Issue</option>
                        <option value="Safety Violation">Safety Violation</option>
                        <option value="Compliance Non-conformity">Compliance Issue</option>
                        <option value="Security Threat">Security Threat</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Remarks / Detail <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="remarks" rows="3" required placeholder="Provide detailed audit remarks..."></textarea>
                </div>
                <div class="alert alert-warning" style="font-size:12px; margin-top:10px;">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>Warning:</strong> This action will immediately inactivate all associated workers, supervisors, and freeze their site access.
                </div>
                <div class="modal-footer" style="padding-top:15px; text-align:right;">
                    <button type="button" class="btn btn-outline" onclick="closeBlockModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Confirm & Block Firm</button>
                </div>
            </form>
        </div>
    </div>

    <style>
    .tabs-container { display: flex; border-bottom: 1px solid #e2e8f0; gap: 10px; }
    .tab-btn { padding: 10px 20px; border: none; background: none; cursor: pointer; font-weight: 500; color: #64748b; border-bottom: 2px solid transparent; transition: all 0.3s; }
    .tab-btn:hover { color: #6366f1; }
    .tab-btn.active { color: #6366f1; border-bottom-color: #6366f1; }
    .tab-btn .badge { margin-left: 5px; font-size: 11px; }
    
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
    .modal-content { background-color: white; margin: 10% auto; padding: 20px; border-radius: 12px; position: relative; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; margin-bottom: 15px; padding-bottom: 10px; }
    .close-modal { font-size: 24px; font-weight: bold; cursor: pointer; }
    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
    .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; }
    </style>

    <script>
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(t => t.style.display = 'none');
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + tabId).style.display = 'block';
        event.currentTarget.classList.add('active');
    }

    function openBlockModal(id, name, code) {
        document.getElementById('block_contractor_id').value = id;
        document.getElementById('modal_contractor_name').innerText = name;
        document.getElementById('modal_vendor_code').innerText = 'Vendor Code: ' + code;
        document.getElementById('blockModal').style.display = 'block';
    }

    function closeBlockModal() {
        document.getElementById('blockModal').style.display = 'none';
    }

    async function submitBlock(event) {
        event.preventDefault();
        const formData = new FormData(event.target);
        const data = Object.fromEntries(formData.entries());
        data.action = 'block';

        if (!confirm('CONFIRM: You are about to block this contractor and all its employees. Proceed?')) return;

        try {
            const res = await fetch('../../api/welfare/block_contractor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify(data)
            });
            const result = await res.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (e) {
            alert('API connection failed');
        }
    }

    async function unblockFirm(id, name) {
        if (!confirm('Are you sure you want to reactivate contractor "' + name + '" and restore its workers?')) return;

        try {
            const res = await fetch('../../api/welfare/block_contractor.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ contractor_id: id, action: 'unblock' })
            });
            const result = await res.json();
            if (result.success) {
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (e) {
            alert('API connection failed');
        }
    }

    async function searchWorkerToBlock() {
        const q = document.getElementById('workerSearchInput').value;
        if (!q) return;
        const res = await fetch('../../api/welfare/search_worker.php?q=' + q);
        const data = await res.json();
        const container = document.getElementById('workerSearchResult');
        if (data.success && data.data.length > 0) {
            let html = '<table class="data-table"><thead><tr><th>Name</th><th>Role</th><th>Contractor</th><th>Action</th></tr></thead><tbody>';
            data.data.forEach(w => {
                html += `<tr>
                    <td>${w.name} (${w.acc_number || 'No ACC'})</td>
                    <td>${w.worker_type}</td>
                    <td>${w.contractor_name}</td>
                    <td>
                        <button class="btn btn-sm btn-danger" onclick="openWorkerBlockModal(${w.id}, '${w.name}', '${w.worker_type}')">
                            <i class="fas fa-user-slash"></i> Block ${w.worker_type}
                        </button>
                    </td>
                </tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        } else {
            container.innerHTML = '<div class="alert alert-info">No workers found.</div>';
        }
    }

    function openWorkerBlockModal(id, name, type) {
        // reuse or create new modal
        alert('Opening Block Modal for ' + name + ' (' + type + ')');
        // implementation would open a modal with temp/permanent choice
    }

    async function retrySync(id, system) {
        try {
            const res = await fetch('../../api/welfare/retry_sync.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ id, system })
            });
            const result = await res.json();
            if (result.success) {
                alert('Sync re-queued successfully');
                location.reload();
            } else {
                alert('Error: ' + result.message);
            }
        } catch (e) {
            alert('API connection failed');
        }
    }
    </script>
    <?php
}

renderLayout("Contractor Control", 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>

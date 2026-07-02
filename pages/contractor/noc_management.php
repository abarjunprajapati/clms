<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['contractor', 'customer', 'super_admin']);
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/layout.php';

$contractor_id = $_SESSION['user_id'];
$company_name = $_SESSION['company_name'] ?? 'Contractor';
$current_page = 'noc_management';

function renderContent() {
    global $conn, $contractor_id;
    try {
        // Resolve the actual contractor's auto-increment ID from the database using their user_id
        $c_stmt = $conn->prepare("SELECT id FROM contractors WHERE user_id = ?");
        $c_stmt->bind_param("i", $contractor_id);
        $c_stmt->execute();
        $c_res = $c_stmt->get_result()->fetch_assoc();
        $db_contractor_id = $c_res ? $c_res['id'] : 0;

        // Fetch incoming NOC requests (filtering by the actual contractors.id)
        $in_req = $conn->prepare("
        SELECT n.*, w.name, w.temp_id, w.aadhaar, c.contractor_name as requesting_contractor 
        FROM noc_requests n 
        JOIN workmen w ON n.workman_id = w.id 
        JOIN contractors c ON n.to_contractor_id = c.user_id 
        WHERE n.from_contractor_id = ? ORDER BY n.created_at DESC
    ");
        $incoming_requests = [];
        if ($in_req) {
            $in_req->bind_param("i", $db_contractor_id);
            $in_req->execute();
            $res = $in_req->get_result();
            if ($res) $incoming_requests = $res->fetch_all(MYSQLI_ASSOC);
        }

    // Fetch outgoing NOC requests
    $out_req = $conn->prepare("
        SELECT n.*, w.name, w.temp_id, w.aadhaar 
        FROM noc_requests n 
        JOIN workmen w ON n.workman_id = w.id 
        WHERE n.to_contractor_id = ? ORDER BY n.created_at DESC
    ");
    $outgoing_requests = [];
    if ($out_req) {
        $out_req->bind_param("i", $contractor_id);
        $out_req->execute();
        $res2 = $out_req->get_result();
        if ($res2) $outgoing_requests = $res2->fetch_all(MYSQLI_ASSOC);
    }

    ?>
    <style>
        .page-content { padding: 24px; width: 100%; margin: 0 auto; }
        .req-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
        .req-card h3 { margin: 0; background: #2563eb; color: white !important; font-size: 16px; padding: 16px 20px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .req-card .tbl-responsive { padding: 20px; overflow-x: auto; }
        .tbl-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
        th { background: #f8fafc; color: #475569; font-weight: 600; text-transform: uppercase; font-size: 12px; }
        .status-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .status-pending { background: #fef3c7; color: #d97706; }
        .status-approved { background: #dcfce7; color: #16a34a; }
        .status-rejected { background: #fee2e2; color: #dc2626; }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 8px; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; border: none; font-size: 14px; }
        .btn-primary { background: #2563eb; color: white; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #16a34a; color: white; }
        .btn-danger { background: #dc2626; color: white; }
        .form-group { margin-bottom: 16px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.2s; }
        .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
    </style>
    
    <div class="page-content">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h1 style="font-size: 24px; color: #2563eb; margin: 0 0 8px 0; font-weight: bold;">NOC Management</h1>
                <p style="color: #64748b; margin: 0;">Manage company change requests for workmen.</p>
            </div>
            <button class="btn btn-primary" onclick="showRequestModal()">
                <i class="fas fa-plus"></i> Request Workman (NOC)
            </button>
        </div>

        <div class="req-card">
            <h3><i class="fas fa-inbox"></i> Incoming Requests (Other contractors want to hire your workmen)</h3>
            <div class="tbl-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Date</th>
                            <th>Workman Name</th>
                            <th>Aadhaar / Temp ID</th>
                            <th>Requesting Contractor</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($incoming_requests)): ?>
                        <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 32px;">No incoming requests found.</td></tr>
                        <?php else: ?>
                            <?php $sno_in = 1; foreach($incoming_requests as $req): ?>
                            <tr>
                                <td><?= $sno_in++ ?></td>
                                <td><?= date('d M Y', strtotime($req['created_at'])) ?></td>
                                <td><strong><?= htmlspecialchars($req['name']) ?></strong></td>
                                <td><?= htmlspecialchars($req['aadhaar']) ?><br><small><?= htmlspecialchars($req['temp_id']) ?></small></td>
                                <td><?= htmlspecialchars($req['requesting_contractor']) ?></td>
                                <td>
                                    <?php 
                                    $st = $req['noc_status'];
                                    if($st === 'pending') echo '<span class="status-badge status-pending">Pending</span>';
                                    else if($st === 'approved_by_contractor' || $st === 'approved_by_welfare' || $st === 'forcefully_released') echo '<span class="status-badge status-approved">Approved</span>';
                                    else echo '<span class="status-badge status-rejected">Rejected</span>';
                                    ?>
                                </td>
                                <td>
                                    <?php if($st === 'pending'): ?>
                                    <button class="btn btn-success" style="padding: 6px 12px; font-size: 12px;" onclick="respondNoc(<?= $req['id'] ?>, 'approve')"><i class="fas fa-check"></i> Approve</button>
                                    <button class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="respondNoc(<?= $req['id'] ?>, 'reject')"><i class="fas fa-times"></i> Reject</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="req-card" style="margin-top: 30px;">
            <h3><i class="fas fa-paper-plane"></i> Outgoing Requests (You requested these workmen)</h3>
            <div class="tbl-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Date</th>
                            <th>Workman Name</th>
                            <th>Aadhaar / Temp ID</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($outgoing_requests)): ?>
                        <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 32px;">No outgoing requests found.</td></tr>
                        <?php else: ?>
                            <?php $sno_out = 1; foreach($outgoing_requests as $req): ?>
                            <tr>
                                <td><?= $sno_out++ ?></td>
                                <td><?= date('d M Y', strtotime($req['created_at'])) ?></td>
                                <td><strong><?= htmlspecialchars($req['name']) ?></strong></td>
                                <td><?= htmlspecialchars($req['aadhaar']) ?><br><small><?= htmlspecialchars($req['temp_id']) ?></small></td>
                                <td>
                                    <?php 
                                    $st = $req['noc_status'];
                                    if($st === 'pending') echo '<span class="status-badge status-pending">Waiting for existing contractor</span>';
                                    else if($st === 'approved_by_contractor' || $st === 'forcefully_released') echo '<span class="status-badge status-pending" style="background:#e0e7ff; color:#4338ca;">Waiting for Welfare Approval</span>';
                                    else if($st === 'approved_by_welfare') echo '<span class="status-badge status-approved">Transfer Complete</span>';
                                    else echo '<span class="status-badge status-rejected">Rejected</span>';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Request NOC Modal -->
    <div id="nocModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
        <div style="background:white; padding: 24px; border-radius: 12px; width: 100%; max-width: 400px;">
            <h3 style="margin-top:0; margin-bottom: 20px;">Request Workman (NOC)</h3>
            <div class="form-group">
                <label style="display:block; margin-bottom:8px; font-weight:600; font-size:14px;">Workman Aadhaar or Temp ID</label>
                <input type="text" id="searchTerm" class="form-control" placeholder="Enter Aadhaar or Temp ID...">
            </div>
            <div style="display:flex; justify-content:flex-end; gap: 12px; margin-top: 24px;">
                <button class="btn" style="background:#f1f5f9; color:#475569;" onclick="document.getElementById('nocModal').style.display='none'">Cancel</button>
                <button class="btn btn-primary" onclick="submitNocRequest()">Submit Request</button>
            </div>
        </div>
    </div>

    <script>
        function showRequestModal() {
            document.getElementById('nocModal').style.display = 'flex';
            document.getElementById('searchTerm').value = '';
            document.getElementById('searchTerm').focus();
        }

        async function submitNocRequest() {
            const term = document.getElementById('searchTerm').value.trim();
            if(!term) return Swal.fire('Error', 'Please enter Aadhaar or Temp ID', 'error');

            try {
                const res = await $.ajax({
                    url: '../../api/contractor/noc_request.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({search_term: term})
                });
                if(res.success) {
                    Swal.fire('Success', res.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error', res.message || 'Failed to submit request', 'error');
                }
            } catch(e) {
                console.error("AJAX Error:", e);
                let errMsg = 'Network error occurred';
                if(e.responseJSON && e.responseJSON.message) errMsg = e.responseJSON.message;
                else if(e.responseText) errMsg = 'Server said: ' + e.responseText.substring(0, 100);
                Swal.fire('Error', errMsg, 'error');
            }
        }

        async function respondNoc(id, action) {
            const confirmMsg = action === 'approve' ? 'Are you sure you want to APPROVE this NOC and release the workman?' : 'Are you sure you want to REJECT this NOC request?';
            
            const result = await Swal.fire({
                title: 'Confirm Action',
                text: confirmMsg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: action === 'approve' ? '#16a34a' : '#dc2626',
                confirmButtonText: 'Yes, ' + action + ' it!'
            });

            if(result.isConfirmed) {
                try {
                    const res = await $.ajax({
                        url: '../../api/contractor/noc_respond.php',
                        type: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify({request_id: id, action: action})
                    });
                    if(res.success) {
                        Swal.fire('Success', res.message, 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Error', res.message || 'Failed to process request', 'error');
                    }
                } catch(e) {
                    console.error("AJAX Error:", e);
                    let errMsg = 'Network error occurred';
                    if(e.responseJSON && e.responseJSON.message) errMsg = e.responseJSON.message;
                    else if(e.responseText) errMsg = 'Server said: ' + e.responseText.substring(0, 100);
                    Swal.fire('Error', errMsg, 'error');
                }
            }
        }
    </script>
    <?php
    } catch (\Throwable $e) {
        echo "<h1>FATAL ERROR IN RENDER CONTENT: " . $e->getMessage() . " at line " . $e->getLine() . "</h1>";
    }
}

renderLayout('NOC Management', 'renderContent', 'contractor', $company_name);
?>

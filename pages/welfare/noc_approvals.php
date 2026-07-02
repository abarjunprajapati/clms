<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_user', 'admin', 'super_admin']);
require_once __DIR__ . '/../../include/config.php';
require_once __DIR__ . '/../../include/layout.php';

$current_page = 'noc_approvals';
$user_name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;

    // Fetch NOC requests waiting for Welfare Approval or pending force release
    $q = "
        SELECT n.*, w.name, w.temp_id, w.aadhaar, 
               c1.contractor_name as requesting_contractor,
               c2.contractor_name as existing_contractor
        FROM noc_requests n 
        JOIN workmen w ON n.workman_id = w.id 
        JOIN contractors c1 ON n.to_contractor_id = c1.user_id 
        LEFT JOIN contractors c2 ON n.from_contractor_id = c2.user_id 
        WHERE n.noc_status IN ('pending', 'approved_by_contractor', 'forcefully_released')
        ORDER BY n.created_at DESC
    ";
    $res = $conn->query($q);
    $requests = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

    ?>
    <style>
        .page-content { padding: 24px; width: 100%; margin: 0 auto; }
        .req-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
        .req-card h3 { margin: 0; background: #2563eb; color: white; font-size: 16px; padding: 16px 20px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
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
        .btn-success { background: #16a34a; color: white; }
        .btn-danger { background: #dc2626; color: white; }
        .btn-warning { background: #f59e0b; color: white; }
    </style>
    
    <div class="page-content">
        <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
            <div>
                <h1 style="font-size: 24px; color: #2563eb; margin: 0 0 8px 0; font-weight: bold;">Welfare NOC Approvals</h1>
                <p style="color: #64748b; margin: 0;">Manage company change requests and force releases.</p>
            </div>
        </div>

        <div class="req-card">
            <h3><i class="fas fa-tasks"></i> Pending Company Change Requests</h3>
            <div class="tbl-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>S.No</th>
                            <th>Date</th>
                            <th>Workman Name</th>
                            <th>Aadhaar / Temp ID</th>
                            <th>Existing Contractor</th>
                            <th>Requesting Contractor</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($requests)): ?>
                        <tr><td colspan="8" style="text-align: center; color: #94a3b8; padding: 32px;">No pending requests found.</td></tr>
                        <?php else: ?>
                            <?php $sno = 1; foreach($requests as $req): ?>
                            <tr>
                                <td><?= $sno++ ?></td>
                                <td><?= date('d M Y', strtotime($req['created_at'])) ?></td>
                                <td><strong><?= htmlspecialchars($req['name']) ?></strong></td>
                                <td><?= htmlspecialchars($req['aadhaar']) ?><br><small><?= htmlspecialchars($req['temp_id']) ?></small></td>
                                <td><?= htmlspecialchars($req['existing_contractor'] ?: 'Common Pool') ?></td>
                                <td><?= htmlspecialchars($req['requesting_contractor']) ?></td>
                                <td>
                                    <?php 
                                    $st = $req['noc_status'];
                                    if($st === 'pending') echo '<span class="status-badge status-pending">Waiting for Existing Contractor</span>';
                                    else if($st === 'approved_by_contractor') echo '<span class="status-badge status-approved">Contractor Approved</span>';
                                    else if($st === 'forcefully_released') echo '<span class="status-badge status-pending">Force Released</span>';
                                    else echo $st;
                                    ?>
                                </td>
                                <td>
                                    <?php if(in_array($st, ['approved_by_contractor', 'forcefully_released'])): ?>
                                        <button class="btn btn-success" style="padding: 6px 12px; font-size: 12px;" onclick="actionNoc(<?= $req['id'] ?>, 'approve')"><i class="fas fa-check"></i> Approve Transfer</button>
                                        <button class="btn btn-danger" style="padding: 6px 12px; font-size: 12px;" onclick="actionNoc(<?= $req['id'] ?>, 'reject')"><i class="fas fa-times"></i> Reject</button>
                                    <?php elseif($st === 'pending'): ?>
                                        <button class="btn btn-warning" style="padding: 6px 12px; font-size: 12px;" onclick="actionNoc(<?= $req['id'] ?>, 'force_release')"><i class="fas fa-gavel"></i> Force Release</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        async function actionNoc(id, action) {
            let confirmMsg = 'Are you sure?';
            if (action === 'approve') confirmMsg = 'Approve the company change transfer?';
            if (action === 'reject') confirmMsg = 'Reject the company change request?';
            if (action === 'force_release') confirmMsg = 'Forcefully release this workman to the common pool? (Ignores existing contractor)';
            
            const result = await Swal.fire({
                title: 'Confirm Action',
                text: confirmMsg,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'Yes, proceed'
            });

            if(result.isConfirmed) {
                try {
                    const res = await $.ajax({
                        url: '../../api/welfare/noc_action.php',
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
                    Swal.fire('Error', 'Network error occurred', 'error');
                }
            }
        }
    </script>
    <?php
}

renderLayout('NOC Approvals', 'renderContent', 'welfare_user', $user_name);
?>

<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
include __DIR__ . '/../../include/NotificationEngine.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare User';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['gate_pass_id'] ?? 0);
    
    $workerData = db_single($conn, "
        SELECT w.name, c.email as contractor_email, c.contractor_name, u.email as welfare_email, gp.application_no 
        FROM gate_passes gp 
        JOIN workmen w ON gp.workman_id = w.id 
        JOIN contractors c ON w.contractor_id = c.id 
        LEFT JOIN users u ON u.role = 'welfare_user'
        WHERE gp.id = ? LIMIT 1", "i", [$id]);

    if ($action === 'approve') {
        if (!$conn->query("UPDATE gate_passes SET status = 'approved', approved_date = CURDATE() WHERE id = $id")) {
            $msg = "Error updating database: " . $conn->error;
        } else {
            $msg = "Temporary Pass request approved.";
        }
        
        if ($workerData) {
            $subject = "Temporary Gate Pass Approved - {$workerData['application_no']}";
            $body = "Dear {$workerData['contractor_name']},\n\nYour temporary gate pass request for {$workerData['name']} has been approved by the Welfare User and is pending issuance.\n\nRegards,\nAdmin";
            @mail($workerData['contractor_email'], $subject, $body);
            if (!empty($workerData['welfare_email'])) {
                @mail($workerData['welfare_email'], $subject, $body);
            }
            NotificationEngine::sendRoleNotification($conn, 'contractor', "Temp pass for {$workerData['name']} approved.", 'success');
        }
    } elseif ($action === 'reject') {
        $reason = $conn->real_escape_string($_POST['reason'] ?? 'Rejected by Welfare User');
        $conn->query("UPDATE gate_passes SET status = 'rejected' WHERE id = $id");
        $msg = "Temporary Pass request rejected.";
        
        if ($workerData) {
            $subject = "Temporary Gate Pass Rejected - {$workerData['application_no']}";
            $body = "Dear {$workerData['contractor_name']},\n\nYour temporary gate pass request for {$workerData['name']} has been rejected.\nReason: " . ($_POST['reason'] ?? 'Rejected by Welfare User') . "\n\nRegards,\nAdmin";
            @mail($workerData['contractor_email'], $subject, $body);
            if (!empty($workerData['welfare_email'])) {
                @mail($workerData['welfare_email'], $subject, $body);
            }
            NotificationEngine::sendRoleNotification($conn, 'contractor', "Temp pass for {$workerData['name']} rejected. Reason: $reason", 'danger');
        }
    }
    
    header("Location: temp_pass_approvals.php?msg=" . urlencode($msg));
    exit;
}

function renderContent() {
    global $conn;
    
    $pending = db_fetch_all($conn, "
        SELECT gp.*, w.name, w.temp_id, w.aadhaar, c.contractor_name 
        FROM gate_passes gp
        JOIN workmen w ON gp.workman_id = w.id
        JOIN contractors c ON w.contractor_id = c.id
        WHERE gp.is_temporary = 1 AND gp.status = 'pending'
        ORDER BY gp.created_at DESC
    ");
    
    ?>
    <div class="content-header">
      <h2 class="page-title">Temporary Pass Approvals</h2>
      <p class="page-subtitle">Review and approve temporary passes requested in extremely urgent situations.</p>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="card glass">
      <div class="card-body" style="padding:0">
        <table class="data-table">
          <thead>
            <tr>
              <th>Request Date</th>
              <th>Workman Name</th>
              <th>Contractor</th>
              <th>Declaration</th>
              <th>Valid Dates</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($pending as $p): ?>
            <tr>
              <td><?= date('d M Y', strtotime($p['created_at'])) ?></td>
              <td>
                <div style="font-weight:600"><?= htmlspecialchars($p['name']) ?></div>
                <div style="font-size:11px;opacity:0.6">ID: <?= htmlspecialchars($p['temp_id'] ?: $p['aadhaar']) ?></div>
              </td>
              <td><?= htmlspecialchars($p['contractor_name']) ?></td>
              <td>
                <?php if (!empty($p['executing_officer_declaration_path'])): ?>
                  <a href="../../<?= htmlspecialchars($p['executing_officer_declaration_path']) ?>" target="_blank" class="btn btn-sm btn-outline"><i class="fas fa-file-pdf"></i> View Declaration</a>
                <?php else: ?>
                  <span class="text-danger">Missing</span>
                <?php endif; ?>
              </td>
              <td>
                <?= date('d M', strtotime($p['valid_from'])) ?> to <?= date('d M Y', strtotime($p['valid_to'])) ?>
              </td>
              <td>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to approve this temporary pass?');">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="gate_pass_id" value="<?= $p['id'] ?>">
                    <button type="submit" class="btn btn-success btn-sm"><i class="fas fa-check"></i> Approve</button>
                </form>
                <button type="button" class="btn btn-danger btn-sm" onclick="rejectRequest(<?= $p['id'] ?>)"><i class="fas fa-times"></i> Reject</button>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($pending)): ?>
            <tr>
              <td colspan="6" style="text-align:center;padding:40px;color:var(--gray-500)">
                <i class="fas fa-check-double" style="font-size:48px;opacity:0.3"></i><br>
                No pending temporary pass requests.
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    
    <script>
    function rejectRequest(id) {
        const reason = prompt("Enter reason for rejection:");
        if (reason !== null) {
            const form = document.createElement('form');
            form.method = 'POST';
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'reject';
            form.appendChild(actionInput);
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'gate_pass_id';
            idInput.value = id;
            form.appendChild(idInput);
            
            const reasonInput = document.createElement('input');
            reasonInput.type = 'hidden';
            reasonInput.name = 'reason';
            reasonInput.value = reason;
            form.appendChild(reasonInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>
    <?php
}

renderLayout("Temporary Pass Approvals", 'renderContent', $role, $name);

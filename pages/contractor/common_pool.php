<?php
require_once '../../include/auth.php';
checkAuth(['contractor']);
include '../../include/config.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;

    if (!$c_id) {
        echo '<div class="alert alert-warning">Complete your registration first.</div>';
        return;
    }

    // Logic: Fetch workers not belonging to this contractor who are either expired, or marked as released/relieved.
    // For now, any worker in the database is in the "pool", but we only want to show them if they are not actively assigned to another contractor, or if we allow "Transfer NOC" flow.
    // Let's show all workers who are "expired" or "rejected" (meaning not active).
    $pool_workers = db_fetch_all($conn, "
        SELECT id, name, aadhaar, trade, skill, status, contractor_id 
        FROM workmen 
        WHERE status IN ('expired', 'rejected') 
        AND is_blocked = 0 
        AND contractor_id != ?
        LIMIT 50
    ", 'i', [$c_id]);

    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-users-viewfinder"></i> Common Pool directory</h2>
        <p class="page-subtitle">Search for previously registered, inactive workers. You can reuse their Aadhaar and safety records.</p>
      </div>
    </div>

    <div class="card glass">
        <div class="card-header">
            <div class="card-title">Worker Search</div>
        </div>
        <div class="card-body">
            <div style="display:flex; gap:10px; margin-bottom: 20px;">
                <input type="text" id="searchAadhaar" class="form-control" placeholder="Search by 12-digit Aadhaar Number..." maxlength="12" style="max-width: 300px;">
                <button class="btn btn-primary" onclick="searchPool()"><i class="fas fa-search"></i> Search Pool</button>
            </div>
            
            <div id="searchResult" style="margin-bottom: 20px;"></div>

            <hr style="border-color: rgba(0,0,0,0.1); margin: 30px 0;">

            <h5 style="font-weight:700; margin-bottom:15px; color: var(--gray-600);">Recently Released Workers</h5>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Aadhaar</th>
                            <th>Trade / Skill</th>
                            <th>Previous Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($pool_workers as $w): ?>
                        <tr>
                            <td style="font-weight:600;"><?= htmlspecialchars($w['name']) ?></td>
                            <td><code><?= substr($w['aadhaar'], 0, 4) . ' **** ****' ?></code></td>
                            <td><?= htmlspecialchars($w['trade']) ?> (<?= htmlspecialchars($w['skill']) ?>)</td>
                            <td><span class="badge badge-gray"><?= strtoupper($w['status']) ?></span></td>
                            <td>
                                <a href="enrolment-4a.php?type=workmen&aadhaar=<?= $w['aadhaar'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-user-plus"></i> Enroll</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if(empty($pool_workers)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">No released workers available in the pool right now.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        function searchPool() {
            const aadhaar = document.getElementById('searchAadhaar').value.trim();
            if(aadhaar.length !== 12) {
                alert("Please enter a valid 12-digit Aadhaar number.");
                return;
            }
            
            const resDiv = document.getElementById('searchResult');
            resDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Searching...';
            
            fetch(`../../api/contractor/fetch_worker_aadhaar.php?aadhaar=${aadhaar}`)
            .then(r => r.json())
            .then(data => {
                if(data.success && data.data) {
                    const w = data.data;
                    resDiv.innerHTML = `
                        <div class="alert alert-success" style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <i class="fas fa-check-circle"></i> <b>Worker Found!</b><br>
                                Name: ${w.name} | Trade: ${w.trade || 'N/A'}<br>
                                Status: Eligible for re-enrollment.
                            </div>
                            <a href="enrolment-4a.php?type=workmen&aadhaar=${aadhaar}" class="btn btn-primary"><i class="fas fa-user-plus"></i> Proceed to Enroll</a>
                        </div>
                    `;
                } else {
                    resDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i> Worker not found in the pool. They might be new or currently active under another contractor.
                            <br><a href="enrolment-4a.php?type=workmen" class="btn btn-sm btn-outline-dark mt-2">Register as New Worker</a>
                        </div>
                    `;
                }
            })
            .catch(e => {
                resDiv.innerHTML = '<span class="text-danger">Network Error.</span>';
            });
        }
    </script>
    <?php
}

renderLayout('Common Pool', 'renderContent', $role, $name);
?>

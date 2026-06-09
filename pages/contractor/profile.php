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

    $c = db_single($conn, "SELECT * FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    
    // Fallback: If not found by user_id, try by contractor_id session variable
    if (!$c && !empty($_SESSION['contractor_id'])) {
        $c = db_single($conn, "SELECT * FROM contractors WHERE vendor_code = ?", 's', [$_SESSION['contractor_id']]);
        
        // If found by vendor_code but user_id was wrong, try to fix it in background
        if ($c && $user_id > 0) {
            db_execute($conn, "UPDATE contractors SET user_id = ? WHERE id = ?", 'ii', [$user_id, $c['id']]);
        }
    }
    
    if (!$c) {
        echo '<div class="alert alert-danger">Contractor profile not found.</div>';
        return;
    }

    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-id-card-clip"></i> Contractor Profile</h2>
        <!-- <p class="page-subtitle">Detailed information as per Contractor Registration registration.</p> -->
      </div>
      <a href="annexure-2a.php" class="btn btn-outline-primary"><i class="fas fa-edit"></i> Edit Details</a>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Basic Info -->
        <div class="card glass">
            <div class="card-header"><div class="card-title">Basic Information</div></div>
            <div class="card-body">
                <div class="profile-row"><span>Vendor Code:</span> <b><?= htmlspecialchars($c['vendor_code']) ?></b></div>
                <div class="profile-row"><span>Name:</span> <b><?= htmlspecialchars($c['contractor_name'] ?? $c['vendor_name'] ?? '') ?></b></div>
                <div class="profile-row"><span>Mobile 1:</span> <b><?= htmlspecialchars($c['mobile'] ?? '') ?></b></div>
                <div class="profile-row"><span>Mobile 2:</span> <b><?= htmlspecialchars($c['vendor_mob2'] ?? 'N/A') ?></b></div>
                <div class="profile-row"><span>Email:</span> <b><?= htmlspecialchars($c['email'] ?? '') ?></b></div>
                <div class="profile-row"><span>Address:</span> <b><?= nl2br(htmlspecialchars($c['address'] ?? '')) ?></b></div>
            </div>
        </div>

        <!-- Compliance Info -->
        <div class="card glass">
            <div class="card-header"><div class="card-title">Compliance & Statutory</div></div>
            <div class="card-body">
                <div class="profile-row"><span>Welfare Dept:</span> <b><?= htmlspecialchars($c['work_awarding_department'] ?? 'N/A') ?></b></div>
                <div class="profile-row"><span>EPF Reg:</span> <b><?= htmlspecialchars($c['epf_registered'] ?? 'N/A') ?> (<?= htmlspecialchars($c['epf_code'] ?? 'N/A') ?>)</b></div>
                <div class="profile-row"><span>ESI Reg:</span> <b><?= htmlspecialchars($c['esi_registered'] ?? 'N/A') ?> (<?= htmlspecialchars($c['esi_code'] ?? 'N/A') ?>)</b></div>
                <div class="profile-row"><span>Labour License:</span> <b><?= htmlspecialchars($c['license_no'] ?? 'N/A') ?></b></div>
                <div class="profile-row"><span>LIN No:</span> <b><?= htmlspecialchars($c['labour_identification_no'] ?? 'N/A') ?></b></div>
                <div class="profile-row"><span>KLWF No:</span> <b><?= htmlspecialchars($c['klwf_registration_no'] ?? 'N/A') ?></b></div>
            </div>
        </div>

        <!-- Insurance & Manpower -->
        <div class="card glass">
            <div class="card-header"><div class="card-title">Insurance & Manpower</div></div>
            <div class="card-body">
                <div class="profile-row"><span>EC Policy No:</span> <b><?= htmlspecialchars($c['ecp_number'] ?? 'N/A') ?></b></div>
                <div class="profile-row"><span>ECP Validity:</span> <b><?= htmlspecialchars($c['ecp_valid_from'] ?? '') ?> to <?= htmlspecialchars($c['ecp_valid_to'] ?? '') ?></b></div>
                <hr>
                <div class="profile-row"><span>Skilled:</span> <b><?= (int)($c['skilled_count'] ?? 0) ?></b></div>
                <div class="profile-row"><span>Semi-Skilled:</span> <b><?= (int)($c['semi_skilled_count'] ?? 0) ?></b></div>
                <div class="profile-row"><span>Unskilled:</span> <b><?= (int)($c['unskilled_count'] ?? 0) ?></b></div>
                <div class="profile-row"><span>Total Proposed:</span> <b><?= (int)($c['workers_proposed'] ?? 0) ?></b></div>
            </div>
        </div>

        <!-- Contact Person -->
        <div class="card glass">
            <div class="card-header"><div class="card-title">Contact Person Details</div></div>
            <div class="card-body">
                <div class="profile-row"><span>Name:</span> <b><?= htmlspecialchars($c['contact_person'] ?? 'N/A') ?></b></div>
                <div class="profile-row"><span>Remarks:</span> <b><?= htmlspecialchars($c['remarks'] ?? 'N/A') ?></b></div>
                <div class="profile-row"><span>Status:</span> <span class="badge badge-success"><?= strtoupper($c['status'] ?? 'PENDING') ?></span></div>
            </div>
        </div>
    </div>

    <style>
        .profile-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--gray-100); font-size: 14px; }
        .profile-row:last-child { border-bottom: none; }
        .profile-row span { color: var(--gray-500); font-weight: 500; }
    </style>
    <?php
}

renderLayout('Contractor Profile', 'renderContent', $role, $name);
?>

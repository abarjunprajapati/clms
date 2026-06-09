<?php
require_once '../../include/config.php';
require_once '../../include/auth_middleware.php';
require_once '../../include/layout.php';

require_role(['customer']);

$name = $_SESSION['customer_name'] ?? $_SESSION['name'] ?? 'Customer';
$customer_code = $_SESSION['customer_code'] ?? '';

// Fetch customer data from sap_customer_master
$cust = db_single($conn, "SELECT * FROM sap_customer_master WHERE customer_code = ?", 's', [$customer_code]);

// Fetch user table data
$user = db_single($conn, "SELECT email, mobile FROM users WHERE contractor_id = ? AND role = 'customer'", 's', [$customer_code]);

function renderContent() {
    global $cust, $user, $customer_code;
    
    if (!$cust) {
        echo '<div class="alert alert-danger m-4">SAP Customer profile not found. Please contact admin.</div>';
        return;
    }
    
    $email = $cust['EMAIL_ADDRESS'] ?: ($user['email'] ?? 'N/A');
    $mobile = $cust['Customer_MOB1'] ?: ($user['mobile'] ?? 'N/A');
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-id-card-clip"></i> Customer Profile</h2>
        <!-- <p class="page-subtitle">Detailed information as synced from SAP Master.</p> -->
      </div>
    </div>

    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Basic Info -->
        <div class="card glass">
            <div class="card-header"><div class="card-title">Basic Information</div></div>
            <div class="card-body">
                <div class="profile-row"><span>Customer Code:</span> <b><?= htmlspecialchars($cust['customer_code']) ?></b></div>
                <div class="profile-row"><span>Company Name:</span> <b><?= htmlspecialchars($cust['customer_name']) ?></b></div>
                <div class="profile-row"><span>Mobile:</span> <b><?= htmlspecialchars($mobile) ?></b></div>
                <div class="profile-row"><span>Email:</span> <b><?= htmlspecialchars($email) ?></b></div>
                <div class="profile-row"><span>Sync Status:</span> <span class="badge badge-success">SAP ACTIVE</span></div>
            </div>
        </div>

        <!-- Address & Contact Info -->
        <div class="card glass">
            <div class="card-header"><div class="card-title">Location & Contact</div></div>
            <div class="card-body">
                <div class="profile-row"><span>City:</span> <b><?= htmlspecialchars($cust['city'] ?? 'N/A') ?></b></div>
                <div class="profile-row"><span>State / region:</span> <b><?= htmlspecialchars($cust['region'] ?? 'N/A') ?></b></div>
                <div class="profile-row"><span>Postal Code:</span> <b><?= htmlspecialchars($cust['post_code'] ?? 'N/A') ?></b></div>
                <div class="profile-row"><span>Street:</span> <b><?= htmlspecialchars($cust['street'] ?? 'N/A') ?></b></div>
            </div>
        </div>
    </div>

    <style>
        .profile-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #edf2f7; font-size: 14px; }
        .profile-row:last-child { border-bottom: none; }
        .profile-row span { color: #718096; font-weight: 500; }
    </style>
    <?php
}

renderLayout("Customer Profile", 'renderContent', $_SESSION['role'], $name);
?>

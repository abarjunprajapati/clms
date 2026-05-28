<?php
require_once '../../include/auth.php';
checkAuth(['welfare_user', 'welfare_admin', 'super_admin', 'execution_officer', 'pass_user']);
include '../../include/config.php';

// Check role permissions
$_welfareRole = $_SESSION['role'] ?? '';
if (!in_array($_welfareRole, ['welfare_user', 'welfare_admin', 'admin', 'super_admin'])) {
    header('Location: ../index.php');
    exit('Unauthorized - Welfare access required');
}

// Fetch lists for filters
$contractorRes = mysqli_query($conn, "SELECT id, contractor_name FROM contractors ORDER BY contractor_name ASC");
$contractors = [];
if ($contractorRes) {
    while ($r = mysqli_fetch_assoc($contractorRes)) {
        $contractors[] = $r;
    }
}

$deptRes = mysqli_query($conn, "SELECT id, name FROM master_departments ORDER BY name ASC");
$departments = [];
if ($deptRes) {
    while ($r = mysqli_fetch_assoc($deptRes)) {
        $departments[] = $r;
    }
}

$tradeRes = mysqli_query($conn, "SELECT DISTINCT trade FROM worker_master WHERE trade IS NOT NULL AND trade != '' ORDER BY trade ASC");
$trades = [];
if ($tradeRes) {
    while ($r = mysqli_fetch_assoc($tradeRes)) {
        $trades[] = $r['trade'];
    }
}

$notif_count = db_count($conn, "SELECT COUNT(*) c FROM notifications WHERE is_read=0");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Enrolled Worker Management</title>
    <link rel="stylesheet" href="../../css/style.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" />
    <style>
        /* Custom overrides & enhanced styles for premium feel */
        :root {
            --primary-hsl: 215, 60%, 40%;
            --success-hsl: 142, 70%, 35%;
            --warning-hsl: 38, 92%, 40%;
            --danger-hsl: 0, 72%, 45%;
            --gray-subtle: #f8fafc;
        }

        body {
            background-color: #f1f5f9;
        }

        .topbar {
            background: linear-gradient(135deg, #1e3a8a, #0d9488);
            color: white;
        }

        .sidebar {
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
        }

        .sidebar-item.active {
            background-color: #f1f5f9;
            color: #1e3a8a;
            border-left: 4px solid #0d9488;
        }

        .stat-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
        }

        /* Filter Toolbar Styles */
        .filter-toolbar {
            background: #ffffff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            padding: 16px;
            margin-bottom: 20px;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-bottom: 12px;
        }

        /* Bulk Actions Panel */
        .bulk-actions-panel {
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            color: #0369a1;
            padding: 12px 18px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: none;
            align-items: center;
            justify-content: space-between;
            animation: slideDown 0.2s ease-out;
        }

        @keyframes slideDown {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Badge/Chips */
        .badge-chip {
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .badge-active { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .badge-pending { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-safety { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .badge-blocked { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .badge-expired { background: #f3f4f6; color: #374151; border: 1px solid #e5e7eb; }

        /* Custom Table Styling */
        .data-table th {
            background-color: #f8fafc;
            color: #475569;
            font-weight: 600;
            border-bottom: 2px solid #e2e8f0;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .data-table tr:hover {
            background-color: #f8fafc;
        }

        .worker-photo-cell {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #e2e8f0;
        }

        .worker-photo-placeholder {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #475569;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
        }

        /* 80% Drawer Styles */
        .drawer-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
            z-index: 1000;
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .drawer-overlay.open {
            display: block;
            opacity: 1;
        }

        .drawer {
            position: fixed;
            top: 0;
            right: -80%;
            width: 80%;
            height: 100%;
            background: #ffffff;
            box-shadow: -10px 0 30px rgba(0,0,0,0.15);
            z-index: 1001;
            transition: right 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
        }

        .drawer.open {
            right: 0;
        }

        .drawer-header {
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8fafc;
        }

        .drawer-body {
            flex: 1;
            overflow-y: auto;
            display: flex;
        }

        .drawer-sidebar {
            width: 220px;
            border-right: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 16px 0;
        }

        .drawer-nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 24px;
            color: #475569;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            border-left: 4px solid transparent;
            cursor: pointer;
        }

        .drawer-nav-item:hover {
            background: #f1f5f9;
            color: #0f172a;
        }

        .drawer-nav-item.active {
            background: #e2e8f0;
            color: #1e3a8a;
            border-left-color: #0d9488;
            font-weight: 600;
        }

        .drawer-content {
            flex: 1;
            padding: 24px;
            overflow-y: auto;
        }

        /* Detail grids */
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }

        .detail-item {
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 10px;
        }

        .detail-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 4px;
            font-weight: 600;
        }

        .detail-val {
            font-size: 14px;
            color: #0f172a;
            font-weight: 500;
        }

        /* Timeline Rail */
        .timeline-rail {
            position: relative;
            padding-left: 24px;
            border-left: 2px solid #e2e8f0;
            margin-left: 10px;
        }

        .timeline-item {
            position: relative;
            margin-bottom: 24px;
        }

        .timeline-marker {
            position: absolute;
            left: -33px;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #ffffff;
            border: 3px solid #0d9488;
        }

        .timeline-item.warning .timeline-marker { border-color: #d97706; }
        .timeline-item.danger .timeline-marker { border-color: #dc2626; }
        .timeline-item.info .timeline-marker { border-color: #2563eb; }

        .timeline-time {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 4px;
        }

        .timeline-title {
            font-weight: 600;
            font-size: 13px;
            color: #0f172a;
        }

        .timeline-desc {
            font-size: 13px;
            color: #475569;
            margin-top: 2px;
        }

        /* Document Preview Modal styling */
        .preview-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            height: 550px;
        }

        .preview-pane {
            background: #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 1px solid #cbd5e1;
            position: relative;
        }

        .preview-pane iframe, .preview-pane img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border: none;
        }

        .preview-details {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
    </style>
</head>
<body>

<div class="topbar">
    <div class="topbar-brand">
        <div class="topbar-logo"><i class="fas fa-users-cog"></i></div>
        <div>
            <div class="topbar-title">Welfare System – Enrolled Worker Management</div>
            <div class="topbar-subtitle">CSL CLMS Annexure 4A Worker Registry</div>
        </div>
    </div>
    <div class="topbar-right">
        <div class="topbar-notif">
            <i class="fas fa-bell" style="font-size:18px"></i>
            <div class="notif-badge"><?= $notif_count ?></div>
        </div>
        <div class="topbar-user">
            <div class="user-avatar">WO</div>
            <div>
                <div style="font-size:13px;font-weight:600"><?= htmlspecialchars($_SESSION['user_name'] ?? 'Welfare Officer') ?></div>
                <div style="font-size:11px;opacity:0.8">Role: Welfare Team</div>
            </div>
        </div>
    </div>
</div>

<div class="layout-wrapper">
    <div class="sidebar">
        <div class="sidebar-section">
            <div class="sidebar-section-label">Welfare Operations</div>
            <a href="verification.php" class="sidebar-item"><i class="fas fa-check-double"></i> Verification Queue</a>
            <a href="enrolled_workers.php" class="sidebar-item active"><i class="fas fa-users"></i> Enrolled Workers</a>
        </div>
        <div class="sidebar-section">
            <div class="sidebar-section-label">Access Control</div>
            <a href="../index.php" class="sidebar-item"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="main-content">
        <!-- Stats Row -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <div class="stat-icon" style="background:#e0f2fe;color:#0284c7"><i class="fas fa-users"></i></div>
                <div class="stat-value" id="stat-total">0</div>
                <div class="stat-label">Total Enrolled Workers</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#d1fae5;color:#059669"><i class="fas fa-id-card"></i></div>
                <div class="stat-value" id="stat-active">0</div>
                <div class="stat-label">Active Passes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fef3c7;color:#d97706"><i class="fas fa-user-clock"></i></div>
                <div class="stat-value" id="stat-pending">0</div>
                <div class="stat-label">Pending Verification</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-user-slash"></i></div>
                <div class="stat-value" id="stat-blocked">0</div>
                <div class="stat-label">Blocked Workers</div>
            </div>
        </div>

        <!-- Sticky Toolbar & Filters -->
        <div class="filter-toolbar">
            <h3 style="font-size:14px;font-weight:600;color:#0f172a;margin-bottom:14px;"><i class="fas fa-filter"></i> Filters & Search</h3>
            <div class="filter-grid">
                <input type="text" class="form-control" id="filter-search" placeholder="Search by Name, Aadhaar, ACC..." />
                
                <select class="form-control" id="filter-status">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Pending Verification">Pending Verification</option>
                    <option value="Safety Pending">Safety Pending</option>
                    <option value="Blocked">Blocked</option>
                    <option value="Expired">Expired</option>
                </select>

                <select class="form-control" id="filter-contractor">
                    <option value="">All Contractors</option>
                    <?php foreach ($contractors as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['contractor_name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select class="form-control" id="filter-department">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select class="form-control" id="filter-trade">
                    <option value="">All Trades</option>
                    <?php foreach ($trades as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:8px;">
                <button class="btn btn-outline" id="btn-reset-filters"><i class="fas fa-undo"></i> Reset</button>
                <button class="btn btn-primary" id="btn-apply-filters"><i class="fas fa-search"></i> Apply Search</button>
            </div>
        </div>

        <!-- Bulk Actions Panel -->
        <div class="bulk-actions-panel" id="bulkActionsPanel">
            <div>
                <i class="fas fa-info-circle"></i> <span id="bulkSelectedCount">0</span> workers selected
            </div>
            <div style="display:flex;gap:8px;">
                <button class="btn btn-success btn-sm" onclick="triggerBulkAction('approve')"><i class="fas fa-check"></i> Bulk Approve</button>
                <button class="btn btn-danger btn-sm" onclick="triggerBulkAction('block')"><i class="fas fa-user-slash"></i> Bulk Block</button>
                <button class="btn btn-outline btn-sm" style="background:#ffffff" onclick="triggerBulkAction('export')"><i class="fas fa-download"></i> Bulk Export</button>
            </div>
        </div>

        <!-- Workers List Data Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-id-badge"></i> Enrolled Workers Registry</div>
            </div>
            <div class="card-body" style="padding:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAllCheckbox" /></th>
                            <th width="60">Photo</th>
                            <th>Worker Name</th>
                            <th>Aadhaar Number</th>
                            <th>ACC Number</th>
                            <th>Contractor</th>
                            <th>Trade / Skill</th>
                            <th>Status</th>
                            <th width="150" style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="workerTableBody">
                        <tr>
                            <td colspan="9" style="text-align:center;padding:40px;color:#64748b;">
                                <i class="fas fa-spinner fa-spin fa-2x"></i><br /><span style="margin-top:10px;display:inline-block">Loading worker records...</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Footer -->
            <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 24px;border-top:1px solid #e2e8f0;background:#ffffff;">
                <div style="font-size:13px;color:#64748b;" id="paginationInfo">
                    Showing 0 to 0 of 0 entries
                </div>
                <div style="display:flex;gap:8px;">
                    <button class="btn btn-outline btn-sm" id="btn-prev-page" disabled><i class="fas fa-chevron-left"></i> Previous</button>
                    <button class="btn btn-outline btn-sm" id="btn-next-page" disabled>Next <i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 80% Collapsible Side Drawer Overlay -->
<div class="drawer-overlay" id="drawerOverlay" onclick="closeDrawer()"></div>

<!-- 80% Collapsible Side Drawer -->
<div class="drawer" id="workerDrawer">
    <div class="drawer-header">
        <div>
            <h2 id="drawerWorkerName" style="font-size:18px;font-weight:700;color:#0f172a;margin:0;">Worker Details</h2>
            <div style="display:flex;gap:8px;margin-top:6px;align-items:center;">
                <span id="drawerWorkerIdLabel" style="font-size:12px;color:#64748b;font-weight:500;">ID: -</span>
                <span class="badge-chip" id="drawerWorkerStatusBadge">Pending</span>
            </div>
        </div>
        <div style="display:flex;gap:8px;">
            <button class="btn btn-primary btn-sm" id="btnEditWorker" onclick="toggleEditMode()"><i class="fas fa-edit"></i> Edit Fields</button>
            <button class="btn btn-outline btn-sm" onclick="closeDrawer()"><i class="fas fa-times"></i> Close</button>
        </div>
    </div>
    
    <div class="drawer-body">
        <!-- Sidebar Navigation inside drawer -->
        <div class="drawer-sidebar">
            <div class="drawer-nav-item active" data-tab="tab-personal" onclick="switchDrawerTab('tab-personal')"><i class="fas fa-user"></i> Personal details</div>
            <div class="drawer-nav-item" data-tab="tab-employment" onclick="switchDrawerTab('tab-employment')"><i class="fas fa-briefcase"></i> Employment info</div>
            <div class="drawer-nav-item" data-tab="tab-qualifications" onclick="switchDrawerTab('tab-qualifications')"><i class="fas fa-graduation-cap"></i> Qualifications</div>
            <div class="drawer-nav-item" data-tab="tab-documents" onclick="switchDrawerTab('tab-documents')"><i class="fas fa-file-alt"></i> Documents</div>
            <div class="drawer-nav-item" data-tab="tab-history" onclick="switchDrawerTab('tab-history')"><i class="fas fa-history"></i> Audit history</div>
            <div class="drawer-nav-item" data-tab="tab-biometric" onclick="switchDrawerTab('tab-biometric')"><i class="fas fa-fingerprint"></i> Biometric & Pass</div>
        </div>
        
        <!-- Tab contents panel -->
        <div class="drawer-content">
            <!-- PERSONAL DETAILS TAB -->
            <div class="drawer-panel active" id="tab-personal">
                <h3 style="font-size:15px;font-weight:700;color:#0f172a;margin-bottom:16px;border-bottom:2px solid #e2e8f0;padding-bottom:8px;">Personal Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-val edit-field-container">
                            <span class="view-mode" id="detail-worker_name">-</span>
                            <input type="text" class="form-control edit-mode" id="edit-worker_name" style="display:none" />
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Aadhaar Number</div>
                        <div class="detail-val" id="detail-aadhaar_no">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Mobile Number</div>
                        <div class="detail-val edit-field-container">
                            <span class="view-mode" id="detail-mobile_no">-</span>
                            <input type="text" class="form-control edit-mode" id="edit-mobile_no" style="display:none" />
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-val edit-field-container">
                            <span class="view-mode" id="detail-email">-</span>
                            <input type="email" class="form-control edit-mode" id="edit-email" style="display:none" />
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Father's Name</div>
                        <div class="detail-val" id="detail-father_name">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Spouse Name</div>
                        <div class="detail-val" id="detail-spouse_name">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Date of Birth</div>
                        <div class="detail-val" id="detail-dob">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Gender</div>
                        <div class="detail-val" id="detail-gender">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Nationality</div>
                        <div class="detail-val edit-field-container">
                            <span class="view-mode" id="detail-nationality">-</span>
                            <input type="text" class="form-control edit-mode" id="edit-nationality" list="welfareNationalityList" style="display:none" />
                            <datalist id="welfareNationalityList">
                                <option value="Indian"></option>
                                <option value="Nepalese"></option>
                                <option value="Bangladeshi"></option>
                                <option value="Sri Lankan"></option>
                                <option value="Myanmar"></option>
                                <option value="Filipino"></option>
                                <option value="Malaysian"></option>
                                <option value="Singaporean"></option>
                                <option value="Emirati"></option>
                                <option value="Saudi Arabian"></option>
                                <option value="Omani"></option>
                                <option value="Qatari"></option>
                                <option value="Kuwaiti"></option>
                            </datalist>
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Blood Group</div>
                        <div class="detail-val edit-field-container">
                            <span class="view-mode" id="detail-blood_group">-</span>
                            <select class="form-control edit-mode" id="edit-blood_group" style="display:none">
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <h3 style="font-size:15px;font-weight:700;color:#0f172a;margin-bottom:16px;border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin-top:24px;">Addresses</h3>
                <div style="display:grid;grid-template-columns:1fr;gap:20px;">
                    <div class="detail-item">
                        <div class="detail-label">Present Address</div>
                        <div class="detail-val" id="detail-present_address">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Permanent Address</div>
                        <div class="detail-val" id="detail-permanent_address">-</div>
                    </div>
                </div>
                
                <!-- Save Button for Edit Mode -->
                <div class="edit-mode" style="display:none;margin-top:24px;border-top:1px solid #e2e8f0;padding-top:16px;text-align:right;">
                    <button class="btn btn-outline" onclick="exitEditMode(false)">Cancel</button>
                    <button class="btn btn-success" onclick="saveWorkerEdits()"><i class="fas fa-save"></i> Save Changes</button>
                </div>
            </div>

            <!-- EMPLOYMENT INFO TAB -->
            <div class="drawer-panel" id="tab-employment" style="display:none">
                <h3 style="font-size:15px;font-weight:700;color:#0f172a;margin-bottom:16px;border-bottom:2px solid #e2e8f0;padding-bottom:8px;">Employment Mapping</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Contractor Name</div>
                        <div class="detail-val" id="detail-contractor_name">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Vendor Code</div>
                        <div class="detail-val" id="detail-contractor_vendor_code">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Work Awarding Department</div>
                        <div class="detail-val" id="detail-department_name">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Work Order Number</div>
                        <div class="detail-val" id="detail-work_order_no">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Pass Type Required</div>
                        <div class="detail-val" id="detail-pass_type">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Worker Designation / Trade</div>
                        <div class="detail-val" id="detail-trade">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Skill Category</div>
                        <div class="detail-val" id="detail-skill_category">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Nature of Work</div>
                        <div class="detail-val" id="detail-nature_of_work">-</div>
                    </div>
                </div>
            </div>

            <!-- QUALIFICATIONS TAB -->
            <div class="drawer-panel" id="tab-qualifications" style="display:none">
                <h3 style="font-size:15px;font-weight:700;color:#0f172a;margin-bottom:16px;border-bottom:2px solid #e2e8f0;padding-bottom:8px;">Educational Qualifications</h3>
                <div class="detail-grid" id="qualificationsDetailGrid">
                    <!-- Loaded dynamically via JS -->
                </div>

                <!-- Dynamic trade validation matrix indicator -->
                <div style="background:#f8fafc;border:1px solid #e2e8f0;padding:16px;border-radius:8px;margin-top:20px;">
                    <h4 style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:10px;"><i class="fas fa-shield-alt"></i> Qualification Matrix Checker</h4>
                    <div style="font-size:13px;color:#475569;display:flex;align-items:center;gap:8px;">
                        <span>Current qualification matching status: </span>
                        <span id="qualificationValidationStatus" class="badge-chip badge-active">PASS</span>
                    </div>
                    <p id="qualificationValidationReason" style="font-size:12px;color:#64748b;margin-top:6px;line-height:1.4;">The worker's trade matches the education level specified in the qualification rules matrix.</p>
                </div>
            </div>

            <!-- DOCUMENTS TAB -->
            <div class="drawer-panel" id="tab-documents" style="display:none">
                <h3 style="font-size:15px;font-weight:700;color:#0f172a;margin-bottom:16px;border-bottom:2px solid #e2e8f0;padding-bottom:8px;">Uploaded Documents</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Document Type</th>
                            <th>Doc Number</th>
                            <th>Expiry Date</th>
                            <th>Status</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="drawerDocsTableBody">
                        <!-- Loaded dynamically -->
                    </tbody>
                </table>
            </div>

            <!-- AUDIT HISTORY TAB -->
            <div class="drawer-panel" id="tab-history" style="display:none">
                <h3 style="font-size:15px;font-weight:700;color:#0f172a;margin-bottom:16px;border-bottom:2px solid #e2e8f0;padding-bottom:8px;">Audit History Rail</h3>
                <div class="timeline-rail" id="drawerHistoryRail">
                    <!-- Loaded dynamically -->
                </div>
            </div>

            <!-- BIOMETRIC & PASS TAB -->
            <div class="drawer-panel" id="tab-biometric" style="display:none">
                <h3 style="font-size:15px;font-weight:700;color:#0f172a;margin-bottom:16px;border-bottom:2px solid #e2e8f0;padding-bottom:8px;">Biometric Sync Status</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Biometric Enrollment</div>
                        <div class="detail-val" id="detail-biometric_status">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Attendance Status</div>
                        <div class="detail-val" id="detail-attendance_status">-</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Safety Training Status</div>
                        <div class="detail-val" id="detail-safety_status">-</div>
                    </div>
                </div>

                <h3 style="font-size:15px;font-weight:700;color:#0f172a;margin-bottom:16px;border-bottom:2px solid #e2e8f0;padding-bottom:8px;margin-top:24px;">Pass Details</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Pass Number</div>
                        <div class="detail-val" id="detail-pass_no">N/A</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Pass Validity To</div>
                        <div class="detail-val" id="detail-pass_validity">N/A</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Reissue Count</div>
                        <div class="detail-val">0</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Document Preview Modal with split panel -->
<div id="previewModal" class="modal-overlay" style="display:none">
    <div class="modal" style="max-width:900px; width: 90%;">
        <div class="modal-header">
            <h3 style="font-size:16px;font-weight:700;" id="previewModalTitle"><i class="fas fa-file-pdf"></i> Document Review</h3>
            <i class="fas fa-times" style="cursor:pointer" onclick="hideModal('previewModal')"></i>
        </div>
        <div class="modal-body">
            <div class="preview-container">
                <div class="preview-pane">
                    <iframe id="docPreviewFrame" src=""></iframe>
                    <div id="noPreviewAvailable" style="display:none;color:#64748b;">No preview available for this file type.</div>
                </div>
                <div class="preview-details">
                    <div>
                        <h4 style="font-weight:700;font-size:14px;color:#0f172a;margin-bottom:12px;">Verification Details</h4>
                        <div class="detail-item" style="margin-bottom:12px;">
                            <div class="detail-label">Document Type</div>
                            <div class="detail-val" id="preview-doc_type">-</div>
                        </div>
                        <div class="detail-item" style="margin-bottom:12px;">
                            <div class="detail-label">Document Number</div>
                            <div class="detail-val" id="preview-doc_number">-</div>
                        </div>
                        <div class="detail-item" style="margin-bottom:12px;">
                            <div class="detail-label">Expiry Date</div>
                            <div class="detail-val" id="preview-expiry_date">-</div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="form-group" style="margin-bottom:12px;">
                            <label class="form-label">Review Remarks <span style="color:var(--danger)">* for rejection</span></label>
                            <textarea class="form-control" id="preview-remarks" rows="3" placeholder="Add remarks..."></textarea>
                        </div>
                        <div style="display:flex;gap:8px;">
                            <button class="btn btn-danger" style="flex:1" onclick="submitDocReview('reject')"><i class="fas fa-times"></i> Reject</button>
                            <button class="btn btn-success" style="flex:1" onclick="submitDocReview('verify')"><i class="fas fa-check"></i> Approve</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Block Reason Modal -->
<div id="blockModal" class="modal-overlay" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3 style="font-size:16px;font-weight:700;color:var(--danger)"><i class="fas fa-user-slash"></i> Block Worker</h3>
            <i class="fas fa-times" style="cursor:pointer" onclick="hideModal('blockModal')"></i>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Blocking Reason <span style="color:var(--danger)">*</span></label>
                <textarea class="form-control" id="block-reason" rows="4" placeholder="Enter reason for blocking..."></textarea>
            </div>
            <div class="alert alert-danger" style="margin-top:12px;">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Blocking a worker will suspend their active gate pass and log the action in the block history.</span>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="hideModal('blockModal')">Cancel</button>
            <button class="btn btn-danger" onclick="submitWorkerBlock()"><i class="fas fa-ban"></i> Confirm Block</button>
        </div>
    </div>
</div>

<!-- Modal: Delete Reason Modal -->
<div id="deleteModal" class="modal-overlay" style="display:none">
    <div class="modal">
        <div class="modal-header">
            <h3 style="font-size:16px;font-weight:700;color:var(--danger)"><i class="fas fa-trash-alt"></i> Soft Delete Worker</h3>
            <i class="fas fa-times" style="cursor:pointer" onclick="hideModal('deleteModal')"></i>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Delete Reason <span style="color:var(--danger)">*</span></label>
                <textarea class="form-control" id="delete-reason" rows="4" placeholder="Enter reason for deletion..."></textarea>
            </div>
            <div class="alert alert-warning" style="margin-top:12px;">
                <i class="fas fa-exclamation-triangle"></i>
                <span>This performs a soft delete. The worker registry status is marked Deleted and legacy record set to Removed.</span>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="hideModal('deleteModal')">Cancel</button>
            <button class="btn btn-danger" onclick="submitWorkerDelete()"><i class="fas fa-trash"></i> Delete Worker</button>
        </div>
    </div>
</div>

<!-- JS Core Helpers -->
<script src="../../js/utils.js"></script>
<script>
    // State Variables
    let currentPage = 1;
    const pageLimit = 10;
    let currentWorkerId = null;
    let selectedWorkerIds = [];
    let isEditMode = false;
    let currentReviewDocId = null;
    let qualificationsData = [];

    // Qualification Matrix Map (Rule validation)
    const tradeQualificationMap = {
        'ITI': ['Electrician', 'Fitter', 'Welder', 'Machinist', 'Turner', 'Plumber', 'Wireman'],
        'Diploma': ['Electrical Engineer', 'Mechanical Engineer', 'Civil Engineer', 'Safety Officer', 'Supervisor'],
        'Degree': ['Project Manager', 'Site Engineer', 'Safety Inspector', 'Engineer'],
        'Secondary': ['Helper', 'Unskilled Labour', 'Security Guard', 'General Worker'],
        '10th': ['Helper', 'Unskilled Labour', 'General Worker'],
        '12th': ['Helper', 'Unskilled Labour', 'General Worker', 'Supervisor']
    };

    // DOM Elements
    const workerTableBody = document.getElementById('workerTableBody');
    const selectAllCheckbox = document.getElementById('selectAllCheckbox');
    const bulkActionsPanel = document.getElementById('bulkActionsPanel');
    const bulkSelectedCount = document.getElementById('bulkSelectedCount');

    // Loader helper
    function showLoader() {
        workerTableBody.innerHTML = `
            <tr>
                <td colspan="9" style="text-align:center;padding:40px;color:#64748b;">
                    <i class="fas fa-spinner fa-spin fa-2x"></i><br /><span style="margin-top:10px;display:inline-block">Loading worker records...</span>
                </td>
            </tr>
        `;
    }

    // Load Data
    async function loadWorkers() {
        showLoader();
        
        const search = document.getElementById('filter-search').value;
        const status = document.getElementById('filter-status').value;
        const contractorId = document.getElementById('filter-contractor').value;
        const deptId = document.getElementById('filter-department').value;
        const trade = document.getElementById('filter-trade').value;

        const queryUrl = `../../api/welfare/workers/list.php?page=${currentPage}&limit=${pageLimit}` +
            `&search=${encodeURIComponent(search)}` +
            `&status=${encodeURIComponent(status)}` +
            `&contractor_id=${contractorId}` +
            `&department_id=${deptId}` +
            `&trade=${encodeURIComponent(trade)}`;

        try {
            const response = await fetch(queryUrl);
            const res = await response.json();
            
            if (res.status === 'success') {
                renderTable(res.data);
                updatePagination(res.pagination);
                updateStats(res.pagination.total);
            } else {
                workerTableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:20px;color:var(--danger)">Error: ${res.message}</td></tr>`;
            }
        } catch (e) {
            workerTableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:20px;color:var(--danger)">Network error: ${e.message}</td></tr>`;
        }
    }

    // Render data rows
    function renderTable(workers) {
        if (!workers || workers.length === 0) {
            workerTableBody.innerHTML = `<tr><td colspan="9" style="text-align:center;padding:40px;color:#64748b;"><i class="fas fa-inbox fa-3x" style="opacity:0.3"></i><br /><br />No workers matching search criteria</td></tr>`;
            return;
        }

        workerTableBody.innerHTML = workers.map(w => {
            const isSelected = selectedWorkerIds.includes(parseInt(w.worker_id));
            const photoEl = w.photo 
                ? `<img src="../../${w.photo}" class="worker-photo-cell" onerror="this.src='https://placehold.co/100x100?text=Photo'" />` 
                : `<div class="worker-photo-placeholder"><i class="fas fa-user"></i></div>`;
            
            let badgeClass = 'badge-pending';
            if (w.worker_status === 'Active') badgeClass = 'badge-active';
            else if (w.worker_status === 'Safety Pending') badgeClass = 'badge-safety';
            else if (w.worker_status === 'Blocked') badgeClass = 'badge-blocked';
            else if (w.worker_status === 'Expired') badgeClass = 'badge-expired';

            // Action buttons mapping
            let actionButtons = `<button class="btn btn-outline btn-sm" onclick="openDrawer(${w.worker_id})" style="padding:4px 8px;font-size:12px;" title="View Profile"><i class="fas fa-eye"></i> Profile</button>`;
            
            if (w.worker_status === 'Pending Verification') {
                actionButtons += `
                    <button class="btn btn-success btn-sm" onclick="approveWorker(${w.worker_id})" style="padding:4px 8px;font-size:12px;margin-left:4px;" title="Approve"><i class="fas fa-check"></i></button>
                    <button class="btn btn-danger btn-sm" onclick="rejectWorker(${w.worker_id})" style="padding:4px 8px;font-size:12px;margin-left:4px;" title="Reject"><i class="fas fa-times"></i></button>
                `;
            } else if (w.worker_status === 'Active') {
                actionButtons += `
                    <button class="btn btn-danger btn-sm" onclick="openBlockModal(${w.worker_id})" style="padding:4px 8px;font-size:12px;margin-left:4px;" title="Block Worker"><i class="fas fa-user-slash"></i></button>
                `;
            } else if (w.worker_status === 'Blocked') {
                actionButtons += `
                    <button class="btn btn-success btn-sm" onclick="unblockWorker(${w.worker_id})" style="padding:4px 8px;font-size:12px;margin-left:4px;" title="Unblock Worker"><i class="fas fa-user-check"></i></button>
                `;
            }

            actionButtons += `
                <button class="btn btn-outline btn-sm" onclick="openDeleteModal(${w.worker_id})" style="padding:4px 8px;font-size:12px;margin-left:4px;color:var(--danger);border-color:var(--danger);" title="Delete Worker"><i class="fas fa-trash"></i></button>
            `;

            return `
                <tr data-id="${w.worker_id}">
                    <td><input type="checkbox" class="row-checkbox" value="${w.worker_id}" ${isSelected ? 'checked' : ''} /></td>
                    <td>${photoEl}</td>
                    <td><strong>${w.worker_name || 'N/A'}</strong></td>
                    <td>${w.aadhaar_no || 'N/A'}</td>
                    <td><code style="background:#f1f5f9;padding:2px 6px;border-radius:4px;">${w.acc_no || 'N/A'}</code></td>
                    <td>${w.contractor_name || 'N/A'}</td>
                    <td>${w.trade || 'N/A'} <span style="font-size:11px;color:#64748b;display:block;">${w.skill_category || ''}</span></td>
                    <td><span class="badge-chip ${badgeClass}">${w.worker_status}</span></td>
                    <td style="text-align:right;">
                        <div style="display:flex;justify-content:flex-end;">
                            ${actionButtons}
                        </div>
                    </td>
                </tr>
            `;
        }).join('');

        // Wire checkbox event listeners
        document.querySelectorAll('.row-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                const id = parseInt(this.value);
                if (this.checked) {
                    if (!selectedWorkerIds.includes(id)) selectedWorkerIds.push(id);
                } else {
                    selectedWorkerIds = selectedWorkerIds.filter(x => x !== id);
                }
                updateBulkActionsPanel();
            });
        });
    }

    // Update bulk actions panel UI
    function updateBulkActionsPanel() {
        if (selectedWorkerIds.length > 0) {
            bulkActionsPanel.style.display = 'flex';
            bulkSelectedCount.textContent = selectedWorkerIds.length;
        } else {
            bulkActionsPanel.style.display = 'none';
        }
    }

    // Select all checkboxes handler
    selectAllCheckbox.addEventListener('change', function() {
        const rowCheckboxes = document.querySelectorAll('.row-checkbox');
        rowCheckboxes.forEach(cb => {
            cb.checked = this.checked;
            const id = parseInt(cb.value);
            if (this.checked) {
                if (!selectedWorkerIds.includes(id)) selectedWorkerIds.push(id);
            } else {
                selectedWorkerIds = selectedWorkerIds.filter(x => x !== id);
            }
        });
        updateBulkActionsPanel();
    });

    // Update pagination footer controls
    function updatePagination(pg) {
        const start = (pg.page - 1) * pg.limit + 1;
        const end = Math.min(pg.page * pg.limit, pg.total);
        
        document.getElementById('paginationInfo').textContent = pg.total > 0 
            ? `Showing ${start} to ${end} of ${pg.total} entries`
            : `Showing 0 to 0 of 0 entries`;

        const prevBtn = document.getElementById('btn-prev-page');
        const nextBtn = document.getElementById('btn-next-page');

        prevBtn.disabled = pg.page <= 1;
        nextBtn.disabled = pg.page * pg.limit >= pg.total;
    }

    // Update header statistics
    async function updateStats(totalWorkers) {
        // Run count queries dynamically
        try {
            const stats = await fetch('../../api/welfare/workers/list.php?limit=1');
            const data = await stats.json();
            
            if (data.status === 'success') {
                const total = data.pagination.total;
                document.getElementById('stat-total').textContent = total;
            }
            
            // Query counts for statuses
            const activeRes = await fetch('../../api/welfare/workers/list.php?limit=1&status=Active');
            const activeData = await activeRes.json();
            if (activeData.status === 'success') document.getElementById('stat-active').textContent = activeData.pagination.total;

            const pendingRes = await fetch('../../api/welfare/workers/list.php?limit=1&status=Pending Verification');
            const pendingData = await pendingRes.json();
            if (pendingData.status === 'success') document.getElementById('stat-pending').textContent = pendingData.pagination.total;

            const blockedRes = await fetch('../../api/welfare/workers/list.php?limit=1&status=Blocked');
            const blockedData = await blockedRes.json();
            if (blockedData.status === 'success') document.getElementById('stat-blocked').textContent = blockedData.pagination.total;

        } catch(e) {
            console.error('Error fetching statistics:', e);
        }
    }

    // Modal Helpers
    function showModal(id) {
        document.getElementById(id).style.display = 'block';
    }
    
    function hideModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    // Worker Approval Action
    async function approveWorker(id) {
        if (!confirm('Are you sure you want to approve this worker? This will trigger Contractor Registration ratio and quota checks.')) return;
        
        try {
            const formData = new FormData();
            formData.append('worker_id', id);
            formData.append('action', 'approve');

            const response = await fetch('../../api/welfare/workers/approve.php', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();
            
            if (res.status === 'success') {
                alert('Worker approved successfully and moved to Safety Pending.');
                loadWorkers();
            } else {
                alert('Error: ' + res.message);
            }
        } catch(e) {
            alert('Failed to approve worker: ' + e.message);
        }
    }

    // Worker Reject Action
    async function rejectWorker(id) {
        const reason = prompt('Please enter the reason for rejection:');
        if (reason === null) return;
        if (!reason.trim()) {
            alert('Rejection reason is required.');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('worker_id', id);
            formData.append('action', 'reject');
            formData.append('remarks', reason);

            const response = await fetch('../../api/welfare/workers/approve.php', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();
            
            if (res.status === 'success') {
                alert('Worker enrollment rejected.');
                loadWorkers();
            } else {
                alert('Error: ' + res.message);
            }
        } catch(e) {
            alert('Failed to reject worker: ' + e.message);
        }
    }

    // Block Modal Opening
    function openBlockModal(id) {
        currentWorkerId = id;
        document.getElementById('block-reason').value = '';
        showModal('blockModal');
    }

    // Block submission
    async function submitWorkerBlock() {
        const reason = document.getElementById('block-reason').value.trim();
        if (!reason) {
            alert('Reason is required.');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('worker_id', currentWorkerId);
            formData.append('action', 'block');
            formData.append('reason', reason);

            const response = await fetch('../../api/welfare/workers/block.php', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();
            
            if (res.status === 'success') {
                hideModal('blockModal');
                alert('Worker blocked successfully.');
                loadWorkers();
                if (document.getElementById('workerDrawer').classList.contains('open')) {
                    openDrawer(currentWorkerId);
                }
            } else {
                alert('Error: ' + res.message);
            }
        } catch(e) {
            alert('Failed to block worker: ' + e.message);
        }
    }

    // Unblock Action
    async function unblockWorker(id) {
        const reason = prompt('Enter remarks for unblocking:');
        if (reason === null) return;
        if (!reason.trim()) {
            alert('Remarks are required.');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('worker_id', id);
            formData.append('action', 'unblock');
            formData.append('reason', reason);

            const response = await fetch('../../api/welfare/workers/block.php', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();
            
            if (res.status === 'success') {
                alert('Worker unblocked successfully.');
                loadWorkers();
                if (document.getElementById('workerDrawer').classList.contains('open')) {
                    openDrawer(id);
                }
            } else {
                alert('Error: ' + res.message);
            }
        } catch(e) {
            alert('Failed to unblock worker: ' + e.message);
        }
    }

    // Delete Modal Opening
    function openDeleteModal(id) {
        currentWorkerId = id;
        document.getElementById('delete-reason').value = '';
        showModal('deleteModal');
    }

    // Delete submission
    async function submitWorkerDelete() {
        const reason = document.getElementById('delete-reason').value.trim();
        if (!reason) {
            alert('Reason is required.');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('worker_id', currentWorkerId);
            formData.append('delete_reason', reason);

            const response = await fetch('../../api/welfare/workers/delete.php', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();
            
            if (res.status === 'success') {
                hideModal('deleteModal');
                alert('Worker deleted successfully.');
                loadWorkers();
                closeDrawer();
            } else {
                alert('Error: ' + res.message);
            }
        } catch(e) {
            alert('Failed to delete worker: ' + e.message);
        }
    }

    // Drawer management
    function openDrawer(id) {
        currentWorkerId = id;
        document.getElementById('drawerOverlay').classList.add('open');
        const drawer = document.getElementById('workerDrawer');
        drawer.classList.add('open');
        exitEditMode(false); // Make sure edit mode is off
        loadWorkerDrawerData(id);
    }

    function closeDrawer() {
        document.getElementById('drawerOverlay').classList.remove('open');
        document.getElementById('workerDrawer').classList.remove('open');
    }

    // Switch tabs inside drawer
    function switchDrawerTab(tabId) {
        document.querySelectorAll('.drawer-nav-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelectorAll('.drawer-panel').forEach(panel => {
            panel.style.display = 'none';
        });

        document.querySelector(`.drawer-nav-item[data-tab="${tabId}"]`).classList.add('active');
        document.getElementById(tabId).style.display = 'block';
    }

    // Fetch and populate data in the side drawer
    async function loadWorkerDrawerData(id) {
        try {
            const response = await fetch(`../../api/welfare/workers/details.php?worker_id=${id}`);
            const res = await response.json();
            
            if (res.status === 'success') {
                const data = res.data;
                const worker = data.worker;
                
                // Set Header details
                document.getElementById('drawerWorkerName').textContent = worker.worker_name || 'Worker Details';
                document.getElementById('drawerWorkerIdLabel').innerHTML = `<strong>Worker ID:</strong> ${worker.worker_id} · <strong>Aadhaar:</strong> ${worker.aadhaar_no}`;
                
                const statusBadge = document.getElementById('drawerWorkerStatusBadge');
                statusBadge.textContent = worker.worker_status;
                statusBadge.className = 'badge-chip';
                if (worker.worker_status === 'Active') statusBadge.classList.add('badge-active');
                else if (worker.worker_status === 'Safety Pending') statusBadge.classList.add('badge-safety');
                else if (worker.worker_status === 'Blocked') statusBadge.classList.add('badge-blocked');
                else if (worker.worker_status === 'Expired') statusBadge.classList.add('badge-expired');
                else statusBadge.classList.add('badge-pending');

                // Populate Personal Details
                document.getElementById('detail-worker_name').textContent = worker.worker_name || 'N/A';
                document.getElementById('detail-aadhaar_no').textContent = worker.aadhaar_no || 'N/A';
                document.getElementById('detail-mobile_no').textContent = worker.mobile_no || 'N/A';
                document.getElementById('detail-email').textContent = worker.email || 'N/A';
                document.getElementById('detail-father_name').textContent = worker.father_name || 'N/A';
                document.getElementById('detail-spouse_name').textContent = worker.spouse_name || 'N/A';
                document.getElementById('detail-dob').textContent = worker.dob || 'N/A';
                document.getElementById('detail-gender').textContent = worker.gender || 'N/A';
                document.getElementById('detail-nationality').textContent = worker.nationality || 'Indian';
                document.getElementById('detail-blood_group').textContent = worker.blood_group || 'N/A';
                document.getElementById('detail-present_address').textContent = worker.present_address || 'N/A';
                document.getElementById('detail-permanent_address').textContent = worker.permanent_address || 'N/A';

                // Populate Edit Mode inputs
                document.getElementById('edit-worker_name').value = worker.worker_name || '';
                document.getElementById('edit-mobile_no').value = worker.mobile_no || '';
                document.getElementById('edit-email').value = worker.email || '';
                document.getElementById('edit-nationality').value = worker.nationality || 'Indian';
                document.getElementById('edit-blood_group').value = worker.blood_group || 'A+';

                // Populate Employment Info
                document.getElementById('detail-contractor_name').textContent = worker.contractor_name || 'N/A';
                document.getElementById('detail-contractor_vendor_code').textContent = worker.contractor_vendor_code || 'N/A';
                document.getElementById('detail-department_name').textContent = worker.department_name || 'N/A';
                document.getElementById('detail-work_order_no').textContent = worker.work_order_no || 'N/A';
                document.getElementById('detail-pass_type').textContent = worker.pass_type || 'N/A';
                document.getElementById('detail-trade').textContent = worker.trade || 'N/A';
                document.getElementById('detail-skill_category').textContent = worker.skill_category || 'N/A';
                document.getElementById('detail-nature_of_work').textContent = worker.nature_of_work || 'N/A';

                // Populate Qualifications
                qualificationsData = data.qualifications;
                renderQualificationsTab(worker);

                // Populate Documents
                renderDocumentsTab(data.documents);

                // Populate Audit Timeline
                renderAuditTimeline(data.audit_logs);

                // Populate Biometric Sync Status
                document.getElementById('detail-biometric_status').innerHTML = worker.biometric_status === 'Verified'
                    ? '<span class="badge-chip badge-active"><i class="fas fa-fingerprint"></i> Synced</span>'
                    : `<span class="badge-chip badge-pending"><i class="fas fa-clock"></i> ${worker.biometric_status}</span>`;
                
                document.getElementById('detail-attendance_status').textContent = worker.attendance_status || 'Inactive';
                document.getElementById('detail-safety_status').textContent = worker.safety_status || 'Pending';
                document.getElementById('detail-pass_no').textContent = worker.acc_no || 'N/A';
                document.getElementById('detail-pass_validity').textContent = worker.pass_validity_to || 'N/A';

            } else {
                alert('Failed to load worker details: ' + res.message);
                closeDrawer();
            }
        } catch(e) {
            alert('Error loading worker details: ' + e.message);
            closeDrawer();
        }
    }

    // Render Qualifications Tab
    function renderQualificationsTab(worker) {
        const grid = document.getElementById('qualificationsDetailGrid');
        if (!qualificationsData || qualificationsData.length === 0) {
            grid.innerHTML = '<div style="color:#64748b;font-size:13px;padding:20px;">No educational qualification records mapped.</div>';
            
            // Default check indicator
            document.getElementById('qualificationValidationStatus').textContent = 'UNCHECKED';
            document.getElementById('qualificationValidationStatus').className = 'badge-chip badge-expired';
            document.getElementById('qualificationValidationReason').textContent = 'No educational qualification record available to validate trade mapping.';
            return;
        }

        const qual = qualificationsData[0]; // Take primary qualification
        grid.innerHTML = `
            <div class="detail-item">
                <div class="detail-label">Education Level</div>
                <div class="detail-val">${qual.education_level || 'N/A'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Trade Specialization</div>
                <div class="detail-val">${qual.trade_name || 'N/A'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Years of Experience</div>
                <div class="detail-val">${qual.experience_years || '0'} Years</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Institute / University</div>
                <div class="detail-val">${qual.institute_name || 'N/A'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Certificate Number</div>
                <div class="detail-val">${qual.certificate_no || 'N/A'}</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Year of Passing</div>
                <div class="detail-val">${qual.passing_year || 'N/A'}</div>
            </div>
        `;

        // Run Qualification Matrix Validation
        const edu = qual.education_level;
        const trade = qual.trade_name;
        
        let isValidTrade = false;
        let allowedTrades = [];
        
        // Search mapping matrix
        if (tradeQualificationMap[edu]) {
            allowedTrades = tradeQualificationMap[edu];
            isValidTrade = allowedTrades.some(t => trade.toLowerCase().includes(t.toLowerCase()) || t.toLowerCase().includes(trade.toLowerCase()));
        } else {
            // General match for unlisted/higher education levels
            isValidTrade = true;
        }

        const statusEl = document.getElementById('qualificationValidationStatus');
        const reasonEl = document.getElementById('qualificationValidationReason');

        if (isValidTrade) {
            statusEl.textContent = 'PASS';
            statusEl.className = 'badge-chip badge-active';
            reasonEl.textContent = `Worker qualification details match Page 40 rules. Education level '${edu}' is fully qualified for trade specialization '${trade}'.`;
        } else {
            statusEl.textContent = 'MISMATCH WARNING';
            statusEl.className = 'badge-chip badge-blocked';
            reasonEl.innerHTML = `<strong>CAUTION:</strong> Education level '${edu}' is not typical for trade specialization '${trade}'. Expected trades for this level include: ${allowedTrades.join(', ')}. Please verify certificate details manual review.`;
        }
    }

    // Render Documents Tab
    function renderDocumentsTab(docs) {
        const tbody = document.getElementById('drawerDocsTableBody');
        if (!docs || docs.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;padding:20px;color:#64748b;">No documents uploaded.</td></tr>';
            return;
        }

        tbody.innerHTML = docs.map(d => {
            let badgeClass = 'badge-pending';
            if (d.verification_status === 'Verified') badgeClass = 'badge-active';
            else if (d.verification_status === 'Rejected') badgeClass = 'badge-blocked';
            else if (d.verification_status === 'Expired') badgeClass = 'badge-expired';

            const previewBtn = `<button class="btn btn-outline btn-sm" onclick="previewDocument(${d.document_id}, '${d.document_type}', '${d.document_number}', '${d.expiry_date}', '../../${d.document_path}')" style="padding:4px 8px;font-size:12px;"><i class="fas fa-eye"></i> Review</button>`;

            return `
                <tr>
                    <td><strong>${d.document_type}</strong></td>
                    <td><code>${d.document_number || 'N/A'}</code></td>
                    <td>${d.expiry_date || 'N/A'}</td>
                    <td><span class="badge-chip ${badgeClass}">${d.verification_status}</span></td>
                    <td style="text-align:right;">${previewBtn}</td>
                </tr>
            `;
        }).join('');
    }

    // Render Audit History timeline
    function renderAuditTimeline(logs) {
        const rail = document.getElementById('drawerHistoryRail');
        if (!logs || logs.length === 0) {
            rail.innerHTML = '<div style="color:#64748b;font-size:13px;padding:10px 0;">No history logged.</div>';
            return;
        }

        rail.innerHTML = logs.map(l => {
            let classType = 'info';
            if (l.action_type.toLowerCase().includes('delete') || l.action_type.toLowerCase().includes('reject') || l.action_type.toLowerCase().includes('suspend')) {
                classType = 'danger';
            } else if (l.action_type.toLowerCase().includes('block') || l.action_type.toLowerCase().includes('warn')) {
                classType = 'warning';
            } else if (l.action_type.toLowerCase().includes('approve') || l.action_type.toLowerCase().includes('verified')) {
                classType = 'success';
            }

            return `
                <div class="timeline-item ${classType}">
                    <div class="timeline-marker"></div>
                    <div class="timeline-time">${window.formatDate ? window.formatDate(l.created_at) : l.created_at} · by ${l.user_name || 'System'}</div>
                    <div class="timeline-title">${l.action_type} - ${l.module_name}</div>
                    <div class="timeline-desc">${l.remarks} <span style="font-size:11px;color:#94a3b8;display:block;">IP: ${l.ip_address}</span></div>
                </div>
            `;
        }).join('');
    }

    // Toggle Edit Mode in Drawer
    function toggleEditMode() {
        if (!isEditMode) {
            isEditMode = true;
            document.querySelectorAll('.view-mode').forEach(el => el.style.display = 'none');
            document.querySelectorAll('.edit-mode').forEach(el => el.style.display = 'block');
            document.getElementById('btnEditWorker').innerHTML = '<i class="fas fa-undo"></i> Exit Edit';
        } else {
            exitEditMode(false);
        }
    }

    function exitEditMode(saved = false) {
        isEditMode = false;
        document.querySelectorAll('.view-mode').forEach(el => el.style.display = 'block');
        document.querySelectorAll('.edit-mode').forEach(el => el.style.display = 'none');
        document.getElementById('btnEditWorker').innerHTML = '<i class="fas fa-edit"></i> Edit Fields';
        if (saved) {
            loadWorkerDrawerData(currentWorkerId);
        }
    }

    // Save Worker Edits
    async function saveWorkerEdits() {
        const name = document.getElementById('edit-worker_name').value.trim();
        const mobile = document.getElementById('edit-mobile_no').value.trim();
        const email = document.getElementById('edit-email').value.trim();
        const nationality = document.getElementById('edit-nationality').value.trim();
        const blood = document.getElementById('edit-blood_group').value;

        if (!name) {
            alert('Worker name is required.');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('worker_id', currentWorkerId);
            formData.append('mobile_no', mobile);
            formData.append('email', email);
            formData.append('nationality', nationality || 'Indian');
            formData.append('blood_group', blood);
            
            // Support updating name in workmen
            // Since we edit workmen, the edit API will sync or handle it
            
            const response = await fetch('../../api/welfare/workers/edit.php', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();
            
            if (res.status === 'success') {
                alert('Worker fields updated successfully.');
                exitEditMode(true);
                loadWorkers();
            } else {
                alert('Error: ' + res.message);
            }
        } catch(e) {
            alert('Failed to save worker edits: ' + e.message);
        }
    }

    // Document Review / Preview Modal opening
    function previewDocument(docId, type, number, expiry, path) {
        currentReviewDocId = docId;
        document.getElementById('previewModalTitle').textContent = `Review Document: ${type}`;
        document.getElementById('preview-doc_type').textContent = type;
        document.getElementById('preview-doc_number').textContent = number || 'N/A';
        document.getElementById('preview-expiry_date').textContent = expiry || 'No Expiry';
        document.getElementById('preview-remarks').value = '';

        const frame = document.getElementById('docPreviewFrame');
        const noPreview = document.getElementById('noPreviewAvailable');

        if (path.toLowerCase().endsWith('.pdf') || path.toLowerCase().endsWith('.png') || path.toLowerCase().endsWith('.jpg') || path.toLowerCase().endsWith('.jpeg')) {
            frame.src = path;
            frame.style.display = 'block';
            noPreview.style.display = 'none';
        } else {
            frame.src = '';
            frame.style.display = 'none';
            noPreview.style.display = 'block';
        }

        showModal('previewModal');
    }

    // Submit Document Approval/Rejection
    async function submitDocReview(action) {
        const remarks = document.getElementById('preview-remarks').value.trim();
        if (action === 'reject' && !remarks) {
            alert('Please enter a rejection reason.');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('worker_id', currentWorkerId);
            formData.append('document_id', currentReviewDocId);
            formData.append('remarks', remarks);

            const response = await fetch('../../api/welfare/workers/documents.php', {
                method: 'POST',
                body: formData
            });
            const res = await response.json();
            
            if (res.status === 'success') {
                hideModal('previewModal');
                alert(`Document successfully ${action === 'verify' ? 'approved' : 'rejected'}.`);
                // Reload documents and audit trail inside drawer
                loadWorkerDrawerData(currentWorkerId);
            } else {
                alert('Error: ' + res.message);
            }
        } catch(e) {
            alert('Failed to submit document review: ' + e.message);
        }
    }

    // Bulk Actions Trigger
    async function triggerBulkAction(action) {
        if (selectedWorkerIds.length === 0) {
            alert('No workers selected.');
            return;
        }

        if (action === 'export') {
            alert(`Initiating SAP-compatible CSV export for ${selectedWorkerIds.length} workers...`);
            window.location.href = `../../api/welfare/workers/export.php?ids=${selectedWorkerIds.join(',')}`;
            return;
        }

        let reason = '';
        if (action === 'block') {
            reason = prompt(`Enter reason for blocking these ${selectedWorkerIds.length} workers:`);
            if (reason === null) return;
            if (!reason.trim()) {
                alert('Reason is required.');
                return;
            }
        } else if (action === 'approve') {
            if (!confirm(`Are you sure you want to bulk-approve ${selectedWorkerIds.length} workers?`)) return;
        }

        let successCount = 0;
        let failCount = 0;
        let errors = [];

        // Run sequential AJAX calls to respect transactions & unique validations for each worker
        for (const id of selectedWorkerIds) {
            try {
                const formData = new FormData();
                formData.append('worker_id', id);
                
                let endpoint = '';
                if (action === 'approve') {
                    formData.append('action', 'approve');
                    endpoint = '../../api/welfare/workers/approve.php';
                } else {
                    formData.append('action', 'block');
                    formData.append('reason', reason);
                    endpoint = '../../api/welfare/workers/block.php';
                }

                const response = await fetch(endpoint, {
                    method: 'POST',
                    body: formData
                });
                const res = await response.json();
                
                if (res.status === 'success') {
                    successCount++;
                } else {
                    failCount++;
                    errors.push(`Worker ID ${id}: ${res.message}`);
                }
            } catch(e) {
                failCount++;
                errors.push(`Worker ID ${id}: Network error`);
            }
        }

        alert(`Bulk action complete.\nApproved/Blocked: ${successCount} successful.\nFailed: ${failCount} failed.`);
        if (errors.length > 0) {
            console.error('Bulk errors:', errors);
        }
        
        // Reset selections
        selectedWorkerIds = [];
        selectAllCheckbox.checked = false;
        updateBulkActionsPanel();
        loadWorkers();
    }

    // Filter Buttons
    document.getElementById('btn-apply-filters').addEventListener('click', () => {
        currentPage = 1;
        loadWorkers();
    });

    document.getElementById('btn-reset-filters').addEventListener('click', () => {
        document.getElementById('filter-search').value = '';
        document.getElementById('filter-status').value = '';
        document.getElementById('filter-contractor').value = '';
        document.getElementById('filter-department').value = '';
        document.getElementById('filter-trade').value = '';
        currentPage = 1;
        loadWorkers();
    });

    // Pagination Click Handlers
    document.getElementById('btn-prev-page').addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            loadWorkers();
        }
    });

    document.getElementById('btn-next-page').addEventListener('click', () => {
        currentPage++;
        loadWorkers();
    });

    // Initial Load on Page DOM Ready
    document.addEventListener('DOMContentLoaded', () => {
        loadWorkers();
    });
</script>

</body>
</html>

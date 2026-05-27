<?php
require_once '../../include/auth.php';
checkAuth(['contractor', 'customer']);
include '../../include/config.php';
include '../../include/customer_portal_context.php';
include '../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Contractor';
$user_id = $_SESSION['user_id'];
clms_get_portal_contractor($conn);

function renderContent() {
    global $conn, $user_id;

    $contractor = db_single($conn, "SELECT id, vendor_code FROM contractors WHERE user_id = ?", 'i', [$user_id]);
    $c_id = $contractor['id'] ?? null;
    $v_code = $contractor['vendor_code'] ?? '';

    if (!$c_id) {
        echo '<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Complete your registration first to access reports.</div>';
        return;
    }

    // Reports Dashboard UI
    ?>
    <div class="content-header mb-4">
        <div class="d-flex justify-content-between align-items-end">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2" style="font-size: 13px; background: transparent; padding: 0; display: flex; list-style: none; gap: 8px; align-items: center;">
                        <li class="breadcrumb-item"><a href="dashboard.php" style="text-decoration: none; color: var(--primary); font-weight: 500;"><i class="fas fa-home" style="font-size: 12px;"></i> Home</a></li>
                        <li class="breadcrumb-item" style="color: var(--gray-400); font-size: 11px;">/</li>
                        <li class="breadcrumb-item active" style="color: var(--gray-600); font-weight: 500;">Reports</li>
                    </ol>
                </nav>
                <h2 class="page-title"><i class="fas fa-chart-bar text-primary me-2"></i> Reports</h2>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline btn-sm" onclick="location.reload()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="card glass mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <div class="row align-items-end g-3">
                <div class="col-md-4">
                    <label class="form-label" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gray-500);">Select Work Order</label>
                    <select class="form-control form-control-sm" id="report_wo">
                        <option value="">All Work Orders</option>
                        <option value="WO/2024/001">WO/2024/001 - Civil Works</option>
                        <option value="WO/2024/045">WO/2024/045 - Electrical Maintenance</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; color: var(--gray-500);">Date Range</label>
                    <div class="input-group input-group-sm">
                        <input type="date" class="form-control" id="date_from" value="<?= date('Y-m-01') ?>">
                        <span class="input-group-text bg-light border-start-0 border-end-0">to</span>
                        <input type="date" class="form-control" id="date_to" value="<?= date('Y-m-d') ?>">
                    </div>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button class="btn btn-primary btn-sm px-4" onclick="applyFilters()">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <button class="btn btn-outline btn-sm" onclick="resetFilters()">
                        Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Statutory Compliance Reports -->
        <div class="col-md-6">
            <div class="report-section-card h-100">
                <div class="section-header statutory">
                    <div class="d-flex align-items-center gap-3">
                        <div class="section-icon"><i class="fas fa-gavel"></i></div>
                        <div>
                            <h5 class="m-0 fw-bold">Statutory Compliance</h5>
                            <!-- <small class="opacity-75">Mandatory legal registers (Form XVI, XVII, etc.)</small> -->
                        </div>
                    </div>
                </div>
                <div class="section-body p-0">
                    <div class="report-list">
                        <div class="report-item" onclick="generateReport('Muster Roll', 'XVI')">
                            <div class="report-info">
                                <div class="report-name">Muster Roll (Form XVI)</div>
                                <div class="report-desc">Official attendance register for all contractual workmen.</div>
                            </div>
                            <div class="report-actions">
                                <button class="report-btn pdf" title="Download PDF"><i class="fas fa-file-pdf"></i></button>
                                <button class="report-btn excel" title="Download Excel"><i class="fas fa-file-excel"></i></button>
                            </div>
                        </div>
                        <div class="report-item" onclick="generateReport('PF/ESI Compliance', 'COMP')">
                            <div class="report-info">
                                <div class="report-name">PF & ESI Compliance Report</div>
                                <div class="report-desc">Detailed statement of social security contributions.</div>
                            </div>
                            <div class="report-actions">
                                <button class="report-btn pdf" title="Download PDF"><i class="fas fa-file-pdf"></i></button>
                                <button class="report-btn excel" title="Download Excel"><i class="fas fa-file-excel"></i></button>
                            </div>
                        </div>
                        <div class="report-item" onclick="generateReport('Wage Register', 'XVII')">
                            <div class="report-info">
                                <div class="report-name">Wage Register (Form XVII)</div>
                                <div class="report-desc">Salary disbursement details and deduction summary.</div>
                            </div>
                            <div class="report-actions">
                                <button class="report-btn pdf" title="Download PDF"><i class="fas fa-file-pdf"></i></button>
                                <button class="report-btn excel" title="Download Excel"><i class="fas fa-file-excel"></i></button>
                            </div>
                        </div>
                        <div class="report-item" onclick="generateReport('Bonus Register', 'C')">
                            <div class="report-info">
                                <div class="report-name">Bonus Register (Form C)</div>
                                <div class="report-desc">Annual bonus payment records for all eligible workmen.</div>
                            </div>
                            <div class="report-actions">
                                <button class="report-btn pdf" title="Download PDF"><i class="fas fa-file-pdf"></i></button>
                                <button class="report-btn excel" title="Download Excel"><i class="fas fa-file-excel"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Operational & Workforce Reports -->
        <div class="col-md-6">
            <div class="report-section-card h-100">
                <div class="section-header operational">
                    <div class="d-flex align-items-center gap-3">
                        <div class="section-icon"><i class="fas fa-tasks"></i></div>
                        <div>
                            <h5 class="m-0 fw-bold">Operational Insights</h5>
                            <!-- <small class="opacity-75">Workforce metrics, training & gate pass reports</small> -->
                        </div>
                    </div>
                </div>
                <div class="section-body p-0">
                    <div class="report-list">
                        <div class="report-item" onclick="generateReport('Active Workers', 'OPS')">
                            <div class="report-info">
                                <div class="report-name">Active Workforce Roster</div>
                                <div class="report-desc">Current snapshot of all enrolled and active workmen.</div>
                            </div>
                            <div class="report-actions">
                                <button class="report-btn excel" title="Download Excel"><i class="fas fa-file-excel"></i></button>
                            </div>
                        </div>
                        <div class="report-item" onclick="generateReport('Attendance Log', 'LOG')">
                            <div class="report-info">
                                <div class="report-name">Daily In/Out Punches</div>
                                <div class="report-desc">Comprehensive log of biometric punch data per worker.</div>
                            </div>
                            <div class="report-actions">
                                <button class="report-btn excel" title="Download Excel"><i class="fas fa-file-excel"></i></button>
                            </div>
                        </div>
                        <div class="report-item" onclick="generateReport('Safety Training', 'SAFE')">
                            <div class="report-info">
                                <div class="report-name">Safety Training Status</div>
                                <div class="report-desc">Pass/Fail metrics for mandatory safety inductions.</div>
                            </div>
                            <div class="report-actions">
                                <button class="report-btn pdf" title="Download PDF"><i class="fas fa-file-pdf"></i></button>
                            </div>
                        </div>
                        <div class="report-item" onclick="generateReport('Pass Expiry', 'EXP')">
                            <div class="report-info">
                                <div class="report-name">Gate Pass Expiry Forecast</div>
                                <div class="report-desc">List of passes expiring in the next 15-30 days.</div>
                            </div>
                            <div class="report-actions">
                                <button class="report-btn pdf" title="Download PDF"><i class="fas fa-file-pdf"></i></button>
                                <button class="report-btn excel" title="Download Excel"><i class="fas fa-file-excel"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Exporting Overlay -->
    <div id="export-overlay" class="export-overlay" style="display:none;">
        <div class="export-modal glass">
            <div class="spinner-border text-primary mb-3" role="status"></div>
            <h5 class="fw-bold mb-1">Generating Report...</h5>
            <p class="text-muted small mb-0" id="export-status">Preparing your document for download</p>
        </div>
    </div>

    <style>
        .report-section-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--gray-200);
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        
        .section-header {
            padding: 24px;
            color: white;
        }
        .section-header.statutory { background: linear-gradient(135deg, #065f46 0%, #10b981 100%); }
        .section-header.operational { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); }
        
        .section-icon {
            width: 48px;
            height: 48px;
            background: rgba(255,255,255,0.2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .report-list {
            padding: 10px;
        }
        
        .report-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            margin-bottom: 5px;
        }
        
        .report-item:hover {
            background: #f8fafc;
            border-color: var(--gray-200);
            transform: translateX(5px);
        }
        
        .report-name {
            font-weight: 600;
            color: var(--gray-800);
            font-size: 14px;
        }
        
        .report-desc {
            font-size: 12px;
            color: var(--gray-500);
            margin-top: 2px;
        }
        
        .report-actions {
            display: flex;
            gap: 8px;
            opacity: 0.6;
            transition: opacity 0.2s;
        }
        
        .report-item:hover .report-actions { opacity: 1; }
        
        .report-btn {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .report-btn.pdf { background: #fee2e2; color: #b91c1c; }
        .report-btn.excel { background: #dcfce7; color: #15803d; }
        .report-btn:hover { transform: scale(1.1); }

        .export-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .export-modal {
            background: white;
            padding: 40px;
            border-radius: 24px;
            text-align: center;
            width: 320px;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }

        .breadcrumb-item + .breadcrumb-item::before { display: none; }
    </style>

    <script>
    function generateReport(reportName, code) {
        const wo = document.getElementById('report_wo').value;
        const from = document.getElementById('date_from').value;
        const to = document.getElementById('date_to').value;
        
        const overlay = document.getElementById('export-overlay');
        const status = document.getElementById('export-status');
        
        overlay.style.display = 'flex';
        status.innerText = `Fetching data for ${reportName}...`;
        
        setTimeout(() => {
            status.innerText = `Compiling ${code} records...`;
            setTimeout(() => {
                status.innerText = `Generating file...`;
                setTimeout(() => {
                    overlay.style.display = 'none';
                    alert(`✅ SUCCESS\n\n${reportName} generated successfully.\nPeriod: ${from} to ${to}\nWork Order: ${wo || 'All'}\n\n(This is a visual demo. Production API will trigger a direct file download.)`);
                }, 1000);
            }, 1000);
        }, 800);
    }

    function applyFilters() {
        const btn = event.currentTarget;
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Filtering...';
        btn.disabled = true;
        
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.disabled = false;
        }, 600);
    }

    function resetFilters() {
        document.getElementById('report_wo').value = '';
        document.getElementById('date_from').value = '<?= date('Y-m-01') ?>';
        document.getElementById('date_to').value = '<?= date('Y-m-d') ?>';
    }
    </script>
    <?php
}

renderLayout('Reports & Analytics', 'renderContent', $role, $name);
?>

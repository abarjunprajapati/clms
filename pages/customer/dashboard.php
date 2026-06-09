<?php
require_once '../../include/config.php';
require_once '../../include/auth_middleware.php';
require_once '../../include/layout.php';

// Ensure user is customer
$role = $_SESSION['role'] ?? 'customer';
require_role(['customer']);

$name = $_SESSION['name'] ?? 'Customer';
$customer_code = $_SESSION['customer_code'] ?? '';
$customer_name = $_SESSION['customer_name'] ?? '';
$customer_email = '';
$customer_mobile = '';

if (!empty($customer_code)) {
    $cust = db_single($conn, "SELECT customer_name, EMAIL_ADDRESS, Customer_MOB1 FROM sap_customer_master WHERE customer_code = ?", 's', [$customer_code]);
    if ($cust) {
        if (empty($customer_name)) {
            $customer_name = $cust['customer_name'];
            $_SESSION['customer_name'] = $customer_name;
        }
        $customer_email = $cust['EMAIL_ADDRESS'] ?: ($cust['email'] ?? '');
        $customer_mobile = $cust['Customer_MOB1'] ?: ($cust['mobile'] ?? '');
    }
}
if (empty($customer_name)) {
    $customer_name = 'Your Company';
}

function renderContent() {
    global $conn, $customer_code, $name, $customer_name, $customer_email, $customer_mobile;

    $annexure3aHistory = [];
    if (!empty($customer_code)) {
        $annexure3aHistory = db_fetch_all($conn, "
            SELECT annexure3a_id, vendor_code, work_order_no, status, reason, updated_at
            FROM contractor_annexure3a_history
            WHERE customer_code = ?
            ORDER BY updated_at DESC
            LIMIT 10
        ", 's', [$customer_code]);
    }
?>
<style>
    :root {
        --customer-primary: #1a365d;
        --customer-secondary: #2b6cb0;
        --customer-accent: #3182ce;
        --customer-bg: #f7fafc;
        --glass-bg: rgba(255, 255, 255, 0.95);
        --glass-border: rgba(226, 232, 240, 1);
    }

    .dashboard-container { padding: 2rem; background: var(--customer-bg); min-height: 100vh; }
    
    .welcome-banner {
        background: linear-gradient(135deg, var(--customer-primary) 0%, var(--customer-secondary) 100%);
        padding: 2.5rem;
        border-radius: 24px;
        color: white;
        margin-bottom: 2.5rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        position: relative;
        overflow: hidden;
    }
    
    .welcome-banner::after {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 400px;
        height: 400px;
        background: rgba(255, 255, 255, 0.05);
        border-radius: 50%;
    }

    .welcome-banner h1 { font-size: 2.2rem; font-weight: 800; margin-bottom: 0.5rem; }
    .welcome-banner p { opacity: 0.9; font-size: 1.1rem; font-weight: 500; }

    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.5rem; margin-top: -3.5rem; padding: 0 1rem; margin-bottom: 2.5rem; }
    
    .stat-card {
        background: var(--glass-bg);
        backdrop-filter: blur(10px);
        padding: 1.5rem;
        border-radius: 20px;
        border: 1px solid var(--glass-border);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        gap: 1.25rem;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover { transform: translateY(-5px); }
    
    .stat-icon {
        width: 54px;
        height: 54px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .stat-info h3 { font-size: 1.75rem; font-weight: 800; margin: 0; color: #1a202c; line-height: 1.2; }
    .stat-info p { margin: 0; color: #718096; font-size: 0.875rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.025em; }

    .chart-row { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
    .chart-grid-secondary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; margin-bottom: 2rem; }
    
    .card {
        background: white;
        border-radius: 24px;
        border: 1px solid #edf2f7;
        padding: 1.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02);
    }
    
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f7fafc;
    }
    
    .card-title { font-size: 1.1rem; font-weight: 700; color: #2d3748; display: flex; align-items: center; gap: 0.75rem; }
    
    .table { width: 100%; border-collapse: collapse; }
    .table th { text-align: left; padding: 12px 16px; font-size: 0.75rem; font-weight: 700; color: #a0aec0; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; }
    .table td { padding: 16px; border-bottom: 1px solid #f7fafc; font-size: 0.9rem; color: #4a5568; }
    
    .badge {
        padding: 4px 10px;
        border-radius: 8px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .badge-success { background: #c6f6d5; color: #22543d; }
    .badge-warning { background: #fef3c7; color: #92400e; }
    .badge-danger { background: #fed7d7; color: #822727; }
    .badge-info { background: #bee3f8; color: #2a4365; }
    .badge-dark { background: #2d3748; color: white; }
    .badge-secondary { background: #edf2f7; color: #4a5568; }
    .contractor-identity { display:flex; flex-direction:column; gap:6px; min-width:190px; }
    .contractor-identity .contractor-name { font-weight:800; color:var(--customer-secondary); line-height:1.25; }
    .contractor-meta-row { display:flex; flex-wrap:wrap; gap:6px; align-items:center; }
    .contractor-meta-chip {
        display:inline-flex;
        align-items:center;
        gap:4px;
        padding:3px 8px;
        border-radius:999px;
        background:#edf2f7;
        color:#2d3748;
        border:1px solid #cbd5e1;
        font-size:11px;
        font-weight:800;
        white-space:nowrap;
    }
    .contractor-meta-chip.code { background:#ebf8ff; color:#1a365d; border-color:#90cdf4; }
    .contractor-flow { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:12px; }
    .flow-step { min-height:116px; display:flex; align-items:flex-start; gap:12px; padding:14px; border:1px solid #e2e8f0; border-radius:8px; background:#fff; text-decoration:none; color:inherit; box-shadow:0 4px 6px -1px rgba(0,0,0,.04); transition:.2s; }
    .flow-step:hover { transform:translateY(-2px); box-shadow:0 10px 15px -3px rgba(0,0,0,.08); color:inherit; }
    .flow-index { width:26px; height:26px; border-radius:50%; background:#edf2f7; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:12px; color:#4a5568; flex-shrink:0; }
    .flow-icon { width:38px; height:38px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:rgba(26,54,93,.1); color:var(--customer-primary); flex-shrink:0; }
    .flow-body { flex:1; min-width:0; }
    .flow-title { font-weight:800; color:#1a202c; line-height:1.25; }
    .flow-detail { font-size:12px; color:#718096; line-height:1.35; margin-top:4px; }
    .flow-step.active .flow-index { background:#f59e0b; color:#fff; }

    .worker-list-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem;
        border-radius: 16px;
        transition: background 0.2s;
        margin-bottom: 0.5rem;
    }
    .worker-list-item:hover { background: #f7fafc; }
    .worker-avatar {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        background: #ebf4ff;
        color: #3182ce;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.9rem;
    }
    .history-table-wrap { width: 100%; overflow-x: auto; }
    .history-table { min-width: 820px; }
    .history-table td { vertical-align: top; }
    .history-annexure { font-weight: 800; color: #1a202c; }
    .history-remarks { white-space: pre-wrap; min-width: 260px; color: #2d3748; line-height: 1.45; }
</style>

<div class="dashboard-container">
    <div class="welcome-banner">
        <h1>Welcome, <?= htmlspecialchars($name) ?></h1>
        <p>Your monitoring hub for <strong><?= htmlspecialchars($customer_name) ?></strong> (Customer Code: <strong><?= htmlspecialchars($customer_code) ?></strong>)</p>
        <div style="margin-top: 15px; display: flex; flex-wrap: wrap; gap: 20px; font-size: 0.9rem; opacity: 0.95;">
            <?php if ($customer_email): ?>
                <span><i class="fas fa-envelope me-1"></i> <?= htmlspecialchars($customer_email) ?></span>
            <?php endif; ?>
            <?php if ($customer_mobile): ?>
                <span><i class="fas fa-phone me-1"></i> <?= htmlspecialchars($customer_mobile) ?></span>
            <?php endif; ?>
            <!-- <span class="badge" style="background: rgba(255,255,255,0.25); color: #fff; font-weight:700;"><i class="fas fa-database me-1"></i> SAP Status: Sync OK</span> -->
        </div>
    </div>

    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(49, 130, 206, 0.1); color: #3182ce;">
                <i class="fas fa-handshake"></i>
            </div>
            <div class="stat-info">
                <h3 id="stat-contractors">--</h3>
                <p>Contractors</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(56, 161, 105, 0.1); color: #38a169;">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3 id="stat-workers">--</h3>
                <p>Total Workforce</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(221, 107, 32, 0.1); color: #dd6b20;">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-info">
                <h3 id="stat-attendance">--</h3>
                <p>Present Today</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: rgba(229, 62, 62, 0.1); color: #e53e3e;">
                <i class="fas fa-id-card"></i>
            </div>
            <div class="stat-info">
                <h3 id="stat-passes">--</h3>
                <p>Active Passes</p>
            </div>
        </div>
        </div>

        <!-- Quick Module Links -->
        <div class="contractor-flow" style="margin:18px 0;">
            <a class="flow-step active" href="annexure-3a.php">
                <div class="flow-index">1</div>
                <div class="flow-icon"><i class="fas fa-file-signature"></i></div>
                <div class="flow-body">
                    <div class="flow-title">Customer Registration</div>
                    <div class="flow-detail">Open registration and statutory details</div>
                </div>
                <span class="badge badge-success">Open</span>
            </a>
            <a class="flow-step active" href="annexure-3a.php?resubmit=1">
                <div class="flow-index">R</div>
                <div class="flow-icon"><i class="fas fa-rotate"></i></div>
                <div class="flow-body">
                    <div class="flow-title">Resubmit Customer Registration</div>
                    <div class="flow-detail">Update EC Policy and Labour License only</div>
                </div>
                <span class="badge badge-warning">Resubmit</span>
            </a>
            <a class="flow-step" href="../contractor/enrolment-4a.php?type=workmen">
                <div class="flow-index">2</div>
                <div class="flow-icon"><i class="fas fa-users"></i></div>
                <div class="flow-body">
                    <div class="flow-title">Worker Management</div>
                    <div class="flow-detail">View workforce records</div>
                </div>
                <span class="badge badge-secondary">Open</span>
            </a>
            <a class="flow-step" href="../contractor/training_request.php">
                <div class="flow-index">3</div>
                <div class="flow-icon"><i class="fas fa-graduation-cap"></i></div>
                <div class="flow-body">
                    <div class="flow-title">Safety Training</div>
                    <div class="flow-detail">Training status and qualification</div>
                </div>
                <span class="badge badge-info">Open</span>
            </a>
            <a class="flow-step" href="../contractor/gatepass-6a.php">
                <div class="flow-index">4</div>
                <div class="flow-icon"><i class="fas fa-id-badge"></i></div>
                <div class="flow-body">
                    <div class="flow-title">Gate Pass</div>
                    <div class="flow-detail">Pass issue and validity status</div>
                </div>
                <span class="badge badge-info">Open</span>
            </a>
            <a class="flow-step" href="../contractor/pass_status.php">
                <div class="flow-index">5</div>
                <div class="flow-icon"><i class="fas fa-id-card"></i></div>
                <div class="flow-body">
                    <div class="flow-title">ACC Card</div>
                    <div class="flow-detail">Permanent card status</div>
                </div>
                <span class="badge badge-info">Open</span>
            </a>
            <a class="flow-step" href="../contractor/attendance.php">
                <div class="flow-index">6</div>
                <div class="flow-icon"><i class="fas fa-calendar-check"></i></div>
                <div class="flow-body">
                    <div class="flow-title">Attendance</div>
                    <div class="flow-detail">Daily attendance monitoring</div>
                </div>
                <span class="badge badge-info">Open</span>
            </a>
            <a class="flow-step" href="../contractor/compliance.php">
                <div class="flow-index">7</div>
                <div class="flow-icon"><i class="fas fa-shield-check"></i></div>
                <div class="flow-body">
                    <div class="flow-title">Compliance Monitor</div>
                    <div class="flow-detail">Statutory compliance status</div>
                </div>
                <span class="badge badge-info">Open</span>
            </a>
            <a class="flow-step" href="../contractor/documents.php">
                <div class="flow-index">8</div>
                <div class="flow-icon"><i class="fas fa-folder-open"></i></div>
                <div class="flow-body">
                    <div class="flow-title">Documents</div>
                    <div class="flow-detail">Uploaded document library</div>
                </div>
                <span class="badge badge-info">Open</span>
            </a>
            <a class="flow-step" href="../contractor/reports.php">
                <div class="flow-index">9</div>
                <div class="flow-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="flow-body">
                    <div class="flow-title">Reports</div>
                    <div class="flow-detail">Muster, attendance and compliance reports</div>
                </div>
                <span class="badge badge-info">Run</span>
            </a>
        </div>

        <div class="card" style="margin-bottom: 2rem;">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-clipboard-check"></i> Welfare Approval History</div>
                <div style="display:flex;align-items:center;gap:12px;">
                    <div class="text-muted small">Latest 10 Customer Registration actions</div>
                    <a href="welfare-actions.php" class="btn btn-sm btn-primary">View All</a>
                </div>
            </div>
            <div class="history-table-wrap">
                <table class="table history-table">
                    <thead>
                        <tr>
                            <th>Annexure</th>
                            <th>Vendor Code</th>
                            <th>Work Order</th>
                            <th>Status</th>
                            <th>Reason / Remarks</th>
                            <th>Date & Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($annexure3aHistory)): ?>
                            <tr><td colspan="6" class="text-center py-4 text-muted">No Customer Registration approval history found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($annexure3aHistory as $history): ?>
                                <?php
                                    $status = strtolower((string)($history['status'] ?? 'submitted'));
                                    $badgeClass = 'badge-info';
                                    if ($status === 'approved') $badgeClass = 'badge-success';
                                    elseif ($status === 'rejected') $badgeClass = 'badge-danger';
                                    elseif ($status === 'resubmitted') $badgeClass = 'badge-warning';
                                    elseif ($status === 'blocked') $badgeClass = 'badge-dark';
                                ?>
                                <tr>
                                    <td>
                                        <div class="history-annexure">Customer Registration</div>
                                        <?php if (!empty($history['annexure3a_id'])): ?>
                                            <div class="text-muted" style="font-size:11px;">Ref: <?= htmlspecialchars((string)$history['annexure3a_id']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= htmlspecialchars($history['vendor_code'] ?: '-') ?></code></td>
                                    <td><code><?= htmlspecialchars($history['work_order_no'] ?: '-') ?></code></td>
                                    <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars(strtoupper($status)) ?></span></td>
                                    <td class="history-remarks"><?= htmlspecialchars($history['reason'] ?: 'No remarks provided yet.') ?></td>
                                    <td><?= !empty($history['updated_at']) ? htmlspecialchars(date('d M Y h:i A', strtotime($history['updated_at']))) : '-' ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="chart-row">
        <!-- Attendance Chart -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-chart-line"></i> Daily Attendance Trend</div>
                <div class="text-muted small">Last 14 Days</div>
            </div>
            <canvas id="attendanceChart" height="100"></canvas>
        </div>

        <!-- Distribution Chart -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-chart-pie"></i> Workforce Distribution</div>
            </div>
            <canvas id="distributionChart"></canvas>
        </div>
    </div>

    <!-- Secondary Charts Grid -->
    <div class="chart-grid-secondary">
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-id-card-clip"></i> Pass Status Summary</div>
            </div>
            <canvas id="passStatsChart"></canvas>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-shield-check"></i> Safety Qualification</div>
            </div>
            <canvas id="safetyStatsChart"></canvas>
        </div>
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-building-user"></i> Dept-wise Manpower</div>
            </div>
            <canvas id="deptDistChart"></canvas>
        </div>
    </div>

    <div class="dashboard-grid" style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: 1.5rem;">
        <!-- Mapped Contractors -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-building"></i> Assigned Contractors & Work Orders</div>
                <div style="display:flex;gap:8px;align-items:center;">
                    <a href="annexure-3a.php" class="btn btn-sm btn-primary">Open Customer Registration</a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table" id="contractorsTable">
                    <thead>
                        <tr>
                            <th>Contractor</th>
                            <th>Work Order</th>
                            <th>Department</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="contractors-list">
                        <tr><td colspan="4" class="text-center py-4 text-muted">Loading contractors...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Workers -->
        <div class="card">
            <div class="card-header">
                <div class="card-title"><i class="fas fa-user-clock"></i> Recent Worker Onboarding</div>
                <a href="workers.php" class="btn btn-sm btn-link">View All</a>
            </div>
            <div id="workers-list">
                <p class="text-center py-4 text-muted">Loading workforce data...</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    loadStats();
    loadCharts();
    loadContractors();
    loadWorkers();
});

async function loadStats() {
    try {
        const res = await fetch('../../api/customer/stats.php');
        const r = await res.json();
        if (r.success) {
            document.getElementById('stat-contractors').innerText = r.data.contractors;
            document.getElementById('stat-workers').innerText = r.data.workers;
            document.getElementById('stat-attendance').innerText = r.data.present_today;
            document.getElementById('stat-passes').innerText = r.data.active_passes;
        }
    } catch (e) { console.error(e); }
}

function createDoughnut(ctx, labels, data, colors) {
    return new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{ data: data, backgroundColor: colors, borderWidth: 0 }]
        },
        options: {
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, font: { size: 10, weight: '600' } } }
            },
            cutout: '70%'
        }
    });
}

async function loadCharts() {
    try {
        const res = await fetch('../../api/customer/charts.php');
        const r = await res.json();
        if (!r.success) return;

        // Attendance Trend Chart
        const ctxAttendance = document.getElementById('attendanceChart').getContext('2d');
        new Chart(ctxAttendance, {
            type: 'line',
            data: {
                labels: r.data.attendance_trend.map(d => d.date),
                datasets: [{
                    label: 'Workers Present',
                    data: r.data.attendance_trend.map(d => d.count),
                    borderColor: '#3182ce',
                    backgroundColor: 'rgba(49, 130, 206, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#3182ce'
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Distribution Chart
        createDoughnut(
            document.getElementById('distributionChart').getContext('2d'),
            r.data.distribution.map(d => d.label),
            r.data.distribution.map(d => d.value),
            ['#3182ce', '#38a169', '#dd6b20', '#e53e3e', '#805ad5', '#319795']
        );

        // Pass Status Chart
        createDoughnut(
            document.getElementById('passStatsChart').getContext('2d'),
            r.data.pass_stats.map(d => d.label.toUpperCase()),
            r.data.pass_stats.map(d => d.value),
            ['#38a169', '#e53e3e', '#dd6b20', '#3182ce']
        );

        // Safety Status Chart
        createDoughnut(
            document.getElementById('safetyStatsChart').getContext('2d'),
            r.data.safety_stats.map(d => d.label),
            r.data.safety_stats.map(d => d.value),
            ['#38a169', '#e53e3e', '#cbd5e0']
        );

        // Dept Distribution Chart
        createDoughnut(
            document.getElementById('deptDistChart').getContext('2d'),
            r.data.dept_distribution.map(d => d.label),
            r.data.dept_distribution.map(d => d.value),
            ['#805ad5', '#319795', '#3182ce', '#38a169']
        );

    } catch (e) { console.error(e); }
}

async function loadContractors() {
    try {
        const res = await fetch('../../api/customer/contractors.php');
        const r = await res.json();
        const list = document.getElementById('contractors-list');
        list.innerHTML = '';
        
        if (r.success && r.data.length > 0) {
            r.data.slice(0, 5).forEach(c => {
                const status = ((c.annexure3a_status && c.annexure3a_status !== 'not_submitted') ? c.annexure3a_status : (c.registration_status || 'pending')).toLowerCase();
                let badgeClass = 'badge-warning';
                if (status === 'approved') badgeClass = 'badge-success';
                else if (status === 'rejected') badgeClass = 'badge-danger';
                else if (status === 'resubmitted') badgeClass = 'badge-warning';
                else if (status === 'blocked') badgeClass = 'badge-dark';
                else if (status === 'inactive') badgeClass = 'badge-secondary';
                else if (status === 'expired') badgeClass = 'badge-info';
                
                list.innerHTML += `
                    <tr>
                        <td>
                            <a href="annexure-3a.php?wo=${encodeURIComponent(c.work_order_no)}" style="text-decoration:none">
                                <div class="contractor-identity">
                                    <div class="contractor-name">${c.vendor_name || c.contractor_name || 'Contractor'}</div>
                                    <div class="contractor-meta-row">
                                        <span class="contractor-meta-chip"><i class="fas fa-user-tag"></i> Role: Contractor</span>
                                        <span class="contractor-meta-chip code"><i class="fas fa-barcode"></i> Vendor Code: ${c.vendor_code}</span>
                                    </div>
                                </div>
                            </a>
                        </td>
                        <td>
                            <div style="font-weight:600">${c.work_order_no}</div>
                            <div class="text-muted" style="font-size:11px">${c.project_name}</div>
                        </td>
                        <td><span class="badge badge-primary">${c.department}</span></td>
                        <td>
                            <span class="badge ${badgeClass}">${status}</span>
                            <a href="annexure-3a.php?wo=${encodeURIComponent(c.work_order_no)}" class="btn btn-sm btn-outline ms-2" title="Open Customer Registration">
                                <i class="fas fa-file-signature"></i>
                            </a>
                        </td>
                    </tr>
                `;
            });
        } else {
            list.innerHTML = '<tr><td colspan="4" class="text-center py-4">No contractors found.</td></tr>';
        }
    } catch (e) { console.error(e); }
}

async function loadWorkers() {
    try {
        const res = await fetch('../../api/customer/workers.php');
        const r = await res.json();
        const list = document.getElementById('workers-list');
        list.innerHTML = '';
        
        if (r.success && r.data.length > 0) {
            r.data.slice(0, 5).forEach(w => {
                const initial = (w.name || 'W').split(' ').map(n => n[0]).join('').substr(0, 2).toUpperCase();
                list.innerHTML += `
                    <div class="worker-list-item">
                        <div class="worker-avatar">${initial}</div>
                        <div style="flex:1">
                            <div style="font-weight:700; font-size:0.95rem; color:#2d3748">${w.name}</div>
                            <div style="font-size:0.75rem; color:#718096; font-weight:500">${w.contractor_name}</div>
                        </div>
                        <span class="badge badge-success" style="font-size:0.65rem">${w.status || 'Active'}</span>
                    </div>
                `;
            });
        } else {
            list.innerHTML = '<p class="text-center py-4 text-muted">No recent workers found.</p>';
        }
    } catch (e) { console.error(e); }
}
</script>
<?php
}

renderLayout("Customer Monitoring Dashboard", 'renderContent', $_SESSION['role'], $name);
?>

<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['execution_officer', 'execution', 'super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/execution_context.php';
include __DIR__ . '/../../include/layout.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Execution Officer';
$userId = $_SESSION['user_id'];

// Get or create execution officer context for this login
$officerId = clms_execution_get_officer_id($conn, $userId);

function renderContent() {
    global $conn, $officerId;

    ?>
    <div class="content-header">
        <div>
            <h2 class="page-title"><i class="fas fa-file-invoice" style="color:#6366f1;margin-right:8px"></i>Monitoring Reports</h2>
            <!-- <p class="page-subtitle">Generate and export daily activity logs, attendance reports, and observation summaries.</p> -->
        </div>
    </div>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
        <!-- Daily Deployment Report -->
        <div class="card glass report-card">
            <div class="card-body">
                <div class="report-icon"><i class="fas fa-users-viewfinder"></i></div>
                <h4 class="report-title">Deployment Summary</h4>
                <p class="report-desc">Consolidated list of workers deployed across all assigned contractors and projects.</p>
                <div class="report-actions">
                    <button class="btn btn-sm btn-primary" onclick="generateReport('deployment')"><i class="fas fa-download"></i> Export PDF</button>
                    <button class="btn btn-sm btn-outline" onclick="generateReport('deployment', 'excel')"><i class="fas fa-file-excel"></i> Export CSV</button>
                </div>
            </div>
        </div>

        <!-- Attendance Reconciliation -->
        <div class="card glass report-card">
            <div class="card-body">
                <div class="report-icon" style="background:rgba(124,58,237,0.1); color:#7c3aed;"><i class="fas fa-calendar-check"></i></div>
                <h4 class="report-title">Attendance Reconciliation</h4>
                <p class="report-desc">Mismatches between deployed manpower and biometric attendance records.</p>
                <div class="report-actions">
                    <button class="btn btn-sm btn-primary" onclick="generateReport('attendance')"><i class="fas fa-download"></i> Export PDF</button>
                    <button class="btn btn-sm btn-outline" onclick="generateReport('attendance', 'excel')"><i class="fas fa-file-excel"></i> Export CSV</button>
                </div>
            </div>
        </div>

        <!-- Observation Log -->
        <div class="card glass report-card">
            <div class="card-body">
                <div class="report-icon" style="background:rgba(245,158,11,0.1); color:#f59e0b;"><i class="fas fa-edit"></i></div>
                <h4 class="report-title">Observation Summary</h4>
                <p class="report-desc">Historical log of all safety and performance observations recorded by you.</p>
                <div class="report-actions">
                    <button class="btn btn-sm btn-primary" onclick="generateReport('observation')"><i class="fas fa-download"></i> Export PDF</button>
                    <button class="btn btn-sm btn-outline" onclick="generateReport('observation', 'excel')"><i class="fas fa-file-excel"></i> Export CSV</button>
                </div>
            </div>
        </div>

        <!-- Escalation Status -->
        <div class="card glass report-card">
            <div class="card-body">
                <div class="report-icon" style="background:rgba(239,68,68,0.1); color:#ef4444;"><i class="fas fa-bullhorn"></i></div>
                <h4 class="report-title">Escalation Audit</h4>
                <p class="report-desc">Status of recommendations forwarded to Welfare and Safety departments.</p>
                <div class="report-actions">
                    <button class="btn btn-sm btn-primary" onclick="generateReport('escalation')"><i class="fas fa-download"></i> Export PDF</button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .report-card { transition: all 0.2s; border: 1px solid transparent; }
    .report-card:hover { border-color: #6366f1; transform: translateY(-5px); }
    .report-icon { width: 50px; height: 50px; border-radius: 12px; background: rgba(99,102,241,0.1); color: #6366f1; display: flex; align-items: center; justify-content: center; font-size: 24px; margin-bottom: 16px; }
    .report-title { margin: 0 0 8px 0; font-size: 16px; font-weight: 700; color: #1e293b; }
    .report-desc { font-size: 13px; color: #64748b; margin-bottom: 20px; line-height: 1.5; height: 40px; overflow: hidden; }
    .report-actions { display: flex; gap: 10px; }
    </style>

    <script>
    function generateReport(type, format = 'csv') {
        window.location.href = `../../api/execution/export_report.php?type=${type}&format=${format}`;
    }
    </script>
    <?php
}

renderLayout("Monitoring Reports", 'renderContent', $role, $name);
?>


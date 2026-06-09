<?php
require_once '../../include/config.php';
require_once '../../include/auth_middleware.php';
require_once '../../include/layout.php';

require_role(['customer']);

$name = $_SESSION['name'] ?? 'Customer';
$customer_code = $_SESSION['customer_code'] ?? '';

function renderContent() {
?>
<div class="page-header">
    <div class="header-content">
        <h1><i class="fas fa-file-chart-column" style="color:#6366f1"></i> Analytical Reports</h1>
        <!-- <p>Access and download detailed reports for workforce monitoring and compliance auditing.</p> -->
    </div>
</div>

<div class="grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
    <!-- Report Cards -->
    <div class="card glass">
        <div class="card-body">
            <h3><i class="fas fa-clipboard-list text-primary"></i> Muster Roll</h3>
            <p class="text-muted small">Daily attendance records for all workers under your contractors.</p>
            <a href="../reports/muster_roll.php" class="btn btn-sm btn-outline mt-3">Generate Report</a>
        </div>
    </div>
    
    <div class="card glass">
        <div class="card-body">
            <h3><i class="fas fa-user-shield text-success"></i> Safety Compliance Report</h3>
            <p class="text-muted small">List of safety-qualified vs non-qualified workers onsite.</p>
            <button class="btn btn-sm btn-outline mt-3" onclick="alert('Coming Soon')">Download PDF</button>
        </div>
    </div>

    <div class="card glass">
        <div class="card-body">
            <h3><i class="fas fa-building-circle-check text-info"></i> Contractor Deployment</h3>
            <p class="text-muted small">Work order utilization and contractor performance summary.</p>
            <button class="btn btn-sm btn-outline mt-3" onclick="alert('Coming Soon')">View Report</button>
        </div>
    </div>

    <div class="card glass">
        <div class="card-body">
            <h3><i class="fas fa-clock text-warning"></i> Late Arrival Report</h3>
            <p class="text-muted small">Identify contractors with frequent late entries and punch-out violations.</p>
            <button class="btn btn-sm btn-outline mt-3" onclick="alert('Coming Soon')">Analyze Data</button>
        </div>
    </div>
</div>
<?php
}

renderLayout("Analytical Reports", 'renderContent', $_SESSION['role'], $name);
?>

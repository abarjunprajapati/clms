<?php
require_once '../../include/config.php';
require_once '../../include/auth_middleware.php';
require_once '../../include/layout.php';

require_role(['customer']);

$name = $_SESSION['name'] ?? 'Customer';
$customer_name = $_SESSION['customer_name'] ?? 'Your Company';
$customer_code = $_SESSION['customer_code'] ?? '';

function renderContent() {
    global $customer_code, $customer_name;
?>
<div class="page-header">
    <div class="header-content">
        <h1><i class="fas fa-calendar-check" style="color:#6366f1"></i> Attendance Monitoring</h1>
        <!-- <p>Live attendance logs for workers under your mapped contractors.</p> -->
    </div>
</div>

<div class="card glass">
    <div class="card-header">
        <div class="card-title">Daily Punch Logs</div>
        <button class="btn btn-sm btn-outline" onclick="loadAttendance()">Refresh</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table data-table" id="attendance-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Worker Name</th>
                        <th>Contractor</th>
                        <th>Punch In</th>
                        <th>Punch Out</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody id="attendance-list">
                    <tr><td colspan="6" class="text-center">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function loadAttendance() {
    try {
        const res = await fetch('../../api/customer/attendance.php');
        const data = await res.json();
        const list = document.getElementById('attendance-list');
        list.innerHTML = '';
        
        if (data.success && data.data.length > 0) {
            data.data.forEach(a => {
                list.innerHTML += `
                    <tr>
                        <td><strong>${a.attendance_date}</strong></td>
                        <td>${a.worker_name}</td>
                        <td><span style="font-size:12px">${a.contractor_name}</span></td>
                        <td><span class="badge badge-success">${a.punch_in || '--:--'}</span></td>
                        <td><span class="badge badge-primary">${a.punch_out || '--:--'}</span></td>
                        <td>${a.location || 'Main Gate'}</td>
                    </tr>
                `;
            });
        } else {
            list.innerHTML = '<tr><td colspan="6" class="text-center">No attendance records found.</td></tr>';
        }
    } catch (e) { console.error(e); }
}

document.addEventListener('DOMContentLoaded', loadAttendance);
</script>
<?php
}

renderLayout("Attendance Monitoring", 'renderContent', $_SESSION['role'], $name);
?>

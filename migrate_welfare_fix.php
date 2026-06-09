<?php
/**
 * CLMS Welfare Dashboard Migration Fix
 * Ensures ALL tables and columns required by welfare admin & user dashboards exist.
 * Safe to run multiple times. Upload to production and access via browser.
 */
require_once __DIR__ . '/include/config.php';

header('Content-Type: text/html; charset=utf-8');
echo "<h2>🛠 CLMS Welfare Dashboard Schema Fix</h2>";
echo "<p>Ensuring all required tables and columns exist...</p><hr>";

$ok = 0; $skip = 0; $fail = 0;

function run($conn, $sql, $msg) {
    global $ok, $fail;
    if ($conn->query($sql)) { echo "<p style='color:green'>✅ $msg</p>"; $ok++; return true; }
    else { echo "<p style='color:red'>❌ $msg — " . htmlspecialchars($conn->error) . "</p>"; $fail++; return false; }
}

function ensure_col($conn, $table, $col, $def) {
    global $ok, $skip, $fail;
    $r = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
    if ($r && $r->num_rows > 0) { echo "<p style='color:gray'>⏭ $table.$col exists</p>"; $skip++; return; }
    if ($conn->query("ALTER TABLE `$table` ADD COLUMN `$col` $def")) {
        echo "<p style='color:green'>✅ Added $table.$col</p>"; $ok++;
    } else {
        echo "<p style='color:red'>❌ Failed $table.$col — " . htmlspecialchars($conn->error) . "</p>"; $fail++;
    }
}

// ===== 1. CORE TABLES =====
echo "<h3>1. Core Tables</h3>";

run($conn, "CREATE TABLE IF NOT EXISTS document_verifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_id INT DEFAULT 0,
    contractor_id INT DEFAULT 0,
    document_type VARCHAR(100),
    document_path VARCHAR(255),
    status VARCHAR(50) DEFAULT 'pending',
    verified_by INT DEFAULT 0,
    remarks TEXT,
    expiry_date DATE DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_dv_status (status),
    KEY idx_dv_workman (workman_id),
    KEY idx_dv_contractor (contractor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "document_verifications table");

run($conn, "CREATE TABLE IF NOT EXISTS compliance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT NOT NULL,
    type VARCHAR(50) DEFAULT NULL,
    month VARCHAR(20),
    year YEAR,
    month_year VARCHAR(7),
    worker_count INT DEFAULT 0,
    attendance_days INT DEFAULT 0,
    wage_total DECIMAL(12,2) DEFAULT 0.00,
    esi_amount DECIMAL(10,2) DEFAULT 0.00,
    pf_amount DECIMAL(10,2) DEFAULT 0.00,
    klwf_amount DECIMAL(10,2) DEFAULT 0.00,
    esi_file VARCHAR(255),
    pf_file VARCHAR(255),
    klwf_file VARCHAR(255),
    validation_status VARCHAR(30) DEFAULT 'pending',
    validation_errors TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    remarks TEXT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_compliance_contractor (contractor_id),
    KEY idx_compliance_status (status),
    KEY idx_compliance_type (type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "compliance table");

run($conn, "CREATE TABLE IF NOT EXISTS noc_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_id INT DEFAULT 0,
    old_contractor_id INT DEFAULT 0,
    new_contractor_id INT DEFAULT 0,
    reason TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    approved_by INT DEFAULT 0,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_noc_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "noc_requests table");

run($conn, "CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT 0,
    action VARCHAR(100),
    module VARCHAR(100),
    details TEXT,
    ip_address VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_audit_user (user_id),
    KEY idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "audit_logs table");

run($conn, "CREATE TABLE IF NOT EXISTS login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    identifier VARCHAR(100),
    ip_address VARCHAR(50),
    status VARCHAR(20),
    failure_reason VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_login_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4", "login_logs table");

// ===== 2. MISSING COLUMNS =====
echo "<h3>2. Column Verification</h3>";

// compliance.type
ensure_col($conn, 'compliance', 'type', "VARCHAR(50) DEFAULT NULL AFTER contractor_id");

// audit_logs columns
ensure_col($conn, 'audit_logs', 'action', "VARCHAR(100)");
ensure_col($conn, 'audit_logs', 'module', "VARCHAR(100)");
ensure_col($conn, 'audit_logs', 'user_id', "INT DEFAULT 0");
ensure_col($conn, 'audit_logs', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// document_verifications columns
ensure_col($conn, 'document_verifications', 'status', "VARCHAR(50) DEFAULT 'pending'");
ensure_col($conn, 'document_verifications', 'created_at', "TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

// noc_requests columns
ensure_col($conn, 'noc_requests', 'status', "VARCHAR(50) DEFAULT 'pending'");

// users.must_change_password
ensure_col($conn, 'users', 'must_change_password', "TINYINT(1) DEFAULT 0");

// workmen columns used by dashboards
ensure_col($conn, 'workmen', 'training_status', "VARCHAR(50) DEFAULT 'pending'");
ensure_col($conn, 'workmen', 'biometric_status', "VARCHAR(50) DEFAULT 'pending'");

// gate_passes columns
ensure_col($conn, 'gate_passes', 'pass_number', "VARCHAR(100) AFTER id");
ensure_col($conn, 'gate_passes', 'pass_type', "VARCHAR(50) DEFAULT 'temporary'");
ensure_col($conn, 'gate_passes', 'valid_to', "DATE DEFAULT NULL");

// ===== 3. STORED PROCEDURE =====
echo "<h3>3. Stored Procedure Check</h3>";
$sp = $conn->query("SHOW PROCEDURE STATUS WHERE Db='" . $conn->real_escape_string($Dbname) . "' AND Name='sp_authenticate_user'");
if ($sp && $sp->num_rows > 0) {
    echo "<p style='color:gray'>⏭ sp_authenticate_user exists</p>"; $skip++;
} else {
    $spSql = "CREATE PROCEDURE sp_authenticate_user(IN p_contractor_id VARCHAR(100))
BEGIN
    SELECT u.id, u.username, u.password, u.role, u.name, u.email, u.contractor_id, u.must_change_password,u.status
    FROM users u
    WHERE (u.username = p_contractor_id OR u.contractor_id = p_contractor_id OR u.email = p_contractor_id)
    AND u.status = 'active'
    LIMIT 1;
END";
    run($conn, $spSql, "Created sp_authenticate_user");
}

// ===== SUMMARY =====
echo "<hr><h3>Summary</h3>";
echo "<p>✅ Success: <strong>$ok</strong> | ⏭ Skipped: <strong>$skip</strong> | ❌ Failed: <strong>$fail</strong></p>";
if ($fail === 0) {
    echo "<p style='color:green;font-size:18px;font-weight:bold'>🎉 All checks passed! Dashboards should work now.</p>";
} else {
    echo "<p style='color:red;font-size:18px;font-weight:bold'>⚠ Some fixes failed. Check errors above.</p>";
}
echo "<p><a href='pages/welfare/admin_dashboard.php'>→ Welfare Admin Dashboard</a> | <a href='pages/welfare/dashboard.php'>→ Welfare User Dashboard</a></p>";
?>

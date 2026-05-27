<?php
/**
 * migrate_execution_officer.php
 * Migration script to add Execution Officer role and associated tables/columns.
 */

require_once __DIR__ . '/../include/config.php';

// Use root for migration if needed, but try config first
$conn = mysqli_connect($Servername, $Username, $Password, $Dbname);
if (!$conn) {
    $conn = mysqli_connect("localhost", "root", "", "new_clms");
}

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Starting Execution Officer Migration...\n\n";

// 1. Update users role enum
echo "Updating users.role enum...\n";
$sql = "ALTER TABLE users MODIFY COLUMN role ENUM('contractor', 'welfare_admin', 'welfare_user', 'safety_user', 'front_line_user', 'pass_user', 'super_admin', 'execution_officer') DEFAULT 'contractor'";
if ($conn->query($sql)) {
    echo "✓ users.role updated\n";
} else {
    echo "✗ Error updating users.role: " . $conn->error . "\n";
}

// Helper to add column if not exists
function addCol($conn, $table, $col, $def) {
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$col'");
    if ($res && $res->num_rows == 0) {
        if ($conn->query("ALTER TABLE `$table` ADD COLUMN `$col` $def")) {
            echo "✓ Added $col to $table\n";
        } else {
            echo "✗ Error adding $col to $table: " . $conn->error . "\n";
        }
    } else {
        echo "- $col already exists in $table\n";
    }
}

// 2. Add mapping columns
echo "\nAdding mapping columns...\n";
addCol($conn, 'workmen', 'execution_officer_id', "BIGINT NULL AFTER contractor_id");
addCol($conn, 'workmen', 'deployment_status', "ENUM('active', 'relieved') DEFAULT 'active' AFTER execution_officer_id");
addCol($conn, 'workmen', 'current_department_id', "BIGINT NULL AFTER deployment_status");
addCol($conn, 'contractors', 'execution_officer_id', "BIGINT NULL AFTER status");
addCol($conn, 'work_orders', 'execution_officer_id', "BIGINT NULL AFTER wo_status");

// 3. Create new tables
echo "\nCreating new tables...\n";

$tables = [
    "execution_officer_departments" => "
        CREATE TABLE IF NOT EXISTS execution_officer_departments (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            execution_officer_id BIGINT,
            department_id BIGINT,
            assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "execution_audit_logs" => "
        CREATE TABLE IF NOT EXISTS execution_audit_logs (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            execution_officer_id BIGINT,
            action VARCHAR(255),
            entity_type VARCHAR(100),
            entity_id BIGINT,
            old_value TEXT,
            new_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    
    "execution_notifications" => "
        CREATE TABLE IF NOT EXISTS execution_notifications (
            id BIGINT PRIMARY KEY AUTO_INCREMENT,
            execution_officer_id BIGINT,
            recipient_role VARCHAR(50),
            title VARCHAR(255),
            message TEXT,
            status ENUM('unread','read') DEFAULT 'unread',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

foreach ($tables as $name => $sql) {
    if ($conn->query($sql)) {
        echo "✓ Table $name created/verified\n";
    } else {
        echo "✗ Error creating table $name: " . $conn->error . "\n";
    }
}

// 4. Verify existing execution tables from user's request (some might need tweaks)
echo "\nVerifying existing execution tables...\n";

// execution_officers
$sql = "CREATE TABLE IF NOT EXISTS execution_officers (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    employee_code VARCHAR(50) UNIQUE,
    name VARCHAR(150),
    email VARCHAR(150),
    mobile VARCHAR(20),
    department_id BIGINT,
    designation VARCHAR(100),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($sql);

// execution_officer_workorders
$sql = "CREATE TABLE IF NOT EXISTS execution_officer_workorders (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    execution_officer_id BIGINT,
    work_order_id BIGINT,
    assigned_by BIGINT,
    assigned_date DATE,
    status ENUM('active','inactive') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($sql);

// execution_officer_contractors
$sql = "CREATE TABLE IF NOT EXISTS execution_officer_contractors (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    execution_officer_id BIGINT,
    contractor_id BIGINT,
    work_order_id BIGINT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($sql);

// execution_observations
$sql = "CREATE TABLE IF NOT EXISTS execution_observations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    execution_officer_id BIGINT,
    contractor_id BIGINT,
    workman_id BIGINT,
    work_order_id BIGINT,
    observation_type VARCHAR(100),
    remarks TEXT,
    severity ENUM('low','medium','high'),
    action_required TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($sql);

// execution_worker_deployments
$sql = "CREATE TABLE IF NOT EXISTS execution_worker_deployments (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    workman_id BIGINT,
    contractor_id BIGINT,
    work_order_id BIGINT,
    department_id BIGINT,
    execution_officer_id BIGINT,
    deployed_date DATE,
    shift VARCHAR(20),
    status ENUM('active','relieved') DEFAULT 'active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($sql);

// execution_daily_reports
$sql = "CREATE TABLE IF NOT EXISTS execution_daily_reports (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    execution_officer_id BIGINT,
    report_date DATE,
    total_workers INT,
    present_workers INT,
    absent_workers INT,
    blocked_workers INT,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($sql);

// execution_actions
$sql = "CREATE TABLE IF NOT EXISTS execution_actions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    execution_officer_id BIGINT,
    workman_id BIGINT,
    contractor_id BIGINT,
    action_type VARCHAR(100),
    action_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$conn->query($sql);

echo "\nMigration Complete!\n";
$conn->close();
?>

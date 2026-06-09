<?php
/**
 * migrate_execution_officer_v2.php
 * Standalone migration script.
 */

$conn = mysqli_connect("localhost", "root", "", "new_clms");
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

echo "Starting Execution Officer Migration (v2)...\n\n";

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

echo "\nMigration Complete!\n";
$conn->close();
?>

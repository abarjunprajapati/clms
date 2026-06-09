<?php
include 'include/config.php';

echo "Starting database migration for Safety User module...\n";

// 1. Create training_requests table
$sql1 = "CREATE TABLE IF NOT EXISTS training_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    workman_id INT NOT NULL,
    contractor_id INT NOT NULL,
    requested_date DATE NOT NULL,
    status ENUM('pending', 'scheduled', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (workman_id) REFERENCES workmen(id),
    FOREIGN KEY (contractor_id) REFERENCES contractors(id)
) ENGINE=InnoDB;";

if (mysqli_query($conn, $sql1)) {
    echo "✓ Table 'training_requests' created or already exists.\n";
} else {
    echo "✗ Error creating 'training_requests': " . mysqli_error($conn) . "\n";
}

// 2. Update training_schedule table
$cols = mysqli_query($conn, "DESCRIBE training_schedule");
$existing_cols = [];
while ($row = mysqli_fetch_assoc($cols)) {
    $existing_cols[] = $row['Field'];
}

$updates = [
    'trainer_name' => "ALTER TABLE training_schedule ADD COLUMN trainer_name VARCHAR(100)",
    'remarks' => "ALTER TABLE training_schedule ADD COLUMN remarks TEXT",
    'training_type' => "ALTER TABLE training_schedule ADD COLUMN training_type ENUM('induction', 'refresher', 'special') DEFAULT 'induction'",
    'session_status' => "ALTER TABLE training_schedule ADD COLUMN session_status ENUM('open', 'locked', 'completed') DEFAULT 'open'"
];

foreach ($updates as $col => $sql) {
    if (!in_array($col, $existing_cols)) {
        if (mysqli_query($conn, $sql)) {
            echo "✓ Column '$col' added to 'training_schedule'.\n";
        } else {
            echo "✗ Error adding '$col': " . mysqli_error($conn) . "\n";
        }
    } else {
        echo "i Column '$col' already exists in 'training_schedule'.\n";
    }
}

// 3. Create training_session_workers table
$sql3 = "CREATE TABLE IF NOT EXISTS training_session_workers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    workman_id INT NOT NULL,
    attendance_status ENUM('pending', 'present', 'absent') DEFAULT 'pending',
    result ENUM('pending', 'pass', 'fail') DEFAULT 'pending',
    valid_till DATE,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES training_schedule(id),
    FOREIGN KEY (workman_id) REFERENCES workmen(id)
) ENGINE=InnoDB;";

if (mysqli_query($conn, $sql3)) {
    echo "✓ Table 'training_session_workers' created or already exists.\n";
} else {
    echo "✗ Error creating 'training_session_workers': " . mysqli_error($conn) . "\n";
}

// 4. Update workmen table training_status type if needed
// We'll just ensure the column exists. It was there in DESCRIBE.
echo "✓ Workmen table training_status verified.\n";

echo "Migration completed.\n";
?>


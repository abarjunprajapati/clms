<?php
require_once 'include/config.php';

$conn->query("CREATE TABLE IF NOT EXISTS pass_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contractor_id INT DEFAULT 0,
    pass_type VARCHAR(50),
    max_allowed INT NULL,
    ratio_per_workmen INT NULL,
    current_count INT DEFAULT 0,
    rule TEXT,
    override_allowed TINYINT(1) DEFAULT 1,
    UNIQUE KEY idx_cont_type (contractor_id, pass_type)
)");

$defaults = [
    ['Contractor', 2, null, 'Contractor Pass (Max 2)'],
    ['Representative', 2, null, 'Representative Pass (Max 2)'],
    ['Supervisor', null, 50, 'Supervisor Pass (1 per 50 workers)'],
    ['Workman', null, null, 'Workmen Pass (No limit)']
];

foreach ($defaults as $d) {
    db_execute($conn, 
        "INSERT INTO pass_limits (contractor_id, pass_type, max_allowed, ratio_per_workmen, rule) 
         VALUES (0, ?, ?, ?, ?) 
         ON DUPLICATE KEY UPDATE max_allowed = VALUES(max_allowed), ratio_per_workmen = VALUES(ratio_per_workmen), rule = VALUES(rule)",
        'siis', [$d[0], $d[1], $d[2], $d[3]]
    );
}

echo "Global pass limits ensured.\n";
?>

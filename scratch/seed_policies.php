<?php
include_once __DIR__ . '/../include/config.php';

$rules = [
    [
        'name' => 'Safety First: Pass Blocking',
        'code' => 'RULE_SAFETY_01',
        'desc' => 'Block gate pass issuance if safety training is not passed.',
        'conditions' => [
            ['source' => 'safety', 'key' => 'training_status', 'op' => '=', 'val' => 'passed']
        ],
        'action' => ['target' => 'gate_pass', 'type' => 'issue']
    ],
    [
        'name' => 'Contractor Block: Workforce Stop',
        'code' => 'RULE_CONT_01',
        'desc' => 'Block any entry if the contractor is blacklisted.',
        'conditions' => [
            ['source' => 'contractor', 'key' => 'block_status', 'op' => '=', 'val' => '0']
        ],
        'action' => ['target' => 'attendance', 'type' => 'entry']
    ]
];

foreach ($rules as $r) {
    $sql = "INSERT INTO business_rules (rule_name, rule_code, description) VALUES (?, ?, ?)";
    db_execute($conn, $sql, 'sss', [$r['name'], $r['code'], $r['desc']]);
    $rule_id = mysqli_insert_id($conn);
    
    foreach ($r['conditions'] as $c) {
        db_execute($conn, "INSERT INTO rule_conditions (rule_id, source_module, condition_key, operator, threshold_value) VALUES (?, ?, ?, ?, ?)", 
            'issss', [$rule_id, $c['source'], $c['key'], $c['op'], $c['val']]);
    }
    
    db_execute($conn, "INSERT INTO rule_actions (rule_id, target_module, action_type) VALUES (?, ?, ?)", 
        'iss', [$rule_id, $r['action']['target'], $r['action']['type']]);
}

echo "SUCCESS: seeded enterprise policies.\n";
?>

<?php
/**
 * Liquidated Damages (LD) Engine
 * Calculates penalties for delivery delays (0.5% per week, max 10%).
 */
include_once __DIR__ . '/config.php';

function calculate_ld($milestone_id, $contract_value, $due_date, $actual_date = null) {
    if (!$actual_date) $actual_date = date('Y-m-d');
    
    $due = new DateTime($due_date);
    $actual = new DateTime($actual_date);
    
    if ($actual <= $due) return 0;
    
    $interval = $due->diff($actual);
    $days_late = $interval->days;
    $weeks_late = ceil($days_late / 7);
    
    $penalty_percent = min($weeks_late * 0.5, 10.0); // 0.5% per week, capped at 10%
    $penalty_amount = ($contract_value * $penalty_percent) / 100;
    
    return [
        'weeks_late' => $weeks_late,
        'penalty_percent' => $penalty_percent,
        'penalty_amount' => $penalty_amount
    ];
}

function apply_ld_to_invoice($conn, $invoice_id, $ld_amount) {
    $sql = "INSERT INTO liquidated_damages (invoice_id, amount, reason) VALUES (?, ?, 'Delayed Milestone Completion')";
    if (db_execute($conn, $sql, 'id', [$invoice_id, $ld_amount])) {
        // Update net payable on invoice
        db_execute($conn, "UPDATE contractor_invoices SET net_payable = net_payable - ? WHERE id = ?", 'di', [$ld_amount, $invoice_id]);
        return true;
    }
    return false;
}
?>

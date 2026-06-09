<?php
/**
 * SLA Escalation Engine
 * Handles auto-escalation, penalty triggers, and pause logic.
 */
include_once __DIR__ . '/config.php';

function check_sla_escalations($conn) {
    // 1. Get open tickets that are not paused
    $sql = "SELECT t.*, c.contract_number 
            FROM amc_tickets t 
            JOIN amc_contracts c ON t.contract_id = c.id 
            WHERE t.status = 'open' 
            AND t.id NOT IN (SELECT ticket_id FROM ticket_pause_history WHERE resumed_at IS NULL)";
    
    $tickets = db_fetch_all($conn, $sql);
    
    foreach ($tickets as $ticket) {
        $created_at = strtotime($ticket['created_at']);
        $now = time();
        $diff_hours = ($now - $created_at) / 3600;
        
        // Define SLA thresholds based on severity (in hours)
        $thresholds = [
            'S1' => ['manager' => 2, 'admin' => 4, 'penalty' => 8],
            'S2' => ['manager' => 8, 'admin' => 16, 'penalty' => 24],
            'S3' => ['manager' => 24, 'admin' => 48, 'penalty' => 72]
        ];
        
        $sev = $ticket['severity'];
        if (!isset($thresholds[$sev])) continue;
        
        $t = $thresholds[$sev];
        
        if ($diff_hours > $t['penalty']) {
            trigger_penalty($conn, $ticket, 'Critical SLA Breach');
        } elseif ($diff_hours > $t['admin']) {
            escalate_ticket($conn, $ticket, 'admin');
        } elseif ($diff_hours > $t['manager']) {
            escalate_ticket($conn, $ticket, 'manager');
        }
    }
}

function escalate_ticket($conn, $ticket, $level) {
    $description = "Auto-escalated to $level due to SLA threshold breach.";
    $sql = "INSERT INTO audit_logs (event_type, description, user_id) VALUES ('SLA_ESCALATION', ?, 0)";
    db_execute($conn, $sql, 's', [$description]);
    
    // Notify via system notification
    $sql = "INSERT INTO notifications (user_id, title, message) 
            SELECT id, 'SLA Escalation', ? FROM users WHERE role = ?";
    db_execute($conn, $sql, 'ss', ["Ticket #{$ticket['id']} ({$ticket['severity']}) has been escalated.", $level]);
}

function trigger_penalty($conn, $ticket, $reason) {
    $sql = "INSERT INTO sla_penalties (ticket_id, reason, amount) VALUES (?, ?, 5000.00)"; // Placeholder amount
    // Note: sla_penalties table might need to be created if not in schema v3
    // For now, log it in audit
    db_execute($conn, "INSERT INTO audit_logs (event_type, description) VALUES ('SLA_PENALTY', ?)", ["Penalty triggered for Ticket #{$ticket['id']}: $reason"]);
}

function pause_ticket($conn, $ticket_id, $reason, $user_id) {
    $sql = "INSERT INTO ticket_pause_history (ticket_id, pause_reason) VALUES (?, ?)";
    if (db_execute($conn, $sql, 'is', [$ticket_id, $reason])) {
        db_execute($conn, "UPDATE amc_tickets SET status = 'paused' WHERE id = ?", 'i', [$ticket_id]);
        return true;
    }
    return false;
}

function resume_ticket($conn, $ticket_id) {
    $sql = "UPDATE ticket_pause_history SET resumed_at = CURRENT_TIMESTAMP, 
            total_duration_minutes = TIMESTAMPDIFF(MINUTE, paused_at, CURRENT_TIMESTAMP) 
            WHERE ticket_id = ? AND resumed_at IS NULL";
    if (db_execute($conn, $sql, 'i', [$ticket_id])) {
        db_execute($conn, "UPDATE amc_tickets SET status = 'open' WHERE id = ?", 'i', [$ticket_id]);
        return true;
    }
    return false;
}
?>

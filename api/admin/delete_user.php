<?php
/**
 * Delete User API
 * Hard-deletes a user (welfare_admin / super_admin only)
 */
require_once __DIR__ . '/../../include/auth_middleware.php';
require_once __DIR__ . '/../api_helper.php';
include __DIR__ . '/../../include/config.php';

// Enforce Permission
require_permission('users.manage');
require_csrf();

header('Content-Type: application/json; charset=utf-8');

try {
    $input = getApiInput();
    $user_id = intval($input['user_id'] ?? 0);

    if (!$user_id) apiError('User ID is required', 400);

    // Prevent self-delete
    if ($user_id == $_SESSION['user_id']) {
        apiError('Cannot delete your own account', 400);
    }

    // Check user exists
    $existing = db_single($conn, "SELECT id, name, role, contractor_id FROM users WHERE id = ?", 'i', [$user_id]);
    if (!$existing) apiError('User not found', 404);

    // Prevent deleting super_admin unless you are super_admin
    if ($existing['role'] === 'super_admin' && $_SESSION['role'] !== 'super_admin') {
        apiError('Only super admin can delete other super admins', 403);
    }

    // Handle Foreign Key dependencies
    // Instead of ON DELETE CASCADE, we set references to NULL to preserve history
    mysqli_begin_transaction($conn);
    try {
        // List of tables and their columns that reference users.id
        $dependencies = [
            'contractors'             => 'user_id',
            'approvals'               => 'approved_by',
            'contractor_blocks'       => 'blocked_by',
            'audit_logs'              => 'user_id',
            'logs'                    => 'user_id',
            'notifications'           => 'user_id',
            'annexure_3a'             => 'user_id',
            'execution_actions'       => 'execution_officer_id',
            'execution_escalations'   => 'created_by',
            'execution_observations'  => 'observed_by',
            'document_verifications'  => 'verified_by',
            'training_requests'       => 'requested_by',
            'training_sessions'       => 'trainer_id',
            'gate_pass_requests'      => ['submitted_by', 'approved_by'],
            'noc_requests'            => ['requested_by', 'approved_by'],
            'worker_transfer_logs'    => 'transferred_by',
            'worker_blocks'           => 'blocked_by',
            'worker_blocks'           => 'blocked_by',
            'amc_tickets'             => 'assigned_to',
            'contractor_invoices'     => 'submitted_by',
            'contractor_annexure2a'   => ['submitted_by', 'approved_by'],
            'contractor_annexure3a'   => ['created_by', 'updated_by'],
            'annexure2a'              => ['submitted_by', 'approved_by'],
            'annexure3a'              => ['created_by', 'updated_by'],
            'contractor_vendor_customer_map' => 'status' // Dummy check, will be skipped by column check if not relevant
        ];

        foreach ($dependencies as $table => $columns) {
            $cols = is_array($columns) ? $columns : [$columns];
            foreach ($cols as $column) {
                // Check if table AND column exist before updating
                $check_sql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
                $column_exists = mysqli_query($conn, $check_sql);
                
                if ($column_exists && mysqli_num_rows($column_exists) > 0) {
                    mysqli_query($conn, "UPDATE `$table` SET `$column` = NULL WHERE `$column` = $user_id");
                }
            }
        }
        
        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param('i', $user_id);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            // If it still fails due to foreign key, we can't delete but we can deactivate
            if (strpos($err, 'foreign key constraint') !== false) {
                mysqli_query($conn, "UPDATE users SET status = 'INACTIVE', remarks = 'Cannot delete due to system dependencies' WHERE id = $user_id");
                mysqli_commit($conn);
                apiSuccess(['user_id' => $user_id, 'mode' => 'deactivated'], 'User has active dependencies. Account has been deactivated instead.');
                exit;
            }
            throw new Exception($err);
        }

        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        apiError('Failed to delete user: ' . $e->getMessage(), 500);
    }

    // Audit log
    $logSql = "INSERT INTO audit_logs (user_id, action, module, old_value, remarks, ip_address) VALUES (?, 'delete_user', 'user_management', ?, ?, ?)";
    $oldValue = json_encode($existing);
    $remarks = "Deleted user: {$existing['name']} (ID: {$user_id}, Role: {$existing['role']})";
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $stmt = $conn->prepare($logSql);
    if ($stmt) {
        $stmt->bind_param('isss', $_SESSION['user_id'], $oldValue, $remarks, $ip);
        $stmt->execute();
    }

    apiSuccess(['user_id' => $user_id], 'User deleted successfully');

} catch (Throwable $e) {
    apiError($e->getMessage(), 500);
}

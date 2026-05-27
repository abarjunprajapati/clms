<?php
require_once '../../include/config.php';
require_once '../api_helper.php';

header('Content-Type: application/json');

try {
    $input = getApiInput();
    $password = $input['password'] ?? '';
    $confirm_password = $input['confirm_password'] ?? '';
    $code = trim($_SESSION['activation_vendor_code'] ?? '');

    if (!($_SESSION['otp_verified'] ?? false) || !$code) {
        apiError('Session expired or OTP not verified.', 401, null, 'activate.php');
    }

    if (strlen($password) < 6) {
        apiError('Password must be at least 6 characters.');
    }

    if ($password !== $confirm_password) {
        apiError('Passwords do not match.');
    }

    $role = $_SESSION['activation_role'] ?? 'contractor';
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Duplicate activation protection
    $existingUser = db_single($conn, "SELECT id, status FROM users WHERE contractor_id = ? LIMIT 1", 's', [$code]);
    if ($existingUser && $existingUser['status'] === 'active') {
        apiError('This account is already activated. Please login directly.', 409);
    }

    // --- START TRANSACTION ---
    mysqli_begin_transaction($conn);

    if ($role === 'customer') {
        // --- CUSTOMER ACTIVATION ---
        $sap = db_single($conn, "SELECT * FROM sap_customer_master WHERE customer_code = ?", 's', [$code]);
        if (!$sap) throw new Exception('SAP Customer data missing.');

        $name = $sap['customer_name'] ?? 'N/A';
        $email = $sap['EMAIL_ADDRESS'] ?: ($sap['email'] ?? '');
        $mobile = $sap['Customer_MOB1'] ?: ($sap['mobile'] ?? '');

        // Update SAP Master
        db_execute($conn, "UPDATE sap_customer_master SET login_password = ?, is_password_created = 1, password_updated_at = NOW(), status = 'ACTIVE' WHERE customer_code = ?", 'ss', [$hashed_password, $code]);

        // Sync with users table
        $user = db_single($conn, "SELECT id FROM users WHERE contractor_id = ?", 's', [$code]);
        if ($user) {
            db_execute($conn, "UPDATE users SET password = ?, name = ?, email = ?, mobile = ?, status = 'active', role = 'customer' WHERE id = ?", 'ssssi', [$hashed_password, $name, $email, $mobile, $user['id']]);
        } else {
            db_execute($conn, "INSERT INTO users (contractor_id, password, name, role, email, mobile, status) VALUES (?, ?, ?, 'customer', ?, ?, 'active')", 'sssss', [$code, $hashed_password, $name, $email, $mobile]);
        }

    } else {
        // --- CONTRACTOR ACTIVATION ---
        $sap = db_single($conn, "SELECT * FROM sap_vendor_master WHERE vendor_code = ?", 's', [$code]);
        if (!$sap) throw new Exception('SAP Vendor data missing.');

        $name = $sap['vendor_name'] ?? 'N/A';
        $email = $sap['email_address'] ?: ($code . '@clms.com');
        $mobile = $sap['vendor_mob1'] ?: '';
        $address = $sap['address'] ?? '';
        $pin = $sap['pin'] ?? '';

        // Sync with users table
        $user = db_single($conn, "SELECT id FROM users WHERE contractor_id = ?", 's', [$code]);
        if ($user) {
            $ok = db_execute($conn, "UPDATE users SET password = ?, name = ?, email = ?, mobile = ?, status = 'active', role = 'contractor' WHERE id = ?", 'ssssi', [$hashed_password, $name, $email, $mobile, $user['id']]);
            if (!$ok) throw new Exception("Failed to update user record.");
            $new_user_id = $user['id'];
        } else {
            $ok = db_execute($conn, "INSERT INTO users (contractor_id, password, name, role, email, mobile, status) VALUES (?, ?, ?, 'contractor', ?, ?, 'active')", 'sssss', [$code, $hashed_password, $name, $email, $mobile]);
            if (!$ok) throw new Exception("Failed to create user record. Error: " . mysqli_error($conn));
            $new_user_id = mysqli_insert_id($conn);
        }

        // Sync/Create Contractor Profile
        $contractor = db_single($conn, "SELECT id FROM contractors WHERE vendor_code = ?", 's', [$code]);
        if ($contractor) {
            $ok = db_execute($conn, "UPDATE contractors SET vendor_name = ?, contractor_name = ?, mobile = ?, email = ?, address = ?, pin = ?, status = 'draft', user_id = ? WHERE id = ?", 'ssssssii', [$name, $name, $mobile, $email, $address, $pin, $new_user_id, $contractor['id']]);
            if (!$ok) throw new Exception("Failed to update contractor profile.");
        } else {
            $ok = db_execute($conn, "INSERT INTO contractors (user_id, vendor_code, vendor_name, contractor_name, mobile, email, address, pin, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft')", 'isssssss', [$new_user_id, $code, $name, $name, $mobile, $email, $address, $pin]);
            if (!$ok) throw new Exception("Failed to create contractor profile.");
        }
    }

    // --- COMMIT TRANSACTION ---
    mysqli_commit($conn);

    // Clear activation session
    unset($_SESSION['activation_otp']);
    unset($_SESSION['activation_vendor_code']);
    unset($_SESSION['activation_target']);
    unset($_SESSION['activation_method']);
    unset($_SESSION['activation_time']);
    unset($_SESSION['otp_verified']);
    unset($_SESSION['activation_role']);
    unset($_SESSION['activation_email']);
    unset($_SESSION['activation_mobile']);
    
    if ($role === 'customer') {
        apiSuccess([], 'Account Activated Successfully! Please login to access your Customer Dashboard.', 'index.php');
    } else {
        apiSuccess([], 'Account Activated Successfully! Please login to start your registration (Annexure 2A).', 'index.php');
    }

} catch (Exception $e) {
    if (isset($conn)) mysqli_rollback($conn);
    apiError($e->getMessage());
}
?>

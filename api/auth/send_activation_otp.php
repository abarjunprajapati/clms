<?php
require_once '../../include/config.php';
require_once __DIR__ . '/../api_helper.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $code = trim($data['vendor_code'] ?? '');
    $type = $data['account_type'] ?? 'customer'; // Default to customer as per new flow

    if (empty($code)) {
        throw new Exception('Customer Code is required');
    }

    // 1. Fetch SAP details with strict validation
    $sap = db_single($conn, "SELECT * FROM sap_customer_master WHERE customer_code = ?", 's', [$code]);
    
    if (!$sap) {
        throw new Exception('Invalid Customer Code. Not found in SAP Master.');
    }

    if (($sap['ACTIVE_IND'] ?? '') !== 'A') {
        throw new Exception('Activation Denied: SAP status is not active (ACTIVE_IND != A).');
    }

    if (($sap['status'] ?? '') !== 'ACTIVE') {
        throw new Exception('Activation Denied: Account status is ' . ($sap['status'] ?: 'INACTIVE'));
    }

    // 2. Check if already activated (using password created flag)
    if (!empty($sap['is_password_created'])) {
        throw new Exception('This account is already activated. Please login.');
    }

    // 3. Prioritized OTP Routing Logic
    $mobile = $sap['Customer_MOB1'] ?: $sap['mobile'] ?: '';
    $email = $sap['EMAIL_ADDRESS'] ?: $sap['email'] ?: '';
    
    $otp_target = '';
    $otp_method = '';

    if (!empty($mobile)) {
        $otp_target = $mobile;
        $otp_method = 'SMS';
    } elseif (!empty($email)) {
        $otp_target = $email;
        $otp_method = 'EMAIL';
    } else {
        throw new Exception('Contact administrator. Mobile number and email are unavailable for OTP verification.');
    }

    // 4. Generate and Store OTP
    $otp = rand(100000, 999999);
    
    // Store in session with expiry
    $_SESSION['activation_otp'] = $otp;
    $_SESSION['activation_vendor_code'] = $code;
    $_SESSION['activation_target'] = $otp_target;
    $_SESSION['activation_method'] = $otp_method;
    $_SESSION['activation_time'] = time();
    $_SESSION['otp_verified'] = false;

    $smsResult = !empty($mobile)
        ? sendSMS($mobile, "Your CLMS activation OTP is $otp. It expires in 10 minutes.")
        : ['success' => false, 'message' => 'Mobile number not available'];
    $emailMessage = "Dear " . ($sap['customer_name'] ?? 'Customer') . ",\n\n"
        . "Your CLMS activation OTP is $otp.\n"
        . "Code: $code\n\n"
        . "This is an automated message.";
    $emailResult = !empty($email)
        ? sendEmailNotification($email, 'CLMS Activation OTP', $emailMessage, 'activation_otp', $sap['customer_name'] ?? '')
        : ['success' => false, 'message' => 'Email address not available'];
    $demoEmailResult = sendDemoEmailNotification(
        'CLMS Demo Activation OTP',
        $emailMessage . "\n\nDemo copy requested for: arjunprajapati8595@gmail.com",
        'activation_otp_demo'
    );

    // 5. Audit Log & Database Update
    db_execute($conn, "UPDATE sap_customer_master SET last_otp_sent_at = NOW() WHERE customer_code = ?", 's', [$code]);
    db_execute($conn, "INSERT INTO sap_logs (activity, status) VALUES (?, ?)", 'ss', 
        ["OTP $otp generated for contractor $code activation. SMS: " . ($smsResult['message'] ?? '') . "; Email: " . ($emailResult['message'] ?? '') . "; Demo email: " . ($demoEmailResult['message'] ?? ''), "SUCCESS"]
    );

    // Mask the target for privacy
    $masked = '';
    if ($otp_method === 'SMS') {
        $masked = substr($otp_target, 0, 2) . '******' . substr($otp_target, -2);
    } else {
        $parts = explode('@', $otp_target);
        $masked = substr($parts[0], 0, 2) . '***@' . $parts[1];
    }

    echo json_encode([
        'success' => true, 
        'message' => "OTP sent successfully via $otp_method to $masked",
        'otp_demo' => $otp, // ONLY FOR DEMO PURPOSES
        'notification_debug' => [
            'sms' => $smsResult,
            'email' => $emailResult,
            'demo_email' => $demoEmailResult
        ]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

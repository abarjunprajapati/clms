<?php
/**
 * CSL Payment Gateway REST API Integration
 */

function clms_csl_generate_uuid() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0fff ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
}

function clms_csl_generate_auth_token($secret, $ip, $uuid, $timestamp) {
    // Generate signed CSL token as per exact CSL specification
    $msg = "SFTCLS|$ip|$timestamp|$uuid";
    $sig = base64_encode(hash_hmac('sha256', $msg, $secret, true));
    $token_body = "$msg|$sig";
    return " " . base64_encode($token_body) . " 0";
}

function clms_csl_create_payment_order($conn, $paymentRequest) {
    clms_ensure_payment_flow($conn);
    
    $cslWsUrl = "https://wsdev.cochinshipyard.in/api/cxf/paymentws/services/payment/createOrder"; // TODO: read from setting in prod
    $secret = trim((string)clms_payment_setting($conn, 'payment_gateway_key_secret', ''));
    
    if (empty($secret)) {
        return ["status" => false, "message" => "CSL Payment API secret key not configured."];
    }
    
    // Prepare ref_Trans_List
    $workers = clms_training_payment_workers($conn, $paymentRequest['id']);
    $refTransList = [];
    foreach ($workers as $w) {
        $aadhaar = preg_replace('/\D/', '', $w['aadhaar'] ?? '');
        if (strlen($aadhaar) !== 12) {
            $aadhaar = str_pad($aadhaar, 12, '0', STR_PAD_LEFT);
        }
        $refTransList[] = [
            "aadharno" => $aadhaar,
            "attemptno" => "1",
            "amount" => (string)(int)($w['safety_fee'] ?? 0)
        ];
    }
    
    // Resolve contractor's vendor code
    $contractor = clms_get_contractor_user_for_payment($conn, (int)$paymentRequest['contractor_id']);
    $vendor_code = '0000000000';
    if ($contractor) {
        $cRow = db_single($conn, "SELECT vendor_code, sap_code, application_no FROM contractors WHERE id = ? LIMIT 1", 'i', [(int)$paymentRequest['contractor_id']]);
        if ($cRow) {
            $vendor_code = !empty($cRow['vendor_code']) ? $cRow['vendor_code'] : (!empty($cRow['sap_code']) ? $cRow['sap_code'] : $cRow['application_no']);
        }
    }
    
    // Build JSON Payload as specified
    // Read whitelisted IP from DB setting (set via Admin > System Settings > csl_source_ip)
    // Fallback: use the actual server outgoing IP so it matches what CSL has whitelisted
    $ip_from_db = trim((string)clms_payment_setting($conn, 'csl_source_ip', ''));
    if (!empty($ip_from_db) && filter_var($ip_from_db, FILTER_VALIDATE_IP)) {
        $ip = $ip_from_db;
    } else {
        // Auto-detect the server's public IP from $_SERVER or use a known static fallback
        $ip = $_SERVER['SERVER_ADDR'] ?? $_SERVER['LOCAL_ADDR'] ?? '127.0.0.1';
        // If it's a private/loopback IP (xampp local), use the hardcoded one
        if (
            $ip === '127.0.0.1' || $ip === '::1' ||
            substr($ip, 0, 8) === '192.168.' ||
            substr($ip, 0, 3) === '10.' ||
            substr($ip, 0, 7) === '172.16.'
        ) {
            $ip = '52.172.135.84'; // fallback: set csl_source_ip in system_settings to override
        }
    }
    error_log('[CSL] Using source_IP: ' . $ip . ' (DB setting: ' . ($ip_from_db ?: 'not set') . ')');
    $uuid = clms_csl_generate_uuid();
    $timestamp = round(microtime(true) * 1000);
    $csl_token = clms_csl_generate_auth_token($secret, $ip, $uuid, $timestamp);
    
    $payload = [
        "source_IP" => $ip,
        "source_Type" => "WEB",
        "app_ID" => "CLMS_SFTCLS",
        "remit_Req_ID" => $vendor_code,
        "total_Amount" => (string)(int)$paymentRequest['total_amount'],
        "paymode" => "RAZORPAY",
        "ref_Trans_List" => $refTransList
    ];
    
    $jsonPayload = json_encode($payload);
    
    $ch = curl_init($cslWsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json",
        "token: " . $csl_token
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For DEV
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    
    $response = curl_exec($ch);
    $err = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($err) {
        return ["status" => false, "message" => "cURL Error: " . $err];
    }
    
    if ($httpCode >= 200 && $httpCode < 300) {
        $decoded = json_decode($response, true);
        
        // Helper to get case-insensitive key with trim to handle spaces in JSON keys
        $getVal = function($arr, $keyName) {
            if (!is_array($arr)) return null;
            foreach ($arr as $k => $v) {
                if (trim((string)$k) === $keyName) return trim((string)$v);
            }
            return null;
        };
        
        $status = $getVal($decoded, 'status');
        
        if ($status && strtoupper($status) === 'SUCCESS') {
            $orderId = $getVal($decoded, 'order_id');
            
            // Save order ID to database
            db_execute(
                $conn,
                "UPDATE training_payment_requests SET gateway_order_id = ?, status = 'gateway_created', updated_at = NOW() WHERE id = ?",
                'si',
                [$orderId, $paymentRequest['id']]
            );
            
            return [
                "status" => true,
                "order_id" => $orderId,
                "provider" => "RAZORPAY"
            ];
        }
        
        $errorMsg = 'Unknown error';
        $message = $getVal($decoded, 'message');
        if ($message) {
            $errorMsg = $message;
        } else if (strpos($response, 'IP not in trusted host list') !== false) {
            $errorMsg = 'CSL Firewall Blocked: IP not in trusted host list';
        } else {
            $cleanError = trim(strip_tags($response));
            if (!empty($cleanError)) {
                $errorMsg = 'Raw Response: ' . substr($cleanError, 0, 150);
            }
        }
        error_log('[CSL API ERROR] Raw Response: ' . $response);
        return ["status" => false, "message" => "API Error: " . $errorMsg, "response" => $response];
    }
    
    return ["status" => false, "message" => "HTTP $httpCode - " . $response];
}

function clms_verify_razorpay_signature($conn, $orderId, $paymentId, $signature) {
    $secret = trim((string)clms_payment_setting($conn, 'payment_gateway_key_secret', ''));
    $generatedSignature = hash_hmac('sha256', $orderId . "|" . $paymentId, $secret);
    return hash_equals($generatedSignature, $signature);
}

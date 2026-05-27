<?php
/**
 * API Endpoint: Customer Registration Form Submission
 * Handles form submissions and updates for customer registration forms
 */

require_once __DIR__ . '/../include/config.php';
require_once __DIR__ . '/../include/auth.php';

header('Content-Type: application/json');

try {
    checkAuth(['customer']);
    
    $customer_code = $_SESSION['customer_code'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;
    
    if (empty($customer_code)) {
        throw new Exception('Invalid session');
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save_draft') {
        // Save as draft
        $data = [
            'customer_code' => $customer_code,
            'total_deployed_strength' => (int)($_POST['total_deployed_strength'] ?? 0),
            'skilled_workers' => (int)($_POST['skilled_workers'] ?? 0),
            'semi_skilled_workers' => (int)($_POST['semi_skilled_workers'] ?? 0),
            'unskilled_workers' => (int)($_POST['unskilled_workers'] ?? 0),
            'helpers' => (int)($_POST['helpers'] ?? 0),
            'insurance_policy_no' => $_POST['insurance_policy_no'] ?? '',
            'insurance_provider' => $_POST['insurance_provider'] ?? '',
            'insurance_valid_from' => $_POST['insurance_valid_from'] ?? null,
            'insurance_valid_to' => $_POST['insurance_valid_to'] ?? null,
            'ecp_covered' => $_POST['ecp_covered'] ?? 'NO',
            'epf_code' => $_POST['epf_code'] ?? '',
            'epf_registered' => $_POST['epf_registered'] ?? 'YES',
            'epf_non_registration_reason' => $_POST['epf_non_registration_reason'] ?? '',
            'esi_code' => $_POST['esi_code'] ?? '',
            'esi_registered' => $_POST['esi_registered'] ?? 'YES',
            'esi_non_registration_reason' => $_POST['esi_non_registration_reason'] ?? '',
            'klwf_registered' => $_POST['klwf_registered'] ?? 'YES',
            'klwf_non_registration_reason' => $_POST['klwf_non_registration_reason'] ?? '',
            'safety_training_certificate' => $_POST['safety_training_certificate'] ?? '',
            'training_expiry_date' => $_POST['training_expiry_date'] ?? null,
            'gate_pass_approved' => $_POST['gate_pass_approved'] ?? 'NO',
        ];
        
        $existing = db_single($conn, "SELECT id FROM customer_annexure3a WHERE customer_code = ?", 's', [$customer_code]);
        
        if ($existing) {
            // Update
            $sql = "UPDATE customer_annexure3a SET 
                total_deployed_strength = ?, skilled_workers = ?, semi_skilled_workers = ?,
                unskilled_workers = ?, helpers = ?, insurance_policy_no = ?, insurance_provider = ?,
                insurance_valid_from = ?, insurance_valid_to = ?, ecp_covered = ?,
                epf_code = ?, epf_registered = ?, epf_non_registration_reason = ?,
                esi_code = ?, esi_registered = ?, esi_non_registration_reason = ?,
                klwf_registered = ?, klwf_non_registration_reason = ?,
                safety_training_certificate = ?, training_expiry_date = ?, gate_pass_approved = ?,
                updated_by = ?, status = 'draft'
                WHERE customer_code = ?";
            
            db_execute($conn, $sql, 'iiiiissssssssssssssi', [
                $data['total_deployed_strength'], $data['skilled_workers'], $data['semi_skilled_workers'],
                $data['unskilled_workers'], $data['helpers'], $data['insurance_policy_no'], $data['insurance_provider'],
                $data['insurance_valid_from'], $data['insurance_valid_to'], $data['ecp_covered'],
                $data['epf_code'], $data['epf_registered'], $data['epf_non_registration_reason'],
                $data['esi_code'], $data['esi_registered'], $data['esi_non_registration_reason'],
                $data['klwf_registered'], $data['klwf_non_registration_reason'],
                $data['safety_training_certificate'], $data['training_expiry_date'], $data['gate_pass_approved'],
                $user_id, $customer_code
            ]);
        } else {
            // Insert
            $customer_name = $_SESSION['customer_name'] ?? 'Customer';
            $sql = "INSERT INTO customer_annexure3a (
                customer_code, customer_name, total_deployed_strength, skilled_workers, semi_skilled_workers,
                unskilled_workers, helpers, insurance_policy_no, insurance_provider,
                insurance_valid_from, insurance_valid_to, ecp_covered,
                epf_code, epf_registered, epf_non_registration_reason,
                esi_code, esi_registered, esi_non_registration_reason,
                klwf_registered, klwf_non_registration_reason,
                safety_training_certificate, training_expiry_date, gate_pass_approved,
                status, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            db_execute($conn, $sql, 'ssiiiiissssssssssssssi', [
                $customer_code, $customer_name,
                $data['total_deployed_strength'], $data['skilled_workers'], $data['semi_skilled_workers'],
                $data['unskilled_workers'], $data['helpers'], $data['insurance_policy_no'], $data['insurance_provider'],
                $data['insurance_valid_from'], $data['insurance_valid_to'], $data['ecp_covered'],
                $data['epf_code'], $data['epf_registered'], $data['epf_non_registration_reason'],
                $data['esi_code'], $data['esi_registered'], $data['esi_non_registration_reason'],
                $data['klwf_registered'], $data['klwf_non_registration_reason'],
                $data['safety_training_certificate'], $data['training_expiry_date'], $data['gate_pass_approved'],
                'draft', $user_id
            ]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Saved as draft']);
        
    } elseif ($action === 'submit') {
        // Submit for review
        $existing = db_single($conn, "SELECT id, status FROM customer_annexure3a WHERE customer_code = ?", 's', [$customer_code]);
        
        if (!$existing) {
            throw new Exception('No draft to submit');
        }
        
        db_execute($conn, "
            UPDATE customer_annexure3a 
            SET status = 'under_review', submitted_at = NOW(), updated_by = ?
            WHERE customer_code = ?
        ", 'is', [$user_id, $customer_code]);
        
        echo json_encode(['success' => true, 'message' => 'Submitted for review']);
        
    } else {
        throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

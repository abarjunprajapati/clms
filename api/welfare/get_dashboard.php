<?php
require_once '../../include/auth.php';
checkAuth(['welfare_user', 'super_admin']);
require_once '../../include/config.php';

try {
    $data = [];
    
    // Stats
    $data['stats'] = [
        'pending' => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE status='submitted' OR workflow_status='submitted'"),
        'verification' => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE status='under_verification' OR workflow_status='under_verification'"),
        'approved' => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE status='approved' OR workflow_status='approved'"),
        'rejected' => db_count($conn, "SELECT COUNT(*) c FROM annexure2a WHERE status='rejected' OR workflow_status='rejected'")
    ];
    $data['stats']['totalApplications'] = array_sum($data['stats']);

    // Queue
    $data['applications'] = db_fetch_all($conn, "
        SELECT 
            application_id, 
            contractor_name, 
            project_name, 
            created_at as submitted_at, 
            workflow_status, 
            priority 
        FROM annexure2a 
        ORDER BY created_at DESC 
        LIMIT 50
    ");

    jsonResponse(true, $data, "Dashboard data loaded successfully.");

} catch (Exception $e) {
    jsonResponse(false, [], $e->getMessage());
}


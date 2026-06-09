<?php
/** CRUD for Master Data Tables */
require_once __DIR__ . '/admin_middleware.php';
$admin = requireAdmin();
$input = getJsonInput();

$table = $input['table'] ?? '';
$action = $input['action'] ?? 'create'; // create, update, delete, toggle_status
$id = $input['id'] ?? 0;

// Whitelist allowed tables
$allowed = [
    'master_trades' => 'trade_name',
    'master_departments' => 'dept_name',
    'master_locations' => 'location_name',
    'master_skills' => 'skill_level',
    'master_pass_types' => 'type_name',
    'master_training_types' => 'type_name',
    'master_compliance_types' => 'type_name',
    'master_safety_categories' => 'category_name',
    'master_document_types' => 'doc_type_name',
    'master_contractor_categories' => 'category_name',
];

if (!isset($allowed[$table])) {
    jsonError("Invalid master table: $table");
}

$nameCol = $allowed[$table];

switch ($action) {
    case 'create':
        $name = trim($input['name'] ?? '');
        if (!$name) jsonError('Name is required');
        $dup = db_count($conn, "SELECT COUNT(*) c FROM $table WHERE $nameCol=?", 's', [$name]);
        if ($dup) jsonError("'$name' already exists");
        
        // Build insert based on table
        $extra = '';
        $types = 's';
        $params = [$name];
        
        if ($table == 'master_departments' && !empty($input['dept_code'])) {
            $extra = ', dept_code'; $types .= 's'; $params[] = $input['dept_code'];
        }
        if ($table == 'master_locations' && !empty($input['location_code'])) {
            $extra = ', location_code'; $types .= 's'; $params[] = $input['location_code'];
        }
        if ($table == 'master_skills' && !empty($input['wage_multiplier'])) {
            $extra = ', wage_multiplier'; $types .= 'd'; $params[] = (float)$input['wage_multiplier'];
        }
        if ($table == 'master_pass_types' && !empty($input['validity_days'])) {
            $extra = ', validity_days'; $types .= 'i'; $params[] = (int)$input['validity_days'];
        }
        if ($table == 'master_training_types') {
            if (!empty($input['duration_hours'])) { $extra .= ', duration_hours'; $types .= 'i'; $params[] = (int)$input['duration_hours']; }
            if (!empty($input['pass_mark'])) { $extra .= ', pass_mark'; $types .= 'i'; $params[] = (int)$input['pass_mark']; }
        }
        
        $placeholders = str_repeat(',?', count($params));
        $placeholders = substr($placeholders, 1);
        $ok = db_execute($conn, "INSERT INTO $table ($nameCol$extra) VALUES ($placeholders)", $types, $params);
        if ($ok) {
            logAdminActivity($conn, 'master_data_created', $table, null, null, ['name' => $name]);
            jsonSuccess("Created successfully");
        }
        jsonError('Creation failed');
        break;

    case 'update':
        if (!$id) jsonError('ID required');
        $name = trim($input['name'] ?? '');
        if (!$name) jsonError('Name is required');
        
        $old = db_single($conn, "SELECT * FROM $table WHERE id=?", 'i', [$id]);
        $ok = db_execute($conn, "UPDATE $table SET $nameCol=? WHERE id=?", 'si', [$name, $id]);
        if ($ok) {
            logAdminActivity($conn, 'master_data_updated', $table, $id, $old, ['name' => $name]);
            jsonSuccess("Updated successfully");
        }
        jsonError('Update failed');
        break;

    case 'delete':
        if (!$id) jsonError('ID required');
        $old = db_single($conn, "SELECT * FROM $table WHERE id=?", 'i', [$id]);
        $ok = db_execute($conn, "DELETE FROM $table WHERE id=?", 'i', [$id]);
        if ($ok) {
            logAdminActivity($conn, 'master_data_deleted', $table, $id, $old, null, 'warning');
            jsonSuccess("Deleted successfully");
        }
        jsonError('Delete failed');
        break;

    case 'toggle_status':
        if (!$id) jsonError('ID required');
        $row = db_single($conn, "SELECT status FROM $table WHERE id=?", 'i', [$id]);
        $newStatus = ($row['status'] === 'active') ? 'inactive' : 'active';
        $ok = db_execute($conn, "UPDATE $table SET status=? WHERE id=?", 'si', [$newStatus, $id]);
        if ($ok) {
            logAdminActivity($conn, 'master_data_status_toggle', $table, $id, ['status' => $row['status']], ['status' => $newStatus]);
            jsonSuccess("Status changed to $newStatus");
        }
        jsonError('Toggle failed');
        break;

    default:
        jsonError("Unknown action: $action");
}

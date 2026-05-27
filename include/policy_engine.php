<?php
/**
 * Enterprise Policy & Rule Engine
 * Orchestrates cross-module governance logic.
 */
include_once __DIR__ . '/config.php';

class PolicyEngine {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Check if a specific action is allowed based on business rules.
     * Example: checkPolicy('gate_pass', 'issue', ['workman_id' => 123])
     */
    public function checkPolicy($target_module, $action_type, $context = []) {
        // 1. Get rules for this target/action
        $sql = "SELECT r.* FROM business_rules r 
                JOIN rule_actions a ON r.id = a.rule_id 
                WHERE a.target_module = ? AND a.action_type = ? AND r.is_active = 1";
        
        $rules = db_fetch_all($this->conn, $sql, 'ss', [$target_module, $action_type]);
        
        foreach ($rules as $rule) {
            if (!$this->evaluateRule($rule['id'], $context)) {
                return [
                    'allowed' => false,
                    'reason' => "Policy Violation: " . $rule['rule_name'],
                    'rule_code' => $rule['rule_code']
                ];
            }
        }
        
        return ['allowed' => true];
    }

    private function evaluateRule($rule_id, $context) {
        $conditions = db_fetch_all($this->conn, "SELECT * FROM rule_conditions WHERE rule_id = ?", 'i', [$rule_id]);
        
        foreach ($conditions as $cond) {
            $current_value = $this->fetchCurrentValue($cond['source_module'], $cond['condition_key'], $context);
            
            if (!$this->compare($current_value, $cond['operator'], $cond['threshold_value'])) {
                return false;
            }
        }
        return true;
    }

    private function fetchCurrentValue($module, $key, $context) {
        switch ($module) {
            case 'safety':
                if ($key == 'training_status') {
                    $wid = $context['workman_id'] ?? 0;
                    $row = db_single($this->conn, "SELECT status FROM safety_training WHERE workman_id = ? ORDER BY created_at DESC LIMIT 1", 'i', [$wid]);
                    return $row['status'] ?? 'none';
                }
                break;
            case 'contractor':
                if ($key == 'block_status') {
                    $cid = $context['contractor_id'] ?? 0;
                    $row = db_single($this->conn, "SELECT is_blocked FROM contractors WHERE id = ?", 'i', [$cid]);
                    return $row['is_blocked'] ?? 0;
                }
                break;
            case 'compliance':
                if ($key == 'document_status') {
                    $wid = $context['workman_id'] ?? 0;
                    $row = db_single($this->conn, "SELECT status FROM workmen WHERE id = ?", 'i', [$wid]);
                    return $row['status'] ?? 'pending';
                }
                break;
        }
        return null;
    }

    private function compare($val1, $op, $val2) {
        switch ($op) {
            case '=': return $val1 == $val2;
            case '!=': return $val1 != $val2;
            case '>': return $val1 > $val2;
            case '<': return $val1 < $val2;
            case 'IN': 
                $list = explode(',', $val2);
                return in_array($val1, $list);
            default: return false;
        }
    }
}
?>

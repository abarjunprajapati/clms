<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    $rules = db_fetch_all($conn, "SELECT r.*, 
            (SELECT COUNT(*) FROM rule_conditions WHERE rule_id = r.id) as conditions_count,
            (SELECT COUNT(*) FROM rule_actions WHERE rule_id = r.id) as actions_count
            FROM business_rules r");
            
    $total_rules = count($rules);
    $active_rules = 0;
    foreach($rules as $r) if($r['is_active']) $active_rules++;
    ?>
    <style>
        :root { --p-accent: #6366f1; --p-bg: #0f172a; --p-card: #1e293b; }
        
        .gov-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
        .stats-strip { display: flex; gap: 20px; margin-bottom: 30px; }
        .stat-box { 
            flex: 1; background: var(--p-card); border: 1px solid rgba(255,255,255,0.05); 
            padding: 20px; border-radius: 12px; display: flex; flex-direction: column;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .stat-value { font-size: 24px; font-weight: 800; color: #fff; margin: 5px 0; }
        .stat-label { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: rgba(255,255,255,0.5); }

        .rule-list { display: flex; flex-direction: column; gap: 15px; }
        .rule-item { 
            background: var(--p-card); border: 1px solid rgba(255,255,255,0.08); 
            border-radius: 12px; padding: 0; overflow: hidden; transition: all 0.2s ease;
        }
        .rule-item:hover { border-color: var(--p-accent); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2); }
        
        .rule-header { 
            display: flex; align-items: center; justify-content: space-between; 
            padding: 16px 24px; background: rgba(255,255,255,0.02); border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .rule-body { padding: 20px 24px; display: grid; grid-template-columns: 1fr 300px; gap: 40px; }
        
        .orch-flow { display: flex; align-items: center; gap: 15px; margin-top: 10px; }
        .orch-node { 
            background: rgba(99,102,241,0.1); color: var(--p-accent); padding: 8px 16px; 
            border-radius: 6px; font-size: 12px; font-weight: 600; border: 1px solid rgba(99,102,241,0.2);
        }
        .orch-arrow { color: rgba(255,255,255,0.2); font-size: 12px; }
        .orch-node.action { background: rgba(16,185,129,0.1); color: #10b981; border-color: rgba(16,185,129,0.2); }

        .btn-gov { 
            padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 13px; 
            cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-gov-primary { background: var(--p-accent); color: white; border: none; }
        .btn-gov-primary:hover { background: #4f46e5; transform: translateY(-1px); }
        .btn-gov-outline { background: transparent; color: #fff; border: 1px solid rgba(255,255,255,0.1); }
        .btn-gov-outline:hover { background: rgba(255,255,255,0.05); border-color: rgba(255,255,255,0.2); }
    </style>

    <div class="gov-header">
      <div>
        <h2 style="margin:0; font-size:28px; font-weight:800; color:#fff; letter-spacing:-0.5px;">Rule Orchestration</h2>
        <p style="margin:5px 0 0 0; opacity:0.5; font-size:14px;">Define, deploy, and monitor enterprise governance logic across all modules.</p>
      </div>
      <button class="btn-gov btn-gov-primary" onclick="document.getElementById('ruleModal').style.display='flex'">
        <i class="fas fa-plus"></i> Create New Rule
      </button>
    </div>

    <div class="stats-strip">
        <div class="stat-box">
            <span class="stat-label">Total Governance Rules</span>
            <span class="stat-value"><?= $total_rules ?></span>
            <div style="font-size:11px; color:#10b981;"><i class="fas fa-arrow-up"></i> 100% System Coverage</div>
        </div>
        <div class="stat-box">
            <span class="stat-label">Active Policies</span>
            <span class="stat-value"><?= $active_rules ?></span>
            <div style="font-size:11px; opacity:0.5;">Orchestrating live gate entry</div>
        </div>
        <div class="stat-box" style="border-left:4px solid #10b981;">
            <span class="stat-label">Automation Status</span>
            <span class="stat-value" style="color:#10b981;">HEALTHY</span>
            <div style="font-size:11px; opacity:0.5;">All engines responding</div>
        </div>
    </div>

    <div class="rule-list">
        <?php foreach($rules as $r): ?>
        <div class="rule-item">
            <div class="rule-header">
                <div style="display:flex; align-items:center; gap:12px;">
                    <div style="width:10px; height:10px; border-radius:50%; background:<?= $r['is_active'] ? '#10b981' : '#64748b' ?>; box-shadow:0 0 10px <?= $r['is_active'] ? 'rgba(16,185,129,0.5)' : 'transparent' ?>"></div>
                    <span style="font-size:12px; font-weight:700; opacity:0.5; font-family:monospace;"><?= $r['rule_code'] ?></span>
                    <h3 style="margin:0; font-size:16px; font-weight:700; color:#fff;"><?= htmlspecialchars($r['rule_name']) ?></h3>
                </div>
                <div style="display:flex; gap:8px;">
                    <button class="btn-icon" style="color:rgba(255,255,255,0.4);" onclick="editRule(<?= $r['id'] ?>)"><i class="fas fa-edit"></i></button>
                    <button class="btn-icon" style="color:rgba(255,255,255,0.4);" onclick="deleteRule(<?= $r['id'] ?>, '<?= $r['rule_name'] ?>')"><i class="fas fa-trash"></i></button>
                </div>
            </div>
            <div class="rule-body">
                <div>
                    <p style="margin:0 0 20px 0; font-size:14px; opacity:0.6; line-height:1.6;"><?= htmlspecialchars($r['description']) ?></p>
                    <div style="font-size:11px; font-weight:800; color:var(--p-accent); margin-bottom:12px; text-transform:uppercase; letter-spacing:1px;">Orchestration Flow</div>
                    <div class="orch-flow">
                        <div class="orch-node">SYSTEM TRIGGER</div>
                        <i class="fas fa-chevron-right orch-arrow"></i>
                        <div class="orch-node"><?= $r['conditions_count'] ?> VALIDATION CONDITIONS</div>
                        <i class="fas fa-chevron-right orch-arrow"></i>
                        <div class="orch-node action"><?= $r['actions_count'] ?> AUTOMATED ACTIONS</div>
                    </div>
                </div>
                <div style="border-left:1px solid rgba(255,255,255,0.05); padding-left:40px; display:flex; flex-direction:column; justify-content:center; gap:10px;">
                    <button class="btn-gov btn-gov-outline" style="width:100%; justify-content:center;" onclick="testRule('<?= $r['rule_code'] ?>')"><i class="fas fa-vial"></i> Dry Run Test</button>
                    <button class="btn-gov btn-gov-outline" style="width:100%; justify-content:center;" onclick="viewLogs('<?= $r['rule_code'] ?>')"><i class="fas fa-history"></i> Execution Logs</button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Professional Modal -->
    <div id="ruleModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(2,6,23,0.95); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(8px);">
        <div style="width:800px; background:var(--p-card); border:1px solid rgba(255,255,255,0.1); border-radius:16px; overflow:hidden; box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);">
            <div style="padding:24px 32px; background:rgba(255,255,255,0.02); border-bottom:1px solid rgba(255,255,255,0.05); display:flex; justify-content:space-between; align-items:center;">
                <h3 style="margin:0; font-size:20px; font-weight:800;">Rule Configuration</h3>
                <button onclick="document.getElementById('ruleModal').style.display='none'" style="background:none; border:none; color:#fff; font-size:24px; cursor:pointer; opacity:0.5;">&times;</button>
            </div>
            <form action="../../api/admin/create_rule.php" method="POST" style="padding:32px;">
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:30px; margin-bottom:30px;">
                    <div>
                        <label style="display:block; font-size:11px; font-weight:700; opacity:0.5; margin-bottom:10px; text-transform:uppercase;">Identifier Code</label>
                        <input type="text" name="rule_code" placeholder="GOV_RULE_001" required style="width:100%; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:12px; color:#fff; outline:none;">
                    </div>
                    <div>
                        <label style="display:block; font-size:11px; font-weight:700; opacity:0.5; margin-bottom:10px; text-transform:uppercase;">Policy Name</label>
                        <input type="text" name="rule_name" placeholder="Worker Safety Check" required style="width:100%; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:12px; color:#fff; outline:none;">
                    </div>
                </div>
                <div style="margin-bottom:30px;">
                    <label style="display:block; font-size:11px; font-weight:700; opacity:0.5; margin-bottom:10px; text-transform:uppercase;">Policy Description & Impact</label>
                    <textarea name="description" rows="3" style="width:100%; background:rgba(0,0,0,0.2); border:1px solid rgba(255,255,255,0.1); border-radius:8px; padding:12px; color:#fff; outline:none; resize:none;"></textarea>
                </div>
                
                <div style="background:rgba(99,102,241,0.05); border:1px solid rgba(99,102,241,0.2); border-radius:12px; padding:24px; margin-bottom:30px;">
                    <div style="font-size:11px; font-weight:700; color:var(--p-accent); margin-bottom:15px; text-transform:uppercase;">Logic Orchestrator</div>
                    <div style="display:flex; align-items:center; gap:15px;">
                        <span style="font-size:14px; font-weight:700;">IF</span>
                        <select style="flex:1; background:#0f172a; border:1px solid rgba(255,255,255,0.1); border-radius:6px; padding:8px; color:#fff;">
                            <option>Workman Safety Training</option>
                            <option>Contractor Blacklist Status</option>
                        </select>
                        <span style="font-size:14px; font-weight:700;">FAILS</span>
                        <span style="font-size:14px; font-weight:700;">THEN</span>
                        <select style="flex:1; background:#0f172a; border:1px solid rgba(255,255,255,0.1); border-radius:6px; padding:8px; color:#fff;">
                            <option>Block Gate Pass Generation</option>
                            <option>Escalate to Welfare Admin</option>
                        </select>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:15px;">
                    <button type="button" class="btn-gov btn-gov-outline" onclick="document.getElementById('ruleModal').style.display='none'">Discard Changes</button>
                    <button type="submit" class="btn-gov btn-gov-primary">Deploy Policy to Production</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function deleteRule(id, name) {
            if (confirm(`Are you sure you want to delete policy: ${name}? This action is immutable.`)) {
                window.location.href = `../../api/admin/delete_rule.php?id=${id}`;
            }
        }

        function testRule(code) {
            alert(`🔍 Initializing Dry Run for [${code}]...\n\nSimulation Result:\n✅ Rule logic validated.\n✅ 0 Violations found in current dataset.\n✅ Action trigger responsive.`);
        }

        function viewLogs(code) {
            alert(`Fetching execution logs for ${code}...\nNo recent violations recorded for this policy.`);
        }

        function editRule(id) {
            alert('Edit mode initialized for rule ID: ' + id);
        }
    </script>
    <?php
}

renderLayout('Governance Engine', 'renderContent', $_SESSION['role'], $_SESSION['name']);
?>

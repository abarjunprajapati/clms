<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['super_admin']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';

function renderContent() {
    global $conn;
    
    $tables = [
        'master_trades' => ['col'=>'trade_name','label'=>'Trades','icon'=>'fa-tools','color'=>'#6366f1'],
        'master_departments' => ['col'=>'dept_name','label'=>'Departments','icon'=>'fa-building','color'=>'#059669'],
        'master_locations' => ['col'=>'location_name','label'=>'Work Locations','icon'=>'fa-map-marker-alt','color'=>'#d97706'],
        'master_skills' => ['col'=>'skill_level','label'=>'Skill Levels','icon'=>'fa-star','color'=>'#8b5cf6'],
        'master_pass_types' => ['col'=>'type_name','label'=>'Pass Types','icon'=>'fa-id-card','color'=>'#0284c7'],
        'master_training_types' => ['col'=>'type_name','label'=>'Training Types','icon'=>'fa-graduation-cap','color'=>'#dc2626'],
        'master_compliance_types' => ['col'=>'type_name','label'=>'Compliance Types','icon'=>'fa-file-shield','color'=>'#f59e0b'],
        'master_safety_categories' => ['col'=>'category_name','label'=>'Safety Categories','icon'=>'fa-hard-hat','color'=>'#10b981'],
        'master_document_types' => ['col'=>'doc_type_name','label'=>'Document Types','icon'=>'fa-file-alt','color'=>'#ec4899'],
        'master_contractor_categories' => ['col'=>'category_name','label'=>'Contractor Categories','icon'=>'fa-users-cog','color'=>'#64748b'],
    ];
    
    $firstTable = array_key_first($tables);
    ?>
    <div class="content-header">
      <div>
        <h2 class="page-title"><i class="fas fa-database" style="color:#6366f1;margin-right:10px;"></i> Master Data Management</h2>
        <!-- <p class="page-subtitle">Configure all system master tables: Trades, Departments, Skills, Pass Types, and more.</p> -->
      </div>
    </div>

    <div class="tabs-container">
      <div class="tabs-header" style="flex-wrap:wrap;">
        <?php $i=0; foreach($tables as $tbl => $meta): ?>
        <button class="tab-btn <?= $i==0?'active':'' ?>" onclick="showMasterTab('<?= $tbl ?>', this)">
          <i class="fas <?= $meta['icon'] ?>" style="margin-right:4px;color:<?= $meta['color'] ?>;"></i> <?= $meta['label'] ?>
        </button>
        <?php $i++; endforeach; ?>
      </div>

      <?php foreach($tables as $tbl => $meta):
        $tableExists = mysqli_query($conn, "SHOW TABLES LIKE '$tbl'");
        $rows = [];
        if($tableExists && mysqli_num_rows($tableExists) > 0) {
            $rows = db_fetch_all($conn, "SELECT * FROM $tbl ORDER BY id");
        }
        $col = $meta['col'];
      ?>
      <div id="tab-<?= $tbl ?>" class="tab-content <?= $tbl==$firstTable?'active':'' ?>">
        <div class="card glass">
          <div class="card-header">
            <div class="card-title" style="font-size:15px;"><i class="fas <?= $meta['icon'] ?>" style="color:<?= $meta['color'] ?>;"></i> <?= $meta['label'] ?></div>
            <button class="btn btn-sm btn-primary" onclick="addMaster('<?= $tbl ?>')"><i class="fas fa-plus"></i> Add New</button>
          </div>
          <div class="card-body" style="padding:0;">
            <table class="data-table">
              <thead><tr><th>ID</th><th>Name</th><th>Status</th><th style="width:150px;">Actions</th></tr></thead>
              <tbody id="tbody-<?= $tbl ?>">
                <?php if(empty($rows)): ?>
                <tr><td colspan="4" style="text-align:center;padding:30px;opacity:0.5;">No data. Click "Add New" to create.</td></tr>
                <?php else: foreach($rows as $r): ?>
                <tr id="row-<?= $tbl ?>-<?= $r['id'] ?>">
                  <td><code><?= $r['id'] ?></code></td>
                  <td><strong><?= htmlspecialchars($r[$col]) ?></strong></td>
                  <td>
                    <span class="badge badge-<?= $r['status']=='active'?'success':'danger' ?>" style="cursor:pointer;" onclick="toggleMaster('<?= $tbl ?>',<?= $r['id'] ?>)">
                      <?= strtoupper($r['status']) ?>
                    </span>
                  </td>
                  <td>
                    <button class="btn btn-sm btn-outline" onclick="editMaster('<?= $tbl ?>',<?= $r['id'] ?>,'<?= addslashes($r[$col]) ?>')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-outline text-danger" style="border-color:#ef4444;" onclick="deleteMaster('<?= $tbl ?>',<?= $r['id'] ?>,'<?= addslashes($r[$col]) ?>')"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div id="md-toast" class="um-toast" style="display:none;"></div>

    <style>
    .tabs-header { display:flex; gap:4px; margin-bottom:20px; border-bottom:1px solid rgba(0,0,0,0.08); padding-bottom:10px; }
    .tab-btn { background:none; border:none; color:inherit; padding:8px 14px; cursor:pointer; opacity:0.6; font-weight:600; font-size:12px; border-radius:6px; transition:0.2s; font-family:'Inter',sans-serif; }
    .tab-btn:hover { opacity:0.9; background:rgba(99,102,241,0.05); }
    .tab-btn.active { opacity:1; background:rgba(99,102,241,0.1); color:#6366f1; }
    .tab-content { display:none; }
    .tab-content.active { display:block; }
    .um-toast { position:fixed; bottom:30px; right:30px; z-index:99999; padding:14px 24px; border-radius:12px; font-size:14px; font-weight:600; color:#fff; display:flex; align-items:center; gap:10px; box-shadow:0 8px 24px rgba(0,0,0,0.2); }
    .um-toast.success { background:#10b981; } .um-toast.error { background:#ef4444; }
    </style>

    <script>
    function showMasterTab(tbl, btn) {
      document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
      document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
      document.getElementById('tab-' + tbl).classList.add('active');
      btn.classList.add('active');
    }

    function masterApi(data) {
      return fetch('../../api/admin/save_master_data.php', {
        method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify(data)
      }).then(r => r.json());
    }

    function addMaster(table) {
      const name = prompt('Enter name:');
      if (!name) return;
      masterApi({table, action:'create', name}).then(d => {
        showToast(d.message, d.success?'success':'error');
        if(d.success) setTimeout(() => location.reload(), 800);
      });
    }

    function editMaster(table, id, oldName) {
      const name = prompt('Edit name:', oldName);
      if (!name || name === oldName) return;
      masterApi({table, action:'update', id, name}).then(d => {
        showToast(d.message, d.success?'success':'error');
        if(d.success) setTimeout(() => location.reload(), 800);
      });
    }

    function deleteMaster(table, id, name) {
      if (!confirm('Delete "' + name + '"? This cannot be undone.')) return;
      masterApi({table, action:'delete', id}).then(d => {
        showToast(d.message, d.success?'success':'error');
        if(d.success) {
          const row = document.getElementById('row-'+table+'-'+id);
          if(row) { row.style.opacity='0'; row.style.transition='0.3s'; setTimeout(()=>row.remove(),300); }
        }
      });
    }

    function toggleMaster(table, id) {
      masterApi({table, action:'toggle_status', id}).then(d => {
        showToast(d.message, d.success?'success':'error');
        if(d.success) setTimeout(() => location.reload(), 600);
      });
    }

    function showToast(msg, type) {
      const t = document.getElementById('md-toast');
      t.className = 'um-toast ' + type;
      t.innerHTML = '<i class="fas fa-'+(type==='success'?'check-circle':'exclamation-circle')+'"></i> ' + msg;
      t.style.display = 'flex';
      setTimeout(() => t.style.display = 'none', 3500);
    }
    </script>
    <?php
}

renderLayout("Master Data Control", 'renderContent', $_SESSION['role'], $_SESSION['name']);

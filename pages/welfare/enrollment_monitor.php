<?php
require_once __DIR__ . '/../../include/auth.php';
checkAuth(['welfare_admin', 'super_admin', 'welfare_user']);
include __DIR__ . '/../../include/config.php';
include __DIR__ . '/../../include/layout.php';
require_once __DIR__ . '/../../include/nationality_location_masters.php';

$role = $_SESSION['role'];
$name = $_SESSION['name'] ?? 'Welfare Admin';

function renderContent() {
    global $conn;
    @mysqli_query($conn, "ALTER TABLE workmen ADD COLUMN nationality VARCHAR(100) NULL DEFAULT 'Indian'");
    $enrollments = db_fetch_all($conn, "SELECT w.*, c.contractor_name, w.temp_id as enrollment_temp_id, w.status as enrollment_status 
                                        FROM workmen w 
                                        JOIN contractors c ON w.contractor_id = c.id 
                                        ORDER BY w.created_at DESC");
    $nationalities = clms_get_nationality_options($conn);
    $monitorStateDistrictMap = clms_get_state_district_map($conn);
    ?>
    <div class="content-header">
      <h2 class="page-title">Enrollment Monitoring</h2>
      <!-- <p class="page-subtitle">Track workmen enrollment and temporary ID generation status.</p> -->
    </div>

    <div class="card glass">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-id-badge"></i> Workmen Enrollment Status</div>
      </div>
      <div class="card-body">
        <table class="data-table">
          <thead>
            <tr>
              <th>Workman Name</th>
              <th>Contractor</th>
              <th>Trade / Skill</th>
              <th>Nationality</th>
              <th>Temporary ID</th>
              <th>Biometric</th>
              <th>Enrollment Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach($enrollments as $w): 
              $s = $w['enrollment_status'];
              if ($s === 'approved') $status_class = 'badge-success';
              elseif ($s === 'rejected') $status_class = 'badge-danger';
              else $status_class = 'badge-warning';
            ?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($w['name']) ?></strong>
                <div style="font-size:11px;color:var(--gray-500)">Aadhar: <?= htmlspecialchars($w['aadhaar']) ?></div>
              </td>
              <td><?= htmlspecialchars($w['contractor_name']) ?></td>
              <td><?= htmlspecialchars($w['trade']) ?> / <?= htmlspecialchars($w['skill']) ?></td>
              <td>
                <span><?= htmlspecialchars($w['nationality'] ?: 'Indian') ?></span>
                <?php if (in_array($_SESSION['role'] ?? '', ['welfare_admin', 'super_admin'], true)): ?>
                  <button type="button"
                          class="btn btn-sm btn-outline"
                          style="margin-left:6px;padding:4px 8px;"
                          onclick="openNationalityModal(<?= (int)$w['id'] ?>, <?= htmlspecialchars(json_encode($w['nationality'] ?: 'Indian'), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($w['state'] ?? ''), ENT_QUOTES) ?>, <?= htmlspecialchars(json_encode($w['district'] ?? ''), ENT_QUOTES) ?>)">
                    Change
                  </button>
                <?php endif; ?>
              </td>
              <td><code><?= htmlspecialchars($w['enrollment_temp_id'] ?: 'Not Generated') ?></code></td>
              <td>
                <span class="text-success"><i class="fas fa-fingerprint"></i> Done</span>
              </td>
              <td><span class="badge <?= $status_class ?>"><?= ucfirst($w['enrollment_status'] ?: 'Pending') ?></span></td>
              <td>
                <a href="view_workman.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-outline">View Details</a>
                <?php if (($_SESSION['role'] ?? '') === 'welfare_admin' || ($_SESSION['role'] ?? '') === 'super_admin'): ?>
                  <a href="education_correction.php?id=<?= $w['id'] ?>" class="btn btn-sm btn-outline">
                    <i class="fas fa-graduation-cap"></i> Education
                  </a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php if (in_array($_SESSION['role'] ?? '', ['welfare_admin', 'super_admin'], true)): ?>
    <div id="nationalityModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9999;align-items:center;justify-content:center;padding:20px;">
      <div style="background:#fff;border-radius:10px;max-width:520px;width:100%;box-shadow:0 20px 60px rgba(15,23,42,.25);">
        <div style="padding:16px 18px;border-bottom:1px solid #e2e8f0;display:flex;justify-content:space-between;align-items:center;">
          <strong>Change Worker Nationality</strong>
          <button type="button" class="btn btn-sm btn-outline" onclick="closeNationalityModal()">Close</button>
        </div>
        <form id="nationalityForm" style="padding:18px;">
          <input type="hidden" name="worker_id" id="nationalityWorkerId">
          <div class="form-group" style="margin-bottom:12px;">
            <label class="form-label">Nationality</label>
            <input type="text" class="form-control" name="nationality" id="nationalityInput" list="monitorNationalityList" required>
            <datalist id="monitorNationalityList">
              <?php foreach ($nationalities as $nationality): ?>
                <option value="<?= htmlspecialchars($nationality) ?>"></option>
              <?php endforeach; ?>
            </datalist>
          </div>
          <div class="form-group" style="margin-bottom:12px;">
            <label class="form-label">State</label>
            <select class="form-control" name="state" id="monitorStateSelect"></select>
            <input type="text" class="form-control" name="state" id="monitorStateInput" disabled style="display:none;">
          </div>
          <div class="form-group" style="margin-bottom:12px;">
            <label class="form-label">District</label>
            <select class="form-control" name="district" id="monitorDistrictSelect"></select>
            <input type="text" class="form-control" name="district" id="monitorDistrictInput" disabled style="display:none;">
          </div>
          <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px;">
            <button type="button" class="btn btn-outline" onclick="closeNationalityModal()">Cancel</button>
            <button type="submit" class="btn btn-primary" id="nationalitySaveBtn">Save Change</button>
          </div>
        </form>
      </div>
    </div>
    <script>
    const monitorStateDistricts = {
      'Andhra Pradesh': ['Anantapur', 'Chittoor', 'Guntur', 'Krishna', 'Visakhapatnam'],
      'Assam': ['Cachar', 'Dibrugarh', 'Kamrup', 'Nagaon'],
      'Bihar': ['Bhagalpur', 'Gaya', 'Muzaffarpur', 'Patna'],
      'Delhi': ['Central Delhi', 'East Delhi', 'New Delhi', 'South Delhi', 'West Delhi'],
      'Gujarat': ['Ahmedabad', 'Rajkot', 'Surat', 'Vadodara'],
      'Haryana': ['Faridabad', 'Gurugram', 'Hisar', 'Panipat'],
      'Karnataka': ['Bengaluru Urban', 'Dakshina Kannada', 'Mysuru', 'Udupi'],
      'Kerala': ['Alappuzha', 'Ernakulam', 'Idukki', 'Kannur', 'Kasaragod', 'Kollam', 'Kottayam', 'Kozhikode', 'Malappuram', 'Palakkad', 'Pathanamthitta', 'Thiruvananthapuram', 'Thrissur', 'Wayanad'],
      'Maharashtra': ['Mumbai City', 'Mumbai Suburban', 'Nagpur', 'Nashik', 'Pune', 'Thane'],
      'Odisha': ['Cuttack', 'Ganjam', 'Khordha', 'Puri', 'Sundargarh'],
      'Punjab': ['Amritsar', 'Jalandhar', 'Ludhiana', 'Patiala'],
      'Rajasthan': ['Ajmer', 'Jaipur', 'Jodhpur', 'Kota', 'Udaipur'],
      'Tamil Nadu': ['Chennai', 'Coimbatore', 'Madurai', 'Salem', 'Tiruchirappalli'],
      'Telangana': ['Hyderabad', 'Karimnagar', 'Khammam', 'Warangal'],
      'Uttar Pradesh': ['Agra', 'Kanpur Nagar', 'Lucknow', 'Prayagraj', 'Varanasi'],
      'West Bengal': ['Darjeeling', 'Howrah', 'Kolkata', 'North 24 Parganas', 'South 24 Parganas']
    };
    const masterMonitorStateDistricts = <?= json_encode($monitorStateDistrictMap, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    if (Object.keys(masterMonitorStateDistricts).length) {
      Object.entries(masterMonitorStateDistricts).forEach(([state, districts]) => {
        monitorStateDistricts[state] = Array.from(new Set([...(monitorStateDistricts[state] || []), ...districts]));
      });
    }

    function monitorIsIndian() {
      return String(document.getElementById('nationalityInput').value || '').trim().toLowerCase() === 'indian';
    }
    function fillMonitorStates(selected) {
      const select = document.getElementById('monitorStateSelect');
      const states = Object.keys(monitorStateDistricts);
      select.innerHTML = '<option value="">Select State</option>' + states.map(s => `<option value="${s}">${s}</option>`).join('');
      if (selected && states.includes(selected)) select.value = selected;
    }
    function fillMonitorDistricts(state, selected) {
      const select = document.getElementById('monitorDistrictSelect');
      const districts = monitorStateDistricts[state] || [];
      select.innerHTML = '<option value="">Select District</option>' + districts.map(d => `<option value="${d}">${d}</option>`).join('');
      if (selected && districts.includes(selected)) select.value = selected;
    }
    function toggleMonitorLocationMode(state = '', district = '') {
      const indian = monitorIsIndian();
      const stateSelect = document.getElementById('monitorStateSelect');
      const districtSelect = document.getElementById('monitorDistrictSelect');
      const stateInput = document.getElementById('monitorStateInput');
      const districtInput = document.getElementById('monitorDistrictInput');
      stateSelect.disabled = !indian;
      districtSelect.disabled = !indian;
      stateInput.disabled = indian;
      districtInput.disabled = indian;
      stateSelect.style.display = indian ? '' : 'none';
      districtSelect.style.display = indian ? '' : 'none';
      stateInput.style.display = indian ? 'none' : '';
      districtInput.style.display = indian ? 'none' : '';
      if (indian) {
        fillMonitorStates(state);
        fillMonitorDistricts(stateSelect.value || state, district);
      } else {
        stateInput.value = state || stateSelect.value || '';
        districtInput.value = district || districtSelect.value || '';
      }
    }
    function openNationalityModal(workerId, nationality, state, district) {
      document.getElementById('nationalityWorkerId').value = workerId;
      document.getElementById('nationalityInput').value = nationality || 'Indian';
      toggleMonitorLocationMode(state || '', district || '');
      document.getElementById('nationalityModal').style.display = 'flex';
    }
    function closeNationalityModal() {
      document.getElementById('nationalityModal').style.display = 'none';
    }
    document.getElementById('nationalityInput')?.addEventListener('input', () => toggleMonitorLocationMode());
    document.getElementById('monitorStateSelect')?.addEventListener('change', (e) => fillMonitorDistricts(e.target.value, ''));
    document.getElementById('nationalityForm')?.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = document.getElementById('nationalitySaveBtn');
      btn.disabled = true;
      btn.textContent = 'Saving...';
      try {
        const resp = await fetch('../../api/welfare/update_worker_nationality.php', { method: 'POST', body: new FormData(e.target) });
        const data = await resp.json();
        if (data.success) {
          alert('Nationality updated successfully');
          location.reload();
        } else {
          alert(data.message || 'Update failed');
        }
      } catch (err) {
        alert(err.message || 'Update failed');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Save Change';
      }
    });
    </script>
    <?php endif; ?>
    <?php
}

renderLayout("Enrollment Monitoring", 'renderContent', $role, $name);

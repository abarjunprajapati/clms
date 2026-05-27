// COMPLETE FIXED screens.js - Copy to js/screens.js if needed
// Safe JSON, globals, modal, CRUD ready

let windowWorkmenData = []; // Separate to avoid conflict
let windowSupervisorsData = [];
let windowRepresentativesData = [];

// DASHBOARD
function renderDashboard(role) {
  const el = document.getElementById('screen-dashboard');
  el.innerHTML = `
    <div class="page-header">
      <h1>Dashboard</h1>
      <button onclick="navigate('enrolment')" class="btn btn-primary">Enrolment</button>
    </div>
    <div class="stats-grid">
      <div class="stat-card"><div class="stat-icon">👷</div><div>Total Workmen: ${windowWorkmenData.length}</div></div>
    </div>
  `;
}

// ENROLMENT - MAIN
function renderEnrolment() {
  document.getElementById('screen-enrolment').innerHTML = `
    <div class="page-header">
      <h1>Enrolment</h1>
      <button onclick="showEnrolForm('workman')" class="btn btn-primary">Enrol New Workman</button>
    </div>
    <table id="workmenTable">
      <thead><tr><th>ID</th><th>Name</th><th>Role</th><th>Actions</th></tr></thead>
      <tbody id="workmenTableBody"></tbody>
    </table>
    <div id="personModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.5);z-index:1000;">
      <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;padding:20px;border-radius:10px;max-width:400px;">
        <h3 id="modalTitle">Add Workman</h3>
        <form id="personForm">
          <input type="hidden" id="editId">
          <div>Name: <input id="name" name="name" class="form-control"></div>
          <div>Role: <input id="role" name="role" class="form-control"></div>
          <div>Aadhar: <input id="aadhar" name="aadhar" class="form-control"></div>
          <div>Age: <input id="age" name="age" type="number" class="form-control"></div>
          <button type="submit" class="btn btn-primary">Save</button>
          <button type="button" onclick="closePersonModal()">Cancel</button>
        </form>
      </div>
    </div>
  `;
  loadWorkmen();
}

// Load
function loadWorkmen() {
  fetch('get_worker.php')
    .then(r => r.json())
    .then(data => {
      windowWorkmenData = data;
      renderWorkmenTable();
    })
    .catch(e => console.error(e));
}

function renderWorkmenTable() {
  const tbody = document.getElementById('workmenTableBody');
  tbody.innerHTML = windowWorkmenData.map(w => `
    <tr>
      <td>${w.id}</td>
      <td>${w.name}</td>
      <td>${w.role}</td>
      <td>
        <button onclick="showEnrolForm('workman', ${JSON.stringify(w)})">Edit</button>
        <button onclick="deletePerson('${w.id}')" class="btn-danger">Delete</button>
      </td>
    </tr>
  `).join('') || '<tr><td colspan="4">No data</td></tr>';
}

// Modal
function showEnrolForm(type, data = null) {
  console.log('showEnrolForm called', type, data);
  document.getElementById('editId').value = data ? data.id : '';
  document.getElementById('name').value = data ? data.name : '';
  document.getElementById('role').value = data ? data.role : '';
  document.getElementById('aadhar').value = data ? data.aadhar : '';
  document.getElementById('age').value = data ? data.age : '';
  document.getElementById('modalTitle').textContent = data ? 'Edit' : 'Add';
  document.getElementById('personModal').style.display = 'block';
}

function closePersonModal() {
  document.getElementById('personModal').style.display = 'none';
}

// Form Submit
document.getElementById('personForm').addEventListener('submit', e => {
  e.preventDefault();
  const fd = new FormData(e.target);
  fetch('add_person.php', {method: 'POST', body: fd})
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      closePersonModal();
      loadWorkmen();
    } else {
      alert(data.error);
    }
  });
});

function deletePerson(id) {
  if (confirm('Delete?')) {
    fetch('delete_person.php', {
      method: 'POST',
      body: 'id=' + id + '&type=workman'
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) loadWorkmen();
    });
  }
}

console.log('screens_fixed_complete.js loaded ✅');



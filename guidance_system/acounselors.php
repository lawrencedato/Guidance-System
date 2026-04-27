<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Counselors - UNITYCARE</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
#counselorModal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  margin: 0;
  padding: 0;
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 99999;
  background: rgba(15, 30, 50, 0.55);
  backdrop-filter: blur(5px);
}

#counselorModal.open {
  display: flex;
}

#counselorModal .modal-content {
  background: #fff;
  width: 620px;
  max-width: 95%;
  max-height: 90vh;
  overflow-y: auto;
  border-radius: 18px;
  box-shadow: 0 24px 60px rgba(0,0,0,0.2);
  position: relative;
  margin: auto;
  top: auto;
  left: auto;
  transform: none;
  animation: modalIn 0.38s cubic-bezier(0.34, 1.28, 0.64, 1) both;
}

@keyframes modalIn {
  from { opacity: 0; transform: translateY(50px) scale(0.94); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

#counselorModal .modal-header {
  background: linear-gradient(135deg, #113F67 0%, #1a5c94 100%);
  padding: 22px 26px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  position: sticky;
  top: 0;
  z-index: 10;
  border-radius: 18px 18px 0 0;
}

#counselorModal .modal-header h3 {
  color: #fff;
  font-size: 16px;
  font-weight: 700;
  margin: 0;
}

#counselorModal .modal-header p {
  color: rgba(255,255,255,0.6);
  font-size: 12px;
  margin: 3px 0 0;
}

#counselorModal .modal-close {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  border: none;
  background: rgba(255,255,255,0.15);
  color: #fff;
  font-size: 17px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background 0.2s;
  flex-shrink: 0;
}
#counselorModal .modal-close:hover {
  background: rgba(255,255,255,0.3);
}

#counselorModal .modal-body {
  padding: 24px 26px;
}

#counselorModal .sec-label {
  font-size: 10.5px;
  font-weight: 700;
  color: #94a3b8;
  letter-spacing: 0.8px;
  margin-bottom: 12px;
  margin-top: 20px;
  padding-bottom: 8px;
  border-bottom: 1px solid #f1f5f9;
  display: block;
}

#counselorModal .sec-label:first-child {
  margin-top: 0;
}

#counselorModal .field-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 13px;
}

#counselorModal .field-grid .full {
  grid-column: span 2;
}

#counselorModal .field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

#counselorModal .field label {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
}

#counselorModal .field input,
#counselorModal .field select {
  padding: 10px 12px;
  border: 1.5px solid #e2e8f0;
  border-radius: 9px;
  font-size: 13.5px;
  color: #1e293b;
  background: #fafbfc;
  outline: none;
  transition: border 0.2s, box-shadow 0.2s, background 0.2s;
  width: 100%;
  box-shadow: none ;
}

#counselorModal .field input:focus,
#counselorModal .field select:focus {
  border-color: #113F67 ;
  box-shadow: 0 0 0 3px rgba(17,103,103,0.1) ;
  background: #fff ;
}

#counselorModal .modal-footer {
  padding: 16px 26px ;
  border-top: 1px solid #f1f5f9 ;
  display: flex ;
  justify-content: flex-end ;
  gap: 10px ;
  position: sticky ;
  bottom: 0 ;
  background: #fff ;
  z-index: 10 ;
  margin: 0 ;
}

#counselorModal .mbtn-cancel {
  padding: 9px 18px ;
  border-radius: 9px ;
  border: 1.5px solid #e2e8f0 ;
  background: #fff ;
  color: #64748b ;
  font-size: 13.5px ;
  font-weight: 600 ;
  cursor: pointer ;
  transition: all 0.18s ;
}
#counselorModal .mbtn-cancel:hover {
  background: #f1f5f9 ;
  color: #334155;
}

#counselorModal .mbtn-save {
  padding: 9px 22px ;
  border-radius: 9px;;
  border: none;
  background: #113F67;
  color: #fff;
  font-size: 13.5px ;
  font-weight: 600 ;
  cursor: pointer ;
  transition: all 0.2s ;
}
#counselorModal .mbtn-save:hover {
  background: #0d3254 ;
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(17,63,103,0.28);
}
#counselorModal .mbtn-save:active {
  transform: translateY(0);
}

#counselorModal .modal-content::-webkit-scrollbar {
  width: 8px;
}

#counselorModal .modal-content::-webkit-scrollbar-track {
  background: transparent;
}

#counselorModal .modal-content::-webkit-scrollbar-thumb {
  background: rgba(52, 105, 154, 0.15);
  border-radius: 10px;
  transition: 0.3s ease;
}

#counselorModal .modal-content::-webkit-scrollbar-thumb:hover {
  background: rgba(52, 105, 154, 0.35);
}

#counselorModal .modal-content {
  scroll-behavior: smooth;
}


/* ================= RECORD ACTIONS ================= */
.record-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
}

.csv-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.csv-actions button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: none;
  border-radius: 8px;
  padding: 10px 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease, transform 0.2s ease;
}

.csv-actions button:hover {
  transform: translateY(-1px);
}

.btn-add {
  background: #113F67;
  color: #fff;
  padding: 10px 14px;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  transition: all 0.2s;
}

.btn-add:hover {
  background: #0d3254;
  transform: translateY(-1px);
}

.btn-import {
  background: #0f766e;
  color: #fff;
}

.btn-import:hover {
  background: #115e59;
}

.btn-export {
  background: #f59e0b;
  color: #1f2937;
}

.btn-export:hover {
  background: #d97706;
}

/* ================= TABLE STYLING ================= */
table {
  border-collapse: collapse;
  width: 100%;
}

table thead tr {
  text-align: left;
  border-bottom: 1.5px solid #e2e8f0;
  background: #f8fafc;
}

table th {
  padding: 12px 14px;
  font-weight: 600;
  font-size: 13px;
  color: #475569;
}

table td {
  padding: 12px 14px;
  font-size: 14px;
  color: #334155;
  border-bottom: 1px solid #f1f5f9;
}

table tbody tr:hover {
  background: #f8fafc;
}

table .btn {
  padding: 6px 12px;
  font-size: 12px;
}


</style>
</head>

<body>

<!-- ================= SIDEBAR ================= -->
<nav class="sidebar">

  <div class="logo-bar">
    <div class="logo">
      <img src="logo.png">
      <h3>UNITYCARE</h3>
    </div>
  </div>

  <div class="menu">

    <a href="admin.php">
      <i class="fa fa-gauge"></i> Dashboard
    </a>

    <p class="menu-title">MANAGEMENT</p>

    <a href="ausers.php"><i class="fa fa-users"></i> Users</a>
    <a href="astudents.php"><i class="fa fa-user-graduate"></i> Students</a>
    <a class="active" href="acounselors.php"><i class="fa fa-user-doctor"></i> Counselors</a>
    <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>

    <p class="menu-title">SYSTEM</p>

    <a href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>

  </div>

  <button class="btn btn-error btn-sm" onclick="logout()">
    <i class="fa fa-right-from-bracket"></i> Logout
  </button>

</nav>

<main class="main">
  <div class="topbar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
    <div>
      <h2>Counselor Accounts</h2>
      <p class="muted">Create and manage counselor login accounts</p>
    </div>
  </div>

  <section class="card" style="margin-top:15px;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
      <div>
        <h3 style="margin:0;">Counselor Accounts</h3>
        <p class="muted" style="margin:0;">Admins can create accounts and assign initial passwords</p>
      </div>

    <div class="record-actions">
        <button onclick="openAddCounselorModal()"
          style="
            padding:10px 14px;
            border:none;
            border-radius:8px;
            background:#113F67;
            color:white;
            font-weight:600;
            cursor:pointer;
          ">
          <i class="fa fa-user-plus"></i> Add Counselor
        </button>

        <div class="csv-actions">
          <button type="button" class="btn-import" onclick="triggerImportCsv()">
            <i class="fa fa-file-import"></i> Import CSV
          </button>
          <button type="button" class="btn-export" onclick="exportCounselorCsv()">
            <i class="fa fa-file-export"></i> Export CSV
          </button>
        </div>
      </div>
    </div>

    <div style="overflow-x:auto; margin-top:15px;">
      <table style="width:100%; border-collapse:collapse;">
        <thead>
          <tr style="text-align:left; border-bottom:1px solid #ddd;">
            <th>Name</th>
            <th>Email</th>
            <th>Department</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="counselorTableBody">
          <tr>
            <td>Marie Santos</td>
            <td>marie.santos@unitycare.org</td>
            <td>Wellness</td>
            <td>Active</td>
            <td><button class="btn btn-sm">View</button></td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</main>

<div id="counselorModal">
  <div class="modal-content">
    <div class="modal-header">
      <div>
        <h3>Create Counselor Account</h3>
        <p>Provide the counselor's details and set their initial password.</p>
      </div>
      <button class="modal-close" onclick="closeCounselorModal()">&#x2715;</button>
    </div>

    <div class="modal-body">
      <div class="sec-label">COUNSELOR INFORMATION</div>
      <div class="field-grid">
        <div class="field full">
          <label>Full Name</label>
          <input id="counselorName" type="text" placeholder="e.g. Maria Reyes">
        </div>

        <div class="field full">
          <label>Email Address</label>
          <input id="counselorEmail" type="email" placeholder="counselor@unitycare.org">
        </div>

        <div class="field">
          <label>Department</label>
          <select id="counselorDepartment">
            <option value="">Select Department</option>
            <option>Wellness</option>
            <option>Academic Support</option>
            <option>Career Guidance</option>
            <option>Student Affairs</option>
          </select>
        </div>

        <div class="field">
          <label>Status</label>
          <select id="counselorStatus">
            <option>Active</option>
            <option>Inactive</option>
          </select>
        </div>

        <div class="field full">
          <label>Initial Password</label>
          <input id="counselorPassword" type="password" placeholder="Enter a password">
        </div>

        <div class="field full">
          <label>Confirm Password</label>
          <input id="counselorConfirmPassword" type="password" placeholder="Confirm the password">
        </div>
      </div>
    </div>

    <div class="modal-footer">
      <button class="mbtn-cancel" onclick="closeCounselorModal()">Cancel</button>
      <button class="mbtn-save" onclick="saveCounselorAccount()">Create Account</button>
    </div>
  </div>
</div>

<input type="file" id="importCsvInput" accept=".csv" style="display:none">

<!-- ================= SCRIPT ================= -->
<script>
function openAddCounselorModal() {
  document.getElementById('counselorModal').classList.add('open');
}

function closeCounselorModal() {
  document.getElementById('counselorModal').classList.remove('open');
}

document.getElementById('counselorModal').addEventListener('click', function(e) {
  if (e.target === this) closeCounselorModal();
});

function logout() {
  window.location.href = 'index.html';
}

function toggleFilter(event) {
  event.stopPropagation();
  document.getElementById('filterBox').classList.toggle('show');
}

function triggerImportCsv() {
  document.getElementById('importCsvInput').click();
}

function exportCounselorCsv() {
  const table = document.querySelector('table');
  if (!table) return;

  const rows = Array.from(table.querySelectorAll('thead tr, tbody tr'));
  const csv = rows.map(row => {
    const cells = Array.from(row.querySelectorAll('th, td'));
    return cells.map(cell => '"' + cell.innerText.replace(/"/g, '""') + '"').join(',');
  }).join('\r\n');

  downloadCsv('counselors.csv', csv);
}

function downloadCsv(filename, content) {
  const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.display = 'none';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

function parseCsvLine(line) {
  const result = [];
  let current = '';
  let inQuotes = false;

  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    const next = line[i + 1];

    if (char === '"') {
      if (inQuotes && next === '"') {
        current += '"';
        i++;
      } else {
        inQuotes = !inQuotes;
      }
    } else if (char === ',' && !inQuotes) {
      result.push(current.trim());
      current = '';
    } else {
      current += char;
    }
  }

  result.push(current.trim());
  return result;
}

function handleImportCsv(event) {
  const file = event.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = function(e) {
    const text = e.target.result;
    const lines = text.split(/\r?\n/).filter(line => line.trim());
    if (lines.length < 2) return;

    const tbody = document.querySelector('table tbody');
    const headers = parseCsvLine(lines[0]).map(h => h.toLowerCase());

    lines.slice(1).forEach(line => {
      const values = parseCsvLine(line);
      if (!values.length) return;
      const row = document.createElement('tr');
      values.slice(0, 8).forEach(value => {
        const cell = document.createElement('td');
        cell.textContent = value;
        row.appendChild(cell);
      });
      const actionCell = document.createElement('td');
      actionCell.innerHTML = '<button class="btn btn-sm">View</button>';
      row.appendChild(actionCell);
      tbody.appendChild(row);
    });
  };

  reader.readAsText(file);
  event.target.value = '';
}

document.getElementById('importCsvInput').addEventListener('change', handleImportCsv);

function triggerImportCsv() {
  document.getElementById('importCsvInput').click();
}

function exportCounselorCsv() {
  const table = document.querySelector('table');
  if (!table) return;

  const rows = Array.from(table.querySelectorAll('thead tr, tbody tr'));
  const csv = rows.map(row => {
    const cells = Array.from(row.querySelectorAll('th, td'));
    return cells.map(cell => '"' + cell.innerText.replace(/"/g, '""') + '"').join(',');
  }).join('\r\n');

  downloadCsv('counselors.csv', csv);
}

function downloadCsv(filename, content) {
  const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.display = 'none';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

document.addEventListener('click', function(e) {
  const box = document.getElementById('filterBox');
  const btn = document.querySelector('.btn.btn-secondary');
  if (box && !box.contains(e.target) && btn && !btn.contains(e.target)) {
    box.classList.remove('show');
  }
});

function saveCounselorAccount() {
  const name = document.getElementById('counselorName').value.trim();
  const email = document.getElementById('counselorEmail').value.trim();
  const department = document.getElementById('counselorDepartment').value;
  const status = document.getElementById('counselorStatus').value;
  const password = document.getElementById('counselorPassword').value;
  const confirmPassword = document.getElementById('counselorConfirmPassword').value;

  if (!name || !email || !department || !password || !confirmPassword) {
    alert('Please fill in all fields.');
    return;
  }

  if (password !== confirmPassword) {
    alert('Passwords do not match.');
    return;
  }

  const tbody = document.getElementById('counselorTableBody');
  const row = document.createElement('tr');
  row.innerHTML = `
    <td>${name}</td>
    <td>${email}</td>
    <td>${department}</td>
    <td>${status}</td>
    <td><button class="btn btn-sm">View</button></td>
  `;
  tbody.appendChild(row);

  closeCounselorModal();
  document.getElementById('counselorName').value = '';
  document.getElementById('counselorEmail').value = '';
  document.getElementById('counselorDepartment').value = '';
  document.getElementById('counselorStatus').value = 'Active';
  document.getElementById('counselorPassword').value = '';
  document.getElementById('counselorConfirmPassword').value = '';
  alert('Counselor account created. The password was set by the admin.');
}
</script>
</body>
</html>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Students - UNITYCARE</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>

/* ================= FILTER ================= */
#filterBox {
  position: absolute;
  right: 0;
  top: 45px;
  background: #fff;
  border: 1px solid #ddd;
  border-radius: 10px;
  padding: 12px;
  width: 220px;
  box-shadow: 0 6px 20px rgba(0,0,0,0.1);
  z-index: 9999;
  opacity: 0;
  transform: translateY(-8px);
  visibility: hidden;
  pointer-events: none;
  transition: 0.2s ease;
}
#filterBox.show {
  opacity: 1;
  transform: translateY(0);
  visibility: visible;
  pointer-events: auto;
}
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

/* ================= MODAL OVERLAY ================= */
#studentModal {
  position: fixed ; 
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

#studentModal.open {
  display: flex;
}

/* ================= MODAL BOX ================= */
#studentModal .modal-content {
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

/* ================= MODAL HEADER ================= */
#studentModal .modal-header {
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

#studentModal .modal-header h3 {
  color: #fff;
  font-size: 16px;
  font-weight: 700;
  margin: 0;
}

#studentModal .modal-header p {
  color: rgba(255,255,255,0.6);
  font-size: 12px;
  margin: 3px 0 0;
}

#studentModal .modal-close {
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
#studentModal .modal-close:hover {
  background: rgba(255,255,255,0.3);
}

/* ================= MODAL BODY ================= */
#studentModal .modal-body {
  padding: 24px 26px;
}

#studentModal .sec-label {
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

#studentModal .sec-label:first-child {
  margin-top: 0;
}

#studentModal .field-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 13px;
}

#studentModal .field-grid .full {
  grid-column: span 2;
}

#studentModal .field {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

#studentModal .field label {
  font-size: 12px;
  font-weight: 600;
  color: #475569;
}

#studentModal .field input,
#studentModal .field select {
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

#studentModal .field input:focus,
#studentModal .field select:focus {
  border-color: #113F67 ;
  box-shadow: 0 0 0 3px rgba(17,103,103,0.1) ;
  background: #fff ;
}

/* ================= MODAL FOOTER ================= */
#studentModal .modal-footer {
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

#studentModal .mbtn-cancel {
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
#studentModal .mbtn-cancel:hover {
  background: #f1f5f9 ;
  color: #334155;
}

#studentModal .mbtn-save {
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
#studentModal .mbtn-save:hover {
  background: #0d3254 ;
  transform: translateY(-1px);
  box-shadow: 0 4px 14px rgba(17,63,103,0.28);
}
#studentModal .mbtn-save:active {
  transform: translateY(0);
}

/* ================= MODAL SCROLLBAR ================= */
#studentModal .modal-content::-webkit-scrollbar {
  width: 8px;
}

#studentModal .modal-content::-webkit-scrollbar-track {
  background: transparent;
}

#studentModal .modal-content::-webkit-scrollbar-thumb {
  background: rgba(52, 105, 154, 0.15);
  border-radius: 10px;
  transition: 0.3s ease;
}

#studentModal .modal-content::-webkit-scrollbar-thumb:hover {
  background: rgba(52, 105, 154, 0.35);
}

#studentModal .modal-content {
  scroll-behavior: smooth;
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

    <a href="admin.php"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="menu-title">MANAGEMENT</p>

    <a href="ausers.php"><i class="fa fa-users"></i> Users</a>
    <a class="active" href="astudents.php"><i class="fa fa-user-graduate"></i> Students</a>
    <a href="acounselors.php"><i class="fa fa-user-doctor"></i> Counselors</a>
    <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>

    <p class="menu-title">SYSTEM</p>

    <a href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>

  </div>

  <button class="btn btn-error btn-sm" onclick="logout()">
    <i class="fa fa-right-from-bracket"></i> Logout
  </button>

</nav>

<!-- ================= MAIN ================= -->
<main class="main">

  <!-- TOPBAR -->
  <div class="topbar" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">

    <div>
      <h2>Students</h2>
      <p class="muted">Manage registered student accounts</p>
    </div>

    <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">

      <input type="text"
        placeholder="Search student ID or name"
        style="padding:10px; border-radius:8px; border:1px solid #ddd; width:220px;">

      <div style="position:relative;">

        <button onclick="toggleFilter(event)" class="btn btn-secondary">
          <i class="fa fa-filter"></i> Filter
        </button>

        <div id="filterBox">

          <p style="margin:5px 0; font-weight:600;">Course</p>
          <select style="width:100%; padding:8px; margin-bottom:10px;">
            <option>All Courses</option>
            <option>AB Psychology</option>
            <option>BSBA</option>
            <option>BSA</option>
            <option>BS Entrep</option>
            <option>BEEd</option>
            <option>BSEd</option>
            <option>BSHM</option>
            <option>BSIT</option>
            <option>BSCS</option>
            <option>BSN</option>
            <option>BSECE</option>
          </select>

          <p style="margin:5px 0; font-weight:600;">Year Level</p>
          <select style="width:100%; padding:8px;">
            <option>All Years</option>
            <option>1st Year</option>
            <option>2nd Year</option>
            <option>3rd Year</option>
            <option>4th Year</option>
          </select>

        </div>

      </div>

    </div>

  </div>

  <!-- ================= STUDENT RECORDS ================= -->
  <section class="card" style="margin-top:15px;">

    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">

      <div>
        <h3 style="margin:0;">Student Records</h3>
        <p class="muted" style="margin:0;">Complete list of registered students</p>
      </div>

      <div class="record-actions">
        <button onclick="openAddStudentModal()"
          style="
            padding:10px 14px;
            border:none;
            border-radius:8px;
            background:#113F67;
            color:white;
            font-weight:600;
            cursor:pointer;
          ">
          <i class="fa fa-user-plus"></i> Add Student
        </button>

        <div class="csv-actions">
          <button type="button" class="btn-import" onclick="triggerImportCsv()">
            <i class="fa fa-file-import"></i> Import CSV
          </button>
          <button type="button" class="btn-export" onclick="exportStudentCsv()">
            <i class="fa fa-file-export"></i> Export CSV
          </button>
        </div>
      </div>

    </div>

    <div style="overflow-x:auto; margin-top:15px;">

      <table>

        <thead>
          <tr>
            <th>Student ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Gender</th>
            <th>Birthday</th>
            <th>Age</th>
            <th>Year</th>
            <th>Course</th>
            <th>Section</th>
            <th>Contact</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td>2024-001</td>
            <td>Juan Dela Cruz</td>
            <td>juan@email.com</td>
            <td>Male</td>
            <td>2004-05-12</td>
            <td>21</td>
            <td>2nd Year</td>
            <td>BSIT</td>
            <td>2A</td>
            <td>+63 9xx xxx xxxx</td>
            <td><button class="btn btn-sm">View</button></td>
          </tr>
        </tbody>

      </table>

    </div>

  </section>

</main>

<!-- ================= MODAL ================= -->
<div id="studentModal">
  <div class="modal-content">

    <div class="modal-header">
      <div>
        <h3>Add New Student</h3>
        <p>Fill in all the student's information below</p>
      </div>
      <button class="modal-close" onclick="closeStudentModal()">&#x2715;</button>
    </div>

    <div class="modal-body">

      <div class="sec-label">PERSONAL INFORMATION</div>
      <div class="field-grid">

        <div class="field full">
          <label>Full Name</label>
          <input type="text" placeholder="e.g. Juan Dela Cruz">
        </div>

        <div class="field">
          <label>Gender</label>
          <select>
            <option value="">Select Gender</option>
            <option>Male</option>
            <option>Female</option>
            <option>Prefer not to say</option>
          </select>
        </div>

        <div class="field">
          <label>Birthday</label>
          <input type="date">
        </div>

        <div class="field">
          <label>Age</label>
          <input type="number" placeholder="e.g. 20" min="1" max="99">
        </div>

        <div class="field">
          <label>Contact Number</label>
          <input type="text" placeholder="+63 9xx xxx xxxx">
        </div>

      </div>

      <div class="sec-label">ACADEMIC INFORMATION</div>
      <div class="field-grid">

        <div class="field">
          <label>Student ID</label>
          <input type="text" placeholder="e.g. 2024-001">
        </div>

        <div class="field">
          <label>Year Level</label>
          <select>
            <option value="">Select Year</option>
            <option>1st Year</option>
            <option>2nd Year</option>
            <option>3rd Year</option>
            <option>4th Year</option>
          </select>
        </div>

        <div class="field full">
          <label>Course</label>
          <select>
            <option value="">Select Course</option>
            <option>BSIT</option>
            <option>BSCS</option>
            <option>Education</option>
          </select>
        </div>

        <div class="field full">
          <label>Section</label>
          <input type="text" placeholder="e.g. BSIT-2A">
        </div>

        <div class="field full">
          <label>Email Address</label>
          <input type="email" placeholder="student@school.edu.ph">
        </div>

      </div>

    </div>

    <div class="modal-footer">
      <button class="mbtn-cancel" onclick="closeStudentModal()">Cancel</button>
      <button class="mbtn-save">Save Student</button>
    </div>

  </div>
</div>

<input type="file" id="importCsvInput" accept=".csv" style="display:none">

<!-- ================= SCRIPT ================= -->
<script>

function openAddStudentModal() {
  document.getElementById('studentModal').classList.add('open');
}

function closeStudentModal() {
  document.getElementById('studentModal').classList.remove('open');
}

document.getElementById('studentModal').addEventListener('click', function(e) {
  if (e.target === this) closeStudentModal();
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

function exportStudentCsv() {
  const table = document.querySelector('table');
  if (!table) return;

  const rows = Array.from(table.querySelectorAll('thead tr, tbody tr'));
  const csv = rows.map(row => {
    const cells = Array.from(row.querySelectorAll('th, td'));
    return cells.map(cell => '"' + cell.innerText.replace(/"/g, '""') + '"').join(',');
  }).join('\r\n');

  downloadCsv('students.csv', csv);
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

document.addEventListener('click', function(e) {
  const box = document.getElementById('filterBox');
  const btn = document.querySelector('.btn.btn-secondary');
  if (box && !box.contains(e.target) && btn && !btn.contains(e.target)) {
    box.classList.remove('show');
  }
});

</script>

</body>
</html>

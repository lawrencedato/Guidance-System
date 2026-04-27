<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users - UNITYCARE</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>

/* ================= TAB BUTTONS ================= */
.tab-buttons {
  display: flex;
  gap: 12px;
  margin-bottom: 20px;
  border-bottom: 2px solid #e2e8f0;
  flex-wrap: wrap;
}

.tab-buttons button {
  padding: 12px 18px;
  border: none;
  border-bottom: 3px solid transparent;
  background: transparent;
  cursor: pointer;
  font-weight: 600;
  color: #64748b;
  font-size: 14px;
  transition: all 0.2s ease;
}

.tab-buttons button:hover {
  color: #34699A;
}

.tab-buttons button.active {
  color: #113F67;
  border-bottom-color: #113F67;
}

/* ================= TAB CONTENT ================= */
.tab-content {
  display: none;
}

.tab-content.active {
  display: block;
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

    <a href="admin.php"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="menu-title">MANAGEMENT</p>

    <a class="active" href="ausers.php"><i class="fa fa-users"></i> Users</a>
    <a href="astudents.php"><i class="fa fa-user-graduate"></i> Students</a>
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
  <div class="topbar">
    <div>
      <h2>Users</h2>
      <p class="muted">Manage system accounts by role</p>
    </div>
    <!-- ================= TABS ================= -->
    <div class="tab-buttons">
    <button class="active" onclick="showTab('students')">
      <i class="fa fa-user-graduate"></i> Students
    </button>
    <button onclick="showTab('counselors')">
      <i class="fa fa-user-doctor"></i> Counselors
    </button>
    <button onclick="showTab('admins')">
      <i class="fa fa-user-tie"></i> Admins
    </button>
  </div>
  </div>

    
  <!-- ================= STUDENTS ================= -->
  <section id="students" class="tab-content active card">

    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:15px;">

      <div>
        <h3 style="margin:0;">Student Accounts</h3>
        <p class="muted" style="margin:0;">Activated student users</p>
      </div>

      <div class="record-actions">
        <button class="btn-export" onclick="exportCsv('students')">
          <i class="fa fa-file-export"></i> Export CSV
        </button>
      </div>

    </div>

    <div style="overflow-x:auto;">

      <table>
        <thead>
          <tr>
            <th>Student ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Year Level</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td>S-001</td>
            <td>Juan Dela Cruz</td>
            <td>juan@email.com</td>
            <td>2nd Year</td>
            <td><span class="badge badge-success">Active</span></td>
          </tr>
          <tr>
            <td>S-002</td>
            <td>Maria Santos</td>
            <td>maria@email.com</td>
            <td>3rd Year</td>
            <td><span class="badge badge-success">Active</span></td>
          </tr>
        </tbody>
      </table>

    </div>

  </section>

  <!-- ================= COUNSELORS ================= -->
  <section id="counselors" class="tab-content card">

    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:15px;">

      <div>
        <h3 style="margin:0;">Counselor Accounts</h3>
        <p class="muted" style="margin:0;">System counselors and staff</p>
      </div>

      <div class="record-actions">
        <button class="btn-export" onclick="exportCsv('counselors')">
          <i class="fa fa-file-export"></i> Export CSV
        </button>
      </div>

    </div>

    <div style="overflow-x:auto;">

      <table>
        <thead>
          <tr>
            <th>Counselor ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Specialization</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td>C-001</td>
            <td>Dr. Reyes</td>
            <td>reyes@unitycare.com</td>
            <td>Mental Health</td>
            <td><span class="badge badge-success">Active</span></td>
          </tr>
          <tr>
            <td>C-002</td>
            <td>Dr. Garcia</td>
            <td>garcia@unitycare.com</td>
            <td>Academic Counseling</td>
            <td><span class="badge badge-success">Active</span></td>
          </tr>
        </tbody>
      </table>

    </div>

  </section>

  <!-- ================= ADMINS ================= -->
  <section id="admins" class="tab-content card">

    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:15px;">

      <div>
        <h3 style="margin:0;">Admin Accounts</h3>
        <p class="muted" style="margin:0;">System administrators</p>
      </div>

      <div class="record-actions">
        <button class="btn-export" onclick="exportCsv('admins')">
          <i class="fa fa-file-export"></i> Export CSV
        </button>
      </div>

    </div>

    <div style="overflow-x:auto;">

      <table>
        <thead>
          <tr>
            <th>Admin ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td>A-001</td>
            <td>System Admin</td>
            <td>admin@unitycare.com</td>
            <td>Super Admin</td>
            <td><span class="badge badge-success">Active</span></td>
          </tr>
          <tr>
            <td>A-002</td>
            <td>John Manager</td>
            <td>manager@unitycare.com</td>
            <td>Admin</td>
            <td><span class="badge badge-success">Active</span></td>
          </tr>
        </tbody>
      </table>

    </div>

  </section>

</main>

<!-- ================= SCRIPT ================= -->
<script>

function showTab(tab) {
  let sections = document.querySelectorAll(".tab-content");
  let buttons = document.querySelectorAll(".tab-buttons button");

  sections.forEach(s => s.classList.remove("active"));
  buttons.forEach(b => b.classList.remove("active"));

  document.getElementById(tab).classList.add("active");

  event.target.classList.add("active");
}

function addUser(type) {
  alert(`Add new ${type} functionality - to be implemented`);
}

function importCsv(type) {
  alert(`Import CSV for ${type} - to be implemented`);
}

function exportCsv(type) {
  alert(`Export ${type} to CSV - to be implemented`);
}

function logout() {
  if (confirm('Are you sure you want to logout?')) {
    window.location.href = 'login.html';
  }
}

</script>

</body>
</html>
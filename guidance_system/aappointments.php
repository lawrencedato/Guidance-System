<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Appointments</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="body">

<!-- ================= SIDEBAR ================= -->
<aside class="sidebar">

  <div class="sidebar-logoBar">

    <div class="sidebar-logo">
      <img src="logo.png" alt="logo">
      <span class="sidebar-logoText">UNITYCARE</span>
    </div>

    <div class="sidebar-settings">
      <button class="sidebar-settingsButton" onclick="toggleSettingsMenu(event)">
        <i class="fa fa-gear"></i>
      </button>

      <div class="sidebar-settingsDropdown" id="settingsDropdown">
        <button onclick="toggleTheme()">
          <i class="fa fa-moon"></i> Theme
        </button>

        <button onclick="logout()">
          <i class="fa fa-right-from-bracket"></i> Logout
        </button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">

    <a href="admin.php"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">MANAGEMENT</p>

    <a href="ausers.php"><i class="fa fa-users"></i> Users</a>
    <a href="astudents.php"><i class="fa fa-user-graduate"></i> Students</a>
    <a href="acounselors.php"><i class="fa fa-user-doctor"></i> Counselors</a>
    <a href="aappointments.php" class="active"><i class="fa fa-calendar"></i> Appointments</a>

    <p class="sidebar-title">SYSTEM</p>

    <a href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>

  </nav>

</aside>


<!-- ================= TOPBAR ================= -->
<header class="topbar">

  <div class="topbar-left">
      <h2>Appointments</h2>
    <p class="topbar-muted">
      Review appointment status only. Student names are confidential and not shown here.
    </p>
  </div>

</header>


<!-- ================= MAIN ================= -->
<main class="aAppointments-main">

  <!-- ================= FILTER BAR ================= -->
  <section class="aAppointments-filter-bar">

    <div class="aAppointments-status-filters">
      <button class="btn-filter active" data-status="all" onclick="filterAppointments('all')">All</button>
      <button class="btn-filter" data-status="pending" onclick="filterAppointments('pending')">Pending</button>
      <button class="btn-filter" data-status="approved" onclick="filterAppointments('approved')">Approved</button>
      <button class="btn-filter" data-status="rejected" onclick="filterAppointments('rejected')">Rejected</button>
    </div>

<div class="aAppointments-date-filters">
  <input type="date" id="filterDate">
  <button class="btn-apply" onclick="applyDateFilter()">Apply</button>
  <button class="btn-clear" onclick="clearDateFilter()">Clear</button>
</div>

  </section>


  <!-- STATUS SUMMARY -->
  <section class="aAppointments-status-summary">
    <div class="aAppointments-summary-card">
      <h3>Pending</h3>
      <p class="aAppointments-large-count" id="pendingCount">2</p>
      <p class="aAppointments-muted">Waiting for approval</p>
    </div>

    <div class="aAppointments-summary-card">
      <h3>Approved</h3>
      <p class="aAppointments-large-count" id="approvedCount">2</p>
      <p class="aAppointments-muted">Confirmed sessions</p>
    </div>

    <div class="aAppointments-summary-card">
      <h3>Rejected</h3>
      <p class="aAppointments-large-count" id="rejectedCount">1</p>
      <p class="aAppointments-muted">Declined requests</p>
    </div>
  </section>


  <!-- TABLE -->
  <section class="aAppointments-card">

    <div class="aAppointments-card-header">
      <div>
        <h3>Appointment Status Overview</h3>
        <p class="aAppointments-muted">View appointment statuses and details</p>
      </div>
    </div>

    <div class="aAppointments-table-wrapper">
      <table class="aAppointments-table" id="appointmentsTable">

        <thead>
          <tr>
            <th>Reference</th>
            <th>Counselor</th>
            <th>Date & Time</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>

          <tr data-status="pending" data-date="2026-04-29">
            <td>APPT-001</td>
            <td>Carl Reyes</td>
            <td>Apr 29, 2026 · 10:00 AM</td>
            <td><span class="tag tag-warning">Pending</span></td>
          </tr>

          <tr data-status="approved" data-date="2026-04-28">
            <td>APPT-002</td>
            <td>Anna Cruz</td>
            <td>Apr 28, 2026 · 2:00 PM</td>
            <td><span class="tag tag-success">Approved</span></td>
          </tr>

          <tr data-status="rejected" data-date="2026-04-30">
            <td>APPT-003</td>
            <td>Michael Tan</td>
            <td>Apr 30, 2026 · 11:30 AM</td>
            <td><span class="tag tag-error">Rejected</span></td>
          </tr>

          <tr data-status="pending" data-date="2026-05-01">
            <td>APPT-004</td>
            <td>Emma Santos</td>
            <td>May 01, 2026 · 9:30 AM</td>
            <td><span class="tag tag-warning">Pending</span></td>
          </tr>

          <tr data-status="approved" data-date="2026-05-02">
            <td>APPT-005</td>
            <td>Samuel Ortiz</td>
            <td>May 02, 2026 · 1:00 PM</td>
            <td><span class="tag tag-success">Approved</span></td>
          </tr>

        </tbody>

      </table>
    </div>

  </section>

</main>


<!-- ================= SCRIPT ================= -->
<script>

let currentStatus = "all";
let selectedDate = null;

function filterAppointments(status) {
  currentStatus = status;

  const buttons = document.querySelectorAll('.btn-filter');
  buttons.forEach(btn => {
    btn.classList.toggle('active', btn.dataset.status === status);
  });

  applyFilters();
}

function applyDateFilter() {
  selectedDate = document.getElementById("filterDate").value || null;
  applyFilters();
}

function clearDateFilter() {
  document.getElementById("filterDate").value = "";
  selectedDate = null;
  applyFilters();
}

function applyFilters() {
  const rows = document.querySelectorAll('#appointmentsTable tbody tr');

  rows.forEach(row => {
    const status = row.dataset.status;
    const date = row.dataset.date;

    let show = true;

    // STATUS FILTER
    if (currentStatus !== "all" && status !== currentStatus) {
      show = false;
    }

    // SINGLE DATE FILTER
    if (selectedDate && date !== selectedDate) {
      show = false;
    }

    row.style.display = show ? "" : "none";
  });
}
// SETTINGS MENU
function toggleSettingsMenu(e){
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}

function toggleTheme(){
  const html = document.documentElement;
  html.setAttribute(
    "data-theme",
    html.getAttribute("data-theme") === "light" ? "dark" : "light"
  );
}

function logout(){
  localStorage.clear();
  window.location.href = "index.php";
}

document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");

  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});

</script>

</body>
</html>
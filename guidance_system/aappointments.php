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

      <div class="topbar-filter-bar">
        <button class="btn-filter" data-status="all" onclick="filterAppointments('all')">All</button>
        <button class="btn-filter" data-status="pending" onclick="filterAppointments('pending')">Pending</button>
        <button class="btn-filter" data-status="approved" onclick="filterAppointments('approved')">Approved</button>
        <button class="btn-filter" data-status="rejected" onclick="filterAppointments('rejected')">Rejected</button>
      </div>

</header>

<!-- ================= MAIN ================= -->
  <main class="aAppointments-main">

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

  <!-- APPOINTMENTS CARD -->
  <section class="aAppointments-card">
    <div class="aAppointments-card-header">
      <div>
        <h3>Appointment Status Overview</h3>
        <p class="aAppointments-muted">
          View appointment statuses and details
        </p>
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
          <tr data-status="pending">
            <td>APPT-001</td>
            <td>Carl Reyes</td>
            <td>Apr 29, 2026 · 10:00 AM</td>
            <td><span class="tag tag-warning">Pending</span></td>
          </tr>

          <tr data-status="approved">
            <td>APPT-002</td>
            <td>Anna Cruz</td>
            <td>Apr 28, 2026 · 2:00 PM</td>
            <td><span class="tag tag-success">Approved</span></td>
          </tr>

          <tr data-status="rejected">
            <td>APPT-003</td>
            <td>Michael Tan</td>
            <td>Apr 30, 2026 · 11:30 AM</td>
            <td><span class="tag tag-error">Rejected</span></td>
          </tr>

          <tr data-status="pending">
            <td>APPT-004</td>
            <td>Emma Santos</td>
            <td>May 01, 2026 · 9:30 AM</td>
            <td><span class="tag tag-warning">Pending</span></td>
          </tr>

          <tr data-status="approved">
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

<script>
function updateAppointmentCounts() {
  const rows = document.querySelectorAll('#appointmentsTable tbody tr');
  const counts = {
    pending: 0,
    approved: 0,
    rejected: 0
  };

  rows.forEach(row => {
    const status = row.dataset.status;

    if (counts[status] !== undefined) {
      counts[status] += 1;
    }
  });

  document.getElementById('pendingCount').textContent = counts.pending;
  document.getElementById('approvedCount').textContent = counts.approved;
  document.getElementById('rejectedCount').textContent = counts.rejected;
}

function filterAppointments(status) {
  const rows = document.querySelectorAll('#appointmentsTable tbody tr');
  const buttons = document.querySelectorAll('.btn-filter');

  buttons.forEach(button => {
    button.classList.toggle(
      'active',
      button.dataset.status === status
    );
  });

  rows.forEach(row => {
    const rowStatus = row.dataset.status;

    row.style.display =
      status === 'all' || rowStatus === status
        ? ''
        : 'none';
  });
}

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
  window.location.href = "login.html";
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
<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: slogin.php");
    exit;
}

$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");

// ── FETCH APPOINTMENTS ──
$appointmentRows = [];
$result = $conn->query("
    SELECT
        a.appointment_id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        CONCAT(c.first_name, ' ', c.last_name) AS counselor_name
    FROM appointments a
    JOIN counselors c ON a.counselor_id = c.counselor_id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");
while ($row = $result->fetch_assoc()) $appointmentRows[] = $row;

// ── STATUS COUNTS ──
$pendingCount   = 0;
$approvedCount  = 0;
$rejectedCount  = 0;
foreach ($appointmentRows as $a) {
    if ($a['status'] === 'Pending')  $pendingCount++;
    if ($a['status'] === 'Approved') $approvedCount++;
    if ($a['status'] === 'Rejected') $rejectedCount++;
}

$conn->close();

// ── HELPER: FORMAT DATE & TIME ──
function formatDateTime($date, $time) {
    $dt = new DateTime($date . ' ' . $time);
    return $dt->format('M d, Y') . ' · ' . $dt->format('g:i A');
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Appointments</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
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
      <button class="btn-filter" data-status="Pending" onclick="filterAppointments('Pending')">Pending</button>
      <button class="btn-filter" data-status="Approved" onclick="filterAppointments('Approved')">Approved</button>
      <button class="btn-filter" data-status="Rejected" onclick="filterAppointments('Rejected')">Rejected</button>
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
      <p class="aAppointments-large-count" id="pendingCount"><?= $pendingCount ?></p>
      <p class="aAppointments-muted">Waiting for approval</p>
    </div>

    <div class="aAppointments-summary-card">
      <h3>Approved</h3>
      <p class="aAppointments-large-count" id="approvedCount"><?= $approvedCount ?></p>
      <p class="aAppointments-muted">Confirmed sessions</p>
    </div>

    <div class="aAppointments-summary-card">
      <h3>Rejected</h3>
      <p class="aAppointments-large-count" id="rejectedCount"><?= $rejectedCount ?></p>
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
          <?php if (empty($appointmentRows)): ?>
            <tr>
              <td colspan="4" style="text-align:center;">No appointments found.</td>
            </tr>
          <?php else: ?>
            <?php foreach ($appointmentRows as $a): ?>
              <?php
                $status    = $a['status'];
                $tagClass  = match($status) {
                  'Approved'  => 'tag-success',
                  'Rejected'  => 'tag-error',
                  'Completed' => 'tag-info',
                  default     => 'tag-warning'
                };
              ?>
              <tr
                data-status="<?= htmlspecialchars($status) ?>"
                data-date="<?= htmlspecialchars($a['appointment_date']) ?>"
              >
                <td>APPT-<?= htmlspecialchars($a['appointment_id']) ?></td>
                <td><?= htmlspecialchars($a['counselor_name']) ?></td>
                <td><?= formatDateTime($a['appointment_date'], $a['appointment_time']) ?></td>
                <td><span class="tag <?= $tagClass ?>"><?= htmlspecialchars($status) ?></span></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>

      </table>
    </div>

  </section>

  <div class="logout-overlay" id="logoutOverlay">
  <div class="logout-modal">
    <div class="logout-icon">
      <i class="fa fa-right-from-bracket"></i>
    </div>
    <h3>Logout</h3>
    <p>Are you sure you want to logout?</p>
    <div class="logout-actions">
      <button class="logout-btn logout-btn--cancel" onclick="closeLogout()">Cancel</button>
      <button class="logout-btn logout-btn--confirm" onclick="confirmLogout()">Yes, Logout</button>
    </div>
  </div>
</div>

</main>


<!-- ================= SCRIPT ================= -->
<script>

let currentStatus = "all";
let selectedDate  = null;

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
    const date   = row.dataset.date;

    let show = true;

    if (currentStatus !== "all" && status !== currentStatus) show = false;
    if (selectedDate && date !== selectedDate) show = false;

    row.style.display = show ? "" : "none";
  });
}

// ================= SETTINGS =================
function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}

function toggleTheme() {
  const html = document.documentElement;
  html.setAttribute(
    "data-theme",
    html.getAttribute("data-theme") === "light" ? "dark" : "light"
  );
}

function logout() {
    document.getElementById('logoutOverlay').classList.add('show');
}
function closeLogout() {
    document.getElementById('logoutOverlay').classList.remove('show');
}
function confirmLogout() {
    window.location.href = 'logout.php?role=admin';
}
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeLogout();
});

document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn  = document.querySelector(".sidebar-settingsButton");
  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});

</script>

</body>
</html>
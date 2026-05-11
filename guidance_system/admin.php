<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: slogin.php");
    exit;
}

$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");

// ── KPI COUNTS ──
$students     = $conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
$counselors   = $conn->query("SELECT COUNT(*) AS c FROM counselors WHERE status = 'Active'")->fetch_assoc()['c'];
$accounts     = $conn->query("SELECT COUNT(*) AS c FROM activated_students WHERE status = 'active'")->fetch_assoc()['c'];
$appointments = $conn->query("SELECT COUNT(*) AS c FROM appointments")->fetch_assoc()['c'];

// ── APPOINTMENT TRENDS (last 7 days) ──
$trendLabels = [];
$trendData   = [];
for ($i = 6; $i >= 0; $i--) {
    $date          = date('Y-m-d', strtotime("-$i days"));
    $trendLabels[] = date('D', strtotime("-$i days"));
    $trendData[]   = (int)$conn->query("SELECT COUNT(*) AS c FROM appointments WHERE DATE(created_at) = '$date'")->fetch_assoc()['c'];
}

// ── APPOINTMENT STATUS BREAKDOWN ──
$statusData = ['Approved' => 0, 'Pending' => 0, 'Rejected' => 0, 'Completed' => 0];
$statusRows = $conn->query("SELECT status, COUNT(*) AS c FROM appointments GROUP BY status");
while ($row = $statusRows->fetch_assoc()) {
    $statusData[$row['status']] = (int)$row['c'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Administrator Dashboard</title>

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
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>
  </div>

  <nav class="sidebar-menu">
    <a href="admin.php" class="active"><i class="fa fa-gauge"></i> Dashboard</a>
    <p class="sidebar-title">MANAGEMENT</p>
    <a href="ausers.php"><i class="fa fa-users"></i> Users</a>
    <a href="astudents.php"><i class="fa fa-user-graduate"></i> Students</a>
    <a href="acounselors.php"><i class="fa fa-user-doctor"></i> Counselors</a>
    <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>
    <p class="sidebar-title">SYSTEM</p>
    <a href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>
    <a href="aauditlogs.php"><i class="fa fa-clipboard-list"></i> Audit Logs</a>
  </nav>
</aside>


<!-- ================= TOPBAR ================= -->
<header class="topbar">

  <div class="topbar-left">
    <h2>Administrator Dashboard</h2>
    <p class="topbar-muted">
      System overview & performance monitoring
    </p>
  </div>

  <div class="aDashboard-live-status">
    <span class="aDashboard-pulse"></span>
    System Active
  </div>

</header>


<!-- ================= MAIN ================= -->
<main class="aDashboard-main">

  <!-- KPI CARDS -->
  <section class="aDashboard-stats">

    <div class="aDashboard-card">
      <h3><i class="fa fa-user-graduate"></i> Students</h3>
      <h2 id="studentsCount"><?= (int)$students ?></h2>
      <p class="aDashboard-muted">Total students</p>
    </div>

    <div class="aDashboard-card">
      <h3><i class="fa fa-user-doctor"></i> Counselors</h3>
      <h2 id="counselorsCount"><?= (int)$counselors ?></h2>
      <p class="aDashboard-muted">Active guidance counselors</p>
    </div>

    <div class="aDashboard-card">
      <h3><i class="fa fa-user-check"></i> Accounts</h3>
      <h2 id="accountsCount"><?= (int)$accounts ?></h2>
      <p class="aDashboard-muted">Activated system users</p>
    </div>

    <div class="aDashboard-card">
      <h3><i class="fa fa-calendar"></i> Appointments</h3>
      <h2 id="appointmentsCount"><?= (int)$appointments ?></h2>
      <p class="aDashboard-muted">Total bookings</p>
    </div>

  </section>


  <!-- QUICK ACTIONS -->
  <section class="aDashboard-card aDashboard-actions">

    <h3>Quick Actions</h3>
    <p class="aDashboard-muted">
      Fast access to common admin tasks
    </p>

    <div class="aDashboard-actions-wrapper">
      <div class="aDashboard-actions-group" style="display: flex; gap: 12px;">

        <button class="aDashboard-btn" onclick="window.location.href='astudents.php'">
          <i class="fa fa-user-graduate"></i>
          Add Student
        </button>

        <div class="aDashboard-actions-group">
          <button class="aDashboard-btn" onclick="window.location.href='acounselors.php'">
            <i class="fa fa-user-doctor"></i>
            Add Counselor Account
          </button>
        </div>

      </div>
    </div>

  </section>


  <!-- ANALYTICS -->
  <section class="aDashboard-card aDashboard-analytics">

    <h3>System Analytics</h3>
    <p class="aDashboard-muted">
      Appointment trends & system status overview
    </p>

    <div class="aDashboard-chart-grid">

      <div class="aDashboard-chart-box">
        <h4>Appointment Trends</h4>
        <div class="aDashboard-chart-container">
          <canvas id="appointmentsChart"></canvas>
        </div>
      </div>

      <div class="aDashboard-chart-box">
        <h4>Appointment Status</h4>

        <div class="aDashboard-chart-center">
          <div class="aDashboard-chart-inner">
            <canvas id="statusChart"></canvas>
          </div>
        </div>

      </div>

    </div>

  </section>

  <!-- LOGOUT MODAL -->
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

<script>
// ================= SETTINGS =================
function toggleSettingsMenu(e){
  e.stopPropagation();
  const dropdown = document.getElementById("settingsDropdown");
  if (dropdown) dropdown.classList.toggle("show");
}

function toggleTheme(){
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
  if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});


// ================= COUNTER ANIMATION =================
function animateValue(id, start, end, duration) {
  const obj = document.getElementById(id);
  if (!obj) return;
  let current = start;
  const step  = (end - start) / (duration / 50);
  const timer = setInterval(() => {
    current += step;
    if (current >= end) { current = end; clearInterval(timer); }
    obj.innerText = Math.floor(current);
  }, 50);
}


// ================= ON LOAD =================
window.onload = () => {

  // ANIMATE KPI COUNTERS
  animateValue("studentsCount",     0, <?= (int)$students ?>,     1000);
  animateValue("counselorsCount",   0, <?= (int)$counselors ?>,   1000);
  animateValue("accountsCount",     0, <?= (int)$accounts ?>,     1000);
  animateValue("appointmentsCount", 0, <?= (int)$appointments ?>, 1000);

  // LINE CHART - Appointment Trends
  const appointmentsCanvas = document.getElementById("appointmentsChart");
  if (appointmentsCanvas) {
    new Chart(appointmentsCanvas, {
      type: "line",
      data: {
        labels: <?= json_encode($trendLabels) ?>,
        datasets: [{
          label: "Appointments",
          data: <?= json_encode($trendData) ?>,
          borderColor: "#34699A",
          backgroundColor: "rgba(52,105,154,0.15)",
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
      }
    });
  }

  // PIE CHART - Appointment Status
  const statusCanvas = document.getElementById("statusChart");
  if (statusCanvas) {
    new Chart(statusCanvas, {
      type: "pie",
      data: {
        labels: <?= json_encode(array_keys($statusData)) ?>,
        datasets: [{
          data: <?= json_encode(array_values($statusData)) ?>,
          backgroundColor: ["#2ecc71", "#f1c40f", "#e74c3c", "#3498db"]
        }]
      },
      options: {
        plugins: { legend: { position: "bottom" } }
      }
    });
  }

};
</script>

</body>
</html>
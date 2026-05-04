<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: slogin.php");
    exit;
}

$conn = new mysqli("127.0.0.1", "root", "", "gcs_db");

// ── APPOINTMENT STATUS COUNTS ──
$statusCounts = ['Approved' => 0, 'Pending' => 0, 'Rejected' => 0, 'Completed' => 0];
$statusResult = $conn->query("SELECT status, COUNT(*) AS c FROM appointments GROUP BY status");
while ($row = $statusResult->fetch_assoc()) {
    $statusCounts[$row['status']] = (int)$row['c'];
}

// ── APPOINTMENT TREND (last 7 days) ──
$trendLabels = [];
$trendData   = [];
for ($i = 6; $i >= 0; $i--) {
    $date          = date('Y-m-d', strtotime("-$i days"));
    $trendLabels[] = date('M d', strtotime("-$i days"));
    $trendData[]   = (int)$conn->query("SELECT COUNT(*) AS c FROM appointments WHERE DATE(created_at) = '$date'")->fetch_assoc()['c'];
}

// ── STUDENT ACTIVATION ──
$totalStudents   = (int)$conn->query("SELECT COUNT(*) AS c FROM students")->fetch_assoc()['c'];
$activatedCount  = (int)$conn->query("SELECT COUNT(*) AS c FROM activated_students WHERE status = 'active'")->fetch_assoc()['c'];
$notActivated    = $totalStudents - $activatedCount;

// ── DAILY ACTIVATIONS (last 7 days) ──
$activationLabels = [];
$activationData   = [];
for ($i = 6; $i >= 0; $i--) {
    $date               = date('Y-m-d', strtotime("-$i days"));
    $activationLabels[] = date('M d', strtotime("-$i days"));
    $activationData[]   = (int)$conn->query("SELECT COUNT(*) AS c FROM activated_students WHERE DATE(created_at) = '$date'")->fetch_assoc()['c'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Reports</title>

<link rel="stylesheet" href="styles.css">
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
    <a href="aadmins.php"><i class="fa fa-user-shield"></i> Admins</a>
    <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>

    <p class="sidebar-title">SYSTEM</p>

    <a href="areports.php" class="active"><i class="fa fa-chart-line"></i> Reports</a>

  </nav>

</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">

  <div class="topbar-left">
    <h2>Reports</h2>
    <p class="topbar-muted">
      System analytics, engagement tracking, and performance insights
    </p>
  </div>

  <div class="aDashboard-live-status">
    <span class="aDashboard-pulse"></span>
    System Active
  </div>

</header>

<!-- ================= MAIN ================= -->
<main class="aReports-main">

  <!-- ================= APPOINTMENT ANALYTICS ================= -->
  <section class="aReports-card">

    <h3 class="aReports-title">Appointment Analytics</h3>

    <div class="aReports-chart-grid">

      <div class="aReports-chart-box">
        <h4>Appointments Trend</h4>
        <div class="aReports-chart-container">
          <canvas id="trendChart"></canvas>
        </div>
      </div>

      <div class="aReports-chart-box">
        <h4>Status Distribution</h4>
        <div class="aReports-chart-center">
          <div class="aReports-chart-inner">
            <canvas id="statusChart"></canvas>
          </div>
        </div>
      </div>

    </div>

  </section>

  <!-- ================= STUDENT ACTIVATION ================= -->
  <section class="aReports-card">

    <h3 class="aReports-title">Account Activation Overview</h3>

    <div class="aReports-stats">

      <div class="aReports-stat-card">
        <h3>Total Students</h3>
        <h2 id="totalStudents"><?= $totalStudents ?></h2>
      </div>

      <div class="aReports-stat-card">
        <h3>Activated Accounts</h3>
        <h2 id="activatedAccounts"><?= $activatedCount ?></h2>
      </div>

      <div class="aReports-stat-card">
        <h3>Not Activated</h3>
        <h2 id="notActivated"><?= $notActivated ?></h2>
      </div>

    </div>

  </section>

  <!-- ================= INSIGHT ================= -->
  <section class="aReports-card">

    <h3 class="aReports-title">Insight Summary</h3>
    <p class="aReports-insight" id="insightText">
      <?= $activatedCount ?> out of <?= $totalStudents ?> students have activated their accounts.
      <?= $notActivated ?> student<?= $notActivated !== 1 ? 's' : '' ?> still pending activation.
      <?php
        $total = array_sum($statusCounts);
        if ($total > 0) {
            $approvedPct = round(($statusCounts['Approved'] / $total) * 100);
            echo " Appointment approval rate: {$approvedPct}%.";
        }
      ?>
    </p>

  </section>

  <!-- ================= ACTIVATION CHART ================= -->
  <section class="aReports-card">

    <h3 class="aReports-title">Daily Account Activations</h3>

    <div class="aReports-chart-container">
      <canvas id="activationChart"></canvas>
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

// ================= SETTINGS =================
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

// ================= LINE CHART - Appointment Trend =================
new Chart(document.getElementById("trendChart"), {
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
    responsive: true,
    maintainAspectRatio: false,
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

// ================= PIE CHART - Status Distribution =================
new Chart(document.getElementById("statusChart"), {
  type: "pie",
  data: {
    labels: <?= json_encode(array_keys($statusCounts)) ?>,
    datasets: [{
      data: <?= json_encode(array_values($statusCounts)) ?>,
      backgroundColor: ["#2ecc71", "#f1c40f", "#e74c3c", "#3498db"]
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: "bottom" } }
  }
});

// ================= BAR CHART - Daily Activations =================
new Chart(document.getElementById("activationChart"), {
  type: "bar",
  data: {
    labels: <?= json_encode($activationLabels) ?>,
    datasets: [{
      label: "Activated Accounts",
      data: <?= json_encode($activationData) ?>,
      backgroundColor: "#34699A"
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

</script>

</body>
</html>
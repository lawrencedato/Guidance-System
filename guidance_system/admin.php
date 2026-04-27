<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - UNITYCARE</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="body">

<!-- SIDEBAR -->
<aside class="sidebar">

  <div class="sidebar-logoBar">

    <div class="sidebar-logo">
      <img src="logo.png" alt="logo">
      <span class="sidebar-logoText">UNITYCARE</span>
    </div>

  </div>

  <nav class="sidebar-menu">

    <a class="active" href="admin.php">
      <i class="fa fa-gauge"></i> Dashboard
    </a>

    <p class="sidebar-title">MANAGEMENT</p>

    <a href="ausers.php"><i class="fa fa-users"></i> Users</a>
    <a href="astudents.php"><i class="fa fa-user-graduate"></i> Students</a>
    <a href="acounselors.php"><i class="fa fa-user-doctor"></i> Counselors</a>
    <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>

    <p class="sidebar-title">SYSTEM</p>

    <a href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>

  </nav>

  <button class="btn btn-error btn-sm" onclick="logout()">
    <i class="fa fa-right-from-bracket"></i> Logout
  </button>

</aside>

<!-- ================= MAIN ================= -->
<main class="main">

  <!-- TOPBAR -->
  <header class="topbar">
    <div>
      <h2>Admin Dashboard</h2>
      <p class="muted">System overview & performance monitoring</p>
    </div>

    <div class="live-status">
      <span class="pulse"></span>
      System Active
    </div>
  </div>

  <!-- ================= KPI STATS ================= -->
  <section class="card-grid stats">

    <!-- TOTAL STUDENTS -->
    <div class="card">
      <h3><i class="fa fa-user-graduate"></i> Students</h3>
      <h2 id="studentsCount">245</h2>
      <p class="muted">Total students</p>
    </div>

    <!-- TOTAL COUNSELORS -->
    <div class="card">
      <h3><i class="fa fa-user-doctor"></i> Counselors</h3>
      <h2 id="counselorsCount">12</h2>
      <p class="muted">Active guidance counselors</p>
    </div>

    <!-- ACTIVATED ACCOUNTS -->
    <div class="card">
      <h3><i class="fa fa-user-check"></i> Accounts</h3>
      <h2 id="accountsCount">180</h2>
      <p class="muted">Activated system users</p>
    </div>

    <!-- APPOINTMENTS -->
    <div class="card">
      <h3><i class="fa fa-calendar"></i> Appointments</h3>
      <h2 id="appointmentsCount">128</h2>
      <p class="muted">Total bookings</p>
    </div>

  </section>

<!-- ================= QUICK ACTIONS ================= -->
<section class="card" style="margin-bottom: 20px;">

  <h3>Quick Actions</h3>
  <p class="muted">Fast access to common admin tasks</p>

  <div style="
    display: flex;
    justify-content: center;
    margin-top: 15px;
  ">

    <div style="
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      justify-content: center;
      max-width: 700px;
    ">

      <button class="btn">
        <i class="fa fa-user-graduate"></i> Add Student
      </button>

      <button class="btn btn-secondary">
        <i class="fa fa-user-doctor"></i> Add Counselor
      </button>

    </div>

  </div>

</section>

<!-- ================= ANALYTICS ================= -->
<section class="card chart-section">

  <h3>System Analytics</h3>
  <p class="muted">Appointment trends & system status overview</p>

  <div style="
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-top: 15px;
  ">

    <!-- LEFT: LINE CHART -->
    <div class="chart-box" style="height: 320px; display:flex; flex-direction:column;">
      <h4>Appointment Trends</h4>
      <div style="flex:1;">
        <canvas id="appointmentsChart"></canvas>
      </div>
    </div>

    <!-- RIGHT: PIE CHART (PROPER SIZE) -->
<div class="chart-box" style="height: 320px; display:flex; flex-direction:column;">

  <h4>Appointment Status</h4>

  <!-- CENTERED RESPONSIVE WRAPPER -->
  <div style="flex:1; display:flex; align-items:center; justify-content:center;">
    <div style="width: 100%; max-width: 260px;">
      <canvas id="statusChart"></canvas>
    </div>
  </div>

</div>

  </div>

</section>
<!-- ================= SCRIPT ================= -->
<script>
function logout() {
  window.location.href = "index.html";
}

/* COUNTERS */
function animateValue(id, start, end, duration) {
  let obj = document.getElementById(id);
  let current = start;
  let step = (end - start) / (duration / 50);

  let timer = setInterval(() => {
    current += step;
    if (current >= end) {
      current = end;
      clearInterval(timer);
    }
    obj.innerText = Math.floor(current);
  }, 50);
}

window.onload = () => {
  animateValue("usersCount", 200, 245, 1000);
  animateValue("appointmentsCount", 100, 128, 1000);
  animateValue("pendingCount", 5, 12, 1000);
  animateValue("announcementCount", 1, 3, 1000);
};

/* APPOINTMENT TREND */
new Chart(document.getElementById('appointmentsChart'), {
  type: 'line',
  data: {
    labels: ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
    datasets: [{
      label: 'Appointments',
      data: [12,19,8,15,22,18,25],
      borderColor: '#34699A',
      backgroundColor: 'rgba(52,105,154,0.2)',
      fill: true,
      tension: 0.4
    }]
  },
  options: {
    plugins: { legend: { display: false } }
  }
});

/* STATUS BREAKDOWN */
new Chart(document.getElementById('statusChart'), {
  type: 'pie',
  data: {
    labels: ['Approved', 'Pending', 'Rejected'],
    datasets: [{
      data: [70, 20, 10],
      backgroundColor: ['#2ecc71', '#f1c40f', '#e74c3c']
    }]
  },
  options: {
    plugins: { legend: { position: 'bottom' } }
  }
});
</script>

<style>
  
/* =========================
   LIVE STATUS INDICATOR
========================= */
.live-status {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--text-light);
}

.pulse {
  width: 10px;
  height: 10px;
  background: var(--success);
  border-radius: 50%;
  position: relative;
}

.pulse::after {
  content: "";
  position: absolute;
  width: 100%;
  height: 100%;
  background: var(--success);
  border-radius: 50%;
  animation: pulseAnim 1.5s infinite;
}

/* ================= SCROLLBAR ================= */

/* width (kept slim but usable) */
::-webkit-scrollbar {
  width: 8px;
}

/* track */
::-webkit-scrollbar-track {
  background: transparent;
}

/* default thumb (very subtle) */
::-webkit-scrollbar-thumb {
  background: rgba(52, 105, 154, 0.15);
  border-radius: 10px;
  transition: 0.3s ease;
}

/* hover state (becomes visible) */
::-webkit-scrollbar-thumb:hover {
  background: rgba(52, 105, 154, 0.35);
}

/* smooth scrolling */
html {
  scroll-behavior: smooth;
}

</style>
</body>
</html>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Administrator Dashboard</title>

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

    <a href="admin.php" class="active"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">MANAGEMENT</p>

    <a href="ausers.php"><i class="fa fa-users"></i> Users</a>
    <a href="astudents.php"><i class="fa fa-user-graduate"></i> Students</a>
    <a href="acounselors.php"><i class="fa fa-user-doctor"></i> Counselors</a>
    <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>

    <p class="sidebar-title">SYSTEM</p>

    <a href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>

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
      <h2 id="studentsCount">245</h2>
      <p class="aDashboard-muted">Total students</p>
    </div>

    <div class="aDashboard-card">
      <h3><i class="fa fa-user-doctor"></i> Counselors</h3>
      <h2 id="counselorsCount">12</h2>
      <p class="aDashboard-muted">Active guidance counselors</p>
    </div>

    <div class="aDashboard-card">
      <h3><i class="fa fa-user-check"></i> Accounts</h3>
      <h2 id="accountsCount">180</h2>
      <p class="aDashboard-muted">Activated system users</p>
    </div>

    <div class="aDashboard-card">
      <h3><i class="fa fa-calendar"></i> Appointments</h3>
      <h2 id="appointmentsCount">128</h2>
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
      <div class="aDashboard-actions-group">

        <button class="aDashboard-btn">
          <i class="fa fa-user-graduate"></i>
          Add Student
        </button>

        <button class="aDashboard-btn aDashboard-btn-secondary">
          <i class="fa fa-user-doctor"></i>
          Add Counselor
        </button>

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

</main>


<!-- ================= SCRIPT ================= -->
<script>
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

/* COUNTER ANIMATION */
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
  animateValue("studentsCount", 200, 245, 1000);
  animateValue("counselorsCount", 5, 12, 1000);
  animateValue("accountsCount", 120, 180, 1000);
  animateValue("appointmentsCount", 80, 128, 1000);
};


/* LINE CHART */
new Chart(document.getElementById("appointmentsChart"), {
  type: "line",
  data: {
    labels: ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"],
    datasets: [{
      label: "Appointments",
      data: [12, 19, 8, 15, 22, 18, 25],
      borderColor: "#34699A",
      backgroundColor: "rgba(52,105,154,0.15)",
      fill: true,
      tension: 0.4
    }]
  },
  options: {
    plugins: {
      legend: {
        display: false
      }
    }
  }
});


/* PIE CHART */
new Chart(document.getElementById("statusChart"), {
  type: "pie",
  data: {
    labels: ["Approved", "Pending", "Rejected"],
    datasets: [{
      data: [70, 20, 10],
      backgroundColor: [
        "#2ecc71",
        "#f1c40f",
        "#e74c3c"
      ]
    }]
  },
  options: {
    plugins: {
      legend: {
        position: "bottom"
      }
    }
  }
});
</script>

</body>
</html>
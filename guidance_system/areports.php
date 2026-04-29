<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Reports</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<!-- ================= SIDEBAR ================= -->
<aside class="sidebar">

  <div class="sidebar-logoBar">
    <div class="sidebar-logo">
      <img src="logo.png">
      <span class="sidebar-logoText">UNITYCARE</span>
    </div>
  </div>

  <nav class="sidebar-menu">
    <a href="admin.php"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">MANAGEMENT</p>
    <a href="ausers.php"><i class="fa fa-users"></i> Users</a>
    <a href="astudents.php"><i class="fa fa-user-graduate"></i> Students</a>
    <a href="acounselors.php"><i class="fa fa-user-doctor"></i> Counselors</a>
    <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>

    <p class="sidebar-title">SYSTEM</p>
    <a class="active" href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>
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

  <!-- ================= APPOINTMENT ANALYTICS (YOUR EXISTING IDEA) ================= -->
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

  <!-- ================= NEW: STUDENT ACTIVATION OVERVIEW ================= -->
  <section class="aReports-card">

    <h3 class="aReports-title">Account Activation Overview</h3>

    <div class="aReports-stats">

      <div class="aReports-card">
        <h3>Total Students</h3>
        <h2 id="totalStudents">0</h2>
      </div>

      <div class="aReports-card">
        <h3>Activated Accounts</h3>
        <h2 id="activatedAccounts">0</h2>
      </div>

      <div class="aReports-card">
        <h3>Not Activated</h3>
        <h2 id="notActivated">0</h2>
      </div>

    </div>

  </section>

  <!-- ================= INSIGHT ================= -->
  <section class="aReports-card">

    <h3 class="aReports-title">Insight Summary</h3>
    <p id="insightText">Loading insights...</p>

  </section>

  <!-- ================= NEW: DAILY ACTIVATION CHART ================= -->
  <section class="aReports-card">

    <h3>Daily Account Activations</h3>

    <div class="aReports-chart-container">
      <canvas id="activationChart"></canvas>
    </div>

  </section>

</main>

<!-- ================= SCRIPT ================= -->
<script>

/* =========================================================
   APPOINTMENT DATA (your existing concept)
========================================================= */
const appointments = [
  {date:"2026-04-29", status:"pending"},
  {date:"2026-04-29", status:"approved"},
  {date:"2026-04-29", status:"rejected"},
  {date:"2026-04-30", status:"approved"},
  {date:"2026-04-30", status:"pending"},
  {date:"2026-05-01", status:"approved"},
  {date:"2026-05-01", status:"approved"}
];

/* =========================================================
   APPOINTMENT SUMMARY
========================================================= */
let approved = 0, pending = 0, rejected = 0;

appointments.forEach(a => {
  if(a.status === "approved") approved++;
  if(a.status === "pending") pending++;
  if(a.status === "rejected") rejected++;
});

/* =========================================================
   APPOINTMENT CHARTS
========================================================= */
const groupedAppointments = {};

appointments.forEach(a => {
  if(!groupedAppointments[a.date]){
    groupedAppointments[a.date] = 0;
  }
  groupedAppointments[a.date]++;
});

/* LINE CHART */
new Chart(document.getElementById("trendChart"), {
  type: "line",
  data: {
    labels: Object.keys(groupedAppointments),
    datasets: [{
      label: "Appointments",
      data: Object.values(groupedAppointments),
      borderColor: "#34699A",
      backgroundColor: "rgba(52,105,154,0.15)",
      fill: true,
      tension: 0.4
    }]
  }
});

/* PIE CHART */
new Chart(document.getElementById("statusChart"), {
  type: "pie",
  data: {
    labels: ["Approved","Pending","Rejected"],
    datasets: [{
      data: [approved, pending, rejected],
      backgroundColor: ["#2ecc71","#f1c40f","#e74c3c"]
    }]
  }
});

/* =========================================================
   STUDENT ACTIVATION DATA (NEW FEATURE)
========================================================= */
const students = [
  {activated:true, date:"2026-04-28"},
  {activated:true, date:"2026-04-28"},
  {activated:false},
  {activated:true, date:"2026-04-29"},
  {activated:false},
  {activated:true, date:"2026-04-30"}
];

/* =========================================================
   COUNTS
========================================================= */
const totalStudents = students.length;
const activatedCount = students.filter(s => s.activated).length;
const notActivated = totalStudents - activatedCount;

/* DISPLAY */
document.getElementById("totalStudents").innerText = totalStudents;
document.getElementById("activatedAccounts").innerText = activatedCount;
document.getElementById("notActivated").innerText = notActivated;

/* =========================================================
   INSIGHT
========================================================= */
document.getElementById("insightText").innerText =
  `${activatedCount} out of ${totalStudents} students have activated their accounts. ${notActivated} still need activation.`;

/* =========================================================
   DAILY ACTIVATION GROUPING
========================================================= */
const activationGrouped = {};

students.forEach(s => {
  if(s.activated){
    activationGrouped[s.date] = (activationGrouped[s.date] || 0) + 1;
  }
});

/* BAR CHART */
new Chart(document.getElementById("activationChart"), {
  type: "bar",
  data: {
    labels: Object.keys(activationGrouped),
    datasets: [{
      label: "Activated Accounts",
      data: Object.values(activationGrouped),
      backgroundColor: "#34699A"
    }]
  }
});

</script>

</body>
</html>
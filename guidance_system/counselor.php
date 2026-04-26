<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>UNITYCARE | Counselor Dashboard</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="body">

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
        <a href="cprofile.html"><i class="fa fa-user"></i> Profile</a>
        <a href="chistory.html"><i class="fa fa-clock"></i> Session History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">
    <a href="counselor.html" class="active"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">SESSIONS</p>
    <a href="cappointments.html"><i class="fa fa-calendar-plus"></i> Appointment Requests</a>
    <a href="cconcerns.html"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>

    <p class="sidebar-title">STUDENTS</p>
    <a href="students.html"><i class="fa fa-users"></i> Students</a>

    <p class="sidebar-title">REPORTS</p>
    <a href="creports.html"><i class="fa fa-file"></i> Reports</a>

    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.html"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.html"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">

  <div class="topbar-left">
    <h3>Welcome!</h3>
  </div>

  <!-- RIGHT SIDE WRAPPER (FIXED) -->
  <div class="topbar-right">

    <div class="topbar-icons">

      <div class="topbar-icons">

 <!-- FEEDBACK NOTIFICATIONS -->
<div class="topbar-icon" onclick="toggleDropdown('feedbackDropdown', event)">
  <i class="fa fa-envelope"></i>
  <span class="badge" id="feedbackCount">0</span>

  <div class="icon-dropdown" id="feedbackDropdown">
    <div class="notif-header">New Feedback</div>
    <div id="notifList">
      <div class="notif-empty">No new feedback</div>
    </div>
  </div>
</div>

<!-- BELL NOTIFICATIONS -->
<div class="topbar-icon" onclick="toggleDropdown('notifDropdown', event)">
  <i class="fa fa-bell"></i>
  <span class="badge">4</span>

  <div class="icon-dropdown" id="notifDropdown">
    <p>No new notifications</p>
  </div>
</div>

    </div>

    <div class="topbar-user">
      <img src="counselor.jpg" alt="user">
      <div>
        <strong>Dr. Lawrence Dato</strong>
        <p>lawrencedato@gmail.com</p>
      </div>
    </div>

  </div>

</header>

<!-- MAIN -->
<main class="cDashboard-main">

  <section class="cDashboard-container">

    <div class="cDashboard-card">
      <h3>Today’s Sessions</h3>
      <h3>6</h3>
      <p>Scheduled counseling sessions for today</p>
    </div>

    <div class="cDashboard-card">
      <h4>My Students</h4>
      <h3>32</h3>
      <p>Assigned students under your care</p>
    </div>

    <div class="cDashboard-card">
      <h4>Pending Concerns</h4>
      <h3>4</h3>
      <p>Cases waiting for action or review</p>
    </div>

    <div class="cDashboard-card">
      <h4>Reports</h4>
      <h3>3</h3>
      <p>Generated counseling reports this week</p>
    </div>

  </section>

</main>

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

</script>

</body>
</html>
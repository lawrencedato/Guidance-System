<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>UNITYCARE - Student Dashboard</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
        <a href="profile.html"><i class="fa fa-user"></i> Profile</a>
        <a href="history.html"><i class="fa fa-clock"></i> Session History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">

    <a href="dashboard.html" class="active"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">SERVICES</p>
    <a href="booking.html"><i class="fa fa-calendar"></i> Book Appointment</a>
    <a href="sconcerns.html"><i class="fa fa-headset"></i> Submit Concern</a>
    <a href="wellness.html"><i class="fa fa-heart"></i> Wellness Check</a>
    <a href="referral.html"><i class="fa fa-route"></i> Referral</a>

    <p class="sidebar-title">UPDATES</p>
    <a href="announcements.html"><i class="fa fa-bullhorn"></i> Announcements</a>

    <p class="sidebar-title">RECORDS</p>
    <a href="documents.html"><i class="fa fa-file"></i> Documents</a>
    <a href="ticket.html"><i class="fa fa-ticket"></i> Tickets</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="feedback.html"><i class="fa fa-comment"></i> Feedback</a>

  </nav>

</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">

  <div class="topbar-left">
    <h3>Welcome back, Vincent! 👋</h3>
  </div>

  <div class="topbar-right">

    <div class="topbar-searchBox">
      <i class="fa fa-search"></i>
      <input type="text" placeholder="Search...">
    </div>

    <div class="topbar-icons">
      <div class="topbar-icon">
        <i class="fa fa-envelope"></i>
        <span class="badge">3</span>
      </div>

      <div class="topbar-icon">
        <i class="fa fa-bell"></i>
        <span class="badge">5</span>
      </div>
    </div>

   <div class="topbar-user">
      <img src="student.jpg" alt="user">
      <div>
        <strong>Vincent Aldolf Sablay</strong>
        <p>vincentsablay@gmail.com</p>
      </div>
    </div>

  </div>

</header>

<!-- ================= MAIN ================= -->
<main class="sDashboard-main">

  <!-- STATS -->
  <section class="sDashboard-stats">
    <div class="sDashboard-card">
      <h4>Upcoming Appointments</h4>
      <h2>2</h2>
      <small>Next: Apr 20, 10:00 AM</small>
    </div>

    <div class="sDashboard-card">
      <h4>Completed Sessions</h4>
      <h2>5</h2>
      <small>Total counseling sessions attended</small>
    </div>

    <div class="sDashboard-card">
      <h4>Active Referrals</h4>
      <h2>1</h2>
      <small>External professional assigned</small>
    </div>

    <div class="sDashboard-card">
      <h4>Pending Concerns</h4>
      <h2>3</h2>
      <small>Awaiting counselor response</small>
    </div>

    <div class="card-emergency">
      <h4>Need immediate help?</h4>
      <p>Contact your counselor or hotline</p>
      <p><strong>📞 0912-345-6789</strong></p>
    </div>
  </section>

  <!-- CONTENT GRID -->
  <section class="sDashboard-content">

    <!-- LEFT -->
    <div class="sDashboard-announcement">
      <h4>Announcement</h4>
      <h4>Mental Health Awareness Seminar</h4>
      <p>Stress management, emotional balance, and self-care strategies for academic pressure.</p>


   <a class="btn"
         href="announcements.html?open=mental-health-seminar">
        View Details
      </a>
    </div>

    <!-- RIGHT -->
    <div class="sDashboard-side">

      <div class="sDashboard-card">
        <h4>Wellness</h4>
        <div class="sDashboard-progress">75%</div>
      </div>

      <div class="sDashboard-card">
        <h4>Mood</h4>
        <div class="sDashboard-mood">😊</div>
      </div>

      <div class="sDashboard-card">
        <h4>Activity</h4>

        <div class="sDashboard-activity-item">
          Booked session <small>Apr 12, 2026</small>
        </div>

        <div class="sDashboard-activity-item">
          Submitted concern <small>Apr 10, 2026</small>
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
  html.setAttribute("data-theme",
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

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports - Counselor</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="body">

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sidebar-logoBar">

    <div class="sidebar-logo">
      <img src="logo.png" alt="logo">
      <span class="sidebar-logoText">UNITYCARE</span>
    </div>

    <div class="sidebar-settings">
      <button class="sidebar-settingsButton" onclick="toggleSettings()">
        <i class="fa fa-gear"></i>
      </button>

      <div class="sidebar-settingsDropdown" id="settingsMenu">
        <a href="profile.html"><i class="fa fa-user"></i> Profile</a>
        <a href="history.html"><i class="fa fa-clock"></i> Session History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">
    <a href="counselor.html"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">SESSIONS</p>
    <a href="cappointments.html"><i class="fa fa-calendar-plus"></i> Appointment Requests</a>
    <a href="cconcerns.html"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>

    <p class="sidebar-title">STUDENTS</p>
    <a href="students.html"><i class="fa fa-users"></i> Students</a>

    <p class="sidebar-title">REPORTS</p>
    <a href="creports.html" class="active"><i class="fa fa-file"></i> Reports</a>

    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.html"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.html"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">
  <div class="topbar-left">
    <h3>Appointment Request</h3>
  </div>

  <div class="topbar-right">

    <div class="topbar-searchBox">
      <i class="fa fa-search"></i>
      <input type="text" placeholder="Search...">
    </div>

    <div class="topbar-icons">
      <div class="topbar-icon">
        <i class="fa fa-envelope"></i>
        <span class="badge">2</span>
      </div>

      <div class="topbar-icon">
        <i class="fa fa-bell"></i>
        <span class="badge">4</span>
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
<main class="cReports-main">

  <p class="cReports-muted">
    Counseling summaries and student progress reports
  </p>

  <div class="cReports-grid">
<!-- SESSION NOTES CARD -->
<div class="cReports-card">

  <h3>Session Notes</h3>
  <p>Add counselor notes after each session</p>

  <textarea class="cReports-textarea" placeholder="Write session notes here..."></textarea>

  <button class="cReports-btn" onclick="saveNotes(this)">
    Save Notes
  </button>

  <div class="cReports-status"></div>

</div>

<!-- TICKET GENERATOR CARD -->
<div class="cReports-card">

  <h3>Generate Case Ticket</h3>
  <p>Create a formal student concern ticket</p>

  <input class="cReports-input" placeholder="Student Name">
  <input class="cReports-input" placeholder="Concern Type (Stress, Anxiety, etc.)">

  <textarea class="cReports-textarea" placeholder="Case description..."></textarea>

  <button class="cReports-btn" onclick="generateTicket(this)">
    Generate Ticket
  </button>

  <div class="cReports-status"></div>

</div>


</main>

<script>
function toggleSettings() {
  const menu = document.getElementById("settingsMenu");
  menu.style.display = menu.style.display === "flex" ? "none" : "flex";
}

function toggleTheme() {
  const html = document.documentElement;
  html.setAttribute(
    "data-theme",
    html.getAttribute("data-theme") === "dark" ? "light" : "dark"
  );
}

function logout() {
  window.location.href = "role.html";
}
</script>

</body>
</html>
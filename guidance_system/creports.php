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
      <button class="sidebar-settingsButton" onclick="toggleSettingsMenu(event)">
        <i class="fa fa-gear"></i>
      </button>

      <div class="sidebar-settingsDropdown" id="settingsMenu">
        <a href="profile.html"><i class="fa fa-user"></i> Profile</a>
        <a href="chistory.html"><i class="fa fa-clock"></i> Session History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">
    <a href="counselor.php"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">SESSIONS</p>
    <a href="cappointments.html"><i class="fa fa-calendar-plus"></i> Appointment Requests</a>
    <a href="cconcerns.html"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>

    <p class="sidebar-title">STUDENTS</p>
    <a href="students.html"><i class="fa fa-users"></i> Students</a>

    <p class="sidebar-title">REPORTS</p>
    <a href="creports.php" class="active"><i class="fa fa-file"></i> Reports</a>

    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.php"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Reports</h2>
  </div>

  <div class="topbar-right">

    <div class="topbar-searchBox">
      <i class="fa fa-search"></i>
      <input type="text" placeholder="Search...">
    </div>


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

  <!-- PAGE HEADER (OUTSIDE CENTER BLOCK) -->
  <div class="cReports-pageHeader">
    <p class="cReports-muted">
      Counseling summaries and student progress reports
    </p>
  </div>

  <!-- CENTER CONTENT -->
  <div class="cReports-center">

    <div class="cReports-card">

      <h3>Session Notes</h3>
      <p>Add counselor notes after each session</p>

      <textarea class="cReports-textarea" placeholder="Write session notes here..."></textarea>

      <button class="cReports-btn" onclick="saveNotes(this)">
        Save Notes
      </button>

      <div class="cReports-status"></div>

    </div>

  </div>

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

function postAnnouncement() {
  db.collection("announcements").add({
    title: titleInput.value,
    message: messageInput.value,
    status: "active",
    createdAt: new Date(),
    eventDate: eventDateInput.value
  });
}

</script>

</body>
</html>
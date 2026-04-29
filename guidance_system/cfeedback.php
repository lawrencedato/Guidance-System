<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Counselor Feedback Review</title>

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
    <h2>Appointment Request</h2>
  </div>

  <div class="topbar-right">

<!-- NOTIFICATIONS -->
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
<main class="cFeedback-main">

  <div class="cFeedback-container">

    <!-- CARD -->
    <div class="cFeedback-card">

      <h3 class="cFeedback-title">Recent Student Feedback</h3>

      <p class="cFeedback-muted">
        Review submitted feedback from counseling sessions
      </p>

      <!-- FEEDBACK LIST -->
      <div class="cFeedback-list">

        <!-- ITEM -->
        <div class="cFeedback-item">
          <div class="cFeedback-header">
            <h4>Vincent Aldolf Sablay</h4>
            <span class="cFeedback-rating">⭐⭐⭐⭐⭐ Excellent</span>
          </div>

          <p class="cFeedback-date">
            Submitted on April 24, 2026
          </p>

          <p class="cFeedback-message">
            The session was very helpful and I felt comfortable sharing my concerns.
            Thank you for the guidance and support.
          </p>
        </div>

        <!-- ITEM -->
        <div class="cFeedback-item">
          <div class="cFeedback-header">
            <h4>Angela Mae Cruz</h4>
            <span class="cFeedback-rating">⭐⭐⭐⭐ Very Good</span>
          </div>

          <p class="cFeedback-date">
            Submitted on April 22, 2026
          </p>

          <p class="cFeedback-message">
            I appreciated the advice given during our session. It helped me manage
            my academic stress better.
          </p>
        </div>

        <!-- ITEM -->
        <div class="cFeedback-item">
          <div class="cFeedback-header">
            <h4>John Michael Reyes</h4>
            <span class="cFeedback-rating">⭐⭐⭐ Good</span>
          </div>

          <p class="cFeedback-date">
            Submitted on April 20, 2026
          </p>

          <p class="cFeedback-message">
            The session was good, but I hope there can be more follow-up guidance
            after counseling.
          </p>
        </div>

      </div>

    </div>

  </div>

</main>

<script>
function toggleSettingsMenu(e) {
  e.stopPropagation();
  const menu = document.getElementById("settingsDropdown");
  menu.style.display =
    menu.style.display === "block" ? "none" : "block";
}

function toggleDropdown(id, e) {
  e.stopPropagation();
  const dropdown = document.getElementById(id);

  dropdown.style.display =
    dropdown.style.display === "block" ? "none" : "block";
}

function toggleTheme() {
  const html = document.documentElement;
  html.setAttribute(
    "data-theme",
    html.getAttribute("data-theme") === "light"
      ? "dark"
      : "light"
  );
}

function logout() {
  localStorage.clear();
  window.location.href = "login.html";
}

/* CLOSE DROPDOWNS */
document.addEventListener("click", function () {
  document.getElementById("settingsMenu").style.display = "none";
  document.getElementById("notifDropdown").style.display = "none";
});

</script>

</body>
</html>
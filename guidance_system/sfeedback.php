<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Session Feedback</title>

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

      <div class="sidebar-settingsDropdown" id="settingsDropdown">
        <a href="sprofile.php"><i class="fa fa-user"></i> Profile</a>
        <a href="shistory.php"><i class="fa fa-clock"></i> Session History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">
    <a href="dashboard.php"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">SERVICES</p>
    <a href="sappointment.php"><i class="fa fa-calendar"></i> Book Appointment</a>
    <a href="sconcerns.php"><i class="fa fa-headset"></i> Submit Concern</a>
    <a href="swellness.php"><i class="fa fa-heart"></i> Wellness Check</a>
    <a href="sreferral.php"><i class="fa fa-route"></i> Referral</a>

    <p class="sidebar-title">UPDATES</p>
    <a href="sannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>

    <p class="sidebar-title">RECORDS</p>
    <a href="sreports.php"><i class="fa fa-ticket"></i> Reports</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="sfeedback.php" class="active"><i class="fa fa-comment"></i> Session Feedback</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">

  <div class="topbar-left">
    <h1>Session Feedback</h1>
  </div>

  <div class="topbar-right">

    <div class="topbar-user">
      <img src="student.jpg" alt="user">
      <div>
        <strong>Vincent Aldolf Sablay</strong>
        <p>vincentsablay@gmail.com</p>
      </div>
    </div>

  </div>

</header>

<!-- MAIN -->
<main class="sFeedback-main">

  <div class="sFeedback-container">

    <!-- CARD -->
    <div class="card sFeedback-card">

      <h3 class="sFeedback-title">How was your session?</h3>

      <p class="sFeedback-muted">
        Rate your experience and leave your comments
      </p>

      <!-- FORM -->
      <div class="sFeedback-form">

        <div class="form-group">
          <label>Rating</label>
          <select>
            <option>⭐ Poor</option>
            <option>⭐⭐ Fair</option>
            <option>⭐⭐⭐ Good</option>
            <option>⭐⭐⭐⭐ Very Good</option>
            <option>⭐⭐⭐⭐⭐ Excellent</option>
          </select>
        </div>

        <div class="form-group">
          <label>Feedback</label>
          <textarea rows="6" placeholder="Write your feedback here..."></textarea>
        </div>

        <button class="sFeedback-btn sFeedback-submit">
          Submit Feedback
        </button>

      </div>

    </div>

  </div>

</main>

<script>
function toggleSettingsMenu(e) {
  e.stopPropagation();
  const menu = document.getElementById("settingsDropdown");
  menu.style.display = menu.style.display === "block" ? "none" : "block";
}

function toggleTheme() {
  const html = document.documentElement;
  html.setAttribute(
    "data-theme",
    html.getAttribute("data-theme") === "light" ? "dark" : "light"
  );
}

function logout() {
  localStorage.clear();
  window.location.href = "login.html";
}

document.addEventListener("click", function(e) {
  const dropdown = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".settings-icon");

  if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
    dropdown.style.display = "none";
  }
});
</script>

</body>
</html>
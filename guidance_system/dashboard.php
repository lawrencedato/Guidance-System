<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Student Dashboard</title>

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

      <div class="sidebar-settingsDropdown" id="settingsDropdown">
        <a href="sprofile.php"><i class="fa fa-user"></i> Profile</a>
        <a href="shistory.php"><i class="fa fa-clock"></i> Session History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">
    <a href="dashboard.php" class="active"><i class="fa fa-th-large"></i> Dashboard</a>

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
    <a href="sfeedback.php"><i class="fa fa-comment"></i> Session Feedback</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Hello, Vincent!</h2>
  </div>

  <div class="topbar-right">
    <div class="topbar-user">
      <img src="student.jpg" alt="user">
      <div>
        <strong>Student Name</strong>
        <p>student@email.com</p>
      </div>
    </div>
  </div>
</header>

<!-- MAIN -->
<main class="sDashboard-main">

  <!-- STATS -->
  <section class="sDashboard-stats">

    <div class="sDashboard-card">
      <h4>Upcoming Appointments</h4>
      <h2>2</h2>
    </div>

    <div class="sDashboard-card">
      <h4>Completed Sessions</h4>
      <h2>5</h2>
    </div>

    <div class="sDashboard-card">
      <h4>Active Referrals</h4>
      <h2>1</h2>
    </div>

    <div class="sDashboard-card">
      <h4>Pending Concerns</h4>
      <h2>3</h2>
    </div>

    <div class="card-emergency">
      <h4>Need immediate help?</h4>
      <p>Contact your counselor or hotline</p>
      <p><strong>📞 0912-345-6789</strong></p>
    </div>

  </section>

  <!-- CONTENT -->
  <section class="sDashboard-content">

    <div class="sDashboard-announcement">
      <img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d" class="sDashboard-announcement-img" alt="Announcement">

      <h4>Announcement</h4>
      <h4>Mental Health Awareness Seminar</h4>
      <p>Stress management, emotional balance, and self-care strategies.</p>

      <a class="btn" href="announcements.html?open=mental-health-seminar">
        View Details
      </a>
    </div>

    <div class="sDashboard-side">

      <!-- MOOD -->
      <div class="sDashboard-card">
        <h4>Mood</h4>

        <div class="sDashboard-mood-display" id="moodDisplay">
          No mood recorded yet
        </div>

        <div class="sDashboard-mood">
          <button onclick="setMood('😢','Very Sad')">😢</button>
          <button onclick="setMood('😕','Sad')">😕</button>
          <button onclick="setMood('😐','Neutral')">😐</button>
          <button onclick="setMood('🙂','Happy')">🙂</button>
          <button onclick="setMood('😁','Very Happy')">😁</button>
        </div>
      </div>

      <!-- ACTIVITY -->
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

<!-- RESET PASSWORD MODAL -->
<div class="reset-modal" id="resetModal">
  <div class="reset-box">

    <h2>Security Required</h2>

    <p>You must reset your password before continuing.</p>

    <input type="password" id="newPassword" placeholder="Enter new password">

    <div id="resetError"></div>

    <button onclick="saveNewPassword()">Update Password</button>

  </div>
</div>

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

/* MOOD */
function setMood(emoji, text){
  localStorage.setItem("userMoodEmoji", emoji);
  localStorage.setItem("userMoodText", text);

  document.getElementById("moodDisplay").innerHTML =
    `<div style="font-size:40px">${emoji}</div><div>${text}</div>`;
}

window.addEventListener("load", () => {
  const emoji = localStorage.getItem("userMoodEmoji");
  const text = localStorage.getItem("userMoodText");

  if (emoji && text) {
    document.getElementById("moodDisplay").innerHTML =
      `<div style="font-size:40px">${emoji}</div><div>${text}</div>`;
  }
});
</script>

</body>
</html>
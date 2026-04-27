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
    <a href="sreports.php"><i class="fa fa-ticket"></i> Reports</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="feedback.html"><i class="fa fa-comment"></i> Session Feedback</a>
  </nav>
</aside>
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

  <!-- IMAGE -->
  <img 
    src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d"
    alt="Announcement Image"
    class="sDashboard-announcement-img"
  >

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

  <!-- DISPLAYED MOOD -->
  <div class="sDashboard-mood-display" id="moodDisplay">
    No mood recorded yet
  </div>

  <!-- MOOD BUTTONS -->
  <div class="sDashboard-mood">
    <button class="sWellness-mood-btn" onclick="setMood('😢', 'Very Sad')">😢</button>
    <button class="sWellness-mood-btn" onclick="setMood('😕', 'Sad')">😕</button>
    <button class="sWellness-mood-btn" onclick="setMood('😐', 'Neutral')">😐</button>
    <button class="sWellness-mood-btn" onclick="setMood('🙂', 'Happy')">🙂</button>
    <button class="sWellness-mood-btn" onclick="setMood('😁', 'Very Happy')">😁</button>
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


/* =========================
   MOOD DISPLAY
   CONNECTED TO WELLNESS PAGE
========================= */

/*
IMPORTANT:
Your wellness.html should save mood like this:

localStorage.setItem("userMoodEmoji", "🙂");
localStorage.setItem("userMoodText", "Good");

This dashboard will automatically load it.
*/

function setMood(emoji, text) {
  const moodDisplay = document.getElementById("moodDisplay");

  // Save mood so BOTH dashboard + wellness page use same data
  localStorage.setItem("userMoodEmoji", emoji);
  localStorage.setItem("userMoodText", text);

  // Display centered emoji + text
  moodDisplay.innerHTML = `
    <div style="font-size: 42px;">${emoji}</div>
    <div style="margin-top: 8px; font-weight: 600;">${text}</div>
  `;
  moodDisplay.style.opacity = "1";
}


/* LOAD SAVED MOOD FROM WELLNESS PAGE */
window.addEventListener("load", () => {
  const emoji = localStorage.getItem("userMoodEmoji");
  const text = localStorage.getItem("userMoodText");
  const moodDisplay = document.getElementById("moodDisplay");

  if (emoji && text) {
    moodDisplay.innerHTML = `
      <div style="font-size: 42px;">${emoji}</div>
      <div style="margin-top: 8px; font-weight: 600;">${text}</div>
    `;
    moodDisplay.style.opacity = "1";
  } else {
    moodDisplay.innerHTML = `
      <div style="font-size: 16px; opacity: 0.7;">
        No mood recorded yet
      </div>
    `;
  }
});

function isStrongPassword(password) {
  return /^(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*]).{6,}$/.test(password);
}

/* FORCE MODAL ON FIRST LOGIN */
window.addEventListener("load", () => {
  const passwordChanged = localStorage.getItem("passwordChanged");

  if (!passwordChanged) {
    document.getElementById("resetModal").style.display = "flex";

    // block dashboard interaction
    document.body.style.pointerEvents = "none";
    document.getElementById("resetModal").style.pointerEvents = "auto";
  }
});

/* SAVE NEW PASSWORD */
function saveNewPassword() {
  const pass = document.getElementById("newPassword").value.trim();
  const error = document.getElementById("resetError");

  error.textContent = "";

  if (!pass) {
    error.textContent = "Password is required.";
    return;
  }

  if (!isStrongPassword(pass)) {
    error.textContent =
      "Must include uppercase, number, symbol (min 6 chars).";
    return;
  }

  // save final password
  localStorage.setItem("finalPassword", pass);
  localStorage.setItem("passwordChanged", "true");

  // hide modal
  document.getElementById("resetModal").style.display = "none";

  // unlock dashboard
  document.body.style.pointerEvents = "auto";

  alert("Password updated successfully!");
}
</script>
<!-- =========================
     RESET PASSWORD MODAL
========================= -->
<div class="reset-modal" id="resetModal">

  <div class="reset-box">

    <h2>Security Required</h2>

    <p>
  You must reset your password before continuing. Your temporary password is no longer valid.
</p>

<p>
  Your new password must include an uppercase letter, a number, and a special symbol, with a minimum of 6 characters.
</p>

    <input type="password" id="newPassword" placeholder="Enter new password">

    <div id="resetError" style="color:red; font-size:13px; margin-top:8px;"></div>

    <button onclick="saveNewPassword()">Update Password</button>

  </div>

</div>
</body>
</html>
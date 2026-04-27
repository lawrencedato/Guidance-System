<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Wellness Check</title>

<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="sWellness.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="body">

<!-- ========================= SIDEBAR ========================= -->
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
    <a href="swellness.php" class="active"><i class="fa fa-heart"></i> Wellness Check</a>
    <a href="sreferral.php"><i class="fa fa-route"></i> Referral</a>

    <p class="sidebar-title">UPDATES</p>
    <a href="sannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>

    <p class="sidebar-title">RECORDS</p>
    <a href="sreports.php"><i class="fa fa-ticket"></i> Reports</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="sfeedback.php"><i class="fa fa-comment"></i> Session Feedback</a>
  </nav>
</aside>

<!-- ========================= TOPBAR ========================= -->
<header class="topbar">

  <div class="topbar-left">
    <h1>Wellness Check</h1>
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

<!-- ========================= MAIN ========================= -->
<main class="sWellness-main">

  <!-- WELLNESS CARD -->
  <section class="sWellness-card">

    <h2>How are you feeling today?</h2>

    <!-- MOOD SELECTOR -->
    <div class="sWellness-mood-container">
      <button class="sWellness-mood-btn" onclick="setMood('😢','Very Sad')">😢</button>
      <button class="sWellness-mood-btn" onclick="setMood('😕','Sad')">😕</button>
      <button class="sWellness-mood-btn" onclick="setMood('😐','Neutral')">😐</button>
      <button class="sWellness-mood-btn" onclick="setMood('🙂','Happy')">🙂</button>
      <button class="sWellness-mood-btn" onclick="setMood('😁','Very Happy')">😁</button>
    </div>

    <!-- MOOD DISPLAY -->
    <div class="sWellness-mood-display">
      Selected Mood: <strong id="moodValue">🙂 Neutral</strong>
    </div>

    <!-- STRESS -->
    <div class="sWellness-form-group">
      <label>Stress Level</label>
      <input type="range" min="0" max="100" value="50"
        oninput="updateStress(this.value)">
      <p class="sWellness-stress-display">
        <strong id="stressValue">Moderate (50%)</strong>
      </p>
    </div>

    <!-- SLEEP -->
    <div class="sWellness-form-group">
      <label>Sleep Quality</label>
      <select>
        <option>Good</option>
        <option>Average</option>
        <option>Poor</option>
      </select>
    </div>

  </section>

</main>

<!-- ========================= SCRIPT ========================= -->
<script>
// ================= SETTINGS MENU =================
function toggleSettingsMenu(e){
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}

// ================= THEME TOGGLE =================
function toggleTheme(){
  const html = document.documentElement;
  html.setAttribute(
    "data-theme",
    html.getAttribute("data-theme") === "light" ? "dark" : "light"
  );
}

// ================= LOGOUT =================
function logout(){
  localStorage.clear();
  window.location.href = "login.html";
}

// ================= CLOSE MENU =================
document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");

  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});

// ================= MOOD SYSTEM =================
function setMood(emoji, text) {
  localStorage.setItem("userMoodEmoji", emoji);
  localStorage.setItem("userMoodText", text);

  document.getElementById("moodValue").innerText = `${emoji} ${text}`;
}

// ================= LOAD MOOD =================
window.addEventListener("load", () => {
  const emoji = localStorage.getItem("userMoodEmoji");
  const text = localStorage.getItem("userMoodText");

  if (emoji && text) {
    document.getElementById("moodValue").innerText = `${emoji} ${text}`;
  }
});

// ================= STRESS =================
function updateStress(v){
  let t = v < 30 ? "Low 😌" : v < 70 ? "Moderate 😐" : "High 😰";
  document.getElementById("stressValue").innerText = `${t} (${v}%)`;
}
</script>

</body>
</html>
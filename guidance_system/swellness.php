<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) session_start();

// ===== GUARD: must be logged in =====
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: slogin.php");
    exit;
}

// ===== LOAD STUDENT DATA =====
$conn = new mysqli("127.0.0.1", "root", "", "gcs_db");
$sid  = $conn->real_escape_string($_SESSION['user_id']);

$studentRes = $conn->query("SELECT * FROM students WHERE student_id='$sid' LIMIT 1");
$student    = $studentRes->fetch_assoc();

$profileRes = $conn->query("SELECT profile_image FROM student_profiles WHERE student_id='$sid' LIMIT 1");
$profile    = $profileRes->fetch_assoc();

$fullName   = htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$email      = htmlspecialchars($student['email'] ?? '');
$profileImg = !empty($profile['profile_image'])
              ? htmlspecialchars($profile['profile_image'])
              : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Wellness Check</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
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
    <a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a>

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
    <h2>Wellness Check</h2>
  </div>

  <div class="topbar-right">
    <div class="topbar-user">
      <img src="<?= $profileImg ?>" alt="user"
           onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($fullName) ?>&background=113f67&color=fff'">
      <div>
        <strong><?= $fullName ?></strong>
        <p><?= $email ?></p>
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

function logout() {
  document.getElementById('logoutOverlay').classList.add('show');
}
function closeLogout() {
  document.getElementById('logoutOverlay').classList.remove('show');
}
function confirmLogout() {
    window.location.href = 'logout.php?role=student';
}
// Close when clicking outside
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});

document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");

  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});

function setMood(emoji, text) {
  localStorage.setItem("userMoodEmoji", emoji);
  localStorage.setItem("userMoodText", text);
  document.getElementById("moodValue").innerText = `${emoji} ${text}`;
}

window.addEventListener("load", () => {
  const emoji = localStorage.getItem("userMoodEmoji");
  const text  = localStorage.getItem("userMoodText");
  if (emoji && text) {
    document.getElementById("moodValue").innerText = `${emoji} ${text}`;
  }
});

function updateStress(v){
  let t = v < 30 ? "Low 😌" : v < 70 ? "Moderate 😐" : "High 😰";
  document.getElementById("stressValue").innerText = `${t} (${v}%)`;
}
</script>
<!-- LOGOUT MODAL -->
  <div class="logout-overlay" id="logoutOverlay">
    <div class="logout-modal">
      <div class="logout-icon">
        <i class="fa fa-right-from-bracket"></i>
      </div>
      <h3>Logout</h3>
      <p>Are you sure you want to logout?</p>
      <div class="logout-actions">
        <button class="logout-btn logout-btn--cancel" onclick="closeLogout()">Cancel</button>
        <button class="logout-btn logout-btn--confirm" onclick="confirmLogout()">Yes, Logout</button>
      </div>
    </div>
  </div>

</body>
</html>
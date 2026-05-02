<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) session_start();

// ===== GUARD =====
if (!isset($_SESSION['student_id'])) {
    header("Location: slogin.php");
    exit;
}

// ===== DB CONNECTION =====
$conn = new mysqli("localhost", "root", "", "gcs_db");
$sid  = $conn->real_escape_string($_SESSION['student_id']);

// ===== LOAD STUDENT DATA =====
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
<title>UNITYCARE | Session History</title>

<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="sHistory.css">
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
        <a href="shistory.php" class="active"><i class="fa fa-clock"></i> Session History</a>
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
    <a href="swellness.php"><i class="fa fa-heart"></i> Wellness Check</a>
    <a href="sreferral.php"><i class="fa fa-route"></i> Referral</a>

    <p class="sidebar-title">UPDATES</p>
    <a href="sannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>

    <p class="sidebar-title">RECORDS</p>
    <a href="sreports.php"><i class="fa fa-ticket"></i> Tickets</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="sfeedback.php"><i class="fa fa-comment"></i> Feedback</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">

  <div class="topbar-left">
    <h2>Session History</h2>
  </div>

  <div class="topbar-right">

    <div class="filter-wrapper">

      <button class="btn" onclick="toggleFilterBox()">
        <i class="fa fa-filter"></i> Filter
      </button>

      <div id="filterBox" class="filter-box">

        <input type="date" id="filterDate">

        <div class="filter-actions">
          <button onclick="applyFilter()" class="btn-apply">Apply</button>
          <button onclick="clearFilter()" class="btn-clear">Clear</button>
        </div>

      </div>

    </div>

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

<!-- MAIN -->
<main class="sHistory-main">

  <!-- CARDS -->
  <div class="sHistory-container">

    <div class="sHistory-card" data-date="2026-01-10">
      <h3>Guidance Counselling</h3>
      <span class="tag info">Completed</span>
      <p><b>Date:</b> January 10, 2026</p>
      <p><b>Counselor:</b> Dr. Lawrence Dato</p>
    </div>

    <div class="sHistory-card" data-date="2026-02-02">
      <h3>Guidance Counselling</h3>
      <span class="tag info">Completed</span>
      <p><b>Date:</b> February 02, 2026</p>
      <p><b>Counselor:</b> Dr. Lawrence Dato</p>
    </div>

    <div class="sHistory-card" data-date="2026-03-15">
      <h3>Follow-up Session</h3>
      <span class="tag info">Completed</span>
      <p><b>Date:</b> March 15, 2026</p>
      <p><b>Notes:</b> Improvement observed</p>
    </div>

  </div>

</main>

<!-- SCRIPT -->
<script>

function toggleFilterBox() {
  document.getElementById("filterBox").classList.toggle("show");
}

function applyFilter() {

  const status = document.getElementById("filterStatus")?.value?.toLowerCase();
  const date = document.getElementById("filterDate").value;

  const items = document.querySelectorAll(".sHistory-card");

  items.forEach(item => {

    const itemDate = item.dataset.date;

    let matchStatus = true;
    let matchDate = true;

    if (date && itemDate) {
      matchDate = new Date(itemDate).toDateString() === new Date(date).toDateString();
    }

    item.style.display = (matchStatus && matchDate) ? "block" : "none";
  });
}

function clearFilter() {

  document.getElementById("filterDate").value = "";

  document.querySelectorAll(".sHistory-card").forEach(item => {
    item.style.display = "block";
  });

}

/* SETTINGS */
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
  fetch('logout.php').finally(() => { window.location.href = "slogin.php"; });
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
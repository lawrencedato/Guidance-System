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

// ===== DB CONNECTION =====
$conn = new mysqli("127.0.0.1", "root", "", "gcs_db");
$sid  = $conn->real_escape_string($_SESSION['user_id']);

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
<title>UNITYCARE | Announcements</title>

<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="logout.css">
<link rel="stylesheet" href="sAnnouncements.css">
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
    <a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a>

    <p class="sidebar-title">SERVICES</p>
    <a href="sappointment.php"><i class="fa fa-calendar"></i> Book Appointment</a>
    <a href="sconcerns.php"><i class="fa fa-headset"></i> Submit Concern</a>
    <a href="swellness.php"><i class="fa fa-heart"></i> Wellness Check</a>
    <a href="sreferral.php"><i class="fa fa-route"></i> Referral</a>

    <p class="sidebar-title">UPDATES</p>
    <a href="sannouncements.php" class="active"><i class="fa fa-bullhorn"></i> Announcements</a>

    <p class="sidebar-title">RECORDS</p>
    <a href="sreports.php"><i class="fa fa-ticket"></i> Reports</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="sfeedback.php"><i class="fa fa-comment"></i> Session Feedback</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">

  <div class="topbar-left">
    <h2>Announcements</h2>
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


<main class="sAnnouncements-main">

  <div class="sAnnouncements-container">

    <!-- CARD 1 -->
    <div class="sAnnouncements-card"
      onclick="openModal(
        'Mental Health Seminar',
        'A full session focused on emotional resilience, stress management, and coping strategies for students.',
        '📅 April 25, 2026 <br> ⏰ 2:00 PM – 4:00 PM <br> 📍 Auditorium / Online',
        'https://images.unsplash.com/photo-1521737604893-d14cc237f11d'
      )">

      <h3>Mental Health Seminar</h3>

      <p>
        Learn how to manage stress, build emotional strength, and improve mental wellness.
      </p>

      <small>Click for details</small>
    </div>

    <!-- CARD 2 -->
    <div class="sAnnouncements-card"
      onclick="openModal(
        'Study Skills Workshop',
        'A workshop designed to improve study habits, time management, and academic performance.',
        '📅 April 28, 2026 <br> ⏰ 10:00 AM – 12:00 PM <br> 📍 Learning Center / Online',
        'https://images.unsplash.com/photo-1523240795612-9a054b0db644'
      )">

      <h3>Study Skills Workshop</h3>

      <p>
        Improve your study techniques and academic performance through guided strategies.
      </p>

      <small>Click for details</small>
    </div>

    <!-- CARD 3 -->
    <div class="sAnnouncements-card"
      onclick="openModal(
        'Student Wellness Activity',
        'An interactive group activity promoting teamwork, stress relief, and mental wellness.',
        '📅 May 2, 2026 <br> ⏰ 1:00 PM – 3:00 PM <br> 📍 School Gym',
        'https://images.unsplash.com/photo-1522202176988-66273c2fd55f'
      )">

      <h3>Student Wellness Activity</h3>

      <p>
        Engage in fun group activities that support mental wellness and connection.
      </p>

      <small>Click for details</small>
    </div>

  </div>

</main>

<!-- ================= SCRIPT ================= -->
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

/* SAVE RESPONSE */
function respond(key, value, id) {
  localStorage.setItem(key, value);

  const el = document.getElementById(id);
  el.innerText = value;

  el.style.color = "var(--primary)";
  el.style.fontWeight = "700";
  el.style.transition = "0.3s";
}

/* LOAD SAVED RESPONSES */
function loadResponses() {
  const map = [
    { key: "seminar1", id: "res1" },
    { key: "counselingWeek", id: "res2" },
    { key: "wellnessReminder", id: "res3" }
  ];

  map.forEach(item => {
    const value = localStorage.getItem(item.key);
    const el = document.getElementById(item.id);

    if (value && el) {
      el.innerText = value;
      el.style.color = "var(--primary)";
      el.style.fontWeight = "700";
    }
  });
}

/* AUTO SCROLL FROM DASHBOARD */
window.addEventListener("load", () => {
  loadResponses();

  const hash = window.location.hash;
  if (hash) {
    const target = document.querySelector(hash);
    if (target) {
      target.scrollIntoView({ behavior: "smooth", block: "center" });

      target.style.border = "2px solid var(--primary)";
      setTimeout(() => {
        target.style.border = "none";
      }, 2000);
    }
  }
});

function openModal(title, body, extra, image) {
  document.getElementById("modalTitle").innerText = title;
  document.getElementById("modalBody").innerText = body;
  document.getElementById("modalExtra").innerHTML = extra;

  const img = document.getElementById("modalImage");

  if (image) {
    img.src = image;
    img.style.display = "block";
  } else {
    img.style.display = "none";
  }

  document.getElementById("announcementModal").style.display = "flex";
}

function closeModal() {
  document.getElementById("announcementModal").style.display = "none";
}

/* AUTO OPEN FROM DASHBOARD */
document.addEventListener("DOMContentLoaded", () => {

  const params = new URLSearchParams(window.location.search);
  const openId = params.get("open");

  if (!openId) return;

  const map = {
    "mental-health-seminar": {
      title: "Mental Health Seminar",
      body: "A full session focused on emotional resilience and stress management.",
      extra: "📅 April 25, 2026 <br> ⏰ 2:00 PM – 4:00 PM <br> 📍 Auditorium",
      image: "https://images.unsplash.com/photo-1521737604893-d14cc237f11d"
    }
  };

  if (map[openId]) {
    const d = map[openId];
    openModal(d.title, d.body, d.extra, d.image);
  }

});

</script>


<div id="announcementModal" class="announcement-modal">
  <div class="announcement-modal-content">

    <!-- HEADER -->
    <div class="announcement-header">
      <h2 id="modalTitle"></h2>
      <span class="announcement-close" onclick="closeModal()">&times;</span>
    </div>

    <!-- IMAGE -->
    <img id="modalImage">

    <!-- BODY -->
    <p id="modalBody"></p>

    <div id="modalExtra"></div>

    <!-- INTEREST -->
    <div class="modal-interest">
      <button id="interestBtn" onclick="toggleInterest()">
        ⭐ Interested
      </button>
      <p id="interestCount">0 interested</p>
    </div>

  </div>
</div>

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
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
$conn = new mysqli("localhost", "root", "", "gcs_db");
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_concern') {
    header('Content-Type: application/json');
    $subject = $conn->real_escape_string(trim($_POST['subject'] ?? ''));
    $message = $conn->real_escape_string(trim($_POST['message'] ?? ''));
    if (!$subject || !$message) {
        echo json_encode(['success' => false, 'message' => 'Please complete all fields.']); exit;
    }
    $ok = $conn->query("
        INSERT INTO concerns (student_id, subject, message, status, created_at)
        VALUES ('$sid', '$subject', '$message', 'Pending', NOW())
    ");
    echo json_encode($ok
        ? ['success' => true]
        : ['success' => false, 'message' => 'Failed to submit. Please try again.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Submit Concern</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="body">

<!-- ================= SIDEBAR ================= -->
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
    <a href="sconcerns.php" class="active"><i class="fa fa-headset"></i> Submit Concern</a>
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

<!-- ================= TOPBAR ================= -->
<header class="topbar">

  <div class="topbar-left">
    <h2>Submit Concern</h2>
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

<!-- ================= CONTENT ================= -->
<main class="sConcern-main">

  <div class="sConcern-container">

    <!-- EMERGENCY -->
    <section class="card-emergency">
      <h3><i class="fa fa-triangle-exclamation"></i> Emergency Support</h3>

      <p class="sConcern-text-muted">
        If you are in urgent distress, contact the hotline instead of submitting a form.
      </p>

      <div class="sConcern-emergency-details">
        <p><strong>📞 Hotline:</strong> 0912-345-6789</p>
        <p><strong>🕒 Hours:</strong> Mon–Fri (8:00 AM – 5:00 PM)</p>
        <p><strong>⚡ Response:</strong> Immediate during office hours</p>
      </div>
    </section>

    <!-- FORM -->
    <section class="sConcern-card sConcern-card-form">

      <h3><i class="fa fa-headset"></i> Contact Counselor</h3>

      <p class="sConcern-text-muted">
        Submit your concern and a counselor will respond as soon as possible.
      </p>

      <div class="sConcern-formGroup">

        <label>Subject</label>
        <input type="text" id="subject" placeholder="e.g. Academic Stress">

        <label>Message</label>
        <textarea id="message" rows="6" placeholder="Describe your concern..."></textarea>

        <button class="sConcern-button" onclick="submitConcern()">
          Submit Concern
        </button>

        <div id="result" class="sConcern-result"></div>

      </div>

    </section>

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

function submitConcern() {
  const subject = document.getElementById("subject").value.trim();
  const message = document.getElementById("message").value.trim();
  const result  = document.getElementById("result");

  if (!subject || !message) {
    result.innerHTML = "<span style='color:var(--error,#e53e3e);'>⚠ Please complete all fields.</span>";
    return;
  }

  const fd = new FormData();
  fd.append('action',  'submit_concern');
  fd.append('subject', subject);
  fd.append('message', message);

  fetch('sconcerns.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      result.innerHTML = json.success
        ? "<span style='color:var(--success,#15803d);'>✔ Concern submitted successfully.</span>"
        : "<span style='color:var(--error,#e53e3e);'>❌ " + json.message + "</span>";
      if (json.success) {
        document.getElementById("subject").value = "";
        document.getElementById("message").value = "";
      }
    })
    .catch(() => {
      result.innerHTML = "<span style='color:var(--error,#e53e3e);'>❌ Something went wrong.</span>";
    });
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
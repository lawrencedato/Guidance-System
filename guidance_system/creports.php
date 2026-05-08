<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counselor') {
    header("Location: slogin.php");
    exit;
}

$conn = new mysqli("127.0.0.1", "root", "", "gcs_db");
$cid  = $conn->real_escape_string($_SESSION['user_id']);

$counselorRes = $conn->query("SELECT * FROM counselors WHERE counselor_id='$cid' LIMIT 1");
$counselor    = $counselorRes->fetch_assoc();

$fullName   = htmlspecialchars(($counselor['first_name'] ?? '') . ' ' . ($counselor['last_name'] ?? ''));
$email      = htmlspecialchars($counselor['email'] ?? '');
$profileImg = !empty($counselor['profile_image'])
    ? htmlspecialchars($counselor['profile_image'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';

$pendingCount = (int)$conn->query(
    "SELECT COUNT(*) c FROM appointments WHERE counselor_id='$cid' AND status='Pending'"
)->fetch_assoc()['c'];

// ── POST HANDLER ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_notes') {
    header('Content-Type: application/json');
    $notes = $conn->real_escape_string(trim($_POST['notes'] ?? ''));
    if (!$notes) { echo json_encode(['success' => false, 'message' => 'Notes cannot be empty.']); exit; }
    $ok = $conn->query("INSERT INTO session_notes (counselor_id, notes, created_at) VALUES ('$cid', '$notes', NOW())");
    echo json_encode($ok ? ['success' => true] : ['success' => false, 'message' => 'Failed to save.']);
    exit;
}

?>


<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports - Counselor</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
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
        <a href="cprofile.php"><i class="fa fa-user"></i> Profile</a>
        <a href="chistory.php"><i class="fa fa-clock"></i> History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">
    <a href="counselor.php"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">SESSIONS</p>
    <a href="cappointments.php"><i class="fa fa-calendar-plus"></i> Appointment Requests</a>
    <a href="cconcerns.php"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>
    <a href="cfeedback.php"><i class="fa fa-comment"></i> Session Feedback</a>

    <p class="sidebar-title">STUDENTS</p>
    <a href="cstudents.php"><i class="fa fa-users"></i> Students</a>

    <p class="sidebar-title">REPORTS</p>
    <a href="creports.php" class="active"><i class="fa fa-file"></i> Reports</a>

    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.php"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Reports</h2>
  </div>

  <div class="topbar-right">

    <div class="topbar-icon" onclick="toggleDropdown('notifDropdown', event)">
      <i class="fa fa-bell"></i>
<?php if ($pendingCount > 0): ?>
  <span class="badge"><?= $pendingCount ?></span>
<?php endif; ?>
<div class="icon-dropdown" id="notifDropdown">
  <?php if ($pendingCount > 0): ?>
    <p><?= $pendingCount ?> pending appointment request(s)</p>
  <?php else: ?>
    <p>No new notifications</p>
  <?php endif; ?>
</div>
...
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
<main class="cReports-main">

  <div class="cReports-pageHeader">
    <p class="cReports-muted">
      Counseling summaries and student progress reports
    </p>
  </div>

  <div class="cReports-center">

    <div class="cReports-card">

      <h3>Session Notes</h3>
      <p>Add counselor notes after each session</p>

      <textarea class="cReports-textarea" placeholder="Write session notes here..."></textarea>

      <button class="cReports-btn" onclick="saveNotes(this)">
  Save Notes
</button>

<div class="cReports-status" id="notesStatus"></div>
    </div>

  </div>
<div class="logout-overlay" id="logoutOverlay">
  <div class="logout-modal">
    <div class="logout-icon"><i class="fa fa-right-from-bracket"></i></div>
    <h3>Logout</h3>
    <p>Are you sure you want to logout?</p>
    <div class="logout-actions">
      <button class="logout-btn logout-btn--cancel" onclick="closeLogout()">Cancel</button>
      <button class="logout-btn logout-btn--confirm" onclick="confirmLogout()">Yes, Logout</button>
    </div>
    
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

function logout() {
  document.getElementById('logoutOverlay').classList.add('show');
}
function closeLogout() {
  document.getElementById('logoutOverlay').classList.remove('show');
}
function confirmLogout() {
  window.location.href = 'logout.php?role=counselor';
}
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

function saveNotes(btn) {
  const textarea = btn.previousElementSibling.previousElementSibling;
  const status   = document.getElementById('notesStatus');
  const notes    = textarea.value.trim();

  if (!notes) {
    status.innerHTML = "<span style='color:var(--error,#e53e3e);'>⚠ Please write your notes first.</span>";
    return;
  }

  btn.disabled    = true;
  btn.textContent = 'Saving...';

  const fd = new FormData();
  fd.append('action', 'save_notes');
  fd.append('notes',  notes);

  fetch('creports.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      btn.disabled    = false;
      btn.textContent = 'Save Notes';
      status.innerHTML = json.success
        ? "<span style='color:var(--success,#15803d);'>✔ Notes saved.</span>"
        : "<span style='color:var(--error,#e53e3e);'>❌ " + json.message + "</span>";
    })
    .catch(() => {
      btn.disabled    = false;
      btn.textContent = 'Save Notes';
      status.innerHTML = "<span style='color:var(--error,#e53e3e);'>❌ Something went wrong.</span>";
    });
}

</script>

</body>
</html>
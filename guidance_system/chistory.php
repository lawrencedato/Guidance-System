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
$phone      = htmlspecialchars($counselor['contact_number'] ?? 'N/A');
$profileImg = !empty($counselor['profile_image'])
    ? htmlspecialchars($counselor['profile_image'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';

$pendingCount = (int)$conn->query(
    "SELECT COUNT(*) c FROM appointments WHERE counselor_id='$cid' AND status='Pending'"
)->fetch_assoc()['c'];
?>


<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>UNITYCARE | History</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link rel="stylesheet" href="history.css">
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
        <a href="chistory.php" class="active"><i class="fa fa-clock"></i> History</a>
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
    <a href="creports.php"><i class="fa fa-file"></i> Session Notes</a>

    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.php"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left">
    <h2>History</h2>
  </div>

  <div class="topbar-right">

    <!-- NOTIFICATIONS -->
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
<main class="cHistory-main">

<!-- TABS + FILTER ROW -->
<div class="cHistory-topbarRow">

  <div class="cHistory-tabs">
    <button class="active" onclick="switchTab(event,'sessions')">Past Sessions</button>
    <button onclick="switchTab(event,'announcements')">Announcements</button>
    <button onclick="switchTab(event,'referrals')">Referrals</button>
  </div>

  <div class="filter-wrapper">

    <button class="btn" onclick="toggleFilterBox()">
      <i class="fa fa-filter"></i> Filter
    </button>

    <div id="filterBox" class="filter-box">

      <div class="filter-box-content">

        <select id="filterYear">
          <option value="all">Year Levels</option>
          <option>1st Year</option>
          <option>2nd Year</option>
          <option>3rd Year</option>
          <option>4th Year</option>
        </select>

        <select id="filterProgram">
          <option value="all">Programs</option>
          <option>BSIT</option>
          <option>BSCS</option>
          <option>BSA</option>
          <option>BSBA</option>
          <option>BEED</option>
        </select>

        <select id="filterStatus">
          <option value="all">Status</option>
          <option>Completed</option>
          <option>Expired</option>
        </select>

        <input type="date" id="filterDate">

        <div class="filter-actions">
          <button onclick="applyFilter()" class="btn-apply">Apply</button>
          <button onclick="clearFilter()" class="btn-clear">Clear</button>
        </div>

      </div>

    </div>

  </div>

</div>

<!-- SESSIONS -->
<div id="sessions" class="cHistory-tabContent">
  <table>
    <thead>
      <tr>
        <th>Student</th>
        <th>Date</th>
        <th>Time</th>
        <th>Type</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <tr><td colspan="5" class="empty">No past sessions found</td></tr>
    </tbody>
  </table>
</div>

<!-- ANNOUNCEMENTS -->
<div id="announcements" class="cHistory-tabContent hidden">
  <table>
    <thead>
      <tr>
        <th>Title</th>
        <th>Post Date</th>
        <th>Year Level</th>
        <th>Reach</th>
      </tr>
    </thead>
    <tbody>
      <tr><td colspan="4" class="empty">No past announcements found</td></tr>
    </tbody>
  </table>
</div>

<!-- REFERRALS -->
<div id="referrals" class="cHistory-tabContent hidden">
  <table>
    <thead>
      <tr>
        <th>Student</th>
        <th>Referred To</th>
        <th>Reason</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <tr><td colspan="4" class="empty">No past referrals found</td></tr>
    </tbody>
  </table>
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
function toggleFilterBox() {
  document.getElementById("filterBox").classList.toggle("show");
}

document.addEventListener("click", function(e) {
  const box = document.getElementById("filterBox");
  const btn = document.querySelector(".filter-wrapper button");

  if (!box.contains(e.target) && !btn.contains(e.target)) {
    box.classList.remove("show");
  }
});

function applyFilter() {
  const year = document.getElementById("filterYear").value.toLowerCase();
  const program = document.getElementById("filterProgram").value.toLowerCase();
  const status = document.getElementById("filterStatus").value.toLowerCase();
  const date = document.getElementById("filterDate").value;

  const rows = document.querySelectorAll("tbody tr");

  rows.forEach(row => {
    const text = row.innerText.toLowerCase();

    let matchYear = year === "all" || text.includes(year);
    let matchProgram = program === "all" || text.includes(program);
    let matchStatus = status === "all" || text.includes(status);

    let matchDate = true;
    if (date) {
      matchDate = text.includes(new Date(date).toLocaleDateString().toLowerCase());
    }

    row.style.display = (matchYear && matchProgram && matchStatus && matchDate) ? "" : "none";
  });

  document.getElementById("filterBox").classList.remove("show");
}

function clearFilter() {
  document.getElementById("filterYear").value = "all";
  document.getElementById("filterProgram").value = "all";
  document.getElementById("filterStatus").value = "all";
  document.getElementById("filterDate").value = "";

  document.querySelectorAll("tbody tr").forEach(row => {
    row.style.display = "";
  });
}

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

function switchTab(event, tabId) {
  document.querySelectorAll(".cHistory-tabContent")
    .forEach(t => t.classList.add("hidden"));

  document.getElementById(tabId).classList.remove("hidden");

  document.querySelectorAll(".cHistory-tabs button")
    .forEach(b => b.classList.remove("active"));

  event.target.classList.add("active");
}
</script>

</body>
</html>
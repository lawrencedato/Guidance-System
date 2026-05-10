<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counselor') {
    header("Location: slogin.php");
    exit;
}

$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");
$cid  = $conn->real_escape_string($_SESSION['user_id']);


$counselorRes = $conn->query("SELECT * FROM counselors WHERE counselor_id='$cid' LIMIT 1");
$counselor    = $counselorRes->fetch_assoc();


$fullName   = htmlspecialchars(($counselor['first_name'] ?? '') . ' ' . ($counselor['last_name'] ?? ''));
$email      = htmlspecialchars($counselor['email'] ?? '');
$profileImg = !empty($counselor['profile_image'])
    ? htmlspecialchars($counselor['profile_image'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';
$phone = htmlspecialchars($counselor['contact_number'] ?? 'N/A');


$pendingCount = (int)$conn->query(
    "SELECT COUNT(*) c FROM appointments WHERE counselor_id='$cid' AND status='Pending'"
)->fetch_assoc()['c'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_referral') {
    header('Content-Type: application/json');
    $date    = $conn->real_escape_string($_POST['referral_date']    ?? '');
    $name    = $conn->real_escape_string($_POST['student_name']     ?? '');
    $year    = $conn->real_escape_string($_POST['year_level']       ?? '');
    $course  = $conn->real_escape_string($_POST['course']           ?? '');
    $reason  = $conn->real_escape_string($_POST['reason']           ?? '');
    $remarks = $conn->real_escape_string($_POST['counselor_remarks']?? '');
    if (!$date || !$name || !$year || !$course || !$reason) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']); exit;
    }
    // Match student by name to get student_id
$nameParts = explode(' ', $name, 2);
$firstName = $conn->real_escape_string($nameParts[0] ?? '');
$lastName  = $conn->real_escape_string($nameParts[1] ?? '');
$studentRow = $conn->query(
    "SELECT student_id FROM students WHERE first_name='$firstName' AND last_name='$lastName' LIMIT 1"
)->fetch_assoc();

if (!$studentRow) {
    echo json_encode(['success' => false, 'message' => "Student \"$name\" not found in the system."]);
    exit;
}
$studentId = (int)$studentRow['student_id'];
$ok = $conn->query("
    INSERT INTO referrals (student_id, counselor_id, referral_date, reason, counselor_remarks, created_at)
    VALUES ($studentId, '$cid', '$date', '$reason', '$remarks', NOW())
");
    echo json_encode($ok
        ? ['success' => true]
        : ['success' => false, 'message' => 'Failed to save. Please try again.']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>UNITYCARE | Referral</title>

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
    <a href="creports.php"><i class="fa fa-file"></i> Session Notes</a>

    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.php" class="active"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Referral</h2>
    <p class="topbar-muted">
      Counselors refer students to a more appropriate professional for further assistance.
    </p>
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

<!-- ================= MAIN ================= -->
<main class="cReferral-main">

  <div class="cReferral-card slip">

    <h2>REFERRAL SLIP</h2>

    <div class="slip-row">
      <label>Date:</label>
      <input type="date" class="slip-input" id="refDate">
    </div>

    <h3>Patient Information</h3>

    <div class="slip-row">
      <label>Name:</label>
      <input type="text" class="slip-input" id="refName">
    </div>

    <div class="slip-row">
      <label>Year Level:</label>
      <select class="slip-input" id="refYear">
        <option value="" disabled selected>Select Year Level</option>
        <option value="1st Year">1st Year</option>
        <option value="2nd Year">2nd Year</option>
        <option value="3rd Year">3rd Year</option>
        <option value="4th Year">4th Year</option>
      </select>
    </div>

    <div class="slip-row">
      <label>Program / Course:</label>
      <select class="slip-input" id="refCourse">
        <option value="" disabled selected>Select Program</option>
        <option value="BSIT">BS Information Technology</option>
        <option value="BSCS">BS Computer Science</option>
        <option value="BSA">BS Accountancy</option>
        <option value="BSED">BS Education</option>
        <option value="BSBA">BS Business Administration</option>
        <option value="BSHM">BS Hospitality Management</option>
        <option value="BSTM">BS Tourism Management</option>
      </select>
    </div>

    <h3>Reason for Referral</h3>
    <textarea class="slip-textarea" id="refReason"></textarea>

    <h3>Counselor’s Remarks (Optional)</h3>
    <textarea class="slip-textarea" id="refRemarks"></textarea>

    <h3>Referred by</h3>

    <img src="images/signature.png" class="signature-img" alt="Counselor Signature">

    <p><?= $fullName ?></p>
    <p><b>Contact:</b> <?= $phone ?> | <?= $email ?></p>

    <button class="cReferral-btn" onclick="createReferral()">
      Create Referral
    </button>
    <div id="referralResult" style="margin-top: 12px; font-size: 14px;"></div>
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

<!-- ================= SCRIPT ================= -->
<script>

function toggleSettingsMenu(e){
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}

document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");

  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});

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

function createReferral() {
  const date    = document.getElementById("refDate").value;
  const name    = document.getElementById("refName").value.trim();
  const year    = document.getElementById("refYear").value;
  const course  = document.getElementById("refCourse").value;
  const reason  = document.getElementById("refReason").value.trim();
  const remarks = document.getElementById("refRemarks").value.trim();

  if (!date || !name || !year || !course || !reason) {
    document.getElementById("referralResult").innerHTML =
      "<span style='color:var(--error,#e53e3e);'>⚠ Please complete all required fields.</span>";
    return;
  }

  const fd = new FormData();
  fd.append('action',          'create_referral');
  fd.append('referral_date',   date);
  fd.append('student_name',    name);
  fd.append('year_level',      year);
  fd.append('course',          course);
  fd.append('reason',          reason);
  fd.append('counselor_remarks', remarks);

  fetch('creferral.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      document.getElementById("referralResult").innerHTML = json.success
        ? "<span style='color:var(--success,#15803d);'>✔ Referral created successfully!</span>"
        : "<span style='color:var(--error,#e53e3e);'>❌ " + json.message + "</span>";
    })
    .catch(() => {
      document.getElementById("referralResult").innerHTML =
        "<span style='color:var(--error,#e53e3e);'>❌ Something went wrong.</span>";
    });
}

</script>

</body>
</html>
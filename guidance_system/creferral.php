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

// ── AJAX: lookup student by ID ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['lookup_student_id'])) {
    header('Content-Type: application/json');
    $sid = (int)$_GET['lookup_student_id'];

    $row = $conn->query("
        SELECT s.student_id, s.first_name, s.last_name, s.year_level, s.course
        FROM students s
        INNER JOIN activated_students a ON a.student_id = s.student_id
        WHERE s.student_id = $sid AND a.status = 'active' AND s.archived = 0
        LIMIT 1
    ")->fetch_assoc();

    if ($row) {
        echo json_encode([
            'found'      => true,
            'name'       => $row['first_name'] . ' ' . $row['last_name'],
            'year_level' => $row['year_level'],
            'course'     => $row['course'],
        ]);
    } else {
        echo json_encode(['found' => false]);
    }
    exit;
}

// ── POST: create referral ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_referral') {
    header('Content-Type: application/json');
    $studentId = (int)($_POST['student_id'] ?? 0);
    $date      = $conn->real_escape_string($_POST['referral_date']     ?? '');
    $year      = $conn->real_escape_string($_POST['year_level']        ?? '');
    $course    = $conn->real_escape_string($_POST['course']            ?? '');
    $reason    = $conn->real_escape_string($_POST['reason']            ?? '');
    $remarks   = $conn->real_escape_string($_POST['counselor_remarks'] ?? '');

    if (!$studentId || !$date || !$year || !$course || !$reason) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields.']); exit;
    }

    $verify = $conn->query("
        SELECT s.student_id FROM students s
        INNER JOIN activated_students a ON a.student_id = s.student_id
        WHERE s.student_id = $studentId AND a.status = 'active' AND s.archived = 0
        LIMIT 1
    ")->fetch_assoc();

    if (!$verify) {
        echo json_encode(['success' => false, 'message' => 'Student not found or inactive.']); exit;
    }

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
<style>
  /* ── Student ID lookup row ── */
  .slip-id-row {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 0;
  }
  .slip-id-row .slip-input {
    flex: 1;
  }
  .slip-id-status {
    font-size: 13px;
    min-height: 20px;
    margin-top: 4px;
    margin-bottom: 8px;
    padding-left: 2px;
  }
  .slip-id-status.found    { color: #15803d; }
  .slip-id-status.notfound { color: #e53e3e; }
  .slip-id-status.loading  { color: #888;    }

  /* readonly field styling — name, year level, course */
  input[readonly].slip-input {
    background: var(--input-bg, #f0f4f8);
    color: var(--text-muted, #555);
    cursor: not-allowed;
    opacity: 0.85;
  }
</style>
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

    <!-- Student ID lookup -->
    <div class="slip-row">
      <label>Student ID:</label>
      <div style="flex:1; display:flex; flex-direction:column;">
        <div class="slip-id-row">
          <input
            type="number"
            class="slip-input"
            id="refStudentId"
            placeholder="Enter student ID"
            oninput="lookupStudent(this.value)"
          >
        </div>
        <div class="slip-id-status" id="idStatus"></div>
      </div>
    </div>

    <!-- Name — readonly, filled by lookup -->
    <div class="slip-row">
      <label>Name:</label>
      <input type="text" class="slip-input" id="refName" readonly placeholder="Auto-filled from Student ID">
    </div>

    <!-- Year Level — readonly, filled by lookup -->
    <div class="slip-row">
      <label>Year Level:</label>
      <input type="text" class="slip-input" id="refYear" readonly placeholder="Auto-filled from Student ID">
    </div>

    <!-- Course — readonly, filled by lookup -->
    <div class="slip-row">
      <label>Program / Course:</label>
      <input type="text" class="slip-input" id="refCourse" readonly placeholder="Auto-filled from Student ID">
    </div>

    <h3>Reason for Referral</h3>
    <textarea class="slip-textarea" id="refReason"></textarea>

    <h3>Counselor's Remarks (Optional)</h3>
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

// ── Student ID lookup ────────────────────────────────────────────────────────
let lookupTimer = null;

function lookupStudent(val) {
  const idStatus    = document.getElementById('idStatus');
  const nameField   = document.getElementById('refName');
  const yearField   = document.getElementById('refYear');
  const courseField = document.getElementById('refCourse');

  // Clear previous state
  clearTimeout(lookupTimer);
  nameField.value   = '';
  yearField.value   = '';
  courseField.value = '';

  const id = val.trim();
  if (!id || isNaN(id) || parseInt(id) <= 0) {
    idStatus.textContent = '';
    idStatus.className   = 'slip-id-status';
    return;
  }

  idStatus.textContent = 'Searching…';
  idStatus.className   = 'slip-id-status loading';

  // Debounce — wait 500ms after user stops typing
  lookupTimer = setTimeout(() => {
    fetch(`creferral.php?lookup_student_id=${encodeURIComponent(id)}`)
      .then(r => r.json())
      .then(data => {
        if (data.found) {
          nameField.value   = data.name;
          yearField.value   = data.year_level;
          courseField.value = data.course;
          idStatus.textContent = '✔ Student found';
          idStatus.className   = 'slip-id-status found';
        } else {
          idStatus.textContent = '✘ No active student found with this ID';
          idStatus.className   = 'slip-id-status notfound';
        }
      })
      .catch(() => {
        idStatus.textContent = '✘ Lookup failed. Please try again.';
        idStatus.className   = 'slip-id-status notfound';
      });
  }, 500);
}

// ── Create referral ──────────────────────────────────────────────────────────
function createReferral() {
  const studentId = document.getElementById('refStudentId').value.trim();
  const date      = document.getElementById('refDate').value;
  const name      = document.getElementById('refName').value.trim();
  const year      = document.getElementById('refYear').value.trim();
  const course    = document.getElementById('refCourse').value.trim();
  const reason    = document.getElementById('refReason').value.trim();
  const remarks   = document.getElementById('refRemarks').value.trim();
  const result    = document.getElementById('referralResult');

  if (!studentId || !name) {
    result.innerHTML = "<span style='color:var(--error,#e53e3e);'>⚠ Please enter a valid Student ID first.</span>";
    return;
  }
  if (!date || !year || !course || !reason) {
    result.innerHTML = "<span style='color:var(--error,#e53e3e);'>⚠ Please complete all required fields.</span>";
    return;
  }

  const fd = new FormData();
  fd.append('action',            'create_referral');
  fd.append('student_id',        studentId);
  fd.append('referral_date',     date);
  fd.append('year_level',        year);
  fd.append('course',            course);
  fd.append('reason',            reason);
  fd.append('counselor_remarks', remarks);

  fetch('creferral.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      result.innerHTML = json.success
        ? "<span style='color:var(--success,#15803d);'>✔ Referral created successfully!</span>"
        : "<span style='color:var(--error,#e53e3e);'>❌ " + json.message + "</span>";
    })
    .catch(() => {
      result.innerHTML = "<span style='color:var(--error,#e53e3e);'>❌ Something went wrong.</span>";
    });
}

// ── UI helpers ───────────────────────────────────────────────────────────────
function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById('settingsDropdown').classList.toggle('show');
}
document.addEventListener('click', e => {
  const menu = document.getElementById('settingsDropdown');
  const btn  = document.querySelector('.sidebar-settingsButton');
  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove('show');
  }
});
function toggleTheme() {
  const html = document.documentElement;
  html.setAttribute('data-theme', html.getAttribute('data-theme') === 'light' ? 'dark' : 'light');
}
function logout()        { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()   { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=counselor'; }
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});

</script>
</body>
</html>
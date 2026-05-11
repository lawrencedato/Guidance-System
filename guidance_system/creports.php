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

$pendingCount = (int)$conn->query(
    "SELECT COUNT(*) c FROM appointments WHERE counselor_id='$cid' AND status='Pending'"
)->fetch_assoc()['c'];

// ── AJAX: lookup student by ID ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'lookup_student') {
    header('Content-Type: application/json');
    $sid = (int)($_GET['student_id'] ?? 0);
    if (!$sid) { echo json_encode(['found' => false]); exit; }
    $res = $conn->query("SELECT first_name, last_name, course, year_level FROM students WHERE student_id='$sid' AND archived=0 LIMIT 1");
    $st  = $res ? $res->fetch_assoc() : null;
    if ($st) {
        echo json_encode([
            'found'      => true,
            'name'       => $st['first_name'] . ' ' . $st['last_name'],
            'course'     => $st['course'],
            'year_level' => $st['year_level']
        ]);
    } else {
        echo json_encode(['found' => false]);
    }
    exit;
}

// ── POST: save notes ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_notes') {
    header('Content-Type: application/json');
    $notes      = $conn->real_escape_string(trim($_POST['notes'] ?? ''));
    $student_id = (int)($_POST['student_id'] ?? 0);

    if (!$student_id) { echo json_encode(['success' => false, 'message' => 'Please enter a valid Student ID.']); exit; }
    if (!$notes)      { echo json_encode(['success' => false, 'message' => 'Notes cannot be empty.']); exit; }

    // verify student exists
    $check = $conn->query("SELECT student_id FROM students WHERE student_id='$student_id' AND archived=0 LIMIT 1");
    if (!$check || $check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit;
    }

    $ok = $conn->query("
        INSERT INTO session_notes (counselor_id, student_id, notes, is_sent, created_at)
        VALUES ('$cid', '$student_id', '$notes', 1, NOW())
    ");
    echo json_encode($ok ? ['success' => true] : ['success' => false, 'message' => 'Failed to save.']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Session Notes - Counselor</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
.cReports-idRow {
  display: flex;
  gap: 10px;
  align-items: flex-start;
  margin-bottom: 16px;
}

.cReports-idInput {
  flex: 1;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid rgba(0, 0, 0, 0.08);
  background: rgba(255, 255, 255, 0.6);
  backdrop-filter: blur(8px);
  color: var(--text);
  font-size: 14px;
  outline: none;
  transition: 0.2s ease;
  box-sizing: border-box;
}

.cReports-idInput:focus {
  border-color: #4988C4;
  box-shadow: 0 0 0 4px rgba(73, 136, 196, 0.15);
}

.cReports-studentPreview {
  display: none;
  align-items: center;
  gap: 12px;
  margin-bottom: 18px;
  padding: 12px 16px;
  border-radius: 14px;
  background: rgba(73, 136, 196, 0.08);
  border: 1px solid rgba(73, 136, 196, 0.2);
}

.cReports-studentPreview.visible {
  display: flex;
}

.cReports-studentAvatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}

.cReports-studentInfo strong {
  display: block;
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
}

.cReports-studentInfo span {
  font-size: 12px;
  color: var(--text-muted);
}

.cReports-notFound {
  display: none;
  font-size: 13px;
  color: #e53e3e;
  margin-bottom: 14px;
}

.cReports-notFound.visible {
  display: block;
}


</style>
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
    <a href="cavailability.php"><i class="fa fa-clock"></i> My Availability</a>
    <a href="cconcerns.php"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>
    <a href="cfeedback.php"><i class="fa fa-comment"></i> Session Feedback</a>

    <p class="sidebar-title">STUDENTS</p>
    <a href="cstudents.php"><i class="fa fa-users"></i> Students</a>

    <p class="sidebar-title">REPORTS</p>
    <a href="creports.php" class="active"><i class="fa fa-file"></i> Session Notes</a>

    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.php"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Session Notes</h2>
    <p class="topbar-muted">
      Write and send confidential session notes to students during or after an appointment.
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

<!-- MAIN -->
<main class="cReports-main">


  <div class="cReports-center">
    <div class="cReports-card">
        <div class="cReports-cardHeader">
        <div>
          <h3>Write Session Notes</h3>
          <p>Send confidential notes directly to your student</p>
        </div>
      </div>

      <!-- Student ID Input -->
      <div class="cReports-idRow">
        <input
          type="number"
          class="cReports-idInput"
          id="studentIdInput"
          placeholder="Enter Student ID (e.g. 220001)"
          min="1"
          oninput="lookupStudent(this.value)"
        >
      </div>

      <!-- Not Found Message -->
      <div class="cReports-notFound" id="notFound">
        ⚠ No student found with that ID.
      </div>

      <!-- Student Preview -->
      <div class="cReports-studentPreview" id="studentPreview">
        <img
          class="cReports-studentAvatar"
          id="studentAvatar"
          src=""
          alt="avatar"
        >
        <div class="cReports-studentInfo">
          <strong id="studentName"></strong>
          <span id="studentMeta"></span>
        </div>
      </div>

      <!-- Notes Textarea -->
      <textarea
        class="cReports-textarea"
        id="notesTextarea"
        placeholder="Write session notes here..."
      ></textarea>

      <button class="cReports-btn" id="sendBtn" onclick="saveNotes()" disabled>
        <i class="fa fa-paper-plane"></i> Send to Student
      </button>

      <div class="cReports-status" id="notesStatus"></div>

    </div>
  </div>

  <!-- Logout Modal -->
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
// ── Sidebar / theme / logout ──
function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}
function toggleTheme() {
  const html = document.documentElement;
  html.setAttribute("data-theme", html.getAttribute("data-theme") === "light" ? "dark" : "light");
}
function logout()        { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()   { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=counselor'; }

document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});
document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn  = document.querySelector(".sidebar-settingsButton");
  if (!menu.contains(e.target) && !btn.contains(e.target)) menu.classList.remove("show");
});

// ── Student ID Lookup ──
let lookupTimer = null;
let validStudent = false;

function lookupStudent(val) {
  const preview  = document.getElementById('studentPreview');
  const notFound = document.getElementById('notFound');
  const sendBtn  = document.getElementById('sendBtn');

  // reset
  preview.classList.remove('visible');
  notFound.classList.remove('visible');
  validStudent = false;
  sendBtn.disabled = true;

  const id = val.trim();
  if (!id || id.length < 3) return;

  // debounce — wait 500ms after user stops typing
  clearTimeout(lookupTimer);
  lookupTimer = setTimeout(() => {
    fetch(`creports.php?action=lookup_student&student_id=${encodeURIComponent(id)}`)
      .then(r => r.json())
      .then(json => {
        if (json.found) {
          document.getElementById('studentName').textContent = json.name;
          document.getElementById('studentMeta').textContent = json.year_level + ' — ' + json.course;
          document.getElementById('studentAvatar').src =
            'https://ui-avatars.com/api/?name=' + encodeURIComponent(json.name) + '&background=113f67&color=fff';
          preview.classList.add('visible');
          notFound.classList.remove('visible');
          validStudent = true;
          sendBtn.disabled = false;
        } else {
          notFound.classList.add('visible');
          preview.classList.remove('visible');
        }
      })
      .catch(() => {
        notFound.classList.add('visible');
      });
  }, 500);
}

// ── Save / Send Notes ──
function saveNotes() {
  const status    = document.getElementById('notesStatus');
  const notes     = document.getElementById('notesTextarea').value.trim();
  const studentId = document.getElementById('studentIdInput').value.trim();
  const sendBtn   = document.getElementById('sendBtn');

  if (!validStudent) {
    status.innerHTML = "<span style='color:#e53e3e;'>⚠ Please enter a valid Student ID.</span>";
    return;
  }
  if (!notes) {
    status.innerHTML = "<span style='color:#e53e3e;'>⚠ Please write your notes first.</span>";
    return;
  }

  sendBtn.disabled    = true;
  sendBtn.innerHTML   = '<i class="fa fa-spinner fa-spin"></i> Sending...';

  const fd = new FormData();
  fd.append('action',     'save_notes');
  fd.append('notes',      notes);
  fd.append('student_id', studentId);

  fetch('creports.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      sendBtn.disabled  = false;
      sendBtn.innerHTML = '<i class="fa fa-paper-plane"></i> Send to Student';

      if (json.success) {
        status.innerHTML = "<span style='color:#15803d;'>✔ Note sent successfully.</span>";
        // reset form
        document.getElementById('notesTextarea').value  = '';
        document.getElementById('studentIdInput').value = '';
        document.getElementById('studentPreview').classList.remove('visible');
        document.getElementById('notFound').classList.remove('visible');
        validStudent = false;
        sendBtn.disabled = true;
      } else {
        status.innerHTML = `<span style='color:#e53e3e;'>❌ ${json.message}</span>`;
      }
    })
    .catch(() => {
      sendBtn.disabled  = false;
      sendBtn.innerHTML = '<i class="fa fa-paper-plane"></i> Send to Student';
      status.innerHTML  = "<span style='color:#e53e3e;'>❌ Something went wrong.</span>";
    });
}
</script>

</body>
</html>
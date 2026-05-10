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
    "SELECT COUNT(*) c FROM appointments WHERE status='Pending'"
)->fetch_assoc()['c'];

// ── HANDLE APPROVE / REJECT ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    header('Content-Type: application/json');
    $apptId  = (int)($_POST['appointment_id'] ?? 0);
    $status  = $_POST['status'] ?? '';
    $allowed = ['Approved', 'Rejected'];
    if (!$apptId || !in_array($status, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
    }
    $conn->begin_transaction();
    try {
        // Row-level lock to prevent two counselors from accepting the same appointment simultaneously
        $lock = $conn->query("SELECT appointment_id FROM appointments WHERE appointment_id=$apptId AND status='Pending' FOR UPDATE");
        if (!$lock || $lock->num_rows === 0) throw new Exception("Appointment no longer available or already handled.");
        // When approving: assign this counselor. When rejecting: keep record but mark rejected.
        $ok = $conn->query(
            "UPDATE appointments SET status='$status', counselor_id='$cid'
             WHERE appointment_id=$apptId"
        );
        if (!$ok || $conn->affected_rows === 0) throw new Exception("Could not update. Try again.");
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ── LOAD PENDING APPOINTMENTS ──
$apptRes = $conn->query("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time,
           a.priority, a.message,
           s.student_id, s.first_name, s.last_name, s.course, s.year_level
    FROM appointments a
    JOIN students s ON s.student_id = a.student_id
    WHERE a.status='Pending'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$appointments = [];
while ($row = $apptRes->fetch_assoc()) $appointments[] = $row;
// ── HANDLE GET: student profile for modal ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_student') {
    header('Content-Type: application/json');
    $apptId = (int)($_GET['appointment_id'] ?? 0);
    if (!$apptId) {
        echo json_encode(['success' => false, 'message' => 'Missing ID.']); exit;
    }
    $res = $conn->query("
        SELECT s.first_name, s.last_name, s.email, s.course, s.year_level,
               sp.emergency_contact_name             AS emergency_name,
               sp.relationship_to_emergency_contact  AS emergency_relation,
               sp.emergency_contact_number           AS emergency_number,
               w.mood_label                          AS last_mood,
               DATE_FORMAT(w.created_at, '%M %d, %Y') AS last_wellness
        FROM appointments a
        JOIN students s      ON s.student_id  = a.student_id
        LEFT JOIN student_profiles sp ON sp.student_id = a.student_id
        LEFT JOIN wellness_checks w ON w.wellness_id = (
            SELECT wellness_id FROM wellness_checks
            WHERE student_id = s.student_id
            ORDER BY created_at DESC LIMIT 1
        )
        WHERE a.appointment_id = $apptId
        LIMIT 1
    ");
    $student = $res ? $res->fetch_assoc() : null;
    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Not found.']); exit;
    }
    echo json_encode(['success' => true, 'student' => $student]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Appointment Requests - UNITYCARE</title>

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
    <a href="cappointments.php" class="active"><i class="fa fa-calendar-plus"></i> Appointment Requests</a>
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
    <h2>Appointment Requests</h2>
  </div>

  <div class="topbar-right">

    <div class="topbar-searchBox">
      <i class="fa fa-search"></i>
      <input type="text" placeholder="Search...">
    </div>

    <div class="filter-wrapper">

      <button class="btn" onclick="toggleFilterBox()">
        <i class="fa fa-filter"></i> Filter
      </button>

      <div id="filterBox" class="filter-box">

        <select id="filterPriority">
          <option value="all">Priority</option>
          <option>Low</option>
          <option>Medium</option>
          <option>High</option>
        </select>

        <input type="date" id="filterDate">

        <div class="filter-actions">
          <button onclick="applyFilter()" class="btn-apply">Apply</button>
          <button onclick="clearFilter()" class="btn-clear">Clear</button>
        </div>

      </div>

    </div>

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
<main class="cAppointment-main">

  <section class="cAppointment-grid">

<?php if (empty($appointments)): ?>
  <div style="text-align:center; padding:3rem; color:var(--text-muted); grid-column:1/-1;">
    <i class="fa fa-calendar-check" style="font-size:2.5rem; opacity:0.3; display:block; margin-bottom:1rem;"></i>
    <p>No pending appointment requests.</p>
  </div>
<?php else: ?>
  <?php foreach ($appointments as $appt):
    $sName = htmlspecialchars($appt['first_name'] . ' ' . $appt['last_name']);
    $apptId = (int)$appt['appointment_id'];
  ?>
  <div class="cAppointment-card" data-id="<?= $apptId ?>">
    <h3><i class="fa fa-user"></i> <?= $sName ?></h3>
    <p><b>Reason:</b> <?= htmlspecialchars($appt['message'] ?? 'N/A') ?></p>
    <p><b>Program:</b> <?= htmlspecialchars($appt['year_level'] . ' - ' . $appt['course']) ?></p>
    <p><b>Date:</b> <?= date('F d, Y', strtotime($appt['appointment_date'])) ?></p>
    <p><b>Time:</b> <?= date('g:i A', strtotime($appt['appointment_time'])) ?></p>
    <p><b>Priority:</b> <?= htmlspecialchars($appt['priority']) ?></p>
    <div class="cAppointment-actions">
      <button class="cAppointment-btn approve" onclick="updateStatus(<?= $apptId ?>, 'Approved', this)">
        <i class="fa fa-check"></i> Approve
      </button>
      <button class="cAppointment-btn decline" onclick="updateStatus(<?= $apptId ?>, 'Rejected', this)">
        <i class="fa fa-times"></i> Decline
      </button>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

  </section>
  
<div id="noResultsMsg" style="display:none; text-align:center; padding:2rem; color:var(--text-muted);">
    <i class="fa fa-search" style="font-size:2rem; opacity:0.3; display:block; margin-bottom:0.75rem;"></i>
    <p>No appointments match your filter.</p>
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

<div class="cStudentModal" id="studentModal">

  <div class="cStudentModal-container">

    <div class="cStudentModal-header">
      <h2>Student Profile</h2>
      <button onclick="closeStudentModal()">✕</button>
    </div>

<div class="cStudentModal-body" id="studentModalBody">
  <p style="text-align:center; padding:2rem; color:var(--text-muted);">Loading...</p>
</div>

  </div>
</div>

<script>
function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}

document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn  = document.querySelector(".sidebar-settingsButton");
  if (!menu.contains(e.target) && !btn.contains(e.target)) menu.classList.remove("show");
});

function toggleTheme() {
  const html = document.documentElement;
  html.setAttribute("data-theme", html.getAttribute("data-theme") === "light" ? "dark" : "light");
}

// ── LOGOUT ──
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

// ── NOTIFICATION DROPDOWN ──
function toggleDropdown(id, e) {
  e.stopPropagation();
  document.getElementById(id).classList.toggle("show");
}
document.addEventListener("click", e => {
  const notif = document.getElementById("notifDropdown");
  if (notif && !notif.contains(e.target)) notif.classList.remove("show");
});

// ── FILTER ──
function toggleFilterBox() {
  document.getElementById("filterBox").classList.toggle("show");
}

function applyFilter() {
  const priority = document.getElementById("filterPriority").value.toLowerCase();
  const date     = document.getElementById("filterDate").value;
  let   visible  = 0;

  document.querySelectorAll(".cAppointment-card").forEach(card => {
    const matchP = priority === "all" || card.dataset.priority === priority;
    const matchD = !date || card.dataset.date === date;
    const show   = matchP && matchD;
    card.style.display = show ? "" : "none";
    if (show) visible++;
  });

  document.getElementById("noResultsMsg").style.display = visible === 0 ? "block" : "none";
}

function clearFilter() {
  document.getElementById("filterPriority").value = "all";
  document.getElementById("filterDate").value     = "";
  document.querySelectorAll(".cAppointment-card").forEach(c => c.style.display = "");
  document.getElementById("noResultsMsg").style.display = "none";
}

// ── SEARCH ──
document.querySelector(".topbar-searchBox input").addEventListener("input", function() {
  const q       = this.value.toLowerCase();
  let   visible = 0;

  document.querySelectorAll(".cAppointment-card").forEach(card => {
    const show = card.dataset.name.includes(q);
    card.style.display = show ? "" : "none";
    if (show) visible++;
  });

  document.getElementById("noResultsMsg").style.display = visible === 0 ? "block" : "none";
});

// ── APPROVE / REJECT ──
function updateStatus(apptId, status, btn) {
  if (!confirm(`${status === 'Approved' ? 'Approve' : 'Decline'} this appointment?`)) return;

  const fd = new FormData();
  fd.append('action',         'update_status');
  fd.append('appointment_id', apptId);
  fd.append('status',         status);

  fetch('cappointments.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        const card = btn.closest('.cAppointment-card');
        card.style.opacity    = '0';
        card.style.transform  = 'scale(0.95)';
        card.style.transition = '0.3s ease';
        setTimeout(() => card.remove(), 300);
      } else {
        alert(json.message || 'Failed to update.');
      }
    })
    .catch(() => alert('Something went wrong.'));
}

// ── STUDENT MODAL ──
function openStudentModal(apptId) {
  document.getElementById("studentModal").classList.add("show");
  const body = document.getElementById("studentModalBody");
  body.innerHTML = '<p style="text-align:center;padding:2rem;color:var(--text-muted);">Loading...</p>';

  fetch('cappointments.php?action=get_student&appointment_id=' + apptId)
    .then(r => r.json())
    .then(json => {
      if (!json.success) {
        body.innerHTML = '<p style="padding:2rem;color:var(--text-muted);">Could not load profile.</p>';
        return;
      }
      const s        = json.student;
      const initials = (s.first_name[0] + s.last_name[0]).toUpperCase();
      body.innerHTML = `
        <div class="cStudentModal-profile">
          <div class="cStudentModal-avatar">${initials}</div>
          <div class="cStudentModal-profileText">
            <div class="cStudentModal-nameRow">
              <h3>${s.first_name} ${s.last_name}</h3>
              <span class="tag stable">Active</span>
            </div>
            <p>${s.course} • ${s.year_level}</p>
          </div>
        </div>
        <div class="cStudentModal-grid" style="margin-top:12px;">
          <div class="cStudentModal-box">
            <h4>Academic Information</h4>
            <p><b>Program:</b> ${s.course}</p>
            <p><b>Year Level:</b> ${s.year_level}</p>
            <p><b>Email:</b> ${s.email}</p>
          </div>
          <div class="cStudentModal-box">
            <h4>Emergency Contact</h4>
            <p><b>Name:</b> ${s.emergency_name || 'N/A'}</p>
            <p><b>Relation:</b> ${s.emergency_relation || 'N/A'}</p>
            <p><b>Contact:</b> ${s.emergency_number || 'N/A'}</p>
          </div>
        </div>
        <div class="cStudentModal-box" style="margin-top:12px;">
          <h4>Last Wellness Check-in</h4>
          <p><b>Mood:</b> ${s.last_mood || 'N/A'}</p>
          <p><b>Date:</b> ${s.last_wellness || 'No check-in yet'}</p>
        </div>`;
    })
    .catch(() => {
      body.innerHTML = '<p style="padding:2rem;color:var(--text-muted);">Could not load profile.</p>';
    });
}

function closeStudentModal() {
  document.getElementById("studentModal").classList.remove("show");
}
</script>

</body>
</html>
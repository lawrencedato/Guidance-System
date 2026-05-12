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
$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");
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


$apptRes = $conn->query("
    SELECT appointment_id, appointment_date, appointment_time, status
    FROM appointments
    WHERE student_id = '$sid'
    ORDER BY appointment_date DESC
");
$apptList = [];
while ($r = $apptRes->fetch_assoc()) $apptList[] = $r;

$latestApproved = null;
foreach ($apptList as $a) {
    if ($a['status'] === 'Approved') { $latestApproved = $a; break; }
}

$rejectedRes = $conn->query("
    SELECT appointment_id, appointment_date, appointment_time,
           rejection_reason, priority
    FROM appointments
    WHERE student_id = '$sid'
      AND status = 'Rejected'
    ORDER BY appointment_date DESC
");
$rejectedList = [];
while ($r = $rejectedRes->fetch_assoc()) $rejectedList[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Session Reports</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<style>
  .sidebar-menu a {
  display: flex;
  align-items: center;
  gap: 8px;
}

.referral-badge {
  width: 9px;
  height: 9px;
  background: rgba(147, 197, 253, 0.35);
  border: 1.5px solid rgba(147, 197, 253, 0.75);
  border-radius: 50%;
  margin-left: auto;
  flex-shrink: 0;
  box-shadow: 0 0 6px rgba(147, 197, 253, 0.5);
  backdrop-filter: blur(4px);
}
</style>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<!-- EXPORT LIB -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
        <a href="shistory.php"><i class="fa fa-clock"></i> History</a>
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
    <a href="sreferral.php" class="<?= basename($_SERVER['PHP_SELF']) === 'sreferral.php' ? 'active' : '' ?>">
            <i class="fa fa-route"></i> Referral
            <span class="referral-badge" id="referralBadge" style="display:none;"></span>
        </a>
    <p class="sidebar-title">UPDATES</p>
    <a href="sannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>

    <p class="sidebar-title">RECORDS</p>
    <a href="sreports.php" class="active"><i class="fa fa-ticket"></i> Reports</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="sfeedback.php"><i class="fa fa-comment"></i> Session Feedback</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">

  <div class="topbar-left">
    <h2>Reports</h2>
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

<!-- MAIN -->
<main class="sReports-main">

  <div class="sReports-container">

    <!-- REPORT CARD -->
    <div class="card sReports-card" id="notesSection">

      <h3 class="sReports-card-title">
        <i class="fa fa-notes-medical"></i> Session Report
      </h3>

      <p class="sReports-card-desc">
        This section contains guidance and recommendations provided by your counselor.
      </p>

      <div class="sReports-notesBox" id="notesContent">
        No notes available yet.
      </div>

      <button class="sReports-exportBtn" onclick="exportNotesPDF()">
        <i class="fa fa-file-pdf"></i> Export Report
      </button>

    </div>

    <!-- CURRENT TICKET -->
    <!-- CURRENT TICKET -->
<div class="card sReports-card">
  <h3 class="sReports-card-title">
    <i class="fa fa-ticket"></i> Your Session Ticket
  </h3>
  <p class="sReports-card-desc">
    This ticket is generated when a counselor approves your appointment.
  </p>

  <?php if ($latestApproved): ?>
    <div style="margin-top:10px; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
      <span style="font-size:13px; color:var(--text-muted); font-weight:600;">Status:</span>
      <span style="background:rgba(34,197,94,0.12); border:1px solid rgba(34,197,94,0.3); color:#15803d; font-size:12px; font-weight:700; padding:4px 14px; border-radius:999px; display:inline-flex; align-items:center; gap:6px;">
        <i class="fa fa-check"></i> <?= htmlspecialchars($latestApproved['status']) ?>
      </span>
    </div>

    <div class="sReports-ticket-section" style="margin-top:14px;">
      <p style="font-size:12px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Ticket ID</p>
      <div class="sReports-ticket-box" style="
        display:inline-flex; align-items:center; gap:10px;
        background:rgba(73,136,196,0.08);
        border:1px solid rgba(73,136,196,0.2);
        border-radius:14px;
        padding:12px 20px;
        font-size:18px; font-weight:700;
        color:#113F67;
        letter-spacing:1px;
        font-family:'Courier New',monospace;
      ">
        <i class="fa fa-ticket" style="font-size:16px; color:#4988C4;"></i>
        APPT-<?= $latestApproved['appointment_id'] ?>
      </div>
    </div>

    <div style="margin-top:12px; display:flex; gap:20px; flex-wrap:wrap;">
      <div style="font-size:13px; color:var(--text-muted);">
        <i class="fa fa-calendar" style="color:#4988C4; margin-right:5px;"></i>
        <b>Date:</b> <?= date('F d, Y', strtotime($latestApproved['appointment_date'])) ?>
      </div>
      <div style="font-size:13px; color:var(--text-muted);">
        <i class="fa fa-clock" style="color:#4988C4; margin-right:5px;"></i>
        <b>Time:</b> <?= date('g:i A', strtotime($latestApproved['appointment_time'])) ?>
      </div>
    </div>

    <a href="sticket_pdf.php?id=<?= $latestApproved['appointment_id'] ?>"
       target="_blank"
       class="sReports-exportBtn"
       style="display:inline-flex; align-items:center; gap:8px; margin-top:18px; text-decoration:none; width:fit-content; padding:12px 20px;">
      <i class="fa fa-file-pdf"></i> Download Ticket PDF
    </a>

  <?php else: ?>
    <div style="text-align:center; padding:2.5rem 1rem; color:var(--text-muted);">
      <i class="fa fa-ticket" style="font-size:2.5rem; opacity:0.2; display:block; margin-bottom:1rem;"></i>
      <p style="margin:0; font-size:13px;">No approved appointments yet. Book an appointment to receive your session ticket.</p>
    </div>
  <?php endif; ?>

      <div class="sReports-status">
  <b>Status:</b>
  <span><?= $latestApproved ? htmlspecialchars($latestApproved['status']) : 'No approved appointments yet' ?></span>
</div>


    </div>

    <!-- HISTORY -->
    <div class="card sReports-card">

      <h3 class="sReports-card-title">
        <i class="fa fa-clock"></i> Ticket History
      </h3>

      <p class="sReports-card-desc">
        View all previously generated tickets.
      </p>

     <p>Total Tickets: <span><?= count($apptList) ?></span></p>
    <ul class="sReports-history-list">
      <?php foreach ($apptList as $ap): ?>
        <li class="sReports-history-item">
          <div class="sReports-history-header">
            <span class="sReports-history-id">APPT-<?= $ap['appointment_id'] ?></span>
            <span class="sReports-history-badge sReports-history-badge--<?= strtolower($ap['status']) ?>">
              <?= htmlspecialchars($ap['status']) ?>
            </span>
          </div>
          <div class="sReports-history-meta">
            <span><i class="fa fa-calendar"></i><?= date('F d, Y', strtotime($ap['appointment_date'])) ?></span>
            <span><i class="fa fa-clock"></i><?= date('g:i A', strtotime($ap['appointment_time'])) ?></span>
          </div>
        </li>
      <?php endforeach; ?>
      <?php if (empty($apptList)): ?>
        <li class="sReports-history-empty">
          <i class="fa fa-ticket"></i>
          <p>No appointments yet.</p>
        </li>
      <?php endif; ?>
    </ul>

    </div>

    <div class="card sReports-card">

  <h3 class="sReports-card-title">
    <i class="fa fa-times-circle" style="color:#ef4444;"></i> Rejected Appointments
  </h3>

  <p class="sReports-card-desc">
    These appointments were declined by the counselor. You may rebook for another available date.
  </p>

  <?php if (empty($rejectedList)): ?>
    <div class="sReports-rejected-empty">
      <i class="fa fa-check-circle"></i>
      <p>No rejected appointments.</p>
    </div>
  <?php else: ?>
    <ul class="sReports-rejected-list">
      <?php foreach ($rejectedList as $r): ?>
        <li class="sReports-rejected-item">

          <div class="sReports-rejected-header">
            <span class="sReports-rejected-id">APPT-<?= $r['appointment_id'] ?></span>
            <span class="sReports-rejected-badge">Rejected</span>
          </div>

          <div class="sReports-rejected-meta">
            <span><i class="fa fa-calendar"></i><?= date('F d, Y', strtotime($r['appointment_date'])) ?></span>
            <span><i class="fa fa-clock"></i><?= date('g:i A', strtotime($r['appointment_time'])) ?></span>
          </div>

          <?php if (!empty($r['rejection_reason'])): ?>
            <div class="sReports-rejected-reason">
              <span class="sReports-rejected-reason-label">Reason</span>
              <?= htmlspecialchars($r['rejection_reason']) ?>
            </div>
          <?php endif; ?>

          <a href="sappointment.php" class="sReports-rejected-rebook">
            <i class="fa fa-calendar-plus"></i> Rebook Appointment
          </a>

        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

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
</main>

<script>

(function() {
    const saved = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", saved);
})();

function toggleSettingsMenu(e){
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}

function toggleTheme() {
    const html = document.documentElement;
    const newTheme = html.getAttribute("data-theme") === "light" ? "dark" : "light";
    html.setAttribute("data-theme", newTheme);
    localStorage.setItem("theme", newTheme);
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

/* CLOSE DROPDOWN */
document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");

  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});

/* EXPORT REPORT */
function exportNotesPDF() {
  const element = document.getElementById("notesSection");

  const opt = {
    margin: 10,
    filename: 'Session_Report.pdf',
    image: { type: 'jpeg', quality: 1 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: 'mm', format: 'a4' }
  };

  html2pdf().set(opt).from(element).save();
}

async function checkReferralBadge() {
  try {
    const res = await fetch('scheck_referral.php');
    const data = await res.json();
    const badge = document.getElementById('referralBadge');
    if (badge) {
      badge.style.display = data.unseen > 0 ? 'inline-block' : 'none';
    }
  } catch (e) {}
}

checkReferralBadge();
</script>

</body>
</html>
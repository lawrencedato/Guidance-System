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

// ===== HANDLE FEEDBACK SUBMISSION =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_feedback') {
    header('Content-Type: application/json');

    $rating  = $conn->real_escape_string($_POST['rating']  ?? '');
    $message = $conn->real_escape_string($_POST['message'] ?? '');
    $apptId  = (int)($_POST['appointment_id'] ?? 0);

    $allowed = ['Poor', 'Fair', 'Good', 'Very Good', 'Excellent'];
    if (!in_array($rating, $allowed) || !$message || !$apptId) {
        echo json_encode(['success' => false, 'message' => 'Please complete all fields.']);
        exit;
    }

    // Verify the appointment belongs to this student and is Completed
    $check = $conn->query("
        SELECT appointment_id, counselor_id FROM appointments
        WHERE appointment_id = $apptId
          AND student_id = '$sid'
          AND status = 'Completed'
        LIMIT 1
    ");
    $appt = $check ? $check->fetch_assoc() : null;

    if (!$appt) {
        echo json_encode(['success' => false, 'message' => 'Invalid or ineligible appointment.']);
        exit;
    }

    $counselorId = (int)$appt['counselor_id'];
    $ok = $conn->query("
        INSERT IGNORE INTO feedback (student_id, counselor_id, appointment_id, rating, message, created_at)
        VALUES ('$sid', $counselorId, $apptId, '$rating', '$message', NOW())
    ");

    echo json_encode($ok && $conn->affected_rows > 0
        ? ['success' => true]
        : ['success' => false, 'message' => 'Feedback already submitted for this session.']);
    exit;
}

// ===== LOAD STUDENT DATA =====
$studentRes = $conn->query("SELECT * FROM students WHERE student_id='$sid' LIMIT 1");
$student    = $studentRes->fetch_assoc();

$profileRes = $conn->query("SELECT profile_image FROM student_profiles WHERE student_id='$sid' LIMIT 1");
$profile    = $profileRes->fetch_assoc();

// ===== LOAD COMPLETED APPOINTMENTS WITHOUT FEEDBACK =====
$pendingRes = $conn->query("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time,
           CONCAT(c.first_name, ' ', c.last_name) AS counselor_name
    FROM appointments a
    JOIN counselors c ON a.counselor_id = c.counselor_id
    LEFT JOIN feedback f ON f.appointment_id = a.appointment_id
    WHERE a.student_id = '$sid'
      AND a.status = 'Completed'
      AND f.feedback_id IS NULL
    ORDER BY a.appointment_date DESC
");
$pendingAppointments = [];
while ($row = $pendingRes->fetch_assoc()) {
    $pendingAppointments[] = $row;
}

$fullName   = htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$email      = htmlspecialchars($student['email'] ?? '');
$profileImg = !empty($profile['profile_image'])
              ? htmlspecialchars($profile['profile_image'])
              : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Session Feedback</title>

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

  .sFeedback-empty {
    text-align: center;
    padding: 32px 0;
    color: var(--muted, #888);
    font-size: 14px;
  }

  .sFeedback-empty i {
    font-size: 36px;
    margin-bottom: 12px;
    display: block;
    opacity: 0.4;
  }
</style>
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
    <a href="sreports.php"><i class="fa fa-ticket"></i> Reports</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="sfeedback.php" class="active"><i class="fa fa-comment"></i> Session Feedback</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">

  <div class="topbar-left">
    <h2>Session Feedback</h2>
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
<main class="sFeedback-main">

  <div class="sFeedback-container">

    <div class="card sFeedback-card">

      <h3 class="sFeedback-title">How was your session?</h3>

      <p class="sFeedback-muted">
        Rate your experience and leave your comments
      </p>

      <div class="sFeedback-form">

        <?php if (empty($pendingAppointments)): ?>

          <div class="sFeedback-empty">
            <i class="fa fa-comment-slash"></i>
            No completed sessions available for feedback at this time.
          </div>

        <?php else: ?>

          <div class="form-group">
            <label>Session</label>
            <select id="appointmentSelect">
              <?php foreach ($pendingAppointments as $appt): ?>
                <option value="<?= $appt['appointment_id'] ?>">
                  <?= htmlspecialchars($appt['counselor_name']) ?> —
                  <?= date('M d, Y', strtotime($appt['appointment_date'])) ?>
                  at <?= date('g:i A', strtotime($appt['appointment_time'])) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label>Rating</label>
            <select id="ratingSelect">
              <option value="Poor">⭐ Poor</option>
              <option value="Fair">⭐⭐ Fair</option>
              <option value="Good">⭐⭐⭐ Good</option>
              <option value="Very Good">⭐⭐⭐⭐ Very Good</option>
              <option value="Excellent">⭐⭐⭐⭐⭐ Excellent</option>
            </select>
          </div>

          <div class="form-group">
            <label>Feedback</label>
            <textarea rows="6" placeholder="Write your feedback here..."></textarea>
          </div>

          <button class="sFeedback-btn sFeedback-submit" onclick="submitFeedback()">
            Submit Feedback
          </button>
          <div id="feedbackResult" style="margin-top:12px; font-size:14px;"></div>

        <?php endif; ?>

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

</main>

<script>
(function() {
    const saved = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", saved);
})();

function toggleSettingsMenu(e) {
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

document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});

document.addEventListener("click", function(e) {
  const dropdown = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");
  if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
    dropdown.classList.remove("show");
  }
});

function submitFeedback() {
  const apptSelect = document.getElementById('appointmentSelect');
  const appointmentId = apptSelect ? apptSelect.value : null;
  const rating  = document.getElementById('ratingSelect').value;
  const message = document.querySelector('.sFeedback-form textarea').value.trim();
  const result  = document.getElementById('feedbackResult');

  if (!message) {
    result.innerHTML = "<span style='color:var(--error,#e53e3e);'>⚠ Please write your feedback first.</span>";
    return;
  }

  const fd = new FormData();
  fd.append('action',         'submit_feedback');
  fd.append('appointment_id', appointmentId);
  fd.append('rating',         rating);
  fd.append('message',        message);

  fetch('sfeedback.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        result.innerHTML = "<span style='color:var(--success,#15803d);'>✔ Feedback submitted. Thank you!</span>";

        // Remove submitted option from dropdown
        apptSelect.remove(apptSelect.selectedIndex);
        document.querySelector('.sFeedback-form textarea').value = '';

        // If no more sessions, replace form with empty state
        if (apptSelect.options.length === 0) {
          document.querySelector('.sFeedback-form').innerHTML = `
            <div class="sFeedback-empty">
              <i class="fa fa-comment-slash"></i>
              No completed sessions available for feedback at this time.
            </div>
          `;
        }
      } else {
        result.innerHTML = "<span style='color:var(--error,#e53e3e);'>❌ " + json.message + "</span>";
      }
    })
    .catch(() => {
      result.innerHTML = "<span style='color:var(--error,#e53e3e);'>❌ Something went wrong.</span>";
    });
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
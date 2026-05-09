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
$conn = new mysqli("127.0.0.1", "root", "", "gcs_db");
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book') {
    header('Content-Type: application/json');
    $date     = $conn->real_escape_string($_POST['date']     ?? '');
    $time     = $conn->real_escape_string($_POST['time']     ?? '');
    $message  = $conn->real_escape_string($_POST['message']  ?? '');
    $priority = $conn->real_escape_string($_POST['priority'] ?? 'Normal');

    // Get an available counselor (least appointments today)
    $cRes = $conn->query("
        SELECT c.counselor_id
        FROM counselors c
        WHERE c.status = 'Active'
        ORDER BY (
            SELECT COUNT(*) FROM appointments a
            WHERE a.counselor_id = c.counselor_id
            AND a.appointment_date = '$date'
        ) ASC
        LIMIT 1
    ");
    $counselorRow = $cRes ? $cRes->fetch_assoc() : null;

    if (!$counselorRow) {
        echo json_encode(['success' => false, 'message' => 'No available counselors for this date.']); exit;
    }

    $assignedCid = $counselorRow['counselor_id'];
    $ok = $conn->query("
        INSERT INTO appointments (student_id, counselor_id, appointment_date, appointment_time, message, priority, status, created_at)
        VALUES ('$sid', '$assignedCid', '$date', '$time', '$message', '$priority', 'Pending', NOW())
    ");
    echo json_encode($ok
        ? ['success' => true]
        : ['success' => false, 'message' => 'Failed to book. Please try again.']);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Appointment Booking</title>

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
    <a href="sappointment.php" class="active"><i class="fa fa-calendar"></i> Book Appointment</a>
    <a href="sconcerns.php"><i class="fa fa-headset"></i> Submit Concern</a>
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

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Book Appointment</h2>
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
<main class="sBooking-main">

<div class="sBooking-card sBooking-booking">

  <h3>Schedule Appointment</h3>

  <div class="sBooking-formGroup">
    <label>Available Slots</label>
    <div id="slots" class="sBooking-slots"></div>
  </div>

  <div class="sBooking-grid">

    <div class="sBooking-left">

      <div class="sBooking-priority">
        <label>Priority</label>
        <select id="priority">
          <option value="all">Priority</option>
          <option>Low</option>
          <option>Medium</option>
          <option>High</option>
        </select>
      </div>

      <div class="sBooking-date">
        <label>Date</label>
        <input type="date" id="date">
      </div>

      <div class="sBooking-time">
        <label>Time</label>
        <input type="time" id="time">
      </div>

    </div>

    <div class="sBooking-message">
      <label>Message</label>
      <textarea id="message" placeholder="Describe your concern..."></textarea>
    </div>

  </div>

  <button class="sBooking-button" onclick="bookAppointment()">
    Confirm Booking
  </button>

  <div id="bookingResult"></div>

</div>

<div class="sBooking-card">
  <h3>Upload Documents</h3>
  <p>You may upload supporting documents.</p>

  <input type="file" id="fileInput">
  <p id="fileName"></p>

  <button class="sBooking-button">Upload File</button>
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

document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");

  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});

/* SLOTS */
function generateSlots(){
  return ["10:00","11:00","14:00","15:00","16:00","17:00"];
}

const defaultSlots = generateSlots();
let bookedSlots = [];

function formatTime(time){
  let [h,m] = time.split(":");
  let hour = +h;
  const ampm = hour >= 12 ? "PM" : "AM";
  hour = hour % 12 || 12;
  return `${hour}:${m} ${ampm}`;
}

function renderSlots(){
  const container = document.getElementById("slots");
  container.innerHTML = "";

  defaultSlots.forEach(time => {
    const btn = document.createElement("button");
    btn.className = "sBooking-slotBtn";

    if (bookedSlots.includes(time)){
      btn.textContent = formatTime(time) + " Taken";
      btn.disabled = true;
    } else {
      btn.textContent = formatTime(time);
      btn.onclick = () => {
        document.getElementById("time").value = time;
      };
    }

    container.appendChild(btn);
  });
}

function bookAppointment() {
  const d        = document.getElementById("date").value;
  const t        = document.getElementById("time").value;
  const msg      = document.getElementById("message").value.trim();
  const priority = document.getElementById("priority").value;
  const result   = document.getElementById("bookingResult");

  if (!d || !t) {
    result.innerHTML = "<span style='color:var(--error,#e53e3e);'>⚠ Please select a date and time.</span>";
    return;
  }

  const fd = new FormData();
  fd.append('action',   'book');
  fd.append('date',     d);
  fd.append('time',     t);
  fd.append('message',  msg);
  fd.append('priority', priority);

  fetch('sappointment.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      result.innerHTML = json.success
        ? "<span style='color:var(--success,#15803d);'>✔ Appointment submitted successfully!</span>"
        : "<span style='color:var(--error,#e53e3e);'>❌ " + json.message + "</span>";
      if (json.success) {
        document.getElementById("date").value    = "";
        document.getElementById("time").value    = "";
        document.getElementById("message").value = "";
      }
    })
    .catch(() => {
      result.innerHTML = "<span style='color:var(--error,#e53e3e);'>❌ Something went wrong.</span>";
    });
}

window.onload = renderSlots;
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
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    header('Content-Type: application/json');
    $apptId  = (int)($_POST['appointment_id'] ?? 0);
    $status  = $_POST['status'] ?? '';
    $allowed = ['Approved', 'Rejected'];
    if (!$apptId || !in_array($status, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
    }
    $ok = $conn->query(
        "UPDATE appointments SET status='$status' WHERE appointment_id=$apptId AND counselor_id='$cid'"
    );
    echo json_encode($ok && $conn->affected_rows > 0
        ? ['success' => true]
        : ['success' => false, 'message' => 'Could not update.']);
    exit;
}

$fullName   = htmlspecialchars(($counselor['first_name'] ?? '') . ' ' . ($counselor['last_name'] ?? ''));
$email      = htmlspecialchars($counselor['email'] ?? '');
$profileImg = !empty($counselor['profile_image'])
    ? htmlspecialchars($counselor['profile_image'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';


$pendingCount = (int)$conn->query(
    "SELECT COUNT(*) c FROM appointments WHERE counselor_id='$cid' AND status='Pending'"
)->fetch_assoc()['c'];

// Add to PHP header after $pendingCount:
$apptRes = $conn->query("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time,
           a.status, a.priority, a.message,
           s.student_id, s.first_name, s.last_name, s.course, s.year_level
    FROM appointments a
    JOIN students s ON s.student_id = a.student_id
    WHERE a.counselor_id='$cid' AND a.status='Pending'
    ORDER BY a.appointment_date ASC
");
$appointments = [];
while ($row = $apptRes->fetch_assoc()) $appointments[] = $row;
?>


<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Appointment Requests - UNITYCARE</title>

<link rel="stylesheet" href="style.css">
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
    <a href="creports.php"><i class="fa fa-file"></i> Reports</a>

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

</main>

<div class="cStudentModal" id="studentModal">

  <div class="cStudentModal-container">

    <div class="cStudentModal-header">
      <h2>Student Profile</h2>
      <button onclick="closeStudentModal()">✕</button>
    </div>

    <div class="cStudentModal-body">

      <div class="cStudentModal-profile">

        <div class="cStudentModal-avatar">JS</div>

        <div class="cStudentModal-profileText">

          <div class="cStudentModal-nameRow">
            <h3>Adolf</h3>

            <span id="studentStatusTag" class="tag stable">
              Stable
            </span>
          </div>

          <p>BSIT • 2nd Year</p>

        </div>

      </div>

      <div class="cStudentModal-box">
        <h4>Wellness Progress: Good</h4>
        <p><b>Overall Score:</b> 82%</p>
        <p><b>Recent Check-in:</b> April 22</p>
      </div>

    </div>

  </div>
</div>

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
  html.setAttribute("data-theme",
    html.getAttribute("data-theme") === "light" ? "dark" : "light"
  );
}

function logout(){
  localStorage.clear();
  window.location.href = 'logout.php?role=counselor';
}

function openStudentModal(){
  document.getElementById("studentModal").classList.add("show");
}

function closeStudentModal(){
  document.getElementById("studentModal").classList.remove("show");
}

function toggleFilterBox(){
  document.getElementById("filterBox").classList.toggle("show");
}

function applyFilter(){
  alert("Filter applied");
}

function clearFilter(){
  alert("Filter cleared");
}

function exportAppointment(btn){
  const card = btn.closest(".cAppointment-card");
  const file = card.getAttribute("data-file");

  if(!file){
    alert("No file found");
    return;
  }

  const a = document.createElement("a");
  a.href = file;
  a.download = file.split("/").pop();
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}
function updateStatus(apptId, status, btn) {
  if (!confirm(`${status === 'Approved' ? 'Approve' : 'Decline'} this appointment?`)) return;
  const fd = new FormData();
  fd.append('action', 'update_status');
  fd.append('appointment_id', apptId);
  fd.append('status', status);
  fetch('cappointments.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        const card = btn.closest('.cAppointment-card');
        card.style.opacity = '0';
        card.style.transition = '0.3s';
        setTimeout(() => card.remove(), 300);
      } else {
        alert(json.message || 'Failed to update.');
      }
    });
}
</script>

</body>
</html>
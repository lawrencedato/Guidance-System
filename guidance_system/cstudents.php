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


$studentsRes = $conn->query("
    SELECT DISTINCT s.student_id, s.first_name, s.last_name, s.course, s.year_level,
           MAX(a.appointment_date) AS last_session
    FROM students s
    JOIN appointments a ON a.student_id = s.student_id
    WHERE a.counselor_id = '$cid'
    GROUP BY s.student_id
    ORDER BY s.last_name ASC
");

// ── HANDLE GET: student profile for modal ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_student') {
    header('Content-Type: application/json');

    $studentId = (int)($_GET['student_id'] ?? 0);
    if (!$studentId) {
        echo json_encode(['success' => false, 'message' => 'Missing student ID.']);
        exit;
    }

    $res = $conn->query("
        SELECT s.student_id, s.first_name, s.last_name,
               s.email, s.course, s.year_level,
               sp.emergency_contact_name    AS emergency_name,
               sp.relationship_to_emergency_contact AS emergency_relation,
               sp.emergency_contact_number  AS emergency_number,
               w.mood_label                 AS last_mood,
               DATE_FORMAT(w.created_at, '%M %d, %Y') AS last_wellness
        FROM students s
        LEFT JOIN student_profiles sp ON sp.student_id = s.student_id
        LEFT JOIN wellness_checks w
               ON w.wellness_id = (
                   SELECT wellness_id FROM wellness_checks
                   WHERE student_id = s.student_id
                   ORDER BY created_at DESC LIMIT 1
               )
        WHERE s.student_id = $studentId
        LIMIT 1
    ");

    $student = $res ? $res->fetch_assoc() : null;

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit;
    }

    echo json_encode(['success' => true, 'student' => $student]);
    exit;
}
$students = [];
while ($row = $studentsRes->fetch_assoc()) $students[] = $row;

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>UNITYCARE | Students List</title>

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
      <button class="sidebar-settingsButton" onclick="toggleSettings()">
        <i class="fa fa-gear"></i>
      </button>

      <div class="sidebar-settingsDropdown" id="settingsMenu">
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
    <a href="cavailability.php"><i class="fa fa-clock"></i> Time Availability</a>
    <a href="cconcerns.php"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>
    <a href="cfeedback.php"><i class="fa fa-comment"></i> Session Feedback</a>

    <p class="sidebar-title">STUDENTS</p>
    <a href="cstudents.php" class="active"><i class="fa fa-users"></i> Students</a>

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
    <h2>Student List</h2>
  </div>

  <div class="topbar-right">

    <div class="topbar-searchBox">
      <i class="fa fa-search"></i>
      <input type="text" id="searchInput" oninput="searchStudents()" placeholder="Search...">
    </div>

    <div class="filter-wrapper">

      <button class="btn" onclick="toggleFilterBox()">
        <i class="fa fa-filter"></i> Filter
      </button>

      <div id="filterBox">

        <select id="filterProgram">
          <option value="all">Programs</option>
          <option>BSIT</option>
          <option>BSBA</option>
          <option>BSA</option>
          <option>BSCS</option>
          <option>BSN</option>
          <option>BSECE</option>
        </select>

        <select id="filterYear">
          <option value="all">Year Levels</option>
          <option>1st Year</option>
          <option>2nd Year</option>
          <option>3rd Year</option>
          <option>4th Year</option>
        </select>

        <select id="filterStatus">
          <option value="all">Status</option>
          <option>Stable</option>
          <option>At Risk</option>
          <option>Critical</option>
        </select>

        <input type="date" id="filterDate">

        <div class="filter-actions">
          <button onclick="applyFilters()" class="btn-apply">Apply</button>
          <button onclick="clearFilters()" class="btn-clear">Clear</button>
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

<!-- LIST -->
<main class="cStudentList-main">

  <div class="cStudentList-container">

<?php if (empty($students)): ?>
  <p style="text-align:center; color:var(--text-muted); padding:3rem;">No students yet.</p>
<?php else: ?>
  <?php foreach ($students as $s):
    $sName    = htmlspecialchars($s['first_name'] . ' ' . $s['last_name']);
    $initials = strtoupper(substr($s['first_name'],0,1) . substr($s['last_name'],0,1));
    $lastSess = $s['last_session'] ? date('F d, Y', strtotime($s['last_session'])) : 'N/A';
  ?>
  <div class="cStudentList-item">
    <div class="cStudentList-info">
      <div class="cStudentList-avatar"><?= $initials ?></div>
      <div class="cStudentList-content">
        <div class="cStudentList-left">
          <div class="cStudentList-nameRow">
            <h3><?= $sName ?></h3>
            <button class="btn-small" onclick="openStudentModal(<?= $s['student_id'] ?>)">View Profile</button>
          </div>
          <p><?= htmlspecialchars($s['course']) ?> • <?= htmlspecialchars($s['year_level']) ?></p>
        </div>
        <div class="cStudentList-right">
          <div class="cStudentList-bottomRight">
            <p>Last Session: <?= $lastSess ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

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

<!-- MODAL -->
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
(function() {
    const saved = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", saved);
})();

function applyFilters() {
  const program = document.getElementById("filterProgram").value.toLowerCase();
  const year = document.getElementById("filterYear").value.toLowerCase();
  const status = document.getElementById("filterStatus").value.toLowerCase();
  const filterDate = document.getElementById("filterDate").value;

  document.querySelectorAll(".cStudentList-item").forEach(item => {
    const text = item.innerText.toLowerCase();

    const matchProgram = program === "all" || text.includes(program);
    const matchYear = year === "all" || text.includes(year);

    const itemStatus = item.querySelector(".tag")?.innerText.toLowerCase();
    const matchStatus = status === "all" || itemStatus === status;

    let matchDate = true;
    const dateValue = item.querySelector("[data-date]")?.dataset.date;

    if (filterDate && dateValue) {
      matchDate =
        new Date(dateValue).toDateString() ===
        new Date(filterDate).toDateString();
    }

    item.style.display = (matchProgram && matchYear && matchStatus && matchDate)
      ? "flex"
      : "none";
  });
}

function toggleFilterBox() {
  document.getElementById("filterBox").classList.toggle("show");
}

function clearFilters() {
  document.getElementById("filterProgram").value = "all";
  document.getElementById("filterYear").value = "all";
  document.getElementById("filterStatus").value = "all";
  document.getElementById("filterDate").value = "";

  document.querySelectorAll(".cStudentList-item").forEach(item => {
    item.style.display = "flex";
  });
}

function openStudentModal(studentId) {
  const modal = document.getElementById("studentModal");
  const body  = document.getElementById("studentModalBody");

  modal.classList.add("show");
  body.innerHTML = '<p style="text-align:center; padding:2rem; color:var(--text-muted);">Loading...</p>';

  fetch('cstudents.php?action=get_student&student_id=' + studentId)
    .then(r => r.json())
    .then(json => {
      if (!json.success) {
        body.innerHTML = '<p style="padding:2rem; color:var(--text-muted);">Could not load profile.</p>';
        return;
      }

      const s        = json.student;
      const initials = (s.first_name[0] + s.last_name[0]).toUpperCase();
      const lastCheck = s.last_wellness ? s.last_wellness : 'No check-in yet';

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

        <div class="cStudentModal-grid">
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
          <h4>Wellness</h4>
          <p><b>Last Check-in:</b> ${lastCheck}</p>
          <p><b>Last Mood:</b> ${s.last_mood || 'N/A'}</p>
        </div>
      `;
    })
    .catch(() => {
      body.innerHTML = '<p style="padding:2rem; color:var(--text-muted);">Could not load profile.</p>';
    });
}

function closeStudentModal() {
  document.getElementById("studentModal").classList.remove("show");
}

function searchStudents() {
  const input = document.getElementById("searchInput").value.toLowerCase();

  document.querySelectorAll(".cStudentList-item").forEach(item => {
    item.style.display = item.innerText.toLowerCase().includes(input)
      ? "flex"
      : "none";
  });
}

function toggleSettings() {
  document.getElementById("settingsMenu").classList.toggle("show");
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
  window.location.href = 'logout.php?role=counselor';
}
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});
</script>

</body>
</html>
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['concern_id']) && isset($_POST['reply'])) {

    $concern_id = intval($_POST['concern_id']);
    $reply = $conn->real_escape_string($_POST['reply']);
    $counselor_id = $cid;

    $insert = $conn->query("
        INSERT INTO concern_replies (concern_id, counselor_id, reply, replied_at)
        VALUES ('$concern_id', '$counselor_id', '$reply', NOW())
    ");

    if ($insert) {

        $conn->query("
            UPDATE concerns
            SET status='Reviewed'
            WHERE concern_id='$concern_id'
        ");

        echo "✔ Reply sent successfully.";
    } else {
        echo "Error sending reply.";
    }

    exit;
}

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


$concernRes = $conn->query("
    SELECT c.concern_id, c.subject, c.message, c.status, c.created_at,
           s.first_name, s.last_name
    FROM concerns c
    JOIN students s ON s.student_id = c.student_id
    LEFT JOIN concern_replies cr ON c.concern_id = cr.concern_id
    ORDER BY c.created_at DESC
");
$concerns = [];
while ($row = $concernRes->fetch_assoc()) $concerns[] = $row;

?>


<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>UNITYCARE | Student Concerns</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="body">

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
    <a href="cconcerns.php" class="active"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>
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

<!-- ================= TOPBAR ================= -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Student Concerns</h2>
  </div>

  <div class="topbar-right">

    <!-- FILTER -->
    <div class="filter-wrapper">

      <button class="btn" onclick="toggleFilterBox()">
        <i class="fa fa-filter"></i> Filter
      </button>

<div id="filterBox" class="filter-box">

  <select id="filterStatus">
    <option value="all">All Status</option>
    <option value="Pending">Pending</option>
    <option value="Reviewed">Reviewed</option>
    <option value="Resolved">Resolved</option>
  </select>

  <input type="date" id="filterDate">

  <div class="filter-actions">
    <button onclick="applyConcernFilter()" class="btn-apply">Apply</button>
    <button onclick="clearConcernFilter()" class="btn-clear">Clear</button>
  </div>

</div>

    </div>

    <!-- BELL -->
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
<main class="cConcerns-main">

  <div class="cConcerns-container">

    <!-- CONCERN CARD -->
<?php if (empty($concerns)): ?>
  <p style="text-align:center; color:var(--text-muted); padding:2rem;">No student concerns at the moment.</p>
<?php else: ?>
  <?php foreach ($concerns as $c): ?>
    <div class="cConcerns-card"
      data-id="<?= $c['concern_id'] ?>"
      data-date="<?= date('Y-m-d', strtotime($c['created_at'])) ?>"
      data-status="<?= htmlspecialchars($c['status']) ?>">
    <h3><i class="fa fa-user"></i> <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></h3>
    <p><b>Subject:</b> <?= htmlspecialchars($c['subject']) ?></p>
    <p><b>Message:</b> <?= htmlspecialchars($c['message']) ?></p>
    <p><b>Status:</b> <?= htmlspecialchars($c['status']) ?></p>
    <p style="font-size:12px; color:var(--text-muted); margin-top:4px;">
        <i class="fa fa-clock" style="margin-right:4px;"></i>
        Submitted: <?= date('F d, Y \a\t g:i A', strtotime($c['created_at'])) ?>
    </p>
<textarea class="cConcerns-replyBox" placeholder="Write your reply..."></textarea>
    <button class="cConcerns-btn" onclick="sendReply(this)">Send Reply</button>
    <div class="cConcerns-result"></div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>

<div id="noResultsMsg" style="display:none; text-align:center; padding:2rem; color:var(--text-muted); width:100%;">
      <i class="fa fa-search" style="font-size:2rem; opacity:0.3; display:block; margin-bottom:0.75rem;"></i>
      <p>No concerns match your filter.</p>
    </div>

  </div>
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

function toggleFilterBox() {
  document.getElementById("filterBox").classList.toggle("show");
}

function applyConcernFilter() {
  const date   = document.getElementById("filterDate").value;
  const status = document.getElementById("filterStatus")?.value || "all";
  const items  = document.querySelectorAll(".cConcerns-card");
  let   visible = 0;

  items.forEach(item => {
    const itemDate   = item.dataset.date;
    const itemStatus = item.dataset.status;

    const matchDate   = !date   || itemDate   === date;
    const matchStatus = status === "all" || itemStatus === status;
    const show        = matchDate && matchStatus;

    item.style.display = show ? "block" : "none";
    if (show) visible++;
  });

  document.getElementById("noResultsMsg").style.display = visible === 0 ? "block" : "none";
}

function clearConcernFilter() {
  document.getElementById("filterDate").value = "";
  const statusEl = document.getElementById("filterStatus");
  if (statusEl) statusEl.value = "all";
  document.querySelectorAll(".cConcerns-card").forEach(item => {
    item.style.display = "block";
  });
  document.getElementById("noResultsMsg").style.display = "none";
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

function sendReply(btn){

  const card = btn.closest(".cConcerns-card");
  const textarea = card.querySelector(".cConcerns-replyBox");
  const result = card.querySelector(".cConcerns-result");

  const concernId = card.dataset.id;
  const reply = textarea.value.trim();

  if (!reply) {
    alert("Please write a reply first.");
    return;
  }

  fetch("", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded"
    },
    body: "concern_id=" + concernId + "&reply=" + encodeURIComponent(reply)
  })
  .then(response => response.text())
  .then(data => {
      result.innerHTML = data;
      textarea.value = "";
  })
  .catch(error => {
      result.innerHTML = "Error sending reply.";
  });

}

</script>

</body>
</html>
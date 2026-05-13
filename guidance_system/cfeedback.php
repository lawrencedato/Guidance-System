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


$feedbackRes = $conn->query("
    SELECT f.rating, f.message AS feedback_text, f.created_at,
           s.first_name, s.last_name
    FROM feedback f
    JOIN students s ON s.student_id = f.student_id
    WHERE f.counselor_id = '$cid'
    ORDER BY f.created_at DESC
    LIMIT 20
");
$feedbacks = [];
if ($feedbackRes) {
    while ($row = $feedbackRes->fetch_assoc()) $feedbacks[] = $row;
}

$ratingStars = [
    'Excellent' => '⭐⭐⭐⭐⭐',
    'Very Good' => '⭐⭐⭐⭐',
    'Good'      => '⭐⭐⭐',
    'Fair'      => '⭐⭐',
    'Poor'      => '⭐',
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Counselor Feedback Review</title>

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
    <a href="cfeedback.php" class="active"><i class="fa fa-comment"></i> Session Feedback</a>

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
    <h2>Session Feedback</h2>
  </div>

  <div class="topbar-right">

    <!-- NOTIFICATIONS -->
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
<main class="cFeedback-main">

  <div class="cFeedback-container">

    <div class="cFeedback-card">

      <h3 class="cFeedback-title">Recent Student Feedback</h3>

      <p class="cFeedback-muted">
        Review submitted feedback from counseling sessions
      </p>

      <div class="cFeedback-list">

<?php if (empty($feedbacks)): ?>
  <p style="text-align:center; color:var(--text-muted); padding:2rem;">No feedback received yet.</p>
<?php else: ?>
<?php foreach ($feedbacks as $f):
    $stars = $ratingStars[$f['rating']] ?? '⭐';
?>
<div class="cFeedback-item">
  <div class="cFeedback-header">
    <h4><?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) ?></h4>
    <span class="cFeedback-rating"><?= $stars ?> <?= htmlspecialchars($f['rating']) ?></span>
  </div>
  <p class="cFeedback-date">Submitted on <?= date('F d, Y', strtotime($f['created_at'])) ?></p>
  <p class="cFeedback-message"><?= htmlspecialchars($f['feedback_text']) ?></p>
</div>
<?php endforeach; ?>
<?php endif; ?>

      </div>

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
(function() {
    const saved = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", saved);
})();

function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById("settingsMenu").classList.toggle("show");
  document.getElementById("notifDropdown").classList.remove("show");
}

function toggleDropdown(id, e) {
  e.stopPropagation();
  const target = document.getElementById(id);
  const isOpen = target.classList.contains("show");
  document.getElementById("settingsMenu").classList.remove("show");
  document.getElementById("notifDropdown").classList.remove("show");
  if (!isOpen) target.classList.add("show");
}

function toggleTheme() {
    const html = document.documentElement;
    const newTheme = html.getAttribute("data-theme") === "light" ? "dark" : "light";
    html.setAttribute("data-theme", newTheme);
    localStorage.setItem("theme", newTheme);
}

// single click-outside handler — only classList, no style.display
document.addEventListener("click", function () {
  document.getElementById("settingsMenu").classList.remove("show");
  document.getElementById("notifDropdown").classList.remove("show");
});

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
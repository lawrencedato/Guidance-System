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
$cid  = $conn->real_escape_string($_SESSION['user_id']); // ✅ was $_SESSION['user_id'] but set wrong key before

$counselorRes = $conn->query("SELECT * FROM counselors WHERE counselor_id='$cid' LIMIT 1");
$counselor    = $counselorRes->fetch_assoc();


$firstName = htmlspecialchars($counselor['first_name'] ?? 'Counselor');
$lastName = htmlspecialchars($counselor['last_name'] ?? 'Counselor');
$fullName  = htmlspecialchars(($counselor['first_name'] ?? '') . ' ' . ($counselor['last_name'] ?? ''));
$email     = htmlspecialchars($counselor['email'] ?? '');
$profileImg = !empty($counselor['profile_image'])
    ? htmlspecialchars($counselor['profile_image'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';


$todaySessions = $conn->query(
    "SELECT COUNT(*) c FROM appointments 
     WHERE counselor_id='$cid' AND status='Approved' AND appointment_date = CURDATE()"
)->fetch_assoc()['c'] ?? 0;

$myStudents = $conn->query(
    "SELECT COUNT(DISTINCT student_id) c FROM appointments WHERE counselor_id='$cid'"
)->fetch_assoc()['c'] ?? 0;

$pendingConcerns = $conn->query(
    "SELECT COUNT(*) c FROM concerns c
     LEFT JOIN concern_replies cr ON c.concern_id = cr.concern_id
     WHERE cr.reply_id IS NULL"
)->fetch_assoc()['c'] ?? 0;

$pendingAppointments = $conn->query(
    "SELECT COUNT(*) c FROM appointments WHERE status='Pending'"
)->fetch_assoc()['c'] ?? 0;

// ===== UPCOMING APPOINTMENTS =====
$upcomingRes = $conn->query(
    "SELECT a.appointment_date, a.appointment_time, a.status,
            s.first_name, s.last_name
     FROM appointments a
     JOIN students s ON s.student_id = a.student_id
     WHERE a.counselor_id='$cid' AND a.status='Approved' AND a.appointment_date >= CURDATE()
     ORDER BY a.appointment_date ASC, a.appointment_time ASC
     LIMIT 5"
);
$upcoming = [];
while ($row = $upcomingRes->fetch_assoc()) $upcoming[] = $row;

// ===== RECENT CONCERNS =====
$concernsRes = $conn->query(
    "SELECT c.concern_id, c.subject, c.status, c.created_at,
            s.first_name, s.last_name,
            cr.counselor_id AS replied_by
     FROM concerns c
     JOIN students s ON s.student_id = c.student_id
     LEFT JOIN concern_replies cr ON c.concern_id = cr.concern_id
     ORDER BY c.created_at DESC
     LIMIT 5"
);
$recentConcerns = [];
while ($row = $concernsRes->fetch_assoc()) $recentConcerns[] = $row;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNITYCARE | Counselor Dashboard</title>
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
        <a href="counselor.php" class="active"><i class="fa fa-gauge"></i> Dashboard</a>
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
        <a href="creferral.php"><i class="fa fa-route"></i> Referrals</a>
    </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
    <div class="topbar-left">
        <h2>Hello, <?= $fullName ?>!</h2>
    </div>
    <div class="topbar-right">
        <div class="topbar-icons">
            <div class="topbar-icon" onclick="toggleDropdown('notifDropdown', event)">
                <i class="fa fa-bell"></i>
                <?php if ($pendingAppointments > 0): ?>
                    <span class="badge"><?= $pendingAppointments ?></span>
                <?php endif; ?>
                <div class="icon-dropdown" id="notifDropdown">
                    <?php if ($pendingAppointments > 0): ?>
                        <p><?= $pendingAppointments ?> pending appointment request(s)</p>
                    <?php else: ?>
                        <p>No new notifications</p>
                    <?php endif; ?>
                </div>
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
<main class="cDashboard-main">

    <!-- STATS -->
    <section class="cDashboard-container">
        <div class="cDashboard-card">
            <h4>Today's Sessions</h4>
            <h3><?= $todaySessions ?></h3>
            <p>Approved sessions scheduled today</p>
        </div>
        <div class="cDashboard-card">
            <h4>My Students</h4>
            <h3><?= $myStudents ?></h3>
            <p>Unique students with appointments</p>
        </div>
        <div class="cDashboard-card">
            <h4>Pending Concerns</h4>
            <h3><?= $pendingConcerns ?></h3>
            <p>Cases waiting for your review</p>
        </div>
        <div class="cDashboard-card">
            <h4>Pending Requests</h4>
            <h3><?= $pendingAppointments ?></h3>
            <p>Appointment requests to approve</p>
        </div>
    </section>

    <!-- UPCOMING APPOINTMENTS -->
    <section class="cDashboard-card" style="margin-top:24px; padding:24px;">
        <h4 style="margin-bottom:16px;">Upcoming Appointments</h4>
        <?php if (count($upcoming) > 0): ?>
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr style="text-align:left; color:var(--text-muted); border-bottom:1px solid var(--border);">
                        <th style="padding:8px 0;">Student</th>
                        <th style="padding:8px 0;">Date</th>
                        <th style="padding:8px 0;">Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcoming as $appt): ?>
                        <tr style="border-bottom:1px solid var(--border);">
                            <td style="padding:10px 0;">
                                <?= htmlspecialchars($appt['first_name'] . ' ' . $appt['last_name']) ?>
                            </td>
                            <td style="padding:10px 0;">
                                <?= date('M d, Y', strtotime($appt['appointment_date'])) ?>
                            </td>
                            <td style="padding:10px 0;">
                                <?= date('h:i A', strtotime($appt['appointment_time'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color:var(--text-muted); font-size:13px;">No upcoming appointments.</p>
        <?php endif; ?>
    </section>

    <!-- RECENT CONCERNS -->
    <section class="cDashboard-card" style="margin-top:24px; padding:24px;">
        <h4 style="margin-bottom:16px;">Recent Concerns</h4>
        <?php if (count($recentConcerns) > 0): ?>
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <thead>
                    <tr style="text-align:left; color:var(--text-muted); border-bottom:1px solid var(--border);">
                        <th style="padding:8px 0;">Student</th>
                        <th style="padding:8px 0;">Subject</th>
                        <th style="padding:8px 0;">Status</th>
                        <th style="padding:8px 0;">Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentConcerns as $c): ?>
                      <tr style="border-bottom:1px solid var(--border);">
                          <td style="padding:10px 0;">
                              <?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?>
                          </td>
                          <td style="padding:10px 0;">
                              <?= htmlspecialchars($c['subject']) ?>
                          </td>
                          <td style="padding:10px 0;">
                              <span style="
                                  padding:2px 10px; border-radius:20px; font-size:12px;
                                  background:<?= $c['status']==='Pending' ? '#fef3c7' : ($c['status']==='Resolved' ? '#d1fae5' : '#e0e7ff') ?>;
                                  color:<?= $c['status']==='Pending' ? '#92400e' : ($c['status']==='Resolved' ? '#065f46' : '#3730a3') ?>;">
                                  <?= htmlspecialchars($c['status']) ?>
                              </span>
                          </td>
                          <td style="padding:10px 0; color:var(--text-muted); font-size:12px;">
                              <?php if ($c['replied_by']): ?>
                                  <?= $c['replied_by'] === $cid ? '✅ You replied' : '💬 Replied by another counselor' ?>
                              <?php else: ?>
                                  ⏳ Awaiting reply
                              <?php endif; ?>
                          </td>
                          <td style="padding:10px 0; color:var(--text-muted);">
                              <?= date('M d, Y', strtotime($c['created_at'])) ?>
                          </td>
                      </tr>
                  <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color:var(--text-muted); font-size:13px;">No concerns yet.</p>
        <?php endif; ?>
    </section>
    
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
function toggleSettingsMenu(e) {
    e.stopPropagation();
    document.getElementById("settingsDropdown").classList.toggle("show");
}
function toggleTheme() {
    const html = document.documentElement;
    html.setAttribute("data-theme", html.getAttribute("data-theme") === "light" ? "dark" : "light");
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

// Close when clicking outside
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});

function toggleDropdown(id, e) {
    e.stopPropagation();
    document.getElementById(id).classList.toggle("show");
}
document.addEventListener("click", e => {
    const menu = document.getElementById("settingsDropdown");
    const btn  = document.querySelector(".sidebar-settingsButton");
    if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target))
        menu.classList.remove("show");
});
</script>

</body>
</html>
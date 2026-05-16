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

// ── HANDLE MARK APPOINTMENT (Complete / Cancel) from dashboard ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_appointment') {
    header('Content-Type: application/json');
    $apptId = (int)($_POST['appointment_id'] ?? 0);
    $result = $_POST['result'] ?? '';
    $reason = $conn->real_escape_string(trim($_POST['reason'] ?? ''));

    if (!$apptId || !in_array($result, ['completed', 'cancelled'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
    }
    if ($result === 'cancelled' && $reason === '') {
        echo json_encode(['success' => false, 'message' => 'Please provide a cancellation reason.']); exit;
    }

    $newStatus = $result === 'completed' ? 'Completed' : 'Cancelled';
    $reasonSql = $result === 'cancelled' ? "'$reason'" : 'NULL';

    $ok = $conn->query("
        UPDATE appointments
        SET status = '$newStatus',
            rejection_reason = $reasonSql
        WHERE appointment_id = $apptId
          AND counselor_id = '$cid'
          AND status = 'Approved'
    ");

    echo json_encode($ok && $conn->affected_rows > 0
        ? ['success' => true]
        : ['success' => false, 'message' => 'Could not update. Appointment may have already been actioned.']);
    exit;
}

$counselorRes = $conn->query("SELECT * FROM counselors WHERE counselor_id='$cid' LIMIT 1");
$counselor    = $counselorRes->fetch_assoc();

$fullName   = htmlspecialchars(($counselor['first_name'] ?? '') . ' ' . ($counselor['last_name'] ?? ''));
$email      = htmlspecialchars($counselor['email'] ?? '');
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
    "SELECT COUNT(*) c FROM appointments 
     WHERE counselor_id='$cid' AND status='Pending'"
)->fetch_assoc()['c'];

// ===== UPCOMING APPOINTMENTS =====
$upcomingRes = $conn->query(
    "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status,
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
<html lang="en">
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
        <a href="cavailability.php"><i class="fa fa-clock"></i> Time Availability</a>
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

    <!-- STAT CARDS -->
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
        <h4 style="margin-bottom:16px; font-size:16px; font-weight:700; color:var(--text);">
            <i class="fa fa-calendar-check" style="color:var(--primary); margin-right:8px;"></i>
            Upcoming Appointments
        </h4>
        <?php if (count($upcoming) > 0): ?>
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr class="cDashboard-tableHead">
                        <th>Student</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($upcoming as $appt): ?>
                        <tr class="cDashboard-tableRow">
                            <td><?= htmlspecialchars($appt['first_name'] . ' ' . $appt['last_name']) ?></td>
                            <td><?= date('M d, Y', strtotime($appt['appointment_date'])) ?></td>
                            <td><?= date('h:i A', strtotime($appt['appointment_time'])) ?></td>
                            <td id="cDashboard-actions-<?= (int)$appt['appointment_id'] ?>">
                                <button class="cDashboard-apptBtn done"
                                        onclick="markApptDone(<?= (int)$appt['appointment_id'] ?>)">
                                    <i class="fa fa-check"></i> Done
                                </button>
                                <button class="cDashboard-apptBtn cancel"
                                        onclick="openCancelModal(<?= (int)$appt['appointment_id'] ?>)">
                                    <i class="fa fa-ban"></i> Cancel
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color:var(--text-muted); font-size:13px; padding:12px 0;">
                <i class="fa fa-calendar" style="opacity:0.3; margin-right:6px;"></i>
                No upcoming appointments.
            </p>
        <?php endif; ?>
    </section>

    <!-- RECENT CONCERNS -->
    <section class="cDashboard-card" style="margin-top:24px; padding:24px;">
        <h4 style="margin-bottom:16px; font-size:16px; font-weight:700; color:var(--text);">
            <i class="fa fa-triangle-exclamation" style="color:var(--primary); margin-right:8px;"></i>
            Recent Concerns
        </h4>
        <?php if (count($recentConcerns) > 0): ?>
            <table style="width:100%; border-collapse:collapse;">
                <thead>
                    <tr class="cDashboard-tableHead">
                        <th>Student</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Reply</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentConcerns as $c):
                        $bClass = $c['status'] === 'Pending'  ? 'pending'
                                : ($c['status'] === 'Resolved' ? 'resolved' : 'other');
                    ?>
                        <tr class="cDashboard-tableRow">
                            <td><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></td>
                            <td><?= htmlspecialchars($c['subject']) ?></td>
                            <td>
                                <span class="cDashboard-concernBadge <?= $bClass ?>">
                                    <?= htmlspecialchars($c['status']) ?>
                                </span>
                            </td>
                            <td class="cDashboard-replyCell">
                                <?php if ($c['replied_by']): ?>
                                    <?= $c['replied_by'] === $cid ? '✅ You replied' : '💬 Replied by another counselor' ?>
                                <?php else: ?>
                                    ⏳ Awaiting reply
                                <?php endif; ?>
                            </td>
                            <td style="color:var(--text-muted); font-size:13px;">
                                <?= date('M d, Y', strtotime($c['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p style="color:var(--text-muted); font-size:13px; padding:12px 0;">
                <i class="fa fa-inbox" style="opacity:0.3; margin-right:6px;"></i>
                No concerns yet.
            </p>
        <?php endif; ?>
    </section>

    <!-- LOGOUT MODAL -->
    <div class="logout-overlay" id="logoutOverlay">
        <div class="logout-modal">
            <div class="logout-icon"><i class="fa fa-right-from-bracket"></i></div>
            <h3>Logout</h3>
            <p>Are you sure you want to logout?</p>
            <div class="logout-actions">
                <button class="logout-btn logout-btn--cancel"  onclick="closeLogout()">Cancel</button>
                <button class="logout-btn logout-btn--confirm" onclick="confirmLogout()">Yes, Logout</button>
            </div>
        </div>
    </div>

</main>

<!-- ══════════════════════════════════════════════
     CANCEL APPOINTMENT MODAL
══════════════════════════════════════════════ -->
<div class="cDashboard-modalOverlay" id="cDashboard-cancelModal">
    <div class="cDashboard-modalBox">
        <h3><i class="fa fa-ban"></i> Cancel Appointment</h3>
        <p>Please provide a reason for cancelling. The student will be able to see this explanation.</p>
        <textarea class="cDashboard-modalTextarea"
                  id="cDashboard-cancelReason"
                  placeholder="e.g. Counselor unavailable due to an emergency..."></textarea>
        <div class="cDashboard-modalError" id="cDashboard-cancelError">
            Please enter a reason before cancelling.
        </div>
        <div class="cDashboard-modalActions">
            <button class="cDashboard-modalBtn back"    onclick="closeCancelModal()">Go Back</button>
            <button class="cDashboard-modalBtn confirm" onclick="confirmCancel()">
                <i class="fa fa-ban"></i> Confirm Cancel
            </button>
        </div>
    </div>
</div>

<script>

// ===== THEME =====
(function() {
    const saved = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", saved);
})();

/* ── Settings dropdown ── */
function toggleSettingsMenu(e) {
    e.stopPropagation();
    document.getElementById("settingsDropdown").classList.toggle("show");
}
document.addEventListener("click", e => {
    const menu = document.getElementById("settingsDropdown");
    const btn  = document.querySelector(".sidebar-settingsButton");
    if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target))
        menu.classList.remove("show");
});

/* ── Theme toggle ── */
function toggleTheme() {
    const html = document.documentElement;
    const newTheme = html.getAttribute("data-theme") === "light" ? "dark" : "light";
    html.setAttribute("data-theme", newTheme);
    localStorage.setItem("theme", newTheme);
}

/* ── Logout ── */
function logout()        { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()   { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=counselor'; }
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeLogout();
});

/* ── Notification dropdown ── */
function toggleDropdown(id, e) {
    e.stopPropagation();
    document.getElementById(id).classList.toggle("show");
}
document.addEventListener("click", e => {
    const notif = document.getElementById("notifDropdown");
    if (notif && !notif.contains(e.target)) notif.classList.remove("show");
});

/* ══════════════════════════════════════════
   DONE — marks appointment as Completed
══════════════════════════════════════════ */
function markApptDone(apptId) {
    if (!confirm('Mark this appointment as Completed?')) return;

    const fd = new FormData();
    fd.append('action',         'mark_appointment');
    fd.append('appointment_id', apptId);
    fd.append('result',         'completed');

    fetch('counselor.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                replaceWithBadge(apptId, 'done');
            } else {
                alert(json.message || 'Failed to update.');
            }
        })
        .catch(() => alert('Something went wrong.'));
}

/* ══════════════════════════════════════════
   CANCEL MODAL
══════════════════════════════════════════ */
let _cancelApptId = null;

function openCancelModal(apptId) {
    _cancelApptId = apptId;
    document.getElementById('cDashboard-cancelReason').value = '';
    document.getElementById('cDashboard-cancelError').style.display = 'none';
    document.getElementById('cDashboard-cancelModal').classList.add('show');
}
function closeCancelModal() {
    _cancelApptId = null;
    document.getElementById('cDashboard-cancelModal').classList.remove('show');
}
document.getElementById('cDashboard-cancelModal').addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});

function confirmCancel() {
    const reason = document.getElementById('cDashboard-cancelReason').value.trim();
    const errEl  = document.getElementById('cDashboard-cancelError');
    if (!reason) { errEl.style.display = 'block'; return; }
    errEl.style.display = 'none';

    const fd = new FormData();
    fd.append('action',         'mark_appointment');
    fd.append('appointment_id', _cancelApptId);
    fd.append('result',         'cancelled');
    fd.append('reason',         reason);

    fetch('counselor.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                const id = _cancelApptId;
                closeCancelModal();
                replaceWithBadge(id, 'cancel');
            } else {
                alert(json.message || 'Failed to cancel.');
            }
        })
        .catch(() => alert('Something went wrong.'));
}

/* ══════════════════════════════════════════
   HELPER — swap buttons → status badge
══════════════════════════════════════════ */
function replaceWithBadge(apptId, status) {
    const cell = document.getElementById('cDashboard-actions-' + apptId);
    if (!cell) return;
    const icon  = status === 'done' ? 'fa-check' : 'fa-ban';
    const label = status === 'done' ? 'Completed' : 'Cancelled';
    cell.innerHTML = `
        <span class="cDashboard-statusBadge ${status}">
            <i class="fa ${icon}"></i> ${label}
        </span>`;
}
</script>
<script>var SESSION_ROLE = 'counselor';</script>
<script src="session_timeout.js"></script>
</body>
</html>
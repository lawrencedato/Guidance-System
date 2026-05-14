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

$conn->query("
    UPDATE appointments
    SET status = 'Rejected',
        rejection_reason = 'Appointment date has passed without counselor action.'
    WHERE status = 'Pending'
      AND counselor_id = '$cid'
      AND CONCAT(appointment_date, ' ', appointment_time) < NOW()
");

$autoCompletedRes   = $conn->query("
    SELECT COUNT(*) c FROM appointments
    WHERE status = 'Approved'
      AND counselor_id = '$cid'
      AND CONCAT(appointment_date, ' ', appointment_time) < NOW()
");
$autoCompletedCount = (int)$autoCompletedRes->fetch_assoc()['c'];

$conn->query("
    UPDATE appointments
    SET status = 'Completed',
        rejection_reason = NULL
    WHERE status = 'Approved'
      AND counselor_id = '$cid'
      AND CONCAT(appointment_date, ' ', appointment_time) < NOW()
");

$counselorRes = $conn->query("SELECT * FROM counselors WHERE counselor_id='$cid' LIMIT 1");
$counselor    = $counselorRes->fetch_assoc();

$fullName   = htmlspecialchars(($counselor['first_name'] ?? '') . ' ' . ($counselor['last_name'] ?? ''));
$email      = htmlspecialchars($counselor['email'] ?? '');
$profileImg = !empty($counselor['profile_image'])
    ? htmlspecialchars($counselor['profile_image'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';

$pendingCount = (int)$conn->query(
    "SELECT COUNT(*) c FROM appointments
     WHERE counselor_id='$cid' AND status='Pending'"
)->fetch_assoc()['c'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    header('Content-Type: application/json');
    $apptId       = (int)($_POST['appointment_id'] ?? 0);
    $status       = $_POST['status'] ?? '';
    $rejectReason = $conn->real_escape_string(trim($_POST['rejection_reason'] ?? ''));
    $allowed      = ['Approved', 'Rejected'];

    if (!$apptId || !in_array($status, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
    }
    if ($status === 'Rejected' && $rejectReason === '') {
        echo json_encode(['success' => false, 'message' => 'Please provide a rejection reason.']); exit;
    }

    $conn->begin_transaction();
    try {
        $lock = $conn->query("SELECT appointment_id FROM appointments WHERE appointment_id=$apptId AND status='Pending' FOR UPDATE");
        if (!$lock || $lock->num_rows === 0) throw new Exception("Appointment no longer available or already handled.");

        $reasonSql = $status === 'Rejected' ? "'$rejectReason'" : 'NULL';
        $reason = $status === 'Rejected' ? $rejectReason : null;
        $stmt = $conn->prepare("CALL update_appointment_status(?, ?, ?, ?)");
        $stmt->bind_param("isss", $apptId, $status, $cid, $reason);
        $ok = $stmt->execute();
        $stmt->close();
        if (!$ok || $conn->affected_rows === 0) throw new Exception("Could not update. Try again.");
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

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

$apptRes = $conn->query("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time,
           a.priority, a.message,
           s.student_id, s.first_name, s.last_name, s.course, s.year_level,
           af.file_name, af.file_path
    FROM appointments a
    JOIN students s ON s.student_id = a.student_id
    LEFT JOIN appointment_files af ON af.appointment_id = a.appointment_id
    WHERE a.status = 'Pending'
      AND a.counselor_id = '$cid'
      AND CONCAT(a.appointment_date, ' ', a.appointment_time) >= NOW()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$appointments = [];
while ($row = $apptRes->fetch_assoc()) $appointments[] = $row;

$approvedRes = $conn->query("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time,
           a.priority, a.message,
           s.first_name, s.last_name, s.course, s.year_level,
           af.file_name, af.file_path
    FROM appointments a
    JOIN students s ON s.student_id = a.student_id
    LEFT JOIN appointment_files af ON af.appointment_id = a.appointment_id
    WHERE a.counselor_id = '$cid'
      AND a.status = 'Approved'
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$approvedAppointments = [];
while ($row = $approvedRes->fetch_assoc()) $approvedAppointments[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Appointment Requests</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
/* ── PRIORITY BADGE ── */
.uc-priority {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
}
.uc-priority.high   { background: rgba(239,68,68,0.12);  color: #b91c1c; }
.uc-priority.medium { background: rgba(245,158,11,0.12); color: #d97706; }
.uc-priority.low    { background: rgba(34,197,94,0.12);  color: #16a34a; }
[data-theme="dark"] .uc-priority.high   { background: rgba(239,68,68,0.15);  color: #fca5a5; }
[data-theme="dark"] .uc-priority.medium { background: rgba(245,158,11,0.15); color: #fbbf24; }
[data-theme="dark"] .uc-priority.low    { background: rgba(34,197,94,0.15);  color: #4ade80; }

/* ── DOWNLOAD BUTTON ── */
.uc-download-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 10px;
  padding: 6px 14px;
  background: rgba(73,136,196,0.1);
  border: 1px solid rgba(73,136,196,0.3);
  border-radius: var(--radius-sm);
  font-size: 12px;
  font-weight: 600;
  color: var(--primary);
  text-decoration: none;
  transition: var(--transition);
}
.uc-download-btn:hover { background: rgba(73,136,196,0.2); }
[data-theme="dark"] .uc-download-btn {
  background: rgba(73,136,196,0.1);
  border-color: rgba(73,136,196,0.25);
  color: #93c5fd;
}
[data-theme="dark"] .uc-download-btn:hover { background: rgba(73,136,196,0.2); }

/* ── PAST PILL ── */
.uc-past-pill {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 2px 8px;
  background: rgba(245,158,11,0.12);
  color: #d97706;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 600;
  margin-left: 8px;
  vertical-align: middle;
}
[data-theme="dark"] .uc-past-pill { background: rgba(245,158,11,0.15); color: #fbbf24; }

/* ── MOOD CHIP ── */
.uc-mood-chip {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 500;
  background: rgba(73,136,196,0.1);
  color: var(--primary);
  border: 1px solid rgba(73,136,196,0.2);
}
[data-theme="dark"] .uc-mood-chip { background: rgba(73,136,196,0.15); color: #93c5fd; border-color: rgba(73,136,196,0.3); }

/* ── STUDENT PROFILE BOXES ── */
.uc-profile-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  margin-top: 16px;
}
.uc-profile-box {
  background: var(--bg-soft);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 14px 16px;
}
.uc-profile-box h4 {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--text-muted);
  margin: 0 0 10px;
}
.uc-profile-box p {
  font-size: 13px;
  color: var(--text-muted);
  margin: 0 0 5px;
  line-height: 1.5;
}
.uc-profile-box p:last-child { margin: 0; }
.uc-profile-box p b { color: var(--text); font-weight: 500; }
.uc-profile-full { grid-column: 1 / -1; }
.uc-profile-avatar {
  width: 52px; height: 52px;
  border-radius: 50%;
  background: rgba(73,136,196,0.12);
  color: var(--primary);
  display: flex; align-items: center; justify-content: center;
  font-size: 18px; font-weight: 700;
  flex-shrink: 0;
  border: 2px solid rgba(73,136,196,0.2);
}
[data-theme="dark"] .uc-profile-avatar {
  background: rgba(73,136,196,0.15);
  color: #93c5fd;
  border-color: rgba(73,136,196,0.3);
}

/* ── RECEIPT MODAL ── */
.uc-receipt-header {
  background: linear-gradient(135deg, #113F67, #4988C4);
  padding: 24px 24px 20px;
  text-align: center;
  border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}
.uc-receipt-header .brand {
  font-size: 10px;
  letter-spacing: 3px;
  color: rgba(255,255,255,0.5);
  text-transform: uppercase;
  margin-bottom: 4px;
}
.uc-receipt-header .title {
  font-size: 17px;
  font-weight: 700;
  letter-spacing: 2px;
  color: #fff;
  text-transform: uppercase;
}
.uc-receipt-header .sub {
  font-size: 11px;
  color: rgba(255,255,255,0.45);
  margin-top: 4px;
}
.uc-receipt-dashes {
  border: none;
  border-top: 2px dashed var(--border-strong);
  margin: 0 20px;
}
.uc-receipt-rows { padding: 8px 24px 4px; }
.uc-receipt-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 7px 0;
  border-bottom: 1px solid var(--border);
  font-size: 13px;
}
.uc-receipt-row:last-child { border: none; }
.uc-receipt-label { color: var(--text-muted); font-size: 12px; }
.uc-receipt-value { color: var(--text); font-weight: 500; text-align: right; max-width: 60%; }
.uc-receipt-status-wrap {
  text-align: center;
  padding: 14px 0;
  border-top: 2px dashed var(--border-strong);
  border-bottom: 2px dashed var(--border-strong);
  margin: 4px 20px;
}
.uc-receipt-status-label {
  font-size: 10px;
  letter-spacing: 2px;
  color: var(--text-muted);
  text-transform: uppercase;
  margin-bottom: 8px;
}
.uc-receipt-approved-pill {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  background: rgba(34,197,94,0.12);
  border: 1px solid rgba(34,197,94,0.3);
  color: #16a34a;
  font-size: 12px;
  font-weight: 700;
  padding: 7px 22px;
  border-radius: 999px;
  letter-spacing: 1px;
}
[data-theme="dark"] .uc-receipt-approved-pill {
  background: rgba(34,197,94,0.15);
  border-color: rgba(34,197,94,0.3);
  color: #4ade80;
}
.uc-receipt-reason-wrap {
  padding: 14px 24px 18px;
  border-bottom: 2px dashed var(--border-strong);
}
.uc-receipt-reason-label {
  font-size: 10px;
  letter-spacing: 2px;
  color: var(--text-muted);
  text-transform: uppercase;
  margin-bottom: 6px;
}
.uc-receipt-reason-text { font-size: 13px; color: var(--text); line-height: 1.6; }
.uc-receipt-barcode {
  text-align: center;
  padding: 14px 0 4px;
  font-family: 'Courier New', monospace;
  letter-spacing: 4px;
  font-size: 14px;
  color: var(--text-muted);
}
.uc-receipt-barcode-num {
  font-size: 10px;
  color: var(--text-muted);
  letter-spacing: 2px;
  text-align: center;
  margin-bottom: 4px;
}
.uc-receipt-footer {
  background: var(--bg-soft);
  text-align: center;
  padding: 10px;
  font-size: 10px;
  letter-spacing: 3px;
  color: var(--text-muted);
  text-transform: uppercase;
  border-top: 1px solid var(--border);
}
.uc-receipt-close-btn {
  display: block;
  width: calc(100% - 48px);
  margin: 14px 24px 20px;
  padding: 11px;
  background: var(--bg-soft);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  cursor: pointer;
  font-family: inherit;
  transition: var(--transition);
}
.uc-receipt-close-btn:hover { background: var(--hover); }

/* ── NOTICE BANNER ── */
.uc-notice {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: rgba(73,136,196,0.08);
  border: 1px solid rgba(73,136,196,0.2);
  border-radius: var(--radius);
  padding: 12px 16px;
  font-size: 13px;
  color: var(--primary);
  margin-bottom: 20px;
}
.uc-notice i { margin-top: 2px; flex-shrink: 0; }
[data-theme="dark"] .uc-notice {
  background: rgba(73,136,196,0.1);
  border-color: rgba(73,136,196,0.25);
  color: #93c5fd;
}

/* ── APPROVED STATUS BADGE ── */
.uc-status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 14px;
  border-radius: var(--radius);
  font-size: 12px;
  font-weight: 600;
  margin-top: 14px;
  width: 100%;
  box-sizing: border-box;
  justify-content: center;
  border: 1px solid transparent;
}
.uc-status-badge.completed {
  background: rgba(34,197,94,0.12);
  color: #15803d;
  border-color: rgba(34,197,94,0.25);
}
.uc-status-badge.cancelled {
  background: rgba(239,68,68,0.12);
  color: #b91c1c;
  border-color: rgba(239,68,68,0.25);
}
[data-theme="dark"] .uc-status-badge.completed { background: rgba(34,197,94,0.12); color: #4ade80; border-color: rgba(34,197,94,0.2); }
[data-theme="dark"] .uc-status-badge.cancelled { background: rgba(239,68,68,0.12); color: #fca5a5; border-color: rgba(239,68,68,0.2); }

/* ── STUDENT MODAL OVERRIDES ── */
/* Widen the student profile modal box */
#studentModal .cAppointment-modalBox {
  max-width: 560px;
  text-align: left;
}
/* Receipt modal — no fixed text-align */
#receiptModal .cAppointment-modalBox {
  max-width: 380px;
  padding: 0;
  overflow: hidden;
}
</style>
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
    <a href="cappointments.php" class="active"><i class="fa fa-calendar-plus"></i> Appointment Requests</a>
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

<header class="topbar">
  <div class="topbar-left">
    <h2>Appointment Requests</h2>
  </div>
  <div class="topbar-right">
    <div class="topbar-searchBox">
      <i class="fa fa-search"></i>
      <input type="text" placeholder="Search..." id="searchInput">
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

<main class="cAppointment-main">

  <div class="cAppointment-tabs">
    <button class="cAppointment-tab active" onclick="switchTab('pending', this)">
      <i class="fa fa-clock"></i> Pending
      <span class="cAppointment-tabBadge"><?= count($appointments) ?></span>
    </button>
    <button class="cAppointment-tab" onclick="switchTab('approved', this)">
      <i class="fa fa-calendar-check"></i> Approved
      <span class="cAppointment-tabBadge"><?= count($approvedAppointments) ?></span>
    </button>
  </div>

  <!-- PENDING TAB -->
  <div class="cAppointment-panel active" id="panel-pending">
    <section class="cAppointment-grid">
      <?php if (empty($appointments)): ?>
        <div style="text-align:center;padding:3rem;color:var(--text-muted);grid-column:1/-1;">
          <i class="fa fa-calendar-check" style="font-size:2.5rem;opacity:0.3;display:block;margin-bottom:1rem;"></i>
          <p>No pending appointment requests.</p>
        </div>
      <?php else: ?>
        <?php foreach ($appointments as $appt):
          $sName  = htmlspecialchars($appt['first_name'] . ' ' . $appt['last_name']);
          $apptId = (int)$appt['appointment_id'];
          $hasFile = !empty($appt['file_path']);
          $prio = strtolower($appt['priority']);
          $prioIcon = $prio === 'high' ? 'fa-circle-exclamation' : ($prio === 'medium' ? 'fa-circle-minus' : 'fa-circle-check');
        ?>
        <div class="cAppointment-card"
             data-id="<?= $apptId ?>"
             data-name="<?= strtolower($sName) ?>"
             data-priority="<?= $prio ?>"
             data-date="<?= $appt['appointment_date'] ?>"
             data-time="<?= $appt['appointment_time'] ?>"
             data-program="<?= htmlspecialchars($appt['year_level'] . ' - ' . $appt['course']) ?>"
             data-message="<?= htmlspecialchars($appt['message'] ?? '') ?>">
          <h3 style="cursor:pointer;" onclick="openStudentModal(<?= $apptId ?>)">
            <i class="fa fa-user"></i> <?= $sName ?>
          </h3>
          <p><b>Reason:</b> <?= htmlspecialchars($appt['message'] ?? 'N/A') ?></p>
          <p><b>Program:</b> <?= htmlspecialchars($appt['year_level'] . ' - ' . $appt['course']) ?></p>
          <p><b>Date:</b> <?= date('F d, Y', strtotime($appt['appointment_date'])) ?></p>
          <p><b>Time:</b> <?= date('g:i A', strtotime($appt['appointment_time'])) ?></p>
          <p><b>Priority:</b>
            <span class="uc-priority <?= $prio ?>">
              <i class="fa <?= $prioIcon ?>"></i>
              <?= htmlspecialchars($appt['priority']) ?>
            </span>
          </p>
          <?php if ($hasFile): ?>
            <a href="<?= htmlspecialchars($appt['file_path']) ?>"
               download="<?= htmlspecialchars($appt['file_name']) ?>"
               class="uc-download-btn">
              <i class="fa fa-file-arrow-down"></i>
              <?= htmlspecialchars($appt['file_name']) ?>
            </a>
          <?php endif; ?>
          <div class="cAppointment-actions">
            <button class="cAppointment-btn approve" onclick="openApproveModal(<?= $apptId ?>, this)">
              <i class="fa fa-check"></i> Approve
            </button>
            <button class="cAppointment-btn decline" onclick="openRejectModal(<?= $apptId ?>)">
              <i class="fa fa-times"></i> Decline
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
    <div id="noResultsMsg" style="display:none;text-align:center;padding:2rem;color:var(--text-muted);">
      <i class="fa fa-search" style="font-size:2rem;opacity:0.3;display:block;margin-bottom:0.75rem;"></i>
      <p>No appointments match your filter.</p>
    </div>
  </div>

  <!-- APPROVED TAB -->
  <div class="cAppointment-panel" id="panel-approved">
    <?php if ($autoCompletedCount > 0): ?>
      <div class="uc-notice">
        <i class="fa fa-circle-info"></i>
        <span>
          <strong><?= $autoCompletedCount ?> past appointment<?= $autoCompletedCount > 1 ? 's were' : ' was' ?> automatically marked as Completed</strong>
          — the session date had already passed without a recorded action.
        </span>
      </div>
    <?php endif; ?>

    <?php if (empty($approvedAppointments)): ?>
      <div style="text-align:center;padding:3rem;color:var(--text-muted);">
        <i class="fa fa-calendar" style="font-size:2.5rem;opacity:0.3;display:block;margin-bottom:1rem;"></i>
        <p>No approved appointments.</p>
      </div>
    <?php else: ?>
      <div class="cAppointment-approvedGrid">
        <?php foreach ($approvedAppointments as $appt):
          $sName  = htmlspecialchars($appt['first_name'] . ' ' . $appt['last_name']);
          $apptId = (int)$appt['appointment_id'];
          $isPast  = strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']) < time();
          $hasFile = !empty($appt['file_path']);
          $prio = strtolower($appt['priority']);
          $prioIcon = $prio === 'high' ? 'fa-circle-exclamation' : ($prio === 'medium' ? 'fa-circle-minus' : 'fa-circle-check');
        ?>
        <div class="cAppointment-card" id="cAppointment-approvedCard-<?= $apptId ?>"
             data-id="<?= $apptId ?>"
             data-name="<?= strtolower($sName) ?>"
             data-priority="<?= $prio ?>"
             data-date="<?= $appt['appointment_date'] ?>">
          <h3 style="cursor:pointer;" onclick="openStudentModal(<?= $apptId ?>)">
            <i class="fa fa-user"></i> <?= $sName ?>
            <?php if ($isPast): ?>
              <span class="uc-past-pill"><i class="fa fa-clock"></i> Past</span>
            <?php endif; ?>
          </h3>
          <p><b>Reason:</b> <?= htmlspecialchars($appt['message'] ?? 'N/A') ?></p>
          <p><b>Program:</b> <?= htmlspecialchars($appt['year_level'] . ' - ' . $appt['course']) ?></p>
          <p><b>Date:</b> <?= date('F d, Y', strtotime($appt['appointment_date'])) ?></p>
          <p><b>Time:</b> <?= date('g:i A', strtotime($appt['appointment_time'])) ?></p>
          <p><b>Priority:</b>
            <span class="uc-priority <?= $prio ?>">
              <i class="fa <?= $prioIcon ?>"></i>
              <?= htmlspecialchars($appt['priority']) ?>
            </span>
          </p>
          <?php if ($hasFile): ?>
            <a href="<?= htmlspecialchars($appt['file_path']) ?>"
               download="<?= htmlspecialchars($appt['file_name']) ?>"
               class="uc-download-btn">
              <i class="fa fa-file-arrow-down"></i>
              <?= htmlspecialchars($appt['file_name']) ?>
            </a>
          <?php endif; ?>
          <div class="cAppointment-approvedActions" id="cAppointment-approvedActions-<?= $apptId ?>">
            <button class="cAppointment-approvedBtn complete" onclick="openCompleteModal(<?= $apptId ?>)">
              <i class="fa fa-check"></i> Complete
            </button>
            <button class="cAppointment-approvedBtn cancel" onclick="openCancelModal(<?= $apptId ?>)">
              <i class="fa fa-ban"></i> Cancel
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>


  <!-- ══════════════════════════════════
       MODALS — using style.css classes
  ══════════════════════════════════ -->

  <!-- LOGOUT MODAL -->
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

  <!-- APPROVE MODAL -->
  <div class="cAppointment-modalOverlay" id="approveModal">
    <div class="cAppointment-modalBox">
      <div class="cAppointment-modalIcon" style="background:rgba(34,197,94,0.12);color:#22c55e;box-shadow:0 8px 24px rgba(34,197,94,0.2);">
        <i class="fa fa-calendar-check"></i>
      </div>
      <h3>Approve Appointment</h3>
      <p>Are you sure you want to approve this appointment? The student will be notified and the session will be confirmed.</p>
      <div class="cAppointment-modalActions">
        <button class="cAppointment-modalBtn back" onclick="closeApproveModal()">
          <i class="fa fa-arrow-left"></i> Go Back
        </button>
        <button class="cAppointment-modalBtn confirm" style="background:linear-gradient(135deg,#16a34a,#22c55e);box-shadow:0 8px 20px rgba(34,197,94,0.3);" onclick="confirmApprove()">
          <i class="fa fa-check"></i> Yes, Approve
        </button>
      </div>
    </div>
  </div>

  <!-- REJECT MODAL -->
  <div class="cAppointment-modalOverlay" id="rejectModal">
    <div class="cAppointment-modalBox">
      <div class="cAppointment-modalIcon" style="background:linear-gradient(135deg,#fee2e2,#fecaca);color:#dc2626;box-shadow:0 8px 24px rgba(220,38,38,0.25);">
        <i class="fa fa-times-circle"></i>
      </div>
      <h3>Decline Appointment</h3>
      <p>Please provide a reason for declining so the student can rebook accordingly.</p>
      <textarea class="cAppointment-modalBox textarea" id="rejectReason"
        style="width:100%;min-height:100px;padding:10px 14px;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg);color:var(--text);font-size:13px;font-family:inherit;resize:vertical;outline:none;box-sizing:border-box;transition:border-color 0.2s,box-shadow 0.2s;"
        placeholder="e.g. Schedule conflict — please rebook for another available date..."></textarea>
      <div class="cAppointment-modalError" id="rejectError">
        <i class="fa fa-circle-exclamation"></i> Please enter a reason before declining.
      </div>
      <div class="cAppointment-modalActions">
        <button class="cAppointment-modalBtn back" onclick="closeRejectModal()">
          <i class="fa fa-arrow-left"></i> Go Back
        </button>
        <button class="cAppointment-modalBtn confirm" onclick="confirmReject()">
          <i class="fa fa-times"></i> Confirm Decline
        </button>
      </div>
    </div>
  </div>

  <!-- COMPLETE MODAL -->
  <div class="cAppointment-modalOverlay" id="completeModal">
    <div class="cAppointment-modalBox">
      <div class="cAppointment-modalIcon" style="background:rgba(73,136,196,0.12);color:#4988C4;box-shadow:0 8px 24px rgba(73,136,196,0.2);">
        <i class="fa fa-circle-check"></i>
      </div>
      <h3>Complete Appointment</h3>
      <p>Mark this appointment as completed? A session record will be saved in history. This action cannot be undone.</p>
      <div class="cAppointment-modalActions">
        <button class="cAppointment-modalBtn back" onclick="closeCompleteModal()">
          <i class="fa fa-arrow-left"></i> Go Back
        </button>
        <button class="cAppointment-modalBtn confirm" style="background:linear-gradient(135deg,#113F67,#4988C4);box-shadow:0 8px 20px rgba(73,136,196,0.3);" onclick="confirmComplete()">
          <i class="fa fa-check"></i> Yes, Complete
        </button>
      </div>
    </div>
  </div>

  <!-- CANCEL MODAL -->
  <div class="cAppointment-modalOverlay" id="cancelModal">
    <div class="cAppointment-modalBox">
      <div class="cAppointment-modalIcon" style="background:linear-gradient(135deg,#fee2e2,#fecaca);color:#dc2626;box-shadow:0 8px 24px rgba(220,38,38,0.25);">
        <i class="fa fa-ban"></i>
      </div>
      <h3>Cancel Appointment</h3>
      <p>Please provide a reason for cancelling so the student can follow up or rebook.</p>
      <textarea id="cancelReason"
        style="width:100%;min-height:100px;padding:10px 14px;border-radius:var(--radius);border:1px solid var(--border);background:var(--bg);color:var(--text);font-size:13px;font-family:inherit;resize:vertical;outline:none;box-sizing:border-box;transition:border-color 0.2s,box-shadow 0.2s;"
        placeholder="e.g. Counselor unavailable due to an emergency..."></textarea>
      <div class="cAppointment-modalError" id="cancelError">
        <i class="fa fa-circle-exclamation"></i> Please enter a reason before cancelling.
      </div>
      <div class="cAppointment-modalActions">
        <button class="cAppointment-modalBtn back" onclick="closeCancelModal()">
          <i class="fa fa-arrow-left"></i> Go Back
        </button>
        <button class="cAppointment-modalBtn confirm" onclick="confirmCancel()">
          <i class="fa fa-ban"></i> Confirm Cancel
        </button>
      </div>
    </div>
  </div>

  <!-- STUDENT PROFILE MODAL -->
  <div class="cAppointment-modalOverlay" id="studentModal">
    <div class="cAppointment-modalBox" id="studentModal-box">
      <div class="cAppointment-modalIcon" style="background:rgba(73,136,196,0.12);color:#4988C4;box-shadow:0 8px 24px rgba(73,136,196,0.2);">
        <i class="fa fa-user"></i>
      </div>
      <h3>Student Profile</h3>
      <div id="studentModalBody">
        <p style="text-align:center;padding:1rem 0;color:var(--text-muted);">Loading...</p>
      </div>
      <div class="cAppointment-modalActions" style="margin-top:20px;">
        <button class="cAppointment-modalBtn back" style="flex:1;" onclick="closeStudentModal()">
          Close
        </button>
      </div>
    </div>
  </div>

  <!-- RECEIPT MODAL -->
  <div class="cAppointment-modalOverlay" id="receiptModal" style="z-index:99999;">
    <div class="cAppointment-modalBox" id="receiptModalBox">
      <div class="uc-receipt-header">
        <div class="brand">UNITYCARE</div>
        <div class="title">Session Ticket</div>
        <div class="sub">Guidance &amp; Counseling Services</div>
      </div>
      <hr class="uc-receipt-dashes">
      <div class="uc-receipt-rows">
        <div class="uc-receipt-row">
          <span class="uc-receipt-label">Ticket No.</span>
          <span class="uc-receipt-value" id="rt-id">—</span>
        </div>
        <div class="uc-receipt-row">
          <span class="uc-receipt-label">Student</span>
          <span class="uc-receipt-value" id="rt-name">—</span>
        </div>
        <div class="uc-receipt-row">
          <span class="uc-receipt-label">Program</span>
          <span class="uc-receipt-value" id="rt-program">—</span>
        </div>
        <div class="uc-receipt-row">
          <span class="uc-receipt-label">Date</span>
          <span class="uc-receipt-value" id="rt-date">—</span>
        </div>
        <div class="uc-receipt-row">
          <span class="uc-receipt-label">Time</span>
          <span class="uc-receipt-value" id="rt-time">—</span>
        </div>
        <div class="uc-receipt-row">
          <span class="uc-receipt-label">Priority</span>
          <span class="uc-receipt-value"><span id="rt-priority" class="uc-priority">—</span></span>
        </div>
      </div>
      <div class="uc-receipt-status-wrap">
        <div class="uc-receipt-status-label">Status</div>
        <div class="uc-receipt-approved-pill">
          <i class="fa fa-check"></i> Approved
        </div>
      </div>
      <div class="uc-receipt-reason-wrap">
        <div class="uc-receipt-reason-label">Reason</div>
        <div class="uc-receipt-reason-text" id="rt-reason">—</div>
      </div>
      <div class="uc-receipt-barcode">||||| ||||| || |||||</div>
      <div class="uc-receipt-barcode-num" id="rt-barcode">APPT-0 &bull; 2026</div>
      <div class="uc-receipt-footer">Thank you</div>
      <button class="uc-receipt-close-btn" onclick="closeReceiptModal()">Close</button>
    </div>
  </div>

</main>

<script>
(function() {
  const saved = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-theme', saved);
})();

/* ── SIDEBAR / SETTINGS ── */
function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById('settingsDropdown').classList.toggle('show');
}
document.addEventListener('click', e => {
  const menu = document.getElementById('settingsDropdown');
  const btn  = document.querySelector('.sidebar-settingsButton');
  if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) menu.classList.remove('show');
});
function toggleTheme() {
  const html = document.documentElement;
  const next = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
  html.setAttribute('data-theme', next);
  localStorage.setItem('theme', next);
  /* Keep textareas themed */
  document.querySelectorAll('#rejectReason, #cancelReason').forEach(ta => {
    ta.style.background = next === 'dark' ? 'rgba(255,255,255,0.05)' : '';
    ta.style.borderColor = next === 'dark' ? 'rgba(255,255,255,0.1)' : '';
    ta.style.color = next === 'dark' ? '#dce8f5' : '';
  });
}

/* ── LOGOUT ── */
function logout()      { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout() { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=counselor'; }
document.getElementById('logoutOverlay').addEventListener('click', function(e) { if (e.target === this) closeLogout(); });

/* ── NOTIFICATIONS ── */
function toggleDropdown(id, e) {
  e.stopPropagation();
  document.getElementById(id).classList.toggle('show');
}
document.addEventListener('click', e => {
  const n = document.getElementById('notifDropdown');
  if (n && !n.contains(e.target)) n.classList.remove('show');
});

/* ── TABS ── */
function switchTab(name, btn) {
  document.querySelectorAll('.cAppointment-tab').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.cAppointment-panel').forEach(p => p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('panel-' + name).classList.add('active');
}

/* ── FILTER ── */
function toggleFilterBox() { document.getElementById('filterBox').classList.toggle('show'); }
function applyFilter() {
  const priority = document.getElementById('filterPriority').value.toLowerCase();
  const date     = document.getElementById('filterDate').value;
  let visible = 0;
  document.querySelectorAll('.cAppointment-card[data-id]').forEach(card => {
    const matchP = priority === 'all' || card.dataset.priority === priority;
    const matchD = !date || card.dataset.date === date;
    const show = matchP && matchD;
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  document.getElementById('noResultsMsg').style.display = visible === 0 ? 'block' : 'none';
}
function clearFilter() {
  document.getElementById('filterPriority').value = 'all';
  document.getElementById('filterDate').value = '';
  document.querySelectorAll('.cAppointment-card[data-id]').forEach(c => c.style.display = '');
  document.getElementById('noResultsMsg').style.display = 'none';
}
document.getElementById('searchInput').addEventListener('input', function() {
  const q = this.value.toLowerCase();
  let visible = 0;
  document.querySelectorAll('.cAppointment-card[data-id]').forEach(card => {
    const show = (card.dataset.name || '').includes(q);
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });
  document.getElementById('noResultsMsg').style.display = visible === 0 ? 'block' : 'none';
});

/* ── MODAL HELPERS ── */
function openModal(id) {
  const el = document.getElementById(id);
  el.classList.add('show');
  el.style.visibility = 'visible';
  el.style.opacity = '1';
}
function closeModal(id) {
  const el = document.getElementById(id);
  el.classList.remove('show');
  el.style.visibility = 'hidden';
  el.style.opacity = '0';
}

/* Close on backdrop click */
['approveModal','rejectModal','completeModal','cancelModal','studentModal','receiptModal'].forEach(id => {
  const el = document.getElementById(id);
  if (el) el.addEventListener('click', function(e) { if (e.target === this) closeModal(id); });
});

/* ── TEXTAREA DARK MODE on load ── */
(function applyTextareaDark() {
  if (document.documentElement.getAttribute('data-theme') === 'dark') {
    document.querySelectorAll('#rejectReason, #cancelReason').forEach(ta => {
      ta.style.background = 'rgba(255,255,255,0.05)';
      ta.style.borderColor = 'rgba(255,255,255,0.1)';
      ta.style.color = '#dce8f5';
    });
  }
})();

/* ── APPROVE ── */
let _approveApptId = null, _approveBtn = null;
function openApproveModal(apptId, btn) {
  _approveApptId = apptId;
  _approveBtn    = btn;
  openModal('approveModal');
}
function closeApproveModal() { closeModal('approveModal'); _approveApptId = null; _approveBtn = null; }
function confirmApprove() {
  const apptId = _approveApptId;
  const btn    = _approveBtn;
  closeApproveModal();
  const card = btn ? btn.closest('.cAppointment-card') : null;
  const fd = new FormData();
  fd.append('action', 'update_status');
  fd.append('appointment_id', apptId);
  fd.append('status', 'Approved');
  fetch('cappointments.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        if (card) populateAndShowReceipt(apptId, card);
        setTimeout(() => {
          if (card) removeCardWithFade(card, '.cAppointment-tab:first-child .cAppointment-tabBadge');
        }, 300);
      } else {
        alert(json.message || 'Failed to update.');
      }
    })
    .catch(() => alert('Something went wrong.'));
}

function populateAndShowReceipt(apptId, card) {
  let reason = '', program = '', time = '';
  card.querySelectorAll('p').forEach(p => {
    const t = p.textContent.trim();
    if (t.startsWith('Reason:'))  reason  = t.replace('Reason:', '').trim();
    if (t.startsWith('Program:')) program = t.replace('Program:', '').trim();
    if (t.startsWith('Time:'))    time    = t.replace('Time:', '').trim();
  });
  const rawName = card.querySelector('h3').textContent.trim().replace(/[\uf000-\uffff]/g, '').trim();
  const rawDate = card.dataset.date;
  const prio    = (card.dataset.priority || '').toLowerCase();

  document.getElementById('rt-id').textContent      = 'APPT-' + apptId;
  document.getElementById('rt-name').textContent    = rawName;
  document.getElementById('rt-program').textContent = program;
  document.getElementById('rt-date').textContent    = new Date(rawDate + 'T00:00:00')
    .toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
  document.getElementById('rt-time').textContent    = time;
  document.getElementById('rt-reason').textContent  = reason || 'N/A';
  document.getElementById('rt-barcode').textContent = 'APPT-' + apptId + ' \u2022 ' + new Date().getFullYear();

  const pBadge = document.getElementById('rt-priority');
  pBadge.textContent = prio.charAt(0).toUpperCase() + prio.slice(1);
  pBadge.className   = 'uc-priority ' + prio;

  openModal('receiptModal');
}
function closeReceiptModal() { closeModal('receiptModal'); }

/* ── REJECT ── */
let _rejectApptId = null;
function openRejectModal(apptId) {
  _rejectApptId = apptId;
  document.getElementById('rejectReason').value = '';
  document.getElementById('rejectError').style.display = 'none';
  openModal('rejectModal');
}
function closeRejectModal() { closeModal('rejectModal'); _rejectApptId = null; }
function confirmReject() {
  const reason = document.getElementById('rejectReason').value.trim();
  const errEl  = document.getElementById('rejectError');
  if (!reason) { errEl.style.display = 'flex'; return; }
  errEl.style.display = 'none';
  const fd = new FormData();
  fd.append('action', 'update_status');
  fd.append('appointment_id', _rejectApptId);
  fd.append('status', 'Rejected');
  fd.append('rejection_reason', reason);
  const id = _rejectApptId;
  fetch('cappointments.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        closeRejectModal();
        const card = document.querySelector('.cAppointment-card[data-id="' + id + '"]');
        if (card) removeCardWithFade(card, '.cAppointment-tab:first-child .cAppointment-tabBadge');
      } else {
        alert(json.message || 'Failed to decline.');
      }
    })
    .catch(() => alert('Something went wrong.'));
}

/* ── COMPLETE ── */
let _completeApptId = null;
function openCompleteModal(apptId) {
  _completeApptId = apptId;
  openModal('completeModal');
}
function closeCompleteModal() { closeModal('completeModal'); _completeApptId = null; }
function confirmComplete() {
  const id = _completeApptId;
  closeCompleteModal();
  const fd = new FormData();
  fd.append('action', 'mark_appointment');
  fd.append('appointment_id', id);
  fd.append('result', 'completed');
  fetch('cappointments.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        replaceActionsWithBadge(id, 'completed');
      } else {
        alert(json.message || 'Failed to update.');
      }
    })
    .catch(() => alert('Something went wrong.'));
}

/* ── CANCEL ── */
let _cancelApptId = null;
function openCancelModal(apptId) {
  _cancelApptId = apptId;
  document.getElementById('cancelReason').value = '';
  document.getElementById('cancelError').style.display = 'none';
  openModal('cancelModal');
}
function closeCancelModal() { closeModal('cancelModal'); _cancelApptId = null; }
function confirmCancel() {
  const reason = document.getElementById('cancelReason').value.trim();
  const errEl  = document.getElementById('cancelError');
  if (!reason) { errEl.style.display = 'flex'; return; }
  errEl.style.display = 'none';
  const fd = new FormData();
  fd.append('action', 'mark_appointment');
  fd.append('appointment_id', _cancelApptId);
  fd.append('result', 'cancelled');
  fd.append('reason', reason);
  const id = _cancelApptId;
  fetch('cappointments.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        closeCancelModal();
        replaceActionsWithBadge(id, 'cancelled');
      } else {
        alert(json.message || 'Failed to cancel.');
      }
    })
    .catch(() => alert('Something went wrong.'));
}

/* ── STUDENT PROFILE ── */
function openStudentModal(apptId) {
  const box = document.getElementById('studentModal-box');
  /* widen for profile content */
  box.style.maxWidth = '560px';
  box.style.textAlign = 'left';
  openModal('studentModal');
  const body = document.getElementById('studentModalBody');
  body.innerHTML = '<p style="text-align:center;padding:1.5rem 0;color:var(--text-muted);">Loading...</p>';
  fetch('cappointments.php?action=get_student&appointment_id=' + apptId)
    .then(r => r.json())
    .then(json => {
      if (!json.success) { body.innerHTML = '<p style="padding:1rem 0;color:var(--text-muted);">Could not load profile.</p>'; return; }
      const s = json.student;
      const initials = ((s.first_name||'?')[0] + (s.last_name||'?')[0]).toUpperCase();
      body.innerHTML = `
        <div style="display:flex;align-items:center;gap:14px;margin-bottom:4px;">
          <div class="uc-profile-avatar">${initials}</div>
          <div>
            <p style="font-size:16px;font-weight:600;color:var(--text);margin:0;">${s.first_name} ${s.last_name}</p>
            <p style="font-size:12px;color:var(--text-muted);margin:3px 0 0;">${s.course} &bull; ${s.year_level}</p>
          </div>
        </div>
        <div class="uc-profile-grid">
          <div class="uc-profile-box">
            <h4>Academic Info</h4>
            <p><b>Program:</b> ${s.course}</p>
            <p><b>Year Level:</b> ${s.year_level}</p>
            <p><b>Email:</b> ${s.email}</p>
          </div>
          <div class="uc-profile-box">
            <h4>Emergency Contact</h4>
            <p><b>Name:</b> ${s.emergency_name || 'N/A'}</p>
            <p><b>Relation:</b> ${s.emergency_relation || 'N/A'}</p>
            <p><b>Contact:</b> ${s.emergency_number || 'N/A'}</p>
          </div>
          <div class="uc-profile-box uc-profile-full">
            <h4>Last Wellness Check-in</h4>
            <div style="display:flex;align-items:center;gap:10px;margin-top:4px;">
              <span class="uc-mood-chip"><i class="fa fa-heart" style="font-size:11px;"></i> ${s.last_mood || 'N/A'}</span>
              <span style="font-size:13px;color:var(--text-muted);">${s.last_wellness || 'No check-in yet'}</span>
            </div>
          </div>
        </div>`;
    })
    .catch(() => { body.innerHTML = '<p style="padding:1rem 0;color:var(--text-muted);">Could not load profile.</p>'; });
}
function closeStudentModal() { closeModal('studentModal'); }

/* ── HELPERS ── */
function removeCardWithFade(card, badgeSelector) {
  card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
  card.style.opacity = '0';
  card.style.transform = 'scale(0.95)';
  setTimeout(() => {
    card.remove();
    const badge = document.querySelector(badgeSelector);
    if (badge) badge.textContent = Math.max(0, parseInt(badge.textContent) - 1);
  }, 300);
}
function replaceActionsWithBadge(apptId, status) {
  const el = document.getElementById('cAppointment-approvedActions-' + apptId);
  if (!el) return;
  const icon  = status === 'completed' ? 'fa-check' : 'fa-ban';
  const label = status === 'completed' ? 'Completed' : 'Cancelled';
  const div   = document.createElement('div');
  div.className = 'uc-status-badge ' + status;
  div.id = 'cAppointment-approvedActions-' + apptId;
  div.innerHTML = '<i class="fa ' + icon + '"></i> ' + label;
  el.replaceWith(div);
  const badge = document.querySelectorAll('.cAppointment-tab')[1]?.querySelector('.cAppointment-tabBadge');
  if (badge) badge.textContent = Math.max(0, parseInt(badge.textContent) - 1);
}

/* ── TEXTAREA FOCUS STYLE ── */
['rejectReason','cancelReason'].forEach(id => {
  const el = document.getElementById(id);
  if (!el) return;
  el.addEventListener('focus', () => {
    el.style.borderColor = '#4988C4';
    el.style.boxShadow = '0 0 0 3px rgba(73,136,196,0.15)';
  });
  el.addEventListener('blur', () => {
    el.style.borderColor = document.documentElement.getAttribute('data-theme') === 'dark' ? 'rgba(255,255,255,0.1)' : 'var(--border)';
    el.style.boxShadow = '';
  });
});
</script>
</body>
</html>
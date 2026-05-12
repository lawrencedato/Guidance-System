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

// ── AUTO-REJECT PAST PENDING APPOINTMENTS ──
$conn->query("
    UPDATE appointments
    SET status = 'Rejected',
        rejection_reason = 'Appointment date has passed without counselor action.'
    WHERE status = 'Pending'
      AND CONCAT(appointment_date, ' ', appointment_time) < NOW()
");

// ── COUNT THEN AUTO-COMPLETE PAST APPROVED APPOINTMENTS (counselor forgot to mark) ──
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
    "SELECT COUNT(*) c FROM appointments WHERE status='Pending'"
)->fetch_assoc()['c'];

// ── HANDLE APPROVE / REJECT (Pending tab) ──
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
        $ok = $conn->query(
            "UPDATE appointments
             SET status='$status', counselor_id='$cid', rejection_reason=$reasonSql
             WHERE appointment_id=$apptId"
        );
        if (!$ok || $conn->affected_rows === 0) throw new Exception("Could not update. Try again.");
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ── HANDLE COMPLETE / CANCEL (Approved tab) ──
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

// ── HANDLE GET: student profile for modal ──
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

// ── LOAD PENDING APPOINTMENTS ──
$apptRes = $conn->query("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time,
           a.priority, a.message,
           s.student_id, s.first_name, s.last_name, s.course, s.year_level
    FROM appointments a
    JOIN students s ON s.student_id = a.student_id
    WHERE a.status = 'Pending'
      AND CONCAT(a.appointment_date, ' ', a.appointment_time) >= NOW()
    ORDER BY a.appointment_date ASC, a.appointment_time ASC
");
$appointments = [];
while ($row = $apptRes->fetch_assoc()) $appointments[] = $row;

// ── LOAD ALL APPROVED APPOINTMENTS (this counselor only) ──
$approvedRes = $conn->query("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time,
           a.priority, a.message,
           s.first_name, s.last_name, s.course, s.year_level
    FROM appointments a
    JOIN students s ON s.student_id = a.student_id
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
<title>Appointment Requests - UNITYCARE</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>


</style>
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

<!-- MAIN -->
<main class="cAppointment-main">

  <!-- TABS -->
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

  <!-- ══ PENDING TAB ══ -->
  <div class="cAppointment-panel active" id="panel-pending">
    <section class="cAppointment-grid">
      <?php if (empty($appointments)): ?>
        <div style="text-align:center; padding:3rem; color:var(--text-muted); grid-column:1/-1;">
          <i class="fa fa-calendar-check" style="font-size:2.5rem; opacity:0.3; display:block; margin-bottom:1rem;"></i>
          <p>No pending appointment requests.</p>
        </div>
      <?php else: ?>
        <?php foreach ($appointments as $appt):
          $sName  = htmlspecialchars($appt['first_name'] . ' ' . $appt['last_name']);
          $apptId = (int)$appt['appointment_id'];
        ?>
        <div class="cAppointment-card"
             data-id="<?= $apptId ?>"
             data-name="<?= strtolower($sName) ?>"
             data-priority="<?= strtolower($appt['priority']) ?>"
             data-date="<?= $appt['appointment_date'] ?>">
          <h3><i class="fa fa-user"></i> <?= $sName ?></h3>
          <p><b>Reason:</b> <?= htmlspecialchars($appt['message'] ?? 'N/A') ?></p>
          <p><b>Program:</b> <?= htmlspecialchars($appt['year_level'] . ' - ' . $appt['course']) ?></p>
          <p><b>Date:</b> <?= date('F d, Y', strtotime($appt['appointment_date'])) ?></p>
          <p><b>Time:</b> <?= date('g:i A', strtotime($appt['appointment_time'])) ?></p>
          <p><b>Priority:</b> <?= htmlspecialchars($appt['priority']) ?></p>
          <div class="cAppointment-actions">
            <button class="cAppointment-btn approve" onclick="approveAppointment(<?= $apptId ?>, this)">
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
    <div id="noResultsMsg" style="display:none; text-align:center; padding:2rem; color:var(--text-muted);">
      <i class="fa fa-search" style="font-size:2rem; opacity:0.3; display:block; margin-bottom:0.75rem;"></i>
      <p>No appointments match your filter.</p>
    </div>
  </div>

  <!-- ══ APPROVED TAB ══ -->
  <div class="cAppointment-panel" id="panel-approved">

    <?php if ($autoCompletedCount > 0): ?>
      <div class="cAppointment-noticeBanner">
        <i class="fa fa-circle-info"></i>
        <span>
          <strong>
            <?= $autoCompletedCount ?>
            past appointment<?= $autoCompletedCount > 1 ? 's were' : ' was' ?>
            automatically marked as Completed
          </strong>
          — the session date had already passed without a recorded action.
        </span>
      </div>
    <?php endif; ?>

    <?php if (empty($approvedAppointments)): ?>
      <div style="text-align:center; padding:3rem; color:var(--text-muted);">
        <i class="fa fa-calendar" style="font-size:2.5rem; opacity:0.3; display:block; margin-bottom:1rem;"></i>
        <p>No approved appointments.</p>
      </div>
    <?php else: ?>
      <div class="cAppointment-approvedGrid">
        <?php foreach ($approvedAppointments as $appt):
          $sName  = htmlspecialchars($appt['first_name'] . ' ' . $appt['last_name']);
          $apptId = (int)$appt['appointment_id'];
          $isPast = strtotime($appt['appointment_date'] . ' ' . $appt['appointment_time']) < time();
        ?>
        <div class="cAppointment-card" id="cAppointment-approvedCard-<?= $apptId ?>">
          <h3>
            <i class="fa fa-user"></i> <?= $sName ?>
            <?php if ($isPast): ?>
              <span class="cAppointment-pastPill">Past</span>
            <?php endif; ?>
          </h3>
          <p><b>Reason:</b> <?= htmlspecialchars($appt['message'] ?? 'N/A') ?></p>
          <p><b>Program:</b> <?= htmlspecialchars($appt['year_level'] . ' - ' . $appt['course']) ?></p>
          <p><b>Date:</b> <?= date('F d, Y', strtotime($appt['appointment_date'])) ?></p>
          <p><b>Time:</b> <?= date('g:i A', strtotime($appt['appointment_time'])) ?></p>
          <p><b>Priority:</b> <?= htmlspecialchars($appt['priority']) ?></p>
          <div class="cAppointment-approvedActions" id="cAppointment-approvedActions-<?= $apptId ?>">
            <button class="cAppointment-approvedBtn complete" onclick="markComplete(<?= $apptId ?>)">
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

</main>

<!-- STUDENT PROFILE MODAL -->
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

<!-- ══════════════════════════════════════════════
     REJECT REASON MODAL
══════════════════════════════════════════════ -->
<div class="cAppointment-modalOverlay" id="rejectModal">
  <div class="cAppointment-modalBox">
    <h3><i class="fa fa-times-circle"></i> Decline Appointment</h3>
    <p>Please provide a reason for declining. The student will be able to see this explanation.</p>
    <textarea id="rejectReason" placeholder="e.g. Schedule conflict — please rebook for another available date..."></textarea>
    <div class="cAppointment-modalError" id="rejectError">Please enter a reason before declining.</div>
    <div class="cAppointment-modalActions">
      <button class="cAppointment-modalBtn back" onclick="closeRejectModal()">Go Back</button>
      <button class="cAppointment-modalBtn confirm" onclick="confirmReject()">
        <i class="fa fa-times"></i> Confirm Decline
      </button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════
     CANCEL REASON MODAL
══════════════════════════════════════════════ -->
<div class="cAppointment-modalOverlay" id="cancelModal">
  <div class="cAppointment-modalBox">
    <h3><i class="fa fa-ban"></i> Cancel Appointment</h3>
    <p>Please provide a reason for cancelling. The student will be able to see this explanation.</p>
    <textarea id="cancelReason" placeholder="e.g. Counselor unavailable due to an emergency..."></textarea>
    <div class="cAppointment-modalError" id="cancelError">Please enter a reason before cancelling.</div>
    <div class="cAppointment-modalActions">
      <button class="cAppointment-modalBtn back" onclick="closeCancelModal()">Go Back</button>
      <button class="cAppointment-modalBtn confirm" onclick="confirmCancel()">
        <i class="fa fa-ban"></i> Confirm Cancel
      </button>
    </div>
  </div>
</div>

<!-- ══ APPROVAL RECEIPT MODAL ══ -->
<!-- REPLACE the entire receiptModal div in cappointments.php with this -->

<div class="cAppointment-modalOverlay" id="receiptModal" style="z-index:99999;">
  <div style="
    background: #ffffff;
    border-radius: 20px;
    width: 340px;
    overflow: hidden;
    box-shadow: 0 18px 45px rgba(0,0,0,0.20);
    font-family: 'Poppins', sans-serif;
    animation: cAppointment-modalPop 0.25s ease;
  " id="receiptCard">

    <!-- ── GRADIENT HEADER (keep as-is) ── -->
    <div style="
      background: linear-gradient(135deg, #113F67, #4988C4);
      color: #fff;
      text-align: center;
      padding: 22px 16px 20px;
    ">
      <div style="font-size:9px; letter-spacing:3px; opacity:0.65; margin-bottom:6px; text-transform:uppercase;">UNITYCARE</div>
      <div style="font-size:18px; font-weight:700; letter-spacing:2px; text-transform:uppercase;">SESSION TICKET</div>
      <div style="font-size:10px; opacity:0.55; margin-top:5px;">Guidance &amp; Counseling Services</div>
    </div>

    <!-- ── DASHED SEPARATOR ── -->
    <div style="border-top:2px dashed #b0cde8; margin:14px 20px 0;"></div>

    <!-- ── TICKET ROWS ── -->
    <div style="padding:10px 24px 8px;">

      <!-- Ticket No. -->
      <div style="display:table; width:100%; padding:7px 0; border-bottom:1px dashed #dce8f0;">
        <span style="display:table-cell; font-size:11px; color:#64748b; width:90px; vertical-align:middle;">Ticket No.</span>
        <span style="display:table-cell; font-size:13px; font-weight:700; color:#113f67; text-align:right; vertical-align:middle;" id="rt-id">—</span>
      </div>

      <!-- Student -->
      <div style="display:table; width:100%; padding:7px 0; border-bottom:1px dashed #dce8f0;">
        <span style="display:table-cell; font-size:11px; color:#64748b; width:90px; vertical-align:middle;">Student</span>
        <span style="display:table-cell; font-size:12px; color:#0f172a; text-align:right; vertical-align:middle;" id="rt-name">—</span>
      </div>

      <!-- Program -->
      <div style="display:table; width:100%; padding:7px 0; border-bottom:1px dashed #dce8f0;">
        <span style="display:table-cell; font-size:11px; color:#64748b; width:90px; vertical-align:middle;">Program</span>
        <span style="display:table-cell; font-size:12px; color:#0f172a; text-align:right; vertical-align:middle;" id="rt-program">—</span>
      </div>

      <!-- Date -->
      <div style="display:table; width:100%; padding:7px 0; border-bottom:1px dashed #dce8f0;">
        <span style="display:table-cell; font-size:11px; color:#64748b; width:90px; vertical-align:middle;">Date</span>
        <span style="display:table-cell; font-size:12px; color:#0f172a; text-align:right; vertical-align:middle;" id="rt-date">—</span>
      </div>

      <!-- Time -->
      <div style="display:table; width:100%; padding:7px 0; border-bottom:1px dashed #dce8f0;">
        <span style="display:table-cell; font-size:11px; color:#64748b; width:90px; vertical-align:middle;">Time</span>
        <span style="display:table-cell; font-size:12px; color:#0f172a; text-align:right; vertical-align:middle;" id="rt-time">—</span>
      </div>

      <!-- Priority -->
      <div style="display:table; width:100%; padding:7px 0;">
        <span style="display:table-cell; font-size:11px; color:#64748b; width:90px; vertical-align:middle;">Priority</span>
        <span style="display:table-cell; text-align:right; vertical-align:middle;">
          <span id="rt-priority" style="font-size:10px; font-weight:600; padding:3px 14px; border-radius:999px; display:inline-block;">—</span>
        </span>
      </div>

    </div>

    <!-- ── STATUS SECTION ── -->
    <div style="border-top:2px dashed #b0cde8; border-bottom:2px dashed #b0cde8; margin:4px 20px; padding:12px 0; text-align:center;">
      <div style="font-size:9px; color:#94a3b8; letter-spacing:1.5px; text-transform:uppercase; margin-bottom:7px;">STATUS</div>
      <div style="display:inline-flex; align-items:center; gap:6px; background:rgba(34,197,94,0.12); border:1px solid rgba(34,197,94,0.35); color:#15803d; font-size:12px; font-weight:700; padding:6px 22px; border-radius:999px; letter-spacing:1px;">
        <i class="fa fa-check"></i> APPROVED
      </div>
    </div>

    <!-- ── REASON ── -->
    <div style="border-bottom:2px dashed #b0cde8; margin:0 20px; padding:12px 0 14px;">
      <div style="font-size:9px; color:#94a3b8; letter-spacing:1.5px; text-transform:uppercase; margin-bottom:5px;">REASON</div>
      <div style="font-size:11.5px; color:#0f172a; line-height:1.6;" id="rt-reason">—</div>
    </div>

    <!-- ── BARCODE ── -->
    <div style="text-align:center; padding:12px 0 6px;">
      <div style="font-size:14px; letter-spacing:5px; color:#94a3b8; font-family:'Courier New',monospace;">||||| ||||| || |||||</div>
      <div style="font-size:9px; color:#94a3b8; letter-spacing:1.5px; margin-top:3px;" id="rt-barcode">APPT-0 &bull; 2026</div>
    </div>

    <!-- ── THANK YOU ── -->
    <div style="background:#f0f4f8; text-align:center; padding:10px; font-size:9px; color:#94a3b8; letter-spacing:3px; text-transform:uppercase; border-top:1px solid #dce8f0;">
      THANK YOU
    </div>

    <!-- ── CLOSE BUTTON ── -->
    <div style="padding:14px 20px;">
      <button onclick="closeReceiptModal()" style="
        width:100%; padding:11px;
        background:#ffffff;
        border:1px solid rgba(15,23,42,0.10);
        border-radius:14px;
        font-size:13px; font-weight:600;
        cursor:pointer;
        font-family:'Poppins',sans-serif;
        color:#0f172a;
        transition:background 0.2s ease;
      " onmouseover="this.style.background='rgba(73,136,196,0.08)'" onmouseout="this.style.background='#ffffff'">
        Close
      </button>
    </div>

  </div>
</div>






<script>

(function() {
    const saved = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", saved);
})();

// ── Settings / theme ──────────────────────────────────────────────────────────
function toggleSettingsMenu(e) {
    e.stopPropagation();
    document.getElementById("settingsDropdown").classList.toggle("show");
}
document.addEventListener("click", e => {
    const menu = document.getElementById("settingsDropdown");
    const btn  = document.querySelector(".sidebar-settingsButton");
    if (!menu.contains(e.target) && !btn.contains(e.target)) menu.classList.remove("show");
});
function toggleTheme() {
    const html = document.documentElement;
    const newTheme = html.getAttribute("data-theme") === "light" ? "dark" : "light";
    html.setAttribute("data-theme", newTheme);
    localStorage.setItem("theme", newTheme);
}

// ── Logout ────────────────────────────────────────────────────────────────────
function logout()      { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout() { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=counselor'; }
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeLogout();
});

// ── Notification dropdown ─────────────────────────────────────────────────────
function toggleDropdown(id, e) {
    e.stopPropagation();
    document.getElementById(id).classList.toggle("show");
}
document.addEventListener("click", e => {
    const notif = document.getElementById("notifDropdown");
    if (notif && !notif.contains(e.target)) notif.classList.remove("show");
});

// ── Tabs ──────────────────────────────────────────────────────────────────────
function switchTab(name, btn) {
    document.querySelectorAll('.cAppointment-tab').forEach(t => t.classList.remove('active'));
    document.querySelectorAll('.cAppointment-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panel-' + name).classList.add('active');
}

// ── Filter (Pending tab) ──────────────────────────────────────────────────────
function toggleFilterBox() {
    document.getElementById("filterBox").classList.toggle("show");
}
function applyFilter() {
    const priority = document.getElementById("filterPriority").value.toLowerCase();
    const date     = document.getElementById("filterDate").value;
    let   visible  = 0;
    document.querySelectorAll(".cAppointment-card[data-id]").forEach(card => {
        const matchP = priority === "all" || card.dataset.priority === priority;
        const matchD = !date || card.dataset.date === date;
        const show   = matchP && matchD;
        card.style.display = show ? "" : "none";
        if (show) visible++;
    });
    document.getElementById("noResultsMsg").style.display = visible === 0 ? "block" : "none";
}
function clearFilter() {
    document.getElementById("filterPriority").value = "all";
    document.getElementById("filterDate").value     = "";
    document.querySelectorAll(".cAppointment-card[data-id]").forEach(c => c.style.display = "");
    document.getElementById("noResultsMsg").style.display = "none";
}

// ── Search ────────────────────────────────────────────────────────────────────
document.getElementById('searchInput').addEventListener("input", function() {
    const q       = this.value.toLowerCase();
    let   visible = 0;
    document.querySelectorAll(".cAppointment-card[data-id]").forEach(card => {
        const show = (card.dataset.name || '').includes(q);
        card.style.display = show ? "" : "none";
        if (show) visible++;
    });
    document.getElementById("noResultsMsg").style.display = visible === 0 ? "block" : "none";
});

// ── APPROVE ───────────────────────────────────────────────────────────────────
function approveAppointment(apptId, btn) {
    if (!confirm('Approve this appointment?')) return;
    const card = btn.closest('.cAppointment-card');
    const fd = new FormData();
    fd.append('action',         'update_status');
    fd.append('appointment_id', apptId);
    fd.append('status',         'Approved');

    fetch('cappointments.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                // Pull data from card paragraphs
                let reason = '', program = '', time = '';
                card.querySelectorAll('p').forEach(p => {
                    const t = p.textContent.trim();
                    if (t.startsWith('Reason:'))  reason  = t.replace('Reason:','').trim();
                    if (t.startsWith('Program:')) program = t.replace('Program:','').trim();
                    if (t.startsWith('Time:'))    time    = t.replace('Time:','').trim();
                });
                const rawName  = card.querySelector('h3').textContent.trim().replace(/^\S+\s*/, '');
                const rawDate  = card.dataset.date;
                const priority = (card.dataset.priority || '').toLowerCase();

                // ── Fill receipt rows ──
                document.getElementById('rt-id').textContent      = 'APPT-' + apptId;
                document.getElementById('rt-name').textContent    = rawName;
                document.getElementById('rt-program').textContent = program;
                document.getElementById('rt-date').textContent    = new Date(rawDate + 'T00:00:00')
                    .toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                document.getElementById('rt-time').textContent    = time;
                document.getElementById('rt-reason').textContent  = reason || 'N/A';
                document.getElementById('rt-barcode').textContent =
                    'APPT-' + apptId + ' \u2022 ' + new Date().getFullYear();

                // ── Priority pill ──
                const pBadge = document.getElementById('rt-priority');
                pBadge.textContent = priority.charAt(0).toUpperCase() + priority.slice(1);
                const pMap = {
                    high:   'background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5;',
                    medium: 'background:#fef3c7; color:#92400e; border:1px solid #fcd34d;',
                    low:    'background:#d1fae5; color:#065f46; border:1px solid #6ee7b7;',
                };
                pBadge.style.cssText = pMap[priority] || 'background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;';

                document.getElementById('receiptModal').classList.add('show');
                removeCardWithFade(card, '.cAppointment-tab:first-child .cAppointment-tabBadge');
            } else {
                alert(json.message || 'Failed to update.');
            }
        })
        .catch(() => alert('Something went wrong.'));
}

function closeReceiptModal() {
    document.getElementById('receiptModal').classList.remove('show');
}

document.getElementById('receiptModal').addEventListener('click', function(e) {
    if (e.target === this) closeReceiptModal();
});

// ── REJECT MODAL ──────────────────────────────────────────────────────────────
let _rejectApptId = null;

function openRejectModal(apptId) {
    _rejectApptId = apptId;
    document.getElementById('rejectReason').value = '';
    document.getElementById('rejectError').style.display = 'none';
    document.getElementById('rejectModal').classList.add('show');
}
function closeRejectModal() {
    _rejectApptId = null;
    document.getElementById('rejectModal').classList.remove('show');
}
document.getElementById('rejectModal').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});

function confirmReject() {
    const reason = document.getElementById('rejectReason').value.trim();
    const errEl  = document.getElementById('rejectError');
    if (!reason) { errEl.style.display = 'block'; return; }
    errEl.style.display = 'none';

    const fd = new FormData();
    fd.append('action',           'update_status');
    fd.append('appointment_id',   _rejectApptId);
    fd.append('status',           'Rejected');
    fd.append('rejection_reason', reason);

    fetch('cappointments.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                const id = _rejectApptId;
                closeRejectModal();
                const card = document.querySelector(`.cAppointment-card[data-id="${id}"]`);
                if (card) removeCardWithFade(card, '.cAppointment-tab:first-child .cAppointment-tabBadge');
            } else {
                alert(json.message || 'Failed to decline.');
            }
        })
        .catch(() => alert('Something went wrong.'));
}

// ── COMPLETE ──────────────────────────────────────────────────────────────────
function markComplete(apptId) {
    if (!confirm('Mark this appointment as Completed?')) return;
    const fd = new FormData();
    fd.append('action',         'mark_appointment');
    fd.append('appointment_id', apptId);
    fd.append('result',         'completed');
    fetch('cappointments.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                replaceActionsWithBadge(apptId, 'completed');
            } else {
                alert(json.message || 'Failed to update.');
            }
        })
        .catch(() => alert('Something went wrong.'));
}

// ── CANCEL MODAL ──────────────────────────────────────────────────────────────
let _cancelApptId = null;

function openCancelModal(apptId) {
    _cancelApptId = apptId;
    document.getElementById('cancelReason').value = '';
    document.getElementById('cancelError').style.display = 'none';
    document.getElementById('cancelModal').classList.add('show');
}
function closeCancelModal() {
    _cancelApptId = null;
    document.getElementById('cancelModal').classList.remove('show');
}
document.getElementById('cancelModal').addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});

function confirmCancel() {
    const reason = document.getElementById('cancelReason').value.trim();
    const errEl  = document.getElementById('cancelError');
    if (!reason) { errEl.style.display = 'block'; return; }
    errEl.style.display = 'none';

    const fd = new FormData();
    fd.append('action',         'mark_appointment');
    fd.append('appointment_id', _cancelApptId);
    fd.append('result',         'cancelled');
    fd.append('reason',         reason);

    fetch('cappointments.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                const id = _cancelApptId;
                closeCancelModal();
                replaceActionsWithBadge(id, 'cancelled');
            } else {
                alert(json.message || 'Failed to cancel.');
            }
        })
        .catch(() => alert('Something went wrong.'));
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function removeCardWithFade(card, badgeSelector) {
    card.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    card.style.opacity    = '0';
    card.style.transform  = 'scale(0.95)';
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
    const label = status === 'completed' ? 'Completed'  : 'Cancelled';
    el.outerHTML = `
        <div class="cAppointment-statusBadge ${status}" id="cAppointment-approvedActions-${apptId}">
            <i class="fa ${icon}"></i> ${label}
        </div>`;
    const badge = document.querySelectorAll('.cAppointment-tab')[1]?.querySelector('.cAppointment-tabBadge');
    if (badge) badge.textContent = Math.max(0, parseInt(badge.textContent) - 1);
}

// ── Student profile modal ─────────────────────────────────────────────────────
function openStudentModal(apptId) {
    document.getElementById("studentModal").classList.add("show");
    const body = document.getElementById("studentModalBody");
    body.innerHTML = '<p style="text-align:center;padding:2rem;color:var(--text-muted);">Loading...</p>';
    fetch('cappointments.php?action=get_student&appointment_id=' + apptId)
        .then(r => r.json())
        .then(json => {
            if (!json.success) {
                body.innerHTML = '<p style="padding:2rem;color:var(--text-muted);">Could not load profile.</p>';
                return;
            }
            const s        = json.student;
            const initials = (s.first_name[0] + s.last_name[0]).toUpperCase();
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
                <div class="cStudentModal-grid" style="margin-top:12px;">
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
                    <h4>Last Wellness Check-in</h4>
                    <p><b>Mood:</b> ${s.last_mood || 'N/A'}</p>
                    <p><b>Date:</b> ${s.last_wellness || 'No check-in yet'}</p>
                </div>`;
        })
        .catch(() => {
            body.innerHTML = '<p style="padding:2rem;color:var(--text-muted);">Could not load profile.</p>';
        });
}
function closeStudentModal() {
    document.getElementById("studentModal").classList.remove("show");
}
</script>
</body>
</html>
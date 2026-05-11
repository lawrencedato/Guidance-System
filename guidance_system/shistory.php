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

// ===== LOAD STUDENT DATA =====
$studentRes = $conn->query("SELECT * FROM students WHERE student_id='$sid' LIMIT 1");
$student    = $studentRes->fetch_assoc();

$profileRes = $conn->query("SELECT profile_image FROM student_profiles WHERE student_id='$sid' LIMIT 1");
$profile    = $profileRes->fetch_assoc();

$fullName   = htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$email      = htmlspecialchars($student['email'] ?? '');
$profileImg = !empty($profile['profile_image'])
              ? htmlspecialchars($profile['profile_image'])
              : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';

// ===== PAST SESSIONS (uses existing view) =====
$sessions = [];
$res = $conn->query("
    SELECT * FROM v_student_session_history
    WHERE student_id = '$sid'
    ORDER BY appointment_date DESC, appointment_time DESC
");
if ($res) while ($row = $res->fetch_assoc()) $sessions[] = $row;

// ===== CONCERNS =====
$concerns = [];
$res = $conn->query("
    SELECT c.concern_id, c.subject, c.message AS concern_message, c.status,
           c.created_at AS concern_date,
           cr.reply, cr.replied_at,
           CONCAT(co.first_name,' ',co.last_name) AS counselor_name
    FROM concerns c
    LEFT JOIN concern_replies cr ON cr.concern_id = c.concern_id
    LEFT JOIN counselors co ON co.counselor_id = cr.counselor_id
    WHERE c.student_id = '$sid'
    ORDER BY c.created_at DESC
");
if ($res) while ($row = $res->fetch_assoc()) $concerns[] = $row;

// ===== REFERRALS =====
$referrals = [];
$res = $conn->query("
    SELECT r.referral_id, r.referral_date, r.reason, r.counselor_remarks, r.created_at,
           CONCAT(c.first_name,' ',c.last_name) AS counselor_name, c.department
    FROM referrals r
    JOIN counselors c ON c.counselor_id = r.counselor_id
    WHERE r.student_id = '$sid'
    ORDER BY r.referral_date DESC
");
if ($res) while ($row = $res->fetch_assoc()) $referrals[] = $row;

// ===== WELLNESS CHECKS =====
$wellness = [];
$res = $conn->query("
    SELECT wellness_id, mood_label, stress_level, sleep_quality, created_at
    FROM wellness_checks
    WHERE student_id = '$sid'
    ORDER BY created_at DESC
");
if ($res) while ($row = $res->fetch_assoc()) $wellness[] = $row;

// ===== ANNOUNCEMENTS RESPONDED TO =====
$announcements = [];
$res = $conn->query("
    SELECT a.announcement_id, a.title, a.message, a.file_name, a.file_path,
           a.created_at, ar.response, ar.responded_at,
           CONCAT(c.first_name,' ',c.last_name) AS counselor_name
    FROM announcement_responses ar
    JOIN announcements a ON a.announcement_id = ar.announcement_id
    JOIN counselors c ON c.counselor_id = a.counselor_id
    WHERE ar.student_id = '$sid'
    ORDER BY ar.responded_at DESC
");
if ($res) while ($row = $res->fetch_assoc()) $announcements[] = $row;

// ===== HELPERS =====
function safeDate($d, $fmt = 'M d, Y') {
    if (empty($d) || $d === '0000-00-00') return '—';
    return date($fmt, strtotime($d));
}
function safeTime($t) {
    if (empty($t)) return '—';
    return date('h:i A', strtotime($t));
}
function statusBadge($status) {
    $map = [
        'Completed' => 'background:#d1fae5;color:#065f46',
        'Approved'  => 'background:#dbeafe;color:#1e40af',
        'Rejected'  => 'background:#fee2e2;color:#991b1b',
        'Pending'   => 'background:#fef3c7;color:#92400e',
        'Resolved'  => 'background:#ede9fe;color:#5b21b6',
        'Reviewed'  => 'background:#e0f2fe;color:#0369a1',
    ];
    $style = $map[$status] ?? 'background:#f3f4f6;color:#374151';
    return "<span style='{$style};padding:3px 11px;border-radius:999px;font-size:.72rem;font-weight:600;white-space:nowrap;'>"
         . htmlspecialchars($status ?? '—') . "</span>";
}
function moodIcon($mood) {
    $icons = [
        'Very Happy' => '😄',
        'Happy'      => '🙂',
        'Neutral'    => '😐',
        'Sad'        => '😔',
        'Very Sad'   => '😢',
    ];
    return ($icons[$mood] ?? '—') . ' ' . htmlspecialchars($mood ?? '—');
}
function stressBar($level) {
    $color = $level >= 70 ? '#ef4444' : ($level >= 40 ? '#f59e0b' : '#10b981');
    return "<div style='display:flex;align-items:center;gap:6px;'>
        <div style='flex:1;background:#e5e7eb;border-radius:999px;height:6px;'>
            <div style='width:{$level}%;background:{$color};border-radius:999px;height:6px;'></div>
        </div>
        <span style='font-size:.78rem;font-weight:600;color:{$color};'>{$level}%</span>
    </div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | History</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

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

.sHistory-main {
  margin-left: 280px;
  padding: 28px 28px 40px;
  background: var(--bg, #f5f9ff);
  min-height: 100vh;
  box-sizing: border-box;
}

/* ── TABS ── */
.sHistory-tabs {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
  background: var(--card, #fff);
  padding: 10px;
  border-radius: 14px;
  border: 1px solid var(--border, #e3e8ef);
  margin-bottom: 14px;
  box-sizing: border-box;
}
.sHistory-tabs button {
  padding: 8px 18px;
  border-radius: 10px;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: .875rem;
  font-weight: 600;
  color: #6b7280;
  transition: all .15s;
  /* Reset any global button styles */
  display: inline-flex;
  align-items: center;
  gap: 6px;
  line-height: 1.4;
}
.sHistory-tabs button.active {
  background: #113F67;
  color: #fff;
  box-shadow: none;
}
.sHistory-tabs button:hover:not(.active) {
  background: #f3f4f6;
  color: #111;
  transform: none;
  box-shadow: none;
}

/* ── FILTER BAR ── */
.sHistory-filterBar {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  background: var(--card, #fff);
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid var(--border, #e3e8ef);
  margin-bottom: 16px;
  align-items: center;
  box-sizing: border-box;
}
.sHistory-filterBar input,
.sHistory-filterBar select {
  padding: 9px 12px;
  border-radius: 10px;
  border: 2px solid var(--border, #e3e8ef);
  font-size: .875rem;
  background: var(--card, #fff);
  color: var(--text, #111);
  outline: none;
  /* Override global input styles */
  box-shadow: none;
}
.sHistory-filterBar input:focus,
.sHistory-filterBar select:focus {
  border-color: #113F67;
  box-shadow: 0 0 0 3px rgba(17,63,103,.15);
}
.sHistory-filterBar .filter-search { flex: 1; min-width: 160px; }

.sHistory-filterBar .btn-apply,
.sHistory-filterBar .btn-clear {
  padding: 9px 16px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  font-weight: 600;
  font-size: .875rem;
  white-space: nowrap;
  display: inline-flex;
  align-items: center;
  gap: 6px;
  transition: background .15s, transform .15s;
  /* Reset global .btn gradient */
  box-shadow: none;
}
.sHistory-filterBar .btn-apply { background: #113F67; color: #fff; }
.sHistory-filterBar .btn-apply:hover { background: #0d3054; transform: none; }
.sHistory-filterBar .btn-clear  { background: #f3f4f6; color: #374151; }
.sHistory-filterBar .btn-clear:hover { background: #e5e7eb; transform: none; }

/* ── TAB CONTENT ── */
.sHistory-tabContent { display: none; }
.sHistory-tabContent.active { display: block; }

/* ── TABLE WRAPPER ── */
.sHistory-table-wrap {
  background: var(--card, #fff);
  border-radius: 14px;
  border: 1px solid var(--border, #e3e8ef);
  overflow: hidden;
  box-sizing: border-box;
}

/* Scoped table — override global th/td rules from style.css */
.sHistory-table-wrap table {
  width: 100%;
  border-collapse: collapse;
  font-size: .875rem;
  /* Reset global table min-width */
  min-width: 0;
}
.sHistory-table-wrap thead tr {
  border-top: none;
}
.sHistory-table-wrap thead th {
  /* Override global th */
  display: table-cell !important;
  position: static !important;
  background: #f8fafc;
  padding: 12px 14px;
  text-align: left;
  font-size: .75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .4px;
  color: #6b7280;
  border-bottom: 1px solid var(--border, #e3e8ef);
  border-top: none;
  vertical-align: middle;
}
.sHistory-table-wrap tbody tr {
  /* Override global tr display */
  display: table-row !important;
  border-top: none;
}
.sHistory-table-wrap tbody td {
  /* Override global td */
  display: table-cell !important;
  position: static !important;
  padding: 12px 14px;
  border-bottom: 1px solid #f1f5f9;
  border-top: none;
  vertical-align: middle;
  color: var(--text, #111);
}
.sHistory-table-wrap tbody tr:last-child td { border-bottom: none; }
.sHistory-table-wrap tbody tr:hover td {
  background: #f8fafc;
}

.sHistory-table-wrap .td-sub {
  font-size: .78rem;
  color: #6b7280;
  margin-top: 2px;
}
.sHistory-table-wrap .td-truncate {
  max-width: 200px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.sHistory-table-wrap td.empty {
  text-align: center;
  color: #9ca3af;
  padding: 2.5rem;
}

/* ── VIEW BUTTON ── */
.sHistory-table-wrap .btn-view {
  background: #f0f5ff;
  color: #113F67;
  border: none;
  padding: 5px 13px;
  border-radius: 8px;
  font-size: .8rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  /* Reset global .btn styles */
  box-shadow: none;
  display: inline-block;
  line-height: 1.5;
  transition: background .15s;
}
.sHistory-table-wrap .btn-view:hover {
  background: #dce8fb;
  transform: none;
  box-shadow: none;
}

/* ── MODAL ── */
.sh-modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(17,63,103,.25);
  backdrop-filter: blur(6px);
  z-index: 99999;
  align-items: center;
  justify-content: center;
}
.sh-modal-overlay.show { display: flex; }
.sh-modal {
  background: var(--card, #fff);
  color: var(--text, #111);
  border-radius: 16px;
  padding: 2rem;
  width: min(520px, 94vw);
  max-height: 88vh;
  overflow-y: auto;
  position: relative;
  box-shadow: 0 24px 64px rgba(0,0,0,.22);
}
.sh-modal h3 {
  margin: 0 0 1.4rem;
  font-size: 1.05rem;
  color: #113F67;
}
.sh-modal-close {
  position: absolute;
  top: 1rem; right: 1.1rem;
  background: none; border: none;
  font-size: 1.1rem; cursor: pointer;
  color: #6b7280; line-height: 1;
  display: flex; align-items: center; justify-content: center;
}
.sh-modal-close:hover { color: #111; }
.sh-detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .75rem 1.4rem;
}
.sh-detail-row { display: flex; flex-direction: column; gap: .18rem; }
.sh-detail-row.full { grid-column: 1 / -1; }
.sh-detail-label {
  font-size: .7rem;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: #9ca3af;
  font-weight: 700;
}
.sh-detail-value { font-size: .9rem; line-height: 1.5; word-break: break-word; }

/* ════════════════════════════
   DARK MODE OVERRIDES
════════════════════════════ */
[data-theme="dark"] .sHistory-tabs {
  background: #1e293b;
  border-color: #334155;
}
[data-theme="dark"] .sHistory-tabs button {
  color: #94a3b8;
}
[data-theme="dark"] .sHistory-tabs button:hover:not(.active) {
  background: #334155;
  color: #e2e8f0;
}
[data-theme="dark"] .sHistory-tabs button.active {
  background: #113F67;
  color: #fff;
}

[data-theme="dark"] .sHistory-filterBar {
  background: #1e293b;
  border-color: #334155;
}
[data-theme="dark"] .sHistory-filterBar input,
[data-theme="dark"] .sHistory-filterBar select {
  background: #0f172a;
  color: #f1f5f9;
  border-color: #334155;
}
[data-theme="dark"] .sHistory-filterBar .btn-clear {
  background: #334155;
  color: #cbd5e1;
}
[data-theme="dark"] .sHistory-filterBar .btn-clear:hover {
  background: #475569;
}

[data-theme="dark"] .sHistory-table-wrap {
  background: #1e293b;
  border-color: #334155;
}
[data-theme="dark"] .sHistory-table-wrap thead th {
  background: #0f172a;
  color: #94a3b8;
  border-color: #334155;
}
[data-theme="dark"] .sHistory-table-wrap tbody td {
  border-color: #334155;
  color: #f1f5f9;
}
[data-theme="dark"] .sHistory-table-wrap tbody tr:hover td {
  background: #0f172a;
}
[data-theme="dark"] .sHistory-table-wrap .td-sub {
  color: #64748b;
}

[data-theme="dark"] .sh-modal {
  background: #1e293b;
  color: #f1f5f9;
  border: 1px solid #334155;
}
[data-theme="dark"] .sh-modal h3 { color: #93c5fd; }
[data-theme="dark"] .sh-detail-label { color: #475569; }
[data-theme="dark"] .sh-modal-close { color: #94a3b8; }
[data-theme="dark"] .sh-modal-close:hover { color: #f1f5f9; }

[data-theme="dark"] .sHistory-table-wrap .btn-view {
  background: #1e3a5f;
  color: #93c5fd;
}
[data-theme="dark"] .sHistory-table-wrap .btn-view:hover {
  background: #1d4ed8;
  color: #fff;
}
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
        <a href="sprofile.php"><i class="fa fa-user"></i> Profile</a>
        <a href="shistory.php" class="active"><i class="fa fa-clock"></i> History</a>
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
    <a href="sreports.php"><i class="fa fa-ticket"></i> Tickets</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="sfeedback.php"><i class="fa fa-comment"></i> Feedback</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left">
    <h2>History</h2>
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
<main class="sHistory-main">

  <!-- TABS -->
  <div class="sHistory-tabs">
    <button class="active" onclick="switchTab(event,'sessions')">
      <i class="fa fa-calendar-check"></i> Past Sessions
    </button>
    <button onclick="switchTab(event,'concerns')">
      <i class="fa fa-headset"></i> Concerns
    </button>
    <button onclick="switchTab(event,'referrals')">
      <i class="fa fa-route"></i> Referrals
    </button>
    <button onclick="switchTab(event,'wellness')">
      <i class="fa fa-heart"></i> Wellness Checks
    </button>
    <button onclick="switchTab(event,'announcements')">
      <i class="fa fa-bullhorn"></i> Announcements
    </button>
  </div>

  <!-- FILTER BAR -->
  <div class="sHistory-filterBar">
    <input type="date" id="filterDate" title="Filter by date">
    <select id="filterStatus">
      <option value="all">All Statuses</option>
      <option>Approved</option>
      <option>Completed</option>
      <option>Rejected</option>
      <option>Pending</option>
      <option>Resolved</option>
      <option>Reviewed</option>
    </select>
    <input type="text" id="filterSearch" class="filter-search"
           placeholder="Search…" oninput="applyFilter()">
    <button class="btn-apply" onclick="applyFilter()">
      <i class="fa fa-filter"></i> Apply
    </button>
    <button class="btn-clear" onclick="clearFilter()">
      <i class="fa fa-xmark"></i> Clear
    </button>
  </div>

  <!-- ══ TAB: PAST SESSIONS ══ -->
  <div id="sessions" class="sHistory-tabContent active">
    <div class="sHistory-table-wrap">
      <table>
        <thead>
          <tr>
            <th>Counselor</th>
            <th>Date</th>
            <th>Time</th>
            <th>Priority</th>
            <th>Status</th>
            <th>Rating</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($sessions)): ?>
            <tr><td colspan="7" class="empty">No past sessions found.</td></tr>
          <?php else: foreach ($sessions as $s): ?>
          <tr
            data-date="<?= htmlspecialchars($s['appointment_date'] ?? '') ?>"
            data-status="<?= htmlspecialchars($s['status'] ?? '') ?>"
            data-search="<?= htmlspecialchars(strtolower($s['counselor_name'] ?? '')) ?>"
          >
            <td>
              <div><?= htmlspecialchars($s['counselor_name'] ?? '—') ?></div>
              <div class="td-sub"><?= htmlspecialchars($s['counselor_department'] ?? '') ?></div>
            </td>
            <td><?= safeDate($s['appointment_date']) ?></td>
            <td><?= safeTime($s['appointment_time']) ?></td>
            <td><?= htmlspecialchars($s['priority'] ?? '—') ?></td>
            <td><?= statusBadge($s['status']) ?></td>
            <td><?= !empty($s['feedback_rating']) ? htmlspecialchars($s['feedback_rating']) : '<span style="color:#9ca3af;">—</span>' ?></td>
            <td>
              <button class="btn-view"
                onclick="openSessionModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)">
                View
              </button>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ══ TAB: CONCERNS ══ -->
  <div id="concerns" class="sHistory-tabContent">
    <div class="sHistory-table-wrap">
      <table>
        <thead>
          <tr>
            <th>Subject</th>
            <th>Submitted</th>
            <th>Status</th>
            <th>Replied By</th>
            <th>Replied At</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($concerns)): ?>
            <tr><td colspan="6" class="empty">No concerns submitted yet.</td></tr>
          <?php else: foreach ($concerns as $c): ?>
          <tr
            data-date="<?= htmlspecialchars(date('Y-m-d', strtotime($c['concern_date'] ?? 'now'))) ?>"
            data-status="<?= htmlspecialchars($c['status'] ?? '') ?>"
            data-search="<?= htmlspecialchars(strtolower(($c['subject'] ?? '') . ' ' . ($c['counselor_name'] ?? ''))) ?>"
          >
            <td class="td-truncate" title="<?= htmlspecialchars($c['subject'] ?? '') ?>">
              <?= htmlspecialchars($c['subject'] ?? '—') ?>
            </td>
            <td><?= safeDate($c['concern_date']) ?></td>
            <td><?= statusBadge($c['status']) ?></td>
            <td><?= !empty($c['counselor_name']) ? htmlspecialchars($c['counselor_name']) : '<span style="color:#9ca3af;">—</span>' ?></td>
            <td><?= !empty($c['replied_at']) ? safeDate($c['replied_at'], 'M d, Y g:i A') : '<span style="color:#9ca3af;">—</span>' ?></td>
            <td>
              <button class="btn-view"
                onclick="openConcernModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)">
                View
              </button>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ══ TAB: REFERRALS ══ -->
  <div id="referrals" class="sHistory-tabContent">
    <div class="sHistory-table-wrap">
      <table>
        <thead>
          <tr>
            <th>Counselor</th>
            <th>Referral Date</th>
            <th>Department</th>
            <th>Reason</th>
            <th>Remarks</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($referrals)): ?>
            <tr><td colspan="6" class="empty">No referrals found.</td></tr>
          <?php else: foreach ($referrals as $r): ?>
          <tr
            data-date="<?= htmlspecialchars($r['referral_date'] ?? '') ?>"
            data-status="all"
            data-search="<?= htmlspecialchars(strtolower(($r['counselor_name'] ?? '') . ' ' . ($r['reason'] ?? ''))) ?>"
          >
            <td><?= htmlspecialchars($r['counselor_name'] ?? '—') ?></td>
            <td><?= safeDate($r['referral_date']) ?></td>
            <td><?= htmlspecialchars($r['department'] ?? '—') ?></td>
            <td class="td-truncate" title="<?= htmlspecialchars($r['reason'] ?? '') ?>">
              <?= htmlspecialchars($r['reason'] ?? '—') ?>
            </td>
            <td class="td-truncate" title="<?= htmlspecialchars($r['counselor_remarks'] ?? '') ?>">
              <?= !empty($r['counselor_remarks']) ? htmlspecialchars($r['counselor_remarks']) : '<span style="color:#9ca3af;">—</span>' ?>
            </td>
            <td>
              <button class="btn-view"
                onclick="openReferralModal(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)">
                View
              </button>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ══ TAB: WELLNESS CHECKS ══ -->
  <div id="wellness" class="sHistory-tabContent">
    <div class="sHistory-table-wrap">
      <table>
        <thead>
          <tr>
            <th>Date & Time</th>
            <th>Mood</th>
            <th>Stress Level</th>
            <th>Sleep Quality</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($wellness)): ?>
            <tr><td colspan="5" class="empty">No wellness checks submitted yet.</td></tr>
          <?php else: foreach ($wellness as $w): ?>
          <tr
            data-date="<?= htmlspecialchars(date('Y-m-d', strtotime($w['created_at']))) ?>"
            data-status="all"
            data-search="<?= htmlspecialchars(strtolower($w['mood_label'] . ' ' . $w['sleep_quality'])) ?>"
          >
            <td><?= safeDate($w['created_at'], 'M d, Y g:i A') ?></td>
            <td><?= moodIcon($w['mood_label']) ?></td>
            <td><?= stressBar((int)$w['stress_level']) ?></td>
            <td>
              <?php
                $sq = $w['sleep_quality'] ?? '—';
                $sqColor = $sq === 'Good' ? '#10b981' : ($sq === 'Poor' ? '#ef4444' : '#f59e0b');
                echo "<span style='color:{$sqColor};font-weight:600;'>" . htmlspecialchars($sq) . "</span>";
              ?>
            </td>
            <td>
              <button class="btn-view"
                onclick="openWellnessModal(<?= htmlspecialchars(json_encode($w), ENT_QUOTES) ?>)">
                View
              </button>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ══ TAB: ANNOUNCEMENTS ══ -->
  <div id="announcements" class="sHistory-tabContent">
    <div class="sHistory-table-wrap">
      <table>
        <thead>
          <tr>
            <th>Title</th>
            <th>Posted By</th>
            <th>Posted On</th>
            <th>My Response</th>
            <th>Responded At</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($announcements)): ?>
            <tr><td colspan="6" class="empty">No announcement responses yet.</td></tr>
          <?php else: foreach ($announcements as $a): ?>
          <?php
            $respColor = $a['response'] === 'Interested' ? '#10b981' : '#ef4444';
            $respIcon  = $a['response'] === 'Interested' ? '✓' : '✗';
          ?>
          <tr
            data-date="<?= htmlspecialchars(date('Y-m-d', strtotime($a['responded_at'] ?? 'now'))) ?>"
            data-status="all"
            data-search="<?= htmlspecialchars(strtolower(($a['title'] ?? '') . ' ' . ($a['counselor_name'] ?? ''))) ?>"
          >
            <td class="td-truncate" title="<?= htmlspecialchars($a['title'] ?? '') ?>">
              <?= htmlspecialchars($a['title'] ?? '—') ?>
            </td>
            <td><?= htmlspecialchars($a['counselor_name'] ?? '—') ?></td>
            <td><?= safeDate($a['created_at']) ?></td>
            <td>
              <span style="color:<?= $respColor ?>;font-weight:700;">
                <?= $respIcon ?> <?= htmlspecialchars($a['response'] ?? '—') ?>
              </span>
            </td>
            <td><?= safeDate($a['responded_at'], 'M d, Y g:i A') ?></td>
            <td>
              <button class="btn-view"
                onclick="openAnnouncementModal(<?= htmlspecialchars(json_encode($a), ENT_QUOTES) ?>)">
                View
              </button>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>

<!-- ══ MODALS ══ -->

<!-- Session Modal -->
<div class="sh-modal-overlay" id="sessionModal">
  <div class="sh-modal">
    <button class="sh-modal-close" onclick="closeModal('sessionModal')"><i class="fa fa-xmark"></i></button>
    <h3><i class="fa fa-calendar-check" style="margin-right:.4rem;"></i>Session Details</h3>
    <div class="sh-detail-grid" id="sessionModalBody"></div>
  </div>
</div>

<!-- Concern Modal -->
<div class="sh-modal-overlay" id="concernModal">
  <div class="sh-modal">
    <button class="sh-modal-close" onclick="closeModal('concernModal')"><i class="fa fa-xmark"></i></button>
    <h3><i class="fa fa-headset" style="margin-right:.4rem;"></i>Concern Details</h3>
    <div class="sh-detail-grid" id="concernModalBody"></div>
  </div>
</div>

<!-- Referral Modal -->
<div class="sh-modal-overlay" id="referralModal">
  <div class="sh-modal">
    <button class="sh-modal-close" onclick="closeModal('referralModal')"><i class="fa fa-xmark"></i></button>
    <h3><i class="fa fa-route" style="margin-right:.4rem;"></i>Referral Details</h3>

    <div style="margin-bottom:1rem;">
      <a id="referralExportBtn" href="sreferral_export.php" target="_blank"
         style="display:inline-flex;align-items:center;gap:6px;background:#113F67;color:#fff;
                padding:7px 16px;border-radius:9px;font-size:.82rem;font-weight:600;
                text-decoration:none;transition:background .15s;">
        <i class="fa fa-file-arrow-down"></i> Export as PDF
      </a>
    </div>

    <div class="sh-detail-grid" id="referralModalBody"></div>
  </div>
</div>

<!-- Wellness Modal -->
<div class="sh-modal-overlay" id="wellnessModal">
  <div class="sh-modal">
    <button class="sh-modal-close" onclick="closeModal('wellnessModal')"><i class="fa fa-xmark"></i></button>
    <h3><i class="fa fa-heart" style="margin-right:.4rem;"></i>Wellness Check Details</h3>
    <div class="sh-detail-grid" id="wellnessModalBody"></div>
  </div>
</div>

<!-- Announcement Modal -->
<div class="sh-modal-overlay" id="announcementModal">
  <div class="sh-modal">
    <button class="sh-modal-close" onclick="closeModal('announcementModal')"><i class="fa fa-xmark"></i></button>
    <h3><i class="fa fa-bullhorn" style="margin-right:.4rem;"></i>Announcement Details</h3>
    <div class="sh-detail-grid" id="announcementModalBody"></div>
  </div>
</div>

<!-- Logout Modal -->
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

<script>
/* ── THEME ── */
(function() {
  const saved = localStorage.getItem("theme") || "light";
  document.documentElement.setAttribute("data-theme", saved);
})();

/* ── TABS ── */
let currentTab = 'sessions';

function switchTab(event, tabId) {
  document.querySelectorAll('.sHistory-tabContent').forEach(t => t.classList.remove('active'));
  document.getElementById(tabId).classList.add('active');
  document.querySelectorAll('.sHistory-tabs button').forEach(b => b.classList.remove('active'));
  event.currentTarget.classList.add('active');
  currentTab = tabId;

  // Status filter only useful for sessions & concerns
  const showStatus = tabId === 'sessions' || tabId === 'concerns';
  document.getElementById('filterStatus').style.display = showStatus ? '' : 'none';

  clearFilter();
}

/* ── FILTER ── */
function applyFilter() {
  const date   = document.getElementById('filterDate').value;
  const status = document.getElementById('filterStatus').value.toLowerCase();
  const search = document.getElementById('filterSearch').value.toLowerCase().trim();

  document.querySelectorAll(`#${currentTab} tbody tr`).forEach(row => {
    const rDate   = row.dataset.date   || '';
    const rStatus = (row.dataset.status || '').toLowerCase();
    const rSearch = (row.dataset.search || '').toLowerCase();

    const matchDate   = !date   || rDate === date;
    const matchStatus = status === 'all' || rStatus === status;
    const matchSearch = !search || rSearch.includes(search);

    row.style.display = (matchDate && matchStatus && matchSearch) ? '' : 'none';
  });
}

function clearFilter() {
  document.getElementById('filterDate').value   = '';
  document.getElementById('filterStatus').value = 'all';
  document.getElementById('filterSearch').value = '';
  document.querySelectorAll(`#${currentTab} tbody tr`).forEach(r => r.style.display = '');
}

/* ── MODAL HELPERS ── */
function row(label, value, full = false) {
  return `<div class="sh-detail-row${full ? ' full' : ''}">
    <span class="sh-detail-label">${label}</span>
    <span class="sh-detail-value">${value ?? '—'}</span>
  </div>`;
}
function fmtDate(d) {
  if (!d || d === '0000-00-00') return '—';
  return new Date(d + 'T00:00:00').toLocaleDateString('en-US', { year:'numeric', month:'short', day:'numeric' });
}
function fmtDateTime(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleString('en-US', { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
}
function fmtTime(t) {
  if (!t) return '—';
  const [h, m] = t.split(':');
  const hr = parseInt(h);
  return `${hr % 12 || 12}:${m} ${hr >= 12 ? 'PM' : 'AM'}`;
}
function badge(status) {
  const map = {
    Completed : 'background:#d1fae5;color:#065f46',
    Approved  : 'background:#dbeafe;color:#1e40af',
    Rejected  : 'background:#fee2e2;color:#991b1b',
    Pending   : 'background:#fef3c7;color:#92400e',
    Resolved  : 'background:#ede9fe;color:#5b21b6',
    Reviewed  : 'background:#e0f2fe;color:#0369a1',
  };
  const s = map[status] || 'background:#f3f4f6;color:#374151';
  return `<span style="${s};padding:3px 11px;border-radius:999px;font-size:.72rem;font-weight:600;">${status || '—'}</span>`;
}
function moodEmoji(mood) {
  const map = { 'Very Happy':'😄','Happy':'🙂','Neutral':'😐','Sad':'😔','Very Sad':'😢' };
  return (map[mood] || '—') + ' ' + (mood || '—');
}

function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

document.querySelectorAll('.sh-modal-overlay').forEach(el =>
  el.addEventListener('click', e => { if (e.target === el) el.classList.remove('show'); })
);

/* ── Session Modal ── */
function openSessionModal(d) {
  document.getElementById('sessionModalBody').innerHTML =
    row('Counselor',        d.counselor_name) +
    row('Department',       d.counselor_department) +
    row('Date',             fmtDate(d.appointment_date)) +
    row('Time',             fmtTime(d.appointment_time)) +
    row('Priority',         d.priority || '—') +
    row('Status',           badge(d.status)) +
    row('Feedback Rating',  d.feedback_rating || '—') +
    row('Files Attached',   d.file_count > 0 ? d.file_count + ' file(s)' : 'None') +
    row('Note / Message',   d.appointment_note || '—', true);
  openModal('sessionModal');
}

/* ── Concern Modal ── */
function openConcernModal(d) {
  document.getElementById('concernModalBody').innerHTML =
    row('Subject',         d.subject || '—', true) +
    row('Submitted On',    fmtDateTime(d.concern_date)) +
    row('Status',          badge(d.status)) +
    row('Replied By',      d.counselor_name || '—') +
    row('Replied At',      fmtDateTime(d.replied_at)) +
    row('Your Message',    d.concern_message || '—', true) +
    row('Counselor Reply', d.reply || '—', true);
  openModal('concernModal');
}

/* ── Referral Modal ── */
function openReferralModal(d) {
  document.getElementById('referralExportBtn').href = 'sreferral_export.php?id=' + (d.referral_id || '');

  document.getElementById('referralModalBody').innerHTML =
    row('Counselor',         d.counselor_name) +
    row('Department',        d.department || '—') +
    row('Referral Date',     fmtDate(d.referral_date)) +
    row('Created At',        fmtDate(d.created_at)) +
    row('Reason',            d.reason || '—', true) +
    row('Counselor Remarks', d.counselor_remarks || '—', true);
  openModal('referralModal');
}

/* ── Wellness Modal ── */
function openWellnessModal(d) {
  const stressColor = d.stress_level >= 70 ? '#ef4444' : d.stress_level >= 40 ? '#f59e0b' : '#10b981';
  const sqColor     = d.sleep_quality === 'Good' ? '#10b981' : d.sleep_quality === 'Poor' ? '#ef4444' : '#f59e0b';
  document.getElementById('wellnessModalBody').innerHTML =
    row('Submitted On',  fmtDateTime(d.created_at), true) +
    row('Mood',          moodEmoji(d.mood_label)) +
    row('Stress Level',  `<span style="color:${stressColor};font-weight:700;">${d.stress_level}%</span>`) +
    row('Sleep Quality', `<span style="color:${sqColor};font-weight:700;">${d.sleep_quality || '—'}</span>`);
  openModal('wellnessModal');
}

/* ── Announcement Modal ── */
function openAnnouncementModal(d) {
  const respColor = d.response === 'Interested' ? '#10b981' : '#ef4444';
  const respIcon  = d.response === 'Interested' ? '✓' : '✗';
  const attachment = d.file_name
    ? `<a href="${d.file_path}" target="_blank" style="color:#113F67;text-decoration:underline;">${d.file_name}</a>`
    : '—';
  document.getElementById('announcementModalBody').innerHTML =
    row('Title',        d.title || '—', true) +
    row('Posted By',    d.counselor_name || '—') +
    row('Posted On',    fmtDate(d.created_at)) +
    row('My Response',  `<span style="color:${respColor};font-weight:700;">${respIcon} ${d.response || '—'}</span>`) +
    row('Responded At', fmtDateTime(d.responded_at)) +
    row('Attachment',   attachment, true) +
    row('Message',      d.message || '—', true);
  openModal('announcementModal');
}

/* ── SETTINGS / THEME / LOGOUT ── */
function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById('settingsDropdown').classList.toggle('show');
}
function toggleTheme() {
  const html = document.documentElement;
  const newTheme = html.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
  html.setAttribute('data-theme', newTheme);
  localStorage.setItem('theme', newTheme);
}
function logout()       { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()  { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout(){ window.location.href = 'logout.php?role=student'; }

document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});
document.addEventListener('click', e => {
  const menu = document.getElementById('settingsDropdown');
  const btn  = document.querySelector('.sidebar-settingsButton');
  if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target))
    menu.classList.remove('show');
});

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
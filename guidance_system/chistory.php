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

/* ── DATA FETCHES ── */

$sessions = [];
$res = $conn->query(
    "SELECT * FROM v_past_sessions
     WHERE counselor_id='$cid'
     ORDER BY appointment_date DESC, appointment_time DESC"
);
if ($res) while ($row = $res->fetch_assoc()) $sessions[] = $row;

$announcements = [];
$res = $conn->query(
    "SELECT * FROM v_announcements_history
     WHERE counselor_id='$cid'
     ORDER BY created_at DESC"
);
if ($res) while ($row = $res->fetch_assoc()) $announcements[] = $row;

$referrals = [];
$res = $conn->query(
    "SELECT * FROM v_referrals_history
     WHERE counselor_id='$cid'
     ORDER BY referral_date DESC"
);
if ($res) while ($row = $res->fetch_assoc()) $referrals[] = $row;

$concerns = [];
$res = $conn->query(
    "SELECT * FROM v_concerns_handled
     WHERE counselor_id='$cid'
     ORDER BY replied_at DESC"
);
if ($res) while ($row = $res->fetch_assoc()) $concerns[] = $row;

/* ── HELPERS ── */
function statusBadge($status) {
    $map = [
        'Completed' => 'background:#d1fae5;color:#065f46',
        'Rejected'  => 'background:#fee2e2;color:#991b1b',
        'Expired'   => 'background:#fef3c7;color:#92400e',
        'Resolved'  => 'background:#dbeafe;color:#1e40af',
        'Pending'   => 'background:#f3e8ff;color:#6b21a8',
    ];
    $style = $map[$status] ?? 'background:#f3f4f6;color:#374151';
    return "<span style='{$style};padding:3px 11px;border-radius:999px;font-size:.72rem;font-weight:600;white-space:nowrap;'>"
         . htmlspecialchars($status ?? '—') . "</span>";
}

function renderStars($rating) {
    if ($rating === null || $rating === '') {
        return '<span style="color:#9ca3af;font-size:.8rem;">—</span>';
    }
    $r   = (int)$rating;
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $color = $i <= $r ? '#f59e0b' : '#d1d5db';
        $out  .= "<i class='fa fa-star' style='color:{$color};font-size:.78rem;'></i>";
    }
    return $out;
}

function safeDate($d, $fmt = 'M d, Y') {
    if (empty($d) || $d === '0000-00-00') return '—';
    return date($fmt, strtotime($d));
}

function safeTime($t) {
    if (empty($t)) return '—';
    return date('h:i A', strtotime($t));
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | History</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link rel="stylesheet" href="history.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

<style>
/* ── FILTER BAR ── */
.cHistory-filterBar {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  background: #fff;
  padding: 14px;
  border-radius: 14px;
  border: 1px solid #e3e8ef;
}
.cHistory-filterBar input,
.cHistory-filterBar select {
  padding: 10px 12px;
  border-radius: 10px;
  border: 2px solid #e3e8ef;
  outline: none;
  font-size: .875rem;
  background: var(--card-bg, #fff);
  color: var(--text, #111);
}
.cHistory-filterBar input:focus,
.cHistory-filterBar select:focus {
  border-color: #113F67;
  box-shadow: 0 0 0 3px rgba(17,63,103,.15);
}
.cHistory-filterBar .filter-search { flex: 1; min-width: 160px; }

.btn-apply,
.btn-clear {
  padding: 10px 18px;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  font-weight: 600;
  font-size: .875rem;
  white-space: nowrap;
}
.btn-apply { background: #113F67; color: #fff; }
.btn-apply:hover { background: #0d3054; }
.btn-clear { background: #f3f4f6; color: #374151; }
.btn-clear:hover { background: #e5e7eb; }

/* ── TABLE ADDITIONS ── */
.td-sub {
  font-size: .78rem;
  color: #6b7280;
  margin-top: 2px;
}
.td-truncate {
  max-width: 200px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.btn-view {
  background: #f0f5ff;
  color: #113F67;
  border: none;
  padding: 5px 13px;
  border-radius: 8px;
  font-size: .8rem;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
}
.btn-view:hover { background: #dce8fb; }

.reach-interested   { color: #10b981; font-weight: 700; }
.reach-uninterested { color: #ef4444; font-weight: 700; }

/* ── DETAIL MODAL ── */
.ch-modal-overlay {
  display: none;
  position: fixed;
  inset: 0;
  background: rgba(17, 63, 103, 0.25);
  backdrop-filter: blur(6px);
  z-index: 99999;
  align-items: center;
  justify-content: center;
}
.ch-modal-overlay.show { display: flex; }

.ch-modal {
  background: var(--card);
  color: var(--text, #111);
  border-radius: 16px;
  padding: 2rem;
  width: min(520px, 94vw);
  max-height: 88vh;
  overflow-y: auto;
  position: relative;
  box-shadow: 0 24px 64px rgba(0,0,0,.22);
}
.ch-modal h3 {
  margin: 0 0 1.5rem;
  font-size: 1.05rem;
  color: #113F67;
}
.ch-modal-close {
  position: absolute;
  top: 1.1rem; right: 1.1rem;
  background: none;
  border: none;
  font-size: 1.1rem;
  cursor: pointer;
  color: #6b7280;
  line-height: 1;
}
.ch-modal-close:hover { color: #111; }

.ch-detail-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .75rem 1.4rem;
}
.ch-detail-row { display: flex; flex-direction: column; gap: .18rem; }
.ch-detail-row.full { grid-column: 1 / -1; }
.ch-detail-label {
  font-size: .7rem;
  text-transform: uppercase;
  letter-spacing: .5px;
  color: #9ca3af;
  font-weight: 700;
}
.ch-detail-value { font-size: .9rem; line-height: 1.5; word-break: break-word; }

/* dark mode */
[data-theme="dark"] .cHistory-filterBar { background: #1e293b; border-color: #334155; }
[data-theme="dark"] .cHistory-filterBar input,
[data-theme="dark"] .cHistory-filterBar select { background: #0f172a; color: #f1f5f9; border-color: #334155; }
[data-theme="dark"] .ch-modal  { background: #1e293b; color: #f1f5f9; }
[data-theme="dark"] .btn-view  { background: #1e3a5f; color: #93c5fd; }
[data-theme="dark"] .btn-view:hover { background: #1d4ed8; color: #fff; }
[data-theme="dark"] .btn-clear { background: #334155; color: #cbd5e1; }
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
        <a href="chistory.php" class="active"><i class="fa fa-clock"></i> History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>
  </div>

  <nav class="sidebar-menu">
    <a href="counselor.php"><i class="fa fa-gauge"></i> Dashboard</a>

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
    <h2>History</h2>
  </div>
  <div class="topbar-right">
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
<main class="cHistory-main">

  <!-- TABS -->
  <div class="cHistory-topbarRow">
    <div class="cHistory-tabs">
      <button class="active" onclick="switchTab(event,'sessions')">Past Sessions</button>
      <button onclick="switchTab(event,'announcements')">Announcements</button>
      <button onclick="switchTab(event,'referrals')">Referrals</button>
      <button onclick="switchTab(event,'concerns')">Concerns Handled</button>
    </div>
  </div>

  <!-- FILTER BAR -->
  <div class="cHistory-filterBar">
    <select id="filterYear">
      <option value="all">All Year Levels</option>
      <option>1st Year</option>
      <option>2nd Year</option>
      <option>3rd Year</option>
      <option>4th Year</option>
    </select>

    <select id="filterProgram">
      <option value="all">All Programs</option>
      <option>AB Psychology</option>
      <option>BSBA</option>
      <option>BSA</option>
      <option>BS Entrep</option>
      <option>BEEd</option>
      <option>BSEd</option>
      <option>BSHM</option>
      <option>BSIT</option>
      <option>BSCS</option>
      <option>BSN</option>
      <option>BSECE</option>
    </select>

    <select id="filterStatus">
      <option value="all">All Statuses</option>
      <option>Completed</option>
      <option>Rejected</option>
      <option>Resolved</option>
      <option>Pending</option>
    </select>

    <input type="date" id="filterDate" title="Filter by date">

    <input type="text" id="filterSearch" class="filter-search"
           placeholder="Search by name, subject…"
           oninput="applyFilter()">

    <button class="btn-apply" onclick="applyFilter()">
      <i class="fa fa-filter"></i> Apply
    </button>
    <button class="btn-clear" onclick="clearFilter()">
      <i class="fa fa-xmark"></i> Clear
    </button>
  </div>

  <!-- ══ TAB: PAST SESSIONS ══ -->
  <div id="sessions" class="cHistory-tabContent">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Date</th>
          <th>Time</th>
          <th>Type</th>
          <th>Status</th>
          <th>Rating</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($sessions)): ?>
          <tr><td colspan="7" class="empty">No past sessions found</td></tr>
        <?php else: foreach ($sessions as $s): ?>
          <tr
            data-year="<?= htmlspecialchars(strtolower($s['year_level'] ?? '')) ?>"
            data-program="<?= htmlspecialchars(strtoupper($s['course'] ?? '')) ?>"
            data-status="<?= htmlspecialchars($s['status'] ?? '') ?>"
            data-date="<?= htmlspecialchars($s['appointment_date'] ?? '') ?>"
            data-search="<?= htmlspecialchars(strtolower($s['student_name'] ?? '')) ?>"
          >
            <td>
              <div><?= htmlspecialchars($s['student_name'] ?? '—') ?></div>
              <div class="td-sub">
                <?= htmlspecialchars($s['course'] ?? '') ?>
                &middot; <?= htmlspecialchars($s['year_level'] ?? '') ?>
              </div>
            </td>
            <td><?= safeDate($s['appointment_date']) ?></td>
            <td><?= safeTime($s['appointment_time']) ?></td>
            <td><?= htmlspecialchars(ucfirst($s['priority'] ?? '—')) ?></td>
            <td><?= statusBadge($s['status']) ?></td>
            <td><?= renderStars($s['feedback_rating']) ?></td>
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

  <!-- ══ TAB: ANNOUNCEMENTS ══ -->
  <div id="announcements" class="cHistory-tabContent hidden">
    <table>
      <thead>
        <tr>
          <th>Title</th>
          <th>Posted</th>
          <th>Total Responses</th>
          <th>Interested</th>
          <th>Not Interested</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($announcements)): ?>
          <tr><td colspan="6" class="empty">No past announcements found</td></tr>
        <?php else: foreach ($announcements as $a): ?>
          <tr
            data-year="all"
            data-program="all"
            data-status="all"
            data-date="<?= htmlspecialchars(date('Y-m-d', strtotime($a['created_at'] ?? 'now'))) ?>"
            data-search="<?= htmlspecialchars(strtolower($a['title'] ?? '')) ?>"
          >
            <td class="td-truncate" title="<?= htmlspecialchars($a['title'] ?? '') ?>">
              <?= htmlspecialchars($a['title'] ?? '—') ?>
            </td>
            <td><?= safeDate($a['created_at']) ?></td>
            <td><?= (int)($a['total_responses'] ?? 0) ?></td>
            <td><span class="reach-interested"><?= (int)($a['interested_count'] ?? 0) ?> ✓</span></td>
            <td><span class="reach-uninterested"><?= (int)($a['not_interested_count'] ?? 0) ?> ✗</span></td>
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

  <!-- ══ TAB: REFERRALS ══ -->
  <div id="referrals" class="cHistory-tabContent hidden">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Referral Date</th>
          <th>Reason</th>
          <th>Remarks</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($referrals)): ?>
          <tr><td colspan="5" class="empty">No past referrals found</td></tr>
        <?php else: foreach ($referrals as $r): ?>
          <tr
            data-year="<?= htmlspecialchars(strtolower($r['year_level'] ?? '')) ?>"
            data-program="<?= htmlspecialchars(strtoupper($r['course'] ?? '')) ?>"
            data-status="all"
            data-date="<?= htmlspecialchars($r['referral_date'] ?? '') ?>"
            data-search="<?= htmlspecialchars(strtolower($r['student_name'] ?? '')) ?>"
          >
            <td>
              <div><?= htmlspecialchars($r['student_name'] ?? '—') ?></div>
              <div class="td-sub">
                <?= htmlspecialchars($r['course'] ?? '') ?>
                &middot; <?= htmlspecialchars($r['year_level'] ?? '') ?>
              </div>
            </td>
            <td><?= safeDate($r['referral_date']) ?></td>
            <td class="td-truncate" title="<?= htmlspecialchars($r['reason'] ?? '') ?>">
              <?= htmlspecialchars($r['reason'] ?? '—') ?>
            </td>
            <td class="td-truncate" title="<?= htmlspecialchars($r['counselor_remarks'] ?? '') ?>">
              <?= !empty($r['counselor_remarks'])
                    ? htmlspecialchars($r['counselor_remarks'])
                    : '<span style="color:#9ca3af;">—</span>' ?>
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

  <!-- ══ TAB: CONCERNS HANDLED ══ -->
  <div id="concerns" class="cHistory-tabContent hidden">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Subject</th>
          <th>Concern Date</th>
          <th>Replied At</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($concerns)): ?>
          <tr><td colspan="6" class="empty">No concerns handled yet</td></tr>
        <?php else: foreach ($concerns as $c): ?>
          <tr
            data-year="<?= htmlspecialchars(strtolower($c['year_level'] ?? '')) ?>"
            data-program="<?= htmlspecialchars(strtoupper($c['course'] ?? '')) ?>"
            data-status="<?= htmlspecialchars($c['concern_status'] ?? '') ?>"
            data-date="<?= htmlspecialchars(date('Y-m-d', strtotime($c['concern_date'] ?? 'now'))) ?>"
            data-search="<?= htmlspecialchars(strtolower(($c['student_name'] ?? '') . ' ' . ($c['subject'] ?? ''))) ?>"
          >
            <td>
              <div><?= htmlspecialchars($c['student_name'] ?? '—') ?></div>
              <div class="td-sub">
                <?= htmlspecialchars($c['course'] ?? '') ?>
                &middot; <?= htmlspecialchars($c['year_level'] ?? '') ?>
              </div>
            </td>
            <td class="td-truncate" title="<?= htmlspecialchars($c['subject'] ?? '') ?>">
              <?= htmlspecialchars($c['subject'] ?? '—') ?>
            </td>
            <td><?= safeDate($c['concern_date']) ?></td>
            <td><?= !empty($c['replied_at']) ? safeDate($c['replied_at'], 'M d, Y g:i A') : '—' ?></td>
            <td><?= statusBadge($c['concern_status'] ?? 'Pending') ?></td>
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

</main>

<!-- ══ MODALS ══ -->

<!-- Session Modal -->
<div class="ch-modal-overlay" id="sessionModal">
  <div class="ch-modal">
    <button class="ch-modal-close" onclick="closeModal('sessionModal')">
      <i class="fa fa-xmark"></i>
    </button>
    <h3><i class="fa fa-calendar-check" style="margin-right:.4rem;"></i>Session Details</h3>
    <div class="ch-detail-grid" id="sessionModalBody"></div>
  </div>
</div>

<!-- Announcement Modal -->
<div class="ch-modal-overlay" id="announcementModal">
  <div class="ch-modal">
    <button class="ch-modal-close" onclick="closeModal('announcementModal')">
      <i class="fa fa-xmark"></i>
    </button>
    <h3><i class="fa fa-bullhorn" style="margin-right:.4rem;"></i>Announcement Details</h3>
    <div class="ch-detail-grid" id="announcementModalBody"></div>
  </div>
</div>

<!-- Referral Modal -->
<div class="ch-modal-overlay" id="referralModal">
  <div class="ch-modal">
    <button class="ch-modal-close" onclick="closeModal('referralModal')">
      <i class="fa fa-xmark"></i>
    </button>
    <h3><i class="fa fa-route" style="margin-right:.4rem;"></i>Referral Details</h3>
    <div class="ch-detail-grid" id="referralModalBody"></div>
  </div>
</div>

<!-- Concern Modal -->
<div class="ch-modal-overlay" id="concernModal">
  <div class="ch-modal">
    <button class="ch-modal-close" onclick="closeModal('concernModal')">
      <i class="fa fa-xmark"></i>
    </button>
    <h3><i class="fa fa-triangle-exclamation" style="margin-right:.4rem;"></i>Concern Details</h3>
    <div class="ch-detail-grid" id="concernModalBody"></div>
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
/* ═══════════════ TAB SWITCHING ═══════════════ */
let currentTab = 'sessions';

function switchTab(event, tabId) {
  document.querySelectorAll('.cHistory-tabContent')
    .forEach(t => t.classList.add('hidden'));
  document.getElementById(tabId).classList.remove('hidden');

  document.querySelectorAll('.cHistory-tabs button')
    .forEach(b => b.classList.remove('active'));
  event.target.classList.add('active');

  currentTab = tabId;

  // Status filter is only meaningful for sessions & concerns
  const showStatus = tabId === 'sessions' || tabId === 'concerns';
  document.getElementById('filterStatus').style.display = showStatus ? '' : 'none';

  clearFilter(); // reset on tab change
}

/* ═══════════════ FILTER ═══════════════ */
function applyFilter() {
  const year    = document.getElementById('filterYear').value.toLowerCase().trim();
  const program = document.getElementById('filterProgram').value.toUpperCase().trim();
  const status  = document.getElementById('filterStatus').value.toLowerCase().trim();
  const date    = document.getElementById('filterDate').value;
  const search  = document.getElementById('filterSearch').value.toLowerCase().trim();

  document.querySelectorAll(`#${currentTab} tbody tr`).forEach(row => {
    const rYear    = (row.dataset.year    || '').toLowerCase();
    const rProgram = (row.dataset.program || '').toUpperCase();
    const rStatus  = (row.dataset.status  || '').toLowerCase();
    const rDate    = (row.dataset.date    || '');
    const rSearch  = (row.dataset.search  || '').toLowerCase();

    // year: match "1st year" contains "1st"
    const matchYear    = year === 'all'    || rYear.includes(year.replace(' year','').trim());
    const matchProgram = program === 'ALL' || rProgram === program;
    const matchStatus  = status === 'all'  || rStatus === status;
    const matchDate    = !date             || rDate === date;
    const matchSearch  = !search           || rSearch.includes(search);

    row.style.display = (matchYear && matchProgram && matchStatus && matchDate && matchSearch)
      ? '' : 'none';
  });
}

function clearFilter() {
  document.getElementById('filterYear').value    = 'all';
  document.getElementById('filterProgram').value = 'all';
  document.getElementById('filterStatus').value  = 'all';
  document.getElementById('filterDate').value    = '';
  document.getElementById('filterSearch').value  = '';
  document.querySelectorAll(`#${currentTab} tbody tr`)
    .forEach(r => r.style.display = '');
}

/* ═══════════════ MODAL HELPERS ═══════════════ */
function row(label, value, full = false) {
  return `<div class="ch-detail-row${full ? ' full' : ''}">
    <span class="ch-detail-label">${label}</span>
    <span class="ch-detail-value">${value ?? '—'}</span>
  </div>`;
}

function fmtDate(d) {
  if (!d || d === '0000-00-00') return '—';
  return new Date(d + 'T00:00:00').toLocaleDateString('en-US',
    { year:'numeric', month:'short', day:'numeric' });
}

function fmtDateTime(dt) {
  if (!dt) return '—';
  return new Date(dt).toLocaleString('en-US',
    { year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit' });
}

function fmtTime(t) {
  if (!t) return '—';
  const [h, m] = t.split(':');
  const hr = parseInt(h);
  return `${hr % 12 || 12}:${m} ${hr >= 12 ? 'PM' : 'AM'}`;
}

function stars(rating) {
  if (!rating) return '—';
  let out = '';
  for (let i = 1; i <= 5; i++)
    out += `<i class="fa fa-star" style="color:${i <= rating ? '#f59e0b' : '#d1d5db'};font-size:.82rem;"></i>`;
  return out;
}

function badge(status) {
  const map = {
    Completed : 'background:#d1fae5;color:#065f46',
    Rejected  : 'background:#fee2e2;color:#991b1b',
    Expired   : 'background:#fef3c7;color:#92400e',
    Resolved  : 'background:#dbeafe;color:#1e40af',
    Pending   : 'background:#f3e8ff;color:#6b21a8',
  };
  const s = map[status] || 'background:#f3f4f6;color:#374151';
  return `<span style="${s};padding:3px 11px;border-radius:999px;font-size:.72rem;font-weight:600;">
    ${status || '—'}</span>`;
}

function openModal(id)  { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }

// Close on backdrop click
document.querySelectorAll('.ch-modal-overlay').forEach(el =>
  el.addEventListener('click', e => { if (e.target === el) el.classList.remove('show'); })
);

/* ── Session Modal ── */
function openSessionModal(d) {
  document.getElementById('sessionModalBody').innerHTML =
    row('Student',          d.student_name) +
    row('Course & Year',    `${d.course || ''} &middot; ${d.year_level || ''}`) +
    row('Date',             fmtDate(d.appointment_date)) +
    row('Time',             fmtTime(d.appointment_time)) +
    row('Session Type',     d.priority ? d.priority.charAt(0).toUpperCase() + d.priority.slice(1) : '—') +
    row('Status',           badge(d.status)) +
    row('Feedback Rating',  stars(d.feedback_rating)) +
    row('Message / Note',   d.appointment_message || '—', true);
  openModal('sessionModal');
}

/* ── Announcement Modal ── */
function openAnnouncementModal(d) {
  const attachment = d.file_name
    ? `<a href="${d.file_path}" target="_blank" style="color:#113F67;text-decoration:underline;">${d.file_name}</a>`
    : '—';
  document.getElementById('announcementModalBody').innerHTML =
    row('Title',           d.title, true) +
    row('Posted On',       fmtDate(d.created_at)) +
    row('Total Responses', d.total_responses) +
    row('Interested',      `<span style="color:#10b981;font-weight:700;">${d.interested_count} ✓</span>`) +
    row('Not Interested',  `<span style="color:#ef4444;font-weight:700;">${d.not_interested_count} ✗</span>`) +
    row('Attachment',      attachment, true) +
    row('Message',         d.message || '—', true);
  openModal('announcementModal');
}

/* ── Referral Modal ── */
function openReferralModal(d) {
  document.getElementById('referralModalBody').innerHTML =
    row('Student',           d.student_name) +
    row('Course & Year',     `${d.course || ''} &middot; ${d.year_level || ''}`) +
    row('Referral Date',     fmtDate(d.referral_date)) +
    row('Created At',        fmtDate(d.created_at)) +
    row('Reason',            d.reason || '—', true) +
    row('Counselor Remarks', d.counselor_remarks || '—', true);
  openModal('referralModal');
}

/* ── Concern Modal ── */
function openConcernModal(d) {
  document.getElementById('concernModalBody').innerHTML =
    row('Student',         d.student_name) +
    row('Course & Year',   `${d.course || ''} &middot; ${d.year_level || ''}`) +
    row('Subject',         d.subject || '—', true) +
    row('Concern Date',    fmtDate(d.concern_date)) +
    row('Replied At',      fmtDateTime(d.replied_at)) +
    row('Status',          badge(d.concern_status)) +
    row('Counselor Reply', d.reply || '—', true);
  openModal('concernModal');
}

/* ═══════════════ SETTINGS / THEME / LOGOUT ═══════════════ */
function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById('settingsDropdown').classList.toggle('show');
}

function toggleTheme() {
  const html = document.documentElement;
  html.setAttribute('data-theme',
    html.getAttribute('data-theme') === 'light' ? 'dark' : 'light');
}

function logout()       { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()  { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout(){ window.location.href = 'logout.php?role=counselor'; }

document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});

document.addEventListener('click', e => {
  const menu = document.getElementById('settingsDropdown');
  const btn  = document.querySelector('.sidebar-settingsButton');
  if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target))
    menu.classList.remove('show');
});
</script>

</body>
</html>
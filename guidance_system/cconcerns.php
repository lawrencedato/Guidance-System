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
$cid  = (int)$_SESSION['user_id'];

/* ── Helper: check if counselor has access to a concern ── */
function counselorHasAccess($conn, $concernId, $cid) {
    $res = $conn->query("
        SELECT c.concern_id FROM concerns c
        WHERE c.concern_id = $concernId
          AND (
              c.status = 'Pending'
              OR (
                  SELECT cr2.counselor_id
                  FROM concern_replies cr2
                  WHERE cr2.concern_id = $concernId AND cr2.sender_type = 'counselor'
                  ORDER BY cr2.replied_at ASC
                  LIMIT 1
              ) = $cid
          )
        LIMIT 1
    ");
    return $res && $res->fetch_assoc();
}

/* ── AJAX: counselor sends a reply ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'counselor_reply') {
    header('Content-Type: application/json');
    $concernId = (int)($_POST['concern_id'] ?? 0);
    $message   = $conn->real_escape_string(trim($_POST['message'] ?? ''));

    if (!$concernId || !$message) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
    }

    if (!counselorHasAccess($conn, $concernId, $cid)) {
        echo json_encode(['success' => false, 'message' => 'Access denied.']); exit;
    }

    $ok = $conn->query("
        INSERT INTO concern_replies (concern_id, counselor_id, reply, replied_at, sender_type)
        VALUES ($concernId, $cid, '$message', NOW(), 'counselor')
    ");

    if ($ok) $conn->query("
        UPDATE concerns SET status = 'Reviewed'
        WHERE concern_id = $concernId
    ");

    echo json_encode($ok
        ? ['success' => true]
        : ['success' => false, 'message' => $conn->error]);
    exit;
}

/* ── AJAX: mark concern resolved ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'mark_resolved') {
    header('Content-Type: application/json');
    $concernId = (int)($_POST['concern_id'] ?? 0);

    $owner = $conn->query("
        SELECT cr2.counselor_id FROM concern_replies cr2
        WHERE cr2.concern_id = $concernId AND cr2.sender_type = 'counselor'
        ORDER BY cr2.replied_at ASC LIMIT 1
    ")->fetch_assoc();

    if (!$owner || (int)$owner['counselor_id'] !== $cid) {
        echo json_encode(['success' => false, 'message' => 'Access denied.']); exit;
    }

    $ok = $conn->query("UPDATE concerns SET status='Resolved' WHERE concern_id=$concernId");
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

/* ── AJAX: fetch full thread ── */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_thread'])) {
    header('Content-Type: application/json');
    $concernId = (int)$_GET['fetch_thread'];

    if (!counselorHasAccess($conn, $concernId, $cid)) {
        echo json_encode(['success' => false]); exit;
    }

    $concern = $conn->query("
        SELECT c.concern_id, c.subject, c.message, c.status, c.created_at,
               s.first_name, s.last_name, s.student_id
        FROM concerns c
        JOIN students s ON s.student_id = c.student_id
        WHERE c.concern_id = $concernId
        LIMIT 1
    ")->fetch_assoc();

    if (!$concern) { echo json_encode(['success' => false]); exit; }

    $res = $conn->query("
        SELECT
            cr.reply        AS message,
            cr.replied_at   AS created_at,
            cr.sender_type  AS sender,
            CASE
                WHEN cr.sender_type = 'counselor'
                    THEN COALESCE(CONCAT(c.first_name,' ',c.last_name), 'Counselor')
                ELSE CONCAT(s.first_name,' ',s.last_name)
            END AS sender_name
        FROM concern_replies cr
        LEFT JOIN counselors c ON c.counselor_id = cr.counselor_id
        LEFT JOIN students   s ON s.student_id   = cr.student_id
        WHERE cr.concern_id = $concernId
        ORDER BY cr.replied_at ASC
    ");

    $messages = [];
    while ($row = $res->fetch_assoc()) $messages[] = $row;

    echo json_encode([
        'success'      => true,
        'subject'      => $concern['subject'],
        'message'      => $concern['message'],
        'status'       => $concern['status'],
        'sent_at'      => $concern['created_at'],
        'student_name' => $concern['first_name'] . ' ' . $concern['last_name'],
        'messages'     => $messages,
    ]);
    exit;
}

/* ── Page data ── */
$counselorRes = $conn->query("SELECT * FROM counselors WHERE counselor_id='$cid' LIMIT 1");
$counselor    = $counselorRes->fetch_assoc();
$fullName     = htmlspecialchars(($counselor['first_name'] ?? '') . ' ' . ($counselor['last_name'] ?? ''));
$email        = htmlspecialchars($counselor['email'] ?? '');
$profileImg   = !empty($counselor['profile_image'])
    ? htmlspecialchars($counselor['profile_image'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';

$pendingCount = (int)$conn->query(
    "SELECT COUNT(*) c FROM appointments WHERE counselor_id='$cid' AND status='Pending'"
)->fetch_assoc()['c'];

/* ── Load concern list:
       Show a concern if it is Pending (any counselor can claim it)
       OR if this counselor was the first to reply (they own it). ── */
$concerns = [];
$res = $conn->query("
    SELECT c.concern_id, c.subject, c.status, c.created_at,
           s.first_name, s.last_name,
           (SELECT COUNT(*) FROM concern_replies cr
            WHERE cr.concern_id = c.concern_id AND cr.sender_type = 'student') AS student_reply_count
    FROM concerns c
    JOIN students s ON s.student_id = c.student_id
    WHERE c.status = 'Pending'
       OR (
           SELECT cr2.counselor_id
           FROM concern_replies cr2
           WHERE cr2.concern_id = c.concern_id AND cr2.sender_type = 'counselor'
           ORDER BY cr2.replied_at ASC
           LIMIT 1
       ) = $cid
    ORDER BY c.created_at DESC
");
while ($row = $res->fetch_assoc()) $concerns[] = $row;
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
<style>
.cConcerns-main {
  display: flex;
  gap: 0;
  height: calc(100vh - 70px);
  overflow: hidden;
}

/* ══ LEFT PANEL ══ */
.cc-left {
  width: 320px;
  min-width: 260px;
  display: flex;
  flex-direction: column;
  border-right: 1px solid var(--border, #e2e8f0);
  background: var(--sidebar-bg, #fff);
  overflow: hidden;
}
.cc-left-header {
  padding: 18px 16px 12px;
  border-bottom: 1px solid var(--border, #e2e8f0);
  flex-shrink: 0;
}
.cc-left-header h3 {
  font-size: 14px;
  font-weight: 700;
  margin: 0 0 10px;
  color: var(--text, #1a202c);
  display: flex;
  align-items: center;
  gap: 7px;
}

.cc-search {
  position: relative;
}
.cc-search input {
  width: 100%;
  box-sizing: border-box;
  padding: 8px 12px 8px 34px;
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 8px;
  font-size: 13px;
  background: var(--input-bg, #f8fafc);
  color: var(--text, #1a202c);
  outline: none;
  transition: border-color .2s;
}
.cc-search input:focus { border-color: #113f67; }
.cc-search i {
  position: absolute;
  left: 10px; top: 50%;
  transform: translateY(-50%);
  color: var(--text-muted, #718096);
  font-size: 12px;
}

.cc-concern-list {
  flex: 1;
  overflow-y: auto;
  padding: 6px 0;
}
.cc-concern-item {
  padding: 12px 16px;
  cursor: pointer;
  border-left: 3px solid transparent;
  transition: background .15s, border-color .15s;
  position: relative;
}
.cc-concern-item:hover  { background: var(--hover-bg, #f7fafc); }
.cc-concern-item.active { background: #eef4fb; border-left-color: #113f67; }
.ci-name {
  font-size: 13px;
  font-weight: 700;
  color: var(--text, #1a202c);
  margin-bottom: 2px;
}
.ci-subject {
  font-size: 12px;
  color: var(--text-muted, #718096);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  margin-bottom: 4px;
}
.ci-meta {
  font-size: 11px;
  color: var(--text-muted, #718096);
  display: flex;
  align-items: center;
  gap: 6px;
}
.cc-badge {
  display: inline-block;
  padding: 2px 7px;
  border-radius: 20px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .4px;
}
.cc-badge.pending  { background: #fff3cd; color: #856404; }
.cc-badge.reviewed { background: #dbeafe; color: #1d4ed8; }
.cc-badge.resolved { background: #d1fae5; color: #065f46; }
.cc-reply-dot {
  width: 8px; height: 8px;
  background: #f59e0b;
  border-radius: 50%;
  position: absolute;
  top: 13px; right: 14px;
}
.cc-empty-list {
  padding: 30px 16px;
  text-align: center;
  color: var(--text-muted, #718096);
  font-size: 13px;
}
.cc-empty-list i { font-size: 34px; opacity: .25; display: block; margin-bottom: 10px; }

.cc-filter-tabs {
  display: flex;
  gap: 4px;
  padding: 8px 16px;
  border-bottom: 1px solid var(--border, #e2e8f0);
  flex-shrink: 0;
}
.cc-tab {
  flex: 1;
  padding: 5px 0;
  border: none;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  cursor: pointer;
  background: transparent;
  color: var(--text-muted, #718096);
  transition: background .15s, color .15s;
}
.cc-tab.active { background: #113f67; color: #fff; }
.cc-tab:hover:not(.active) { background: var(--hover-bg, #f7fafc); }

/* ══ RIGHT PANEL ══ */
.cc-right {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: var(--main-bg, #f7fafc);
}
.cc-placeholder {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--text-muted, #718096);
  gap: 10px;
  padding: 40px;
  text-align: center;
}
.cc-placeholder i { font-size: 52px; opacity: .2; }
.cc-placeholder p { font-size: 14px; margin: 0; }

.cc-thread {
  flex: 1;
  display: none;
  flex-direction: column;
  overflow: hidden;
}
.cc-thread.visible { display: flex; }

.cc-thread-header {
  padding: 14px 20px;
  background: var(--sidebar-bg, #fff);
  border-bottom: 1px solid var(--border, #e2e8f0);
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}
.cc-thread-header-left h4 {
  font-size: 14px;
  font-weight: 700;
  margin: 0 0 2px;
  color: var(--text, #1a202c);
}
.cc-thread-header-left p {
  font-size: 12px;
  color: var(--text-muted, #718096);
  margin: 0;
}
.cc-thread-header-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}
.cc-resolve-btn {
  padding: 6px 14px;
  border: none;
  border-radius: 8px;
  background: #d1fae5;
  color: #065f46;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: background .2s;
  display: flex;
  align-items: center;
  gap: 5px;
}
.cc-resolve-btn:hover { background: #a7f3d0; }
.cc-resolve-btn:disabled { opacity: .5; cursor: not-allowed; }

.cc-messages {
  flex: 1;
  overflow-y: auto;
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.cc-msg {
  display: flex;
  flex-direction: column;
  max-width: 72%;
}
.cc-msg.me   { align-self: flex-end;   align-items: flex-end; }
.cc-msg.them { align-self: flex-start; align-items: flex-start; }
.cc-msg-bubble {
  padding: 10px 14px;
  border-radius: 14px;
  font-size: 13.5px;
  line-height: 1.55;
  word-break: break-word;
  white-space: pre-wrap;
}
.cc-msg.me   .cc-msg-bubble {
  background: #113f67;
  color: #fff;
  border-bottom-right-radius: 4px;
}
.cc-msg.them .cc-msg-bubble {
  background: var(--sidebar-bg, #fff);
  color: var(--text, #1a202c);
  border: 1px solid var(--border, #e2e8f0);
  border-bottom-left-radius: 4px;
}
.cc-msg-meta {
  font-size: 11px;
  color: var(--text-muted, #718096);
  margin-top: 3px;
  padding: 0 4px;
}

/* Pending claim notice */
.cc-claim-notice {
  display: none;
  margin: 0 16px 10px;
  padding: 9px 14px;
  background: #fffbeb; border: 1px solid #fcd34d;
  border-radius: 8px; font-size: 12px; color: #92400e;
  align-items: center; gap: 8px;
}
.cc-claim-notice.show { display: flex; }

.cc-reply-box {
  padding: 12px 16px;
  background: var(--sidebar-bg, #fff);
  border-top: 1px solid var(--border, #e2e8f0);
  display: flex;
  gap: 10px;
  align-items: flex-end;
  flex-shrink: 0;
}
.cc-reply-box textarea {
  flex: 1;
  resize: none;
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 10px;
  padding: 10px 13px;
  font-size: 13.5px;
  font-family: inherit;
  background: var(--input-bg, #f8fafc);
  color: var(--text, #1a202c);
  line-height: 1.5;
  min-height: 44px;
  max-height: 130px;
  outline: none;
  transition: border-color .2s;
}
.cc-reply-box textarea:focus { border-color: #113f67; }
.cc-send-btn {
  width: 44px; height: 44px;
  border: none; border-radius: 10px;
  background: #113f67; color: #fff;
  font-size: 16px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  transition: background .2s, opacity .2s;
}
.cc-send-btn:hover    { background: #0d3050; }
.cc-send-btn:disabled { opacity: .5; cursor: not-allowed; }

.cc-concern-list::-webkit-scrollbar,
.cc-messages::-webkit-scrollbar { width: 4px; }
.cc-concern-list::-webkit-scrollbar-thumb,
.cc-messages::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 4px; }

[data-theme="dark"] .cc-left,
[data-theme="dark"] .cc-thread-header,
[data-theme="dark"] .cc-reply-box { background: #1e2533; }
[data-theme="dark"] .cc-concern-item.active { background: #1c2f45; }
[data-theme="dark"] .cc-msg.them .cc-msg-bubble { background: #1e2533; border-color: #2d3748; }
[data-theme="dark"] .cc-reply-box textarea { background: #161d2b; border-color: #2d3748; color: #e2e8f0; }
[data-theme="dark"] .cc-search input { background: #161d2b; border-color: #2d3748; color: #e2e8f0; }
</style>
</head>
<body class="body">

<!-- ══════════════════ SIDEBAR ══════════════════ -->
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
    <a href="cavailability.php"><i class="fa fa-clock"></i> My Availability</a>
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

<header class="topbar">
  <div class="topbar-left">
    <h2>Student Concerns</h2>
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

<main class="cConcerns-main">

  <div class="cc-left">
    <div class="cc-left-header">
      <h3><i class="fa fa-triangle-exclamation" style="color:#113f67;"></i> Concerns</h3>
      <div class="cc-search">
        <i class="fa fa-magnifying-glass"></i>
        <input type="text" id="ccSearch" placeholder="Search student or subject…" oninput="filterList()">
      </div>
    </div>

    <div class="cc-filter-tabs">
      <button class="cc-tab active" data-filter="all"      onclick="setTab(this)">All</button>
      <button class="cc-tab"        data-filter="Pending"  onclick="setTab(this)">Pending</button>
      <button class="cc-tab"        data-filter="Reviewed" onclick="setTab(this)">Reviewed</button>
      <button class="cc-tab"        data-filter="Resolved" onclick="setTab(this)">Resolved</button>
    </div>

    <div class="cc-concern-list" id="ccConcernList">
      <?php if (empty($concerns)): ?>
        <div class="cc-empty-list">
          <i class="fa fa-inbox"></i>
          No student concerns yet.
        </div>
      <?php else: foreach ($concerns as $c): ?>
        <div class="cc-concern-item"
             id="cci-<?= $c['concern_id'] ?>"
             data-status="<?= htmlspecialchars($c['status']) ?>"
             data-name="<?= htmlspecialchars(strtolower($c['first_name'] . ' ' . $c['last_name'])) ?>"
             data-subject="<?= htmlspecialchars(strtolower($c['subject'])) ?>"
             onclick="openThread(<?= $c['concern_id'] ?>, <?= htmlspecialchars(json_encode($c['first_name'] . ' ' . $c['last_name'])) ?>, <?= htmlspecialchars(json_encode($c['subject'])) ?>)">
          <div class="ci-name"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></div>
          <div class="ci-subject"><?= htmlspecialchars($c['subject']) ?></div>
          <div class="ci-meta">
            <span class="cc-badge <?= strtolower($c['status']) ?>">
              <?= htmlspecialchars($c['status']) ?>
            </span>
            <span><?= date('M d, Y', strtotime($c['created_at'])) ?></span>
          </div>
          <?php if ($c['student_reply_count'] > 0): ?>
            <div class="cc-reply-dot" title="Student replied"></div>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div class="cc-right">

    <div class="cc-placeholder" id="ccPlaceholder">
      <i class="fa fa-comments"></i>
      <p>Select a concern from the list<br>to view and respond to the conversation.</p>
    </div>

    <div class="cc-thread" id="ccThread">

      <div class="cc-thread-header">
        <div class="cc-thread-header-left">
          <h4 id="threadStudentName">—</h4>
          <p id="threadSubject">—</p>
        </div>
        <div class="cc-thread-header-right">
          <span class="cc-badge pending" id="threadStatus">Pending</span>
          <button class="cc-resolve-btn" id="resolveBtn" onclick="markResolved()">
            <i class="fa fa-circle-check"></i> Mark Resolved
          </button>
        </div>
      </div>

      <div class="cc-messages" id="threadMessages"></div>

      <!-- Notice shown when concern is still Pending (unclaimed) -->
      <div class="cc-claim-notice" id="claimNotice">
        <i class="fa fa-circle-info"></i>
        <span>This concern is unclaimed. Sending a reply will assign it to you.</span>
      </div>

      <div class="cc-reply-box">
        <textarea
          id="replyText"
          rows="1"
          placeholder="Type your reply… (Enter to send, Shift+Enter for new line)"
          oninput="autoResize(this)"
          onkeydown="handleReplyKey(event)"
        ></textarea>
        <button class="cc-send-btn" id="sendBtn" onclick="sendReply()" title="Send reply">
          <i class="fa fa-paper-plane"></i>
        </button>
      </div>

    </div>
  </div>
</main>

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
(function() {
  const saved = localStorage.getItem("theme") || "light";
  document.documentElement.setAttribute("data-theme", saved);
})();

let activeConcernId = null;
let pollTimer       = null;
let activeFilter    = 'all';

function setTab(btn) {
  document.querySelectorAll('.cc-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  activeFilter = btn.dataset.filter;
  filterList();
}

function filterList() {
  const q = document.getElementById('ccSearch').value.toLowerCase();
  document.querySelectorAll('.cc-concern-item').forEach(item => {
    const matchFilter = activeFilter === 'all' || item.dataset.status === activeFilter;
    const matchSearch = !q || item.dataset.name.includes(q) || item.dataset.subject.includes(q);
    item.style.display = (matchFilter && matchSearch) ? 'block' : 'none';
  });
}

function openThread(concernId, studentName, subject) {
  activeConcernId = concernId;
  clearInterval(pollTimer);

  document.querySelectorAll('.cc-concern-item').forEach(el => el.classList.remove('active'));
  const item = document.getElementById('cci-' + concernId);
  if (item) item.classList.add('active');

  document.getElementById('ccPlaceholder').style.display = 'none';
  document.getElementById('ccThread').classList.add('visible');
  document.getElementById('threadStudentName').textContent = studentName;
  document.getElementById('threadSubject').textContent = subject;
  document.getElementById('threadMessages').innerHTML =
    '<div style="text-align:center;padding:30px;color:#718096;font-size:13px;">' +
    '<i class="fa fa-spinner fa-spin"></i> Loading…</div>';

  fetchThread();
  pollTimer = setInterval(fetchThread, 8000);
}

function fetchThread() {
  if (!activeConcernId) return;
  fetch('cconcerns.php?fetch_thread=' + activeConcernId)
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;

      const isPending  = data.status === 'Pending';
      const isResolved = data.status === 'Resolved';

      /* Update list item badge + data-status */
      const listItem = document.getElementById('cci-' + activeConcernId);
      if (listItem) {
        listItem.dataset.status = data.status;
        const lb = listItem.querySelector('.cc-badge');
        if (lb) { lb.textContent = data.status; lb.className = 'cc-badge ' + data.status.toLowerCase(); }
      }

      /* Thread header badge */
      const badge = document.getElementById('threadStatus');
      badge.textContent = data.status;
      badge.className   = 'cc-badge ' + data.status.toLowerCase();

      /* Resolve button — only shown/enabled when not already resolved */
      const resolveBtn = document.getElementById('resolveBtn');
      resolveBtn.disabled = isResolved;
      resolveBtn.style.display = isResolved ? 'none' : 'flex';

      /* Claim notice — shown only while still Pending */
      document.getElementById('claimNotice').classList.toggle('show', isPending);

      /* Messages */
      const box = document.getElementById('threadMessages');
      const wasAtBottom = (box.scrollHeight - box.scrollTop - box.clientHeight) < 80;
      box.innerHTML = '';

      appendMsg(box, {
        sender:      'student',
        sender_name: data.student_name,
        message:     data.message,
        created_at:  data.sent_at,
      });

      if (data.messages.length === 0) {
        const w = document.createElement('div');
        w.style.cssText = 'text-align:center;padding:20px 0;font-size:12px;color:#718096;';
        w.innerHTML = '<i class="fa fa-hourglass-half" style="margin-right:5px;"></i>No replies yet. Write the first response below.';
        box.appendChild(w);
      } else {
        data.messages.forEach(m => appendMsg(box, m));
      }

      if (wasAtBottom) box.scrollTop = box.scrollHeight;
    });
}

function appendMsg(box, m) {
  const isMe = m.sender === 'counselor';
  const div  = document.createElement('div');
  div.className = 'cc-msg ' + (isMe ? 'me' : 'them');
  div.innerHTML = `
    <div class="cc-msg-bubble">${escHtml(m.message)}</div>
    <div class="cc-msg-meta">
      ${escHtml(isMe ? 'You' : (m.sender_name || 'Student'))}
      &middot; ${fmtTime(m.created_at)}
    </div>`;
  box.appendChild(div);
}

function sendReply() {
  const text = document.getElementById('replyText').value.trim();
  const btn  = document.getElementById('sendBtn');
  if (!text || !activeConcernId) return;

  btn.disabled = true;

  const fd = new FormData();
  fd.append('action',     'counselor_reply');
  fd.append('concern_id', activeConcernId);
  fd.append('message',    text);

  fetch('cconcerns.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      btn.disabled = false;
      if (json.success) {
        document.getElementById('replyText').value = '';
        autoResize(document.getElementById('replyText'));

        /* If this was a Pending concern, remove it from other counselors' views
           by updating the list item's data-status so local filter still works */
        const listItem = document.getElementById('cci-' + activeConcernId);
        if (listItem) listItem.dataset.status = 'Reviewed';

        fetchThread();
      } else {
        alert(json.message || 'Failed to send reply.');
      }
    })
    .catch(() => {
      btn.disabled = false;
      alert('Something went wrong. Please try again.');
    });
}

function markResolved() {
  if (!activeConcernId) return;
  const fd = new FormData();
  fd.append('action',     'mark_resolved');
  fd.append('concern_id', activeConcernId);

  fetch('cconcerns.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => { if (json.success) fetchThread(); });
}

function handleReplyKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendReply();
  }
}

function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 130) + 'px';
}

function escHtml(str) {
  return String(str || '')
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmtTime(dt) {
  if (!dt) return '';
  const d    = new Date(dt.replace(' ','T'));
  const diff = Date.now() - d;
  if (isNaN(diff))     return dt;
  if (diff < 60000)    return 'Just now';
  if (diff < 3600000)  return Math.floor(diff/60000)   + 'm ago';
  if (diff < 86400000) return Math.floor(diff/3600000) + 'h ago';
  return d.toLocaleDateString('en-PH', { month:'short', day:'numeric', year:'numeric' });
}

function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}
function toggleTheme() {
  const html = document.documentElement;
  const t = html.getAttribute("data-theme") === "light" ? "dark" : "light";
  html.setAttribute("data-theme", t);
  localStorage.setItem("theme", t);
}
function toggleDropdown(id, e) {
  e.stopPropagation();
  document.getElementById(id).classList.toggle("show");
}
function logout()        { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()   { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=counselor'; }
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});
document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn  = document.querySelector(".sidebar-settingsButton");
  if (!menu.contains(e.target) && !btn.contains(e.target)) menu.classList.remove("show");
});
</script>
</body>
</html>
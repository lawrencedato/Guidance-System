<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: slogin.php");
    exit;
}

$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");
$sid  = (int)$_SESSION['user_id'];

/* ── Ensure columns exist ── */
$conn->query("ALTER TABLE concern_replies ADD COLUMN IF NOT EXISTS sender_type ENUM('counselor','student') NOT NULL DEFAULT 'counselor'");
$conn->query("ALTER TABLE concern_replies ADD COLUMN IF NOT EXISTS student_id INT NULL");

$studentRes = $conn->query("SELECT * FROM students WHERE student_id='$sid' LIMIT 1");
$student    = $studentRes->fetch_assoc();
$profileRes = $conn->query("SELECT profile_image FROM student_profiles WHERE student_id='$sid' LIMIT 1");
$profile    = $profileRes->fetch_assoc();

$fullName   = htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$email      = htmlspecialchars($student['email'] ?? '');
$profileImg = !empty($profile['profile_image'])
              ? htmlspecialchars($profile['profile_image'])
              : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';

/* ── AJAX: submit new concern ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_concern') {
    header('Content-Type: application/json');
    $subject = $conn->real_escape_string(trim($_POST['subject'] ?? ''));
    $message = $conn->real_escape_string(trim($_POST['message'] ?? ''));
    if (!$subject || !$message) {
        echo json_encode(['success' => false, 'message' => 'Please complete all fields.']); exit;
    }
    $ok = $conn->query("
        INSERT INTO concerns (student_id, subject, message, status, created_at)
        VALUES ('$sid', '$subject', '$message', 'Pending', NOW())
    ");
    echo json_encode($ok
        ? ['success' => true, 'concern_id' => $conn->insert_id]
        : ['success' => false, 'message' => 'Failed to submit. Please try again.']);
    exit;
}

/* ── AJAX: student sends a reply ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'student_reply') {
    header('Content-Type: application/json');
    $concernId = (int)($_POST['concern_id'] ?? 0);
    $message   = $conn->real_escape_string(trim($_POST['message'] ?? ''));

    $own = $conn->query("
        SELECT concern_id, status FROM concerns
        WHERE concern_id = $concernId AND student_id = $sid LIMIT 1
    ")->fetch_assoc();

    if (!$own || !$message) {
        echo json_encode(['success' => false, 'message' => 'Invalid request.']); exit;
    }

    if ($own['status'] === 'Resolved') {
        echo json_encode(['success' => false, 'message' => 'This concern is already resolved. You cannot reply anymore.']); exit;
    }

    $ok = $conn->query("
        INSERT INTO concern_replies (concern_id, student_id, reply, replied_at, sender_type)
        VALUES ($concernId, $sid, '$message', NOW(), 'student')
    ");

    if ($ok) $conn->query("
        UPDATE concerns SET status = 'Pending'
        WHERE concern_id = $concernId AND status != 'Resolved'
    ");

    echo json_encode($ok
        ? ['success' => true]
        : ['success' => false, 'message' => 'Failed to send reply.']);
    exit;
}

/* ── AJAX: fetch full thread ── */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['fetch_thread'])) {
    header('Content-Type: application/json');
    $concernId = (int)$_GET['fetch_thread'];

    $concern = $conn->query("
        SELECT concern_id, subject, message, status, created_at
        FROM concerns
        WHERE concern_id = $concernId AND student_id = $sid
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
        'success'  => true,
        'subject'  => $concern['subject'],
        'message'  => $concern['message'],
        'status'   => $concern['status'],
        'sent_at'  => $concern['created_at'],
        'messages' => $messages,
    ]);
    exit;
}

/* ── Load concern list ── */
$concerns = [];
$res = $conn->query("
    SELECT c.concern_id, c.subject, c.status, c.created_at,
           (SELECT COUNT(*) FROM concern_replies cr
            WHERE cr.concern_id = c.concern_id AND cr.sender_type = 'counselor') AS reply_count
    FROM concerns c
    WHERE c.student_id = $sid
    ORDER BY c.created_at DESC
");
while ($row = $res->fetch_assoc()) $concerns[] = $row;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Submit Concern</title>
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
    
.sConcern-main {
  display: flex;
  gap: 0;
  height: calc(100vh - 70px);
  overflow: hidden;
}
.sc-left {
  width: 320px;
  min-width: 260px;
  display: flex;
  flex-direction: column;
  border-right: 1px solid var(--border, #e2e8f0);
  background: var(--sidebar-bg, #fff);
  overflow: hidden;
}
.sc-left-header {
  padding: 18px 16px 12px;
  border-bottom: 1px solid var(--border, #e2e8f0);
  flex-shrink: 0;
}
.sc-left-header h3 {
  font-size: 14px; font-weight: 700;
  margin: 0 0 10px;
  color: var(--text, #1a202c);
  display: flex; align-items: center; gap: 7px;
}
.sc-new-btn {
  width: 100%; padding: 9px 0;
  background: #113f67; color: #fff;
  border: none; border-radius: 8px;
  font-size: 13px; font-weight: 600;
  cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 7px;
  transition: background .2s;
}
.sc-new-btn:hover { background: #0d3050; }
.sc-concern-list { flex: 1; overflow-y: auto; padding: 6px 0; }
.sc-concern-item {
  padding: 12px 16px; cursor: pointer;
  border-left: 3px solid transparent;
  transition: background .15s, border-color .15s;
  position: relative;
}
.sc-concern-item:hover  { background: var(--hover-bg, #f7fafc); }
.sc-concern-item.active { background: #eef4fb; border-left-color: #113f67; }
.ci-subject {
  font-size: 13px; font-weight: 600;
  color: var(--text, #1a202c);
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  margin-bottom: 4px;
}
.ci-meta {
  font-size: 11px; color: var(--text-muted, #718096);
  display: flex; align-items: center; gap: 6px;
}
.sc-badge {
  display: inline-block; padding: 2px 7px;
  border-radius: 20px; font-size: 10px;
  font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
}
.sc-badge.pending  { background: #fff3cd; color: #856404; }
.sc-badge.reviewed { background: #dbeafe; color: #1d4ed8; }
.sc-badge.resolved { background: #d1fae5; color: #065f46; }
.sc-empty-list {
  padding: 30px 16px; text-align: center;
  color: var(--text-muted, #718096); font-size: 13px;
}
.sc-empty-list i { font-size: 34px; opacity: .25; display: block; margin-bottom: 10px; }
.sc-right {
  flex: 1; display: flex; flex-direction: column;
  overflow: hidden; background: var(--main-bg, #f7fafc);
}
.sc-emergency-bar {
  margin: 12px 18px 0; padding: 9px 14px;
  background: #fff5f5; border: 1px solid #fed7d7;
  border-radius: 8px; font-size: 12px; color: #c53030;
  display: flex; align-items: center; gap: 8px; flex-shrink: 0;
}
.sc-placeholder {
  flex: 1; display: flex; flex-direction: column;
  align-items: center; justify-content: center;
  color: var(--text-muted, #718096);
  gap: 10px; padding: 40px; text-align: center;
}
.sc-placeholder i { font-size: 52px; opacity: .2; }
.sc-placeholder p { font-size: 14px; margin: 0; }
.sc-thread { flex: 1; display: none; flex-direction: column; overflow: hidden; }
.sc-thread.visible { display: flex; }
.sc-thread-header {
  padding: 14px 20px;
  background: var(--sidebar-bg, #fff);
  border-bottom: 1px solid var(--border, #e2e8f0);
  flex-shrink: 0;
  display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.sc-thread-header h4 { font-size: 14px; font-weight: 700; margin: 0 0 2px; color: var(--text,#1a202c); }
.sc-thread-header p  { font-size: 12px; color: var(--text-muted,#718096); margin: 0; }
.sc-messages {
  flex: 1; overflow-y: auto; padding: 18px 20px;
  display: flex; flex-direction: column; gap: 12px;
}
.sc-msg { display: flex; flex-direction: column; max-width: 72%; }
.sc-msg.me   { align-self: flex-end;   align-items: flex-end; }
.sc-msg.them { align-self: flex-start; align-items: flex-start; }
.sc-msg-bubble {
  padding: 10px 14px; border-radius: 14px;
  font-size: 13.5px; line-height: 1.55;
  word-break: break-word; white-space: pre-wrap;
}
.sc-msg.me   .sc-msg-bubble { background: #113f67; color: #fff; border-bottom-right-radius: 4px; }
.sc-msg.them .sc-msg-bubble { background: var(--sidebar-bg,#fff); color: var(--text,#1a202c); border: 1px solid var(--border,#e2e8f0); border-bottom-left-radius: 4px; }
.sc-msg-meta { font-size: 11px; color: var(--text-muted,#718096); margin-top: 3px; padding: 0 4px; }

.sc-resolved-notice {
  display: none;
  margin: 0 16px 10px;
  padding: 9px 14px;
  background: #d1fae5; border: 1px solid #6ee7b7;
  border-radius: 8px; font-size: 12px; color: #065f46;
  align-items: center; gap: 8px;
}
.sc-resolved-notice.show { display: flex; }

.sc-reply-box {
  padding: 12px 16px;
  background: var(--sidebar-bg, #fff);
  border-top: 1px solid var(--border, #e2e8f0);
  display: flex; gap: 10px; align-items: flex-end; flex-shrink: 0;
}
.sc-reply-box textarea {
  flex: 1; resize: none;
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 10px; padding: 10px 13px;
  font-size: 13.5px; font-family: inherit;
  background: var(--input-bg, #f8fafc); color: var(--text, #1a202c);
  line-height: 1.5; min-height: 44px; max-height: 130px;
  outline: none; transition: border-color .2s;
}
.sc-reply-box textarea:focus { border-color: #113f67; }
.sc-reply-box textarea:disabled {
  background: var(--hover-bg,#f1f5f9);
  color: var(--text-muted,#718096);
  cursor: not-allowed;
}
.sc-send-btn {
  width: 44px; height: 44px; border: none; border-radius: 10px;
  background: #113f67; color: #fff; font-size: 16px; cursor: pointer;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
  transition: background .2s, opacity .2s;
}
.sc-send-btn:hover    { background: #0d3050; }
.sc-send-btn:disabled { opacity: .5; cursor: not-allowed; }

.sc-modal-overlay {
  position: fixed; inset: 0; background: rgba(0,0,0,.45);
  z-index: 1000; display: flex; align-items: center; justify-content: center;
  opacity: 0; pointer-events: none; transition: opacity .2s;
}
.sc-modal-overlay.show { opacity: 1; pointer-events: all; }
.sc-modal {
  background: var(--sidebar-bg,#fff); border-radius: 16px;
  padding: 26px 26px 22px; width: 100%; max-width: 460px;
  box-shadow: 0 20px 60px rgba(0,0,0,.18);
  transform: translateY(12px); transition: transform .2s;
}
.sc-modal-overlay.show .sc-modal { transform: translateY(0); }
.sc-modal h3 { font-size: 15px; font-weight: 700; margin: 0 0 4px; color: var(--text,#1a202c); }
.sc-modal > p { font-size: 13px; color: var(--text-muted,#718096); margin: 0 0 18px; }
.sc-modal label { display: block; font-size: 11px; font-weight: 700; color: var(--text-muted,#718096); text-transform: uppercase; letter-spacing: .5px; margin-bottom: 6px; }
.sc-modal input, .sc-modal textarea {
  width: 100%; box-sizing: border-box; padding: 10px 13px;
  border: 1px solid var(--border,#e2e8f0); border-radius: 8px;
  font-size: 13.5px; font-family: inherit;
  background: var(--input-bg,#f8fafc); color: var(--text,#1a202c);
  margin-bottom: 14px; outline: none; transition: border-color .2s;
}
.sc-modal input:focus, .sc-modal textarea:focus { border-color: #113f67; }
.sc-modal textarea { resize: vertical; min-height: 96px; }
.sc-modal-actions { display: flex; gap: 10px; margin-top: 4px; }
.sc-modal-cancel { flex: 1; padding: 10px; border: 1px solid var(--border,#e2e8f0); border-radius: 8px; background: transparent; color: var(--text,#1a202c); font-size: 13px; cursor: pointer; }
.sc-modal-cancel:hover { background: var(--hover-bg,#f7fafc); }
.sc-modal-submit { flex: 2; padding: 10px; border: none; border-radius: 8px; background: #113f67; color: #fff; font-size: 13px; font-weight: 700; cursor: pointer; transition: background .2s; }
.sc-modal-submit:hover { background: #0d3050; }
#modalResult { font-size: 13px; min-height: 18px; }

.sc-concern-list::-webkit-scrollbar,
.sc-messages::-webkit-scrollbar { width: 4px; }
.sc-concern-list::-webkit-scrollbar-thumb,
.sc-messages::-webkit-scrollbar-thumb { background: #cbd5e0; border-radius: 4px; }

[data-theme="dark"] .sc-left,
[data-theme="dark"] .sc-thread-header,
[data-theme="dark"] .sc-reply-box,
[data-theme="dark"] .sc-modal { background: #1e2533; }
[data-theme="dark"] .sc-concern-item.active { background: #1c2f45; }
[data-theme="dark"] .sc-msg.them .sc-msg-bubble { background: #1e2533; border-color: #2d3748; }
[data-theme="dark"] .sc-reply-box textarea { background: #161d2b; border-color: #2d3748; color: #e2e8f0; }
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
        <a href="sprofile.php"><i class="fa fa-user"></i> Profile</a>
        <a href="shistory.php"><i class="fa fa-clock"></i> History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>
  </div>
  <nav class="sidebar-menu">
    <a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a>
    <p class="sidebar-title">SERVICES</p>
    <a href="sappointment.php"><i class="fa fa-calendar"></i> Book Appointment</a>
    <a href="sconcerns.php" class="active"><i class="fa fa-headset"></i> Submit Concern</a>
    <a href="swellness.php"><i class="fa fa-heart"></i> Wellness Check</a>
    <a href="sreferral.php" class="<?= basename($_SERVER['PHP_SELF']) === 'sreferral.php' ? 'active' : '' ?>">
            <i class="fa fa-route"></i> Referral
            <span class="referral-badge" id="referralBadge" style="display:none;"></span>
        </a>
    <p class="sidebar-title">UPDATES</p>
    <a href="sannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>
    <p class="sidebar-title">RECORDS</p>
    <a href="sreports.php"><i class="fa fa-ticket"></i> Reports</a>
    <p class="sidebar-title">SYSTEM</p>
    <a href="sfeedback.php"><i class="fa fa-comment"></i> Session Feedback</a>
  </nav>
</aside>

<header class="topbar">
  <div class="topbar-left"><h2>Submit Concern</h2></div>
  <div class="topbar-right">
    <div class="topbar-user">
      <img src="<?= $profileImg ?>" alt="user"
           onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($fullName) ?>&background=113f67&color=fff'">
      <div><strong><?= $fullName ?></strong><p><?= $email ?></p></div>
    </div>
  </div>
</header>

<main class="sConcern-main">
  <div class="sc-left">
    <div class="sc-left-header">
      <h3><i class="fa fa-headset" style="color:#113f67;"></i> My Concerns</h3>
      <button class="sc-new-btn" onclick="openModal()">
        <i class="fa fa-plus"></i> New Concern
      </button>
    </div>
    <div class="sc-concern-list" id="concernList">
      <?php if (empty($concerns)): ?>
        <div class="sc-empty-list">
          <i class="fa fa-inbox"></i>
          No concerns yet.<br>Click <strong>New Concern</strong> to get started.
        </div>
      <?php else: foreach ($concerns as $c): ?>
        <div class="sc-concern-item"
             id="ci-<?= $c['concern_id'] ?>"
             onclick="openThread(<?= $c['concern_id'] ?>, <?= htmlspecialchars(json_encode($c['subject'])) ?>)">
          <div class="ci-subject"><?= htmlspecialchars($c['subject']) ?></div>
          <div class="ci-meta">
            <span class="sc-badge <?= strtolower($c['status']) ?>">
              <?= htmlspecialchars($c['status']) ?>
            </span>
            <span><?= date('M d, Y', strtotime($c['created_at'])) ?></span>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <div class="sc-right">
    <div class="sc-emergency-bar">
      <i class="fa fa-triangle-exclamation"></i>
      <span><strong>Emergency?</strong> Call our hotline: <strong>0912-345-6789</strong> &nbsp;|&nbsp; Mon–Fri, 8:00 AM – 5:00 PM</span>
    </div>

    <div class="sc-placeholder" id="scPlaceholder">
      <i class="fa fa-comments"></i>
      <p>Select a concern to view the conversation,<br>or click <strong>New Concern</strong> to submit one.</p>
    </div>

    <div class="sc-thread" id="scThread">
      <div class="sc-thread-header">
        <div>
          <h4 id="threadSubject">—</h4>
          <p id="threadMeta">—</p>
        </div>
        <span class="sc-badge pending" id="threadStatus">Pending</span>
      </div>

      <div class="sc-messages" id="threadMessages"></div>

      <div class="sc-resolved-notice" id="resolvedNotice">
        <i class="fa fa-circle-check"></i>
        <span>This concern has been resolved. You can no longer send replies.</span>
      </div>

      <div class="sc-reply-box">
        <textarea id="replyText" rows="1"
          placeholder="Type your reply… (Enter to send, Shift+Enter for new line)"
          oninput="autoResize(this)"
          onkeydown="handleReplyKey(event)"></textarea>
        <button class="sc-send-btn" id="sendBtn" onclick="sendReply()" title="Send reply">
          <i class="fa fa-paper-plane"></i>
        </button>
      </div>
    </div>
  </div>
</main>

<div class="sc-modal-overlay" id="concernModal" onclick="modalBgClick(event)">
  <div class="sc-modal">
    <h3><i class="fa fa-headset" style="margin-right:6px;color:#113f67;"></i> New Concern</h3>
    <p>Submit your concern and a counselor will respond as soon as possible.</p>
    <label>Subject</label>
    <input type="text" id="modalSubject" placeholder="e.g. Academic Stress" maxlength="200">
    <label>Message</label>
    <textarea id="modalMessage" rows="4" placeholder="Describe your concern in detail…"></textarea>
    <div id="modalResult"></div>
    <div class="sc-modal-actions">
      <button class="sc-modal-cancel" onclick="closeModal()">Cancel</button>
      <button class="sc-modal-submit" onclick="submitConcern()">
        <i class="fa fa-paper-plane" style="margin-right:6px;"></i>Submit
      </button>
    </div>
  </div>
</div>

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

function openModal() {
  document.getElementById('concernModal').classList.add('show');
  setTimeout(() => document.getElementById('modalSubject').focus(), 150);
}
function closeModal() {
  document.getElementById('concernModal').classList.remove('show');
  document.getElementById('modalResult').innerHTML = '';
}
function modalBgClick(e) {
  if (e.target === document.getElementById('concernModal')) closeModal();
}

function submitConcern() {
  const subject = document.getElementById('modalSubject').value.trim();
  const message = document.getElementById('modalMessage').value.trim();
  const result  = document.getElementById('modalResult');
  if (!subject || !message) {
    result.innerHTML = "<span style='color:#e53e3e;font-size:13px;'>⚠ Please complete all fields.</span>";
    return;
  }
  const fd = new FormData();
  fd.append('action','submit_concern');
  fd.append('subject', subject);
  fd.append('message', message);
  fetch('sconcerns.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        const list    = document.getElementById('concernList');
        const emptyEl = list.querySelector('.sc-empty-list');
        if (emptyEl) emptyEl.remove();
        const item = document.createElement('div');
        item.className = 'sc-concern-item';
        item.id = 'ci-' + json.concern_id;
        item.onclick = () => openThread(json.concern_id, subject);
        item.innerHTML = `
          <div class="ci-subject">${escHtml(subject)}</div>
          <div class="ci-meta">
            <span class="sc-badge pending">Pending</span>
            <span>Just now</span>
          </div>`;
        list.prepend(item);
        document.getElementById('modalSubject').value = '';
        document.getElementById('modalMessage').value = '';
        closeModal();
        openThread(json.concern_id, subject);
      } else {
        result.innerHTML = `<span style='color:#e53e3e;font-size:13px;'>❌ ${escHtml(json.message)}</span>`;
      }
    })
    .catch(() => {
      result.innerHTML = "<span style='color:#e53e3e;font-size:13px;'>❌ Something went wrong.</span>";
    });
}

function openThread(concernId, subject) {
  activeConcernId = concernId;
  clearInterval(pollTimer);
  document.querySelectorAll('.sc-concern-item').forEach(el => el.classList.remove('active'));
  const item = document.getElementById('ci-' + concernId);
  if (item) item.classList.add('active');
  document.getElementById('scPlaceholder').style.display = 'none';
  document.getElementById('scThread').classList.add('visible');
  document.getElementById('threadSubject').textContent = subject;
  document.getElementById('threadMessages').innerHTML =
    '<div style="text-align:center;padding:30px;color:#718096;font-size:13px;"><i class="fa fa-spinner fa-spin"></i> Loading…</div>';
  fetchThread();
  pollTimer = setInterval(fetchThread, 8000);
}

function fetchThread() {
  if (!activeConcernId) return;
  fetch('sconcerns.php?fetch_thread=' + activeConcernId)
    .then(r => r.json())
    .then(data => {
      if (!data.success) return;

      const isResolved = data.status === 'Resolved';

      const badge = document.getElementById('threadStatus');
      badge.textContent = data.status;
      badge.className   = 'sc-badge ' + data.status.toLowerCase();

      const listItem = document.getElementById('ci-' + activeConcernId);
      if (listItem) {
        const lb = listItem.querySelector('.sc-badge');
        if (lb) { lb.textContent = data.status; lb.className = 'sc-badge ' + data.status.toLowerCase(); }
      }

      const total = data.messages.length;
      document.getElementById('threadMeta').textContent =
        total === 0 ? 'No replies yet' : total + ' message' + (total !== 1 ? 's' : '') + ' in thread';

      const textarea = document.getElementById('replyText');
      const sendBtn  = document.getElementById('sendBtn');
      const notice   = document.getElementById('resolvedNotice');
      textarea.disabled = isResolved;
      sendBtn.disabled  = isResolved;
      textarea.placeholder = isResolved
        ? 'This concern is resolved. Replies are disabled.'
        : 'Type your reply… (Enter to send, Shift+Enter for new line)';
      notice.classList.toggle('show', isResolved);

      const box = document.getElementById('threadMessages');
      const wasAtBottom = (box.scrollHeight - box.scrollTop - box.clientHeight) < 80;
      box.innerHTML = '';

      appendMsg(box, {
        sender: 'student', sender_name: 'You',
        message: data.message, created_at: data.sent_at,
      });

      if (data.messages.length === 0) {
        const w = document.createElement('div');
        w.style.cssText = 'text-align:center;padding:20px 0;font-size:12px;color:#718096;';
        w.innerHTML = '<i class="fa fa-hourglass-half" style="margin-right:5px;"></i>Waiting for counselor response…';
        box.appendChild(w);
      } else {
        data.messages.forEach(m => appendMsg(box, m));
      }

      if (wasAtBottom) box.scrollTop = box.scrollHeight;
    });
}

function appendMsg(box, m) {
  const isMe = m.sender === 'student';
  const div  = document.createElement('div');
  div.className = 'sc-msg ' + (isMe ? 'me' : 'them');
  div.innerHTML = `
    <div class="sc-msg-bubble">${escHtml(m.message)}</div>
    <div class="sc-msg-meta">
      ${escHtml(isMe ? 'You' : (m.sender_name || 'Counselor'))}
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
  fd.append('action',     'student_reply');
  fd.append('concern_id', activeConcernId);
  fd.append('message',    text);
  fetch('sconcerns.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      btn.disabled = false;
      if (json.success) {
        document.getElementById('replyText').value = '';
        autoResize(document.getElementById('replyText'));
        fetchThread();
      } else {
        alert(json.message || 'Failed to send reply.');
        fetchThread();
      }
    })
    .catch(() => { btn.disabled = false; alert('Something went wrong.'); });
}

function handleReplyKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendReply(); }
}
function autoResize(el) {
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 130) + 'px';
}
function escHtml(str) {
  return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function fmtTime(dt) {
  if (!dt) return '';
  const d = new Date(dt.replace(' ','T'));
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
function logout()        { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()   { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=student'; }
document.getElementById('logoutOverlay').addEventListener('click', function(e) { if (e.target === this) closeLogout(); });
document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn  = document.querySelector(".sidebar-settingsButton");
  if (!menu.contains(e.target) && !btn.contains(e.target)) menu.classList.remove("show");
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
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
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Submit Concern</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>

/* ─────────────────────────────────────────
   SIDEBAR
───────────────────────────────────────── */
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

  <!-- LEFT -->
  <div class="sConcern-left">
    <div class="sConcern-left-header">
      <h3><i class="fa fa-headset" style="color:#4988C4;"></i> My Concerns</h3>
      <button class="sConcern-new-btn" onclick="openModal()">
        <i class="fa fa-plus"></i> New Concern
      </button>
    </div>
    <div class="sConcern-list" id="concernList">
      <?php if (empty($concerns)): ?>
        <div class="sConcern-empty-list">
          <i class="fa fa-inbox"></i>
          No concerns yet.<br>Click <strong>New Concern</strong> to get started.
        </div>
      <?php else: foreach ($concerns as $c): ?>
        <div class="sConcern-item"
             id="ci-<?= $c['concern_id'] ?>"
             onclick="openThread(<?= $c['concern_id'] ?>, <?= htmlspecialchars(json_encode($c['subject'])) ?>)">
          <div class="sConcern-item-subject"><?= htmlspecialchars($c['subject']) ?></div>
          <div class="sConcern-item-meta">
            <span class="sConcern-badge <?= strtolower($c['status']) ?>">
              <?= htmlspecialchars($c['status']) ?>
            </span>
            <span><?= date('M d, Y', strtotime($c['created_at'])) ?></span>
          </div>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- RIGHT -->
  <div class="sConcern-right">
    <div class="sConcern-emergency-bar">
      <i class="fa fa-triangle-exclamation"></i>
      <span><strong>Emergency?</strong> Call our hotline: <strong>0912-345-6789</strong> &nbsp;|&nbsp; Mon–Fri, 8:00 AM – 5:00 PM</span>
    </div>

    <div class="sConcern-placeholder" id="scPlaceholder">
      <i class="fa fa-comments"></i>
      <p>Select a concern to view the conversation,<br>or click <strong>New Concern</strong> to submit one.</p>
    </div>

    <div class="sConcern-thread" id="scThread">
      <div class="sConcern-thread-header">
        <div>
          <h4 id="threadSubject">—</h4>
          <p id="threadMeta">—</p>
        </div>
        <span class="sConcern-badge pending" id="threadStatus">Pending</span>
      </div>

      <div class="sConcern-messages" id="threadMessages"></div>

      <div class="sConcern-resolved-notice" id="resolvedNotice">
        <i class="fa fa-circle-check"></i>
        <span>This concern has been resolved. You can no longer send replies.</span>
      </div>

      <div class="sConcern-reply-box">
        <textarea id="replyText" rows="1"
          placeholder="Type your reply… (Enter to send, Shift+Enter for new line)"
          oninput="autoResize(this)"
          onkeydown="handleReplyKey(event)"></textarea>
        <button class="sConcern-send-btn" id="sendBtn" onclick="sendReply()" title="Send reply">
          <i class="fa fa-paper-plane"></i>
        </button>
      </div>
    </div>
  </div>
</main>

<!-- MODAL -->
<div class="sConcern-modal-overlay" id="concernModal" onclick="modalBgClick(event)">
  <div class="sConcern-modal">
    <h3><i class="fa fa-headset" style="margin-right:6px;color:#4988C4;"></i> New Concern</h3>
    <p>Submit your concern and a counselor will respond as soon as possible.</p>
    <label>Subject</label>
    <input type="text" id="modalSubject" placeholder="e.g. Academic Stress" maxlength="200">
    <label>Message</label>
    <textarea id="modalMessage" rows="4" placeholder="Describe your concern in detail…"></textarea>
    <div id="sConcernModalResult"></div>
    <div class="sConcern-modal-actions">
      <button class="sConcern-modal-cancel" onclick="closeModal()">Cancel</button>
      <button class="sConcern-modal-submit" onclick="submitConcern()">
        <i class="fa fa-paper-plane" style="margin-right:6px;"></i>Submit
      </button>
    </div>
  </div>
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
  document.getElementById('sConcernModalResult').innerHTML = '';
}
function modalBgClick(e) {
  if (e.target === document.getElementById('concernModal')) closeModal();
}

function submitConcern() {
  const subject = document.getElementById('modalSubject').value.trim();
  const message = document.getElementById('modalMessage').value.trim();
  const result  = document.getElementById('sConcernModalResult');
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
        const emptyEl = list.querySelector('.sConcern-empty-list');
        if (emptyEl) emptyEl.remove();
        const item = document.createElement('div');
        item.className = 'sConcern-item';
        item.id = 'ci-' + json.concern_id;
        item.onclick = () => openThread(json.concern_id, subject);
        item.innerHTML = `
          <div class="sConcern-item-subject">${escHtml(subject)}</div>
          <div class="sConcern-item-meta">
            <span class="sConcern-badge pending">Pending</span>
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
  document.querySelectorAll('.sConcern-item').forEach(el => el.classList.remove('active'));
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
      badge.className   = 'sConcern-badge ' + data.status.toLowerCase();

      const listItem = document.getElementById('ci-' + activeConcernId);
      if (listItem) {
        const lb = listItem.querySelector('.sConcern-badge');
        if (lb) { lb.textContent = data.status; lb.className = 'sConcern-badge ' + data.status.toLowerCase(); }
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
  div.className = 'sConcern-msg ' + (isMe ? 'me' : 'them');
  div.innerHTML = `
    <div class="sConcern-msg-bubble">${escHtml(m.message)}</div>
    <div class="sConcern-msg-meta">
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
    const res  = await fetch('scheck_referral.php');
    const data = await res.json();
    const badge = document.getElementById('referralBadge');
    if (badge) badge.style.display = data.unseen > 0 ? 'inline-block' : 'none';
  } catch (e) {}
}

checkReferralBadge();
</script>
</body>
</html>
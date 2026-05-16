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

/* ── Load concern list ── */
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
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Student Concerns</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
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
    <a href="cavailability.php"><i class="fa fa-clock"></i> Time Availability</a>
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

  <!-- ══ LEFT PANEL ══ -->
  <div class="cConcerns-left">
    <div class="cConcerns-left-header">
      <h3><i class="fa fa-triangle-exclamation" style="color:#113f67;"></i> Concerns</h3>
      <div class="cConcerns-search">
        <i class="fa fa-magnifying-glass"></i>
        <input type="text" id="ccSearch" placeholder="Search student or subject…" oninput="filterList()">
      </div>
    </div>

    <div class="cConcerns-filter-tabs">
      <button class="cConcerns-tab active" data-filter="all"      onclick="setTab(this)">All</button>
      <button class="cConcerns-tab"        data-filter="Pending"  onclick="setTab(this)">Pending</button>
      <button class="cConcerns-tab"        data-filter="Reviewed" onclick="setTab(this)">Reviewed</button>
      <button class="cConcerns-tab"        data-filter="Resolved" onclick="setTab(this)">Resolved</button>
    </div>

    <div class="cConcerns-list" id="ccConcernList">
      <?php if (empty($concerns)): ?>
        <div class="cConcerns-empty-list">
          <i class="fa fa-inbox"></i>
          No student concerns yet.
        </div>
      <?php else: foreach ($concerns as $c): ?>
        <div class="cConcerns-item"
             id="cci-<?= $c['concern_id'] ?>"
             data-status="<?= htmlspecialchars($c['status']) ?>"
             data-name="<?= htmlspecialchars(strtolower($c['first_name'] . ' ' . $c['last_name'])) ?>"
             data-subject="<?= htmlspecialchars(strtolower($c['subject'])) ?>"
             onclick="openThread(<?= $c['concern_id'] ?>, <?= htmlspecialchars(json_encode($c['first_name'] . ' ' . $c['last_name'])) ?>, <?= htmlspecialchars(json_encode($c['subject'])) ?>)">
          <div class="cConcerns-item-name"><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></div>
          <div class="cConcerns-item-subject"><?= htmlspecialchars($c['subject']) ?></div>
          <div class="cConcerns-item-meta">
            <span class="cConcerns-badge <?= strtolower($c['status']) ?>">
              <?= htmlspecialchars($c['status']) ?>
            </span>
            <span><?= date('M d, Y', strtotime($c['created_at'])) ?></span>
          </div>
          <?php if ($c['student_reply_count'] > 0): ?>
            <div class="cConcerns-reply-dot" title="Student replied"></div>
          <?php endif; ?>
        </div>
      <?php endforeach; endif; ?>
    </div>
  </div>

  <!-- ══ RIGHT PANEL ══ -->
  <div class="cConcerns-right">

    <div class="cConcerns-placeholder" id="ccPlaceholder">
      <i class="fa fa-comments"></i>
      <p>Select a concern from the list<br>to view and respond to the conversation.</p>
    </div>

    <div class="cConcerns-thread" id="ccThread">

      <div class="cConcerns-thread-header">
        <div>
          <h4 id="threadStudentName">—</h4>
          <p id="threadSubject">—</p>
        </div>
        <div class="cConcerns-thread-header-right">
          <span class="cConcerns-badge pending" id="threadStatus">Pending</span>
          <button class="cConcerns-resolve-btn" id="resolveBtn" onclick="markResolved()">
            <i class="fa fa-circle-check"></i> Mark Resolved
          </button>
        </div>
      </div>

      <div class="cConcerns-messages" id="threadMessages"></div>

      <!-- Claim notice -->
      <div class="cConcerns-claim-notice" id="claimNotice">
        <i class="fa fa-circle-info"></i>
        <span>This concern is unclaimed. Sending a reply will assign it to you.</span>
      </div>

      <div class="cConcerns-reply-box">
        <textarea
          id="replyText"
          rows="1"
          placeholder="Type your reply… (Enter to send, Shift+Enter for new line)"
          oninput="autoResize(this)"
          onkeydown="handleReplyKey(event)"
        ></textarea>
        <button class="cConcerns-send-btn" id="sendBtn" onclick="sendReply()" title="Send reply">
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
  document.querySelectorAll('.cConcerns-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  activeFilter = btn.dataset.filter;
  filterList();
}

function filterList() {
  const q = document.getElementById('ccSearch').value.toLowerCase();
  document.querySelectorAll('.cConcerns-item').forEach(item => {
    const matchFilter = activeFilter === 'all' || item.dataset.status === activeFilter;
    const matchSearch = !q || item.dataset.name.includes(q) || item.dataset.subject.includes(q);
    item.style.display = (matchFilter && matchSearch) ? 'block' : 'none';
  });
}

function openThread(concernId, studentName, subject) {
  activeConcernId = concernId;
  clearInterval(pollTimer);

  document.querySelectorAll('.cConcerns-item').forEach(el => el.classList.remove('active'));
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

      const listItem = document.getElementById('cci-' + activeConcernId);
      if (listItem) {
        listItem.dataset.status = data.status;
        const lb = listItem.querySelector('.cConcerns-badge');
        if (lb) { lb.textContent = data.status; lb.className = 'cConcerns-badge ' + data.status.toLowerCase(); }
      }

      const badge = document.getElementById('threadStatus');
      badge.textContent = data.status;
      badge.className   = 'cConcerns-badge ' + data.status.toLowerCase();

      const resolveBtn = document.getElementById('resolveBtn');
      resolveBtn.disabled = isResolved;
      resolveBtn.style.display = isResolved ? 'none' : 'flex';

      document.getElementById('claimNotice').classList.toggle('show', isPending);

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
  div.className = 'cConcerns-msg ' + (isMe ? 'me' : 'them');
  div.innerHTML = `
    <div class="cConcerns-msg-bubble">${escHtml(m.message)}</div>
    <div class="cConcerns-msg-meta">
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
<script>var SESSION_ROLE = 'counselor';</script>
<script src="session_timeout.js"></script>
</body>
</html>
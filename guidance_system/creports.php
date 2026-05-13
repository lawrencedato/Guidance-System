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

// ── AJAX: lookup student by ID ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'lookup_student') {
    header('Content-Type: application/json');
    $sid = (int)($_GET['student_id'] ?? 0);
    if (!$sid) { echo json_encode(['found' => false]); exit; }
    $res = $conn->query("SELECT first_name, last_name, course, year_level FROM students WHERE student_id='$sid' AND archived=0 LIMIT 1");
    $st  = $res ? $res->fetch_assoc() : null;
    if ($st) {
        echo json_encode([
            'found'      => true,
            'name'       => $st['first_name'] . ' ' . $st['last_name'],
            'course'     => $st['course'],
            'year_level' => $st['year_level']
        ]);
    } else {
        echo json_encode(['found' => false]);
    }
    exit;
}

// ── POST: save notes ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_notes') {
    header('Content-Type: application/json');
    $notes      = $conn->real_escape_string(trim($_POST['notes'] ?? ''));
    $student_id = (int)($_POST['student_id'] ?? 0);

    if (!$student_id) { echo json_encode(['success' => false, 'message' => 'Please enter a valid Student ID.']); exit; }
    if (!$notes)      { echo json_encode(['success' => false, 'message' => 'Notes cannot be empty.']); exit; }

    $check = $conn->query("SELECT student_id FROM students WHERE student_id='$student_id' AND archived=0 LIMIT 1");
    if (!$check || $check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit;
    }

    $ok = $conn->query("
        INSERT INTO session_notes (counselor_id, student_id, notes, is_sent, created_at)
        VALUES ('$cid', '$student_id', '$notes', 1, NOW())
    ");
    echo json_encode($ok ? ['success' => true] : ['success' => false, 'message' => 'Failed to save.']);
    exit;
}

// ── Fetch sent notes for this counselor ──
$sentNotesRes = $conn->query("
    SELECT sn.note_id, sn.notes, sn.created_at,
           CONCAT(s.first_name, ' ', s.last_name) AS student_name,
           s.student_id, s.course, s.year_level
    FROM session_notes sn
    JOIN students s ON sn.student_id = s.student_id
    WHERE sn.counselor_id = '$cid'
      AND sn.is_sent = 1
    ORDER BY sn.created_at DESC
");
$sentNotes = [];
while ($r = $sentNotesRes->fetch_assoc()) $sentNotes[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Session Notes</title>
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
    <a href="counselor.php"><i class="fa fa-gauge"></i> Dashboard</a>
    <p class="sidebar-title">SESSIONS</p>
    <a href="cappointments.php"><i class="fa fa-calendar-plus"></i> Appointment Requests</a>
    <a href="cavailability.php"><i class="fa fa-clock"></i> Time Availability</a>
    <a href="cconcerns.php"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>
    <a href="cfeedback.php"><i class="fa fa-comment"></i> Session Feedback</a>
    <p class="sidebar-title">STUDENTS</p>
    <a href="cstudents.php"><i class="fa fa-users"></i> Students</a>
    <p class="sidebar-title">REPORTS</p>
    <a href="creports.php" class="active"><i class="fa fa-file"></i> Session Notes</a>
    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.php"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Session Notes</h2>
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
<main class="cReports-main">
  <div class="cReports-center">

    <!-- ── CARD 1: WRITE NOTE ── -->
    <div class="cReports-card" style="display:flex; flex-direction:column; gap:32px;">
      <div>
        <h3 style="margin:0 0 4px;">Write Session Notes</h3>
        <p style="font-size:13px; color:var(--text-muted); margin:0 0 18px;">Send confidential notes directly to your student.</p>

        <div class="cReports-idRow">
          <input
            type="number"
            class="cReports-idInput"
            id="studentIdInput"
            placeholder="Enter Student ID (e.g. 220001)"
            min="1"
            oninput="lookupStudent(this.value)"
          >
        </div>

        <div class="cReports-notFound" id="notFound">
          ⚠ No student found with that ID.
        </div>

        <div class="cReports-studentPreview" id="studentPreview">
          <img class="cReports-studentAvatar" id="studentAvatar" src="" alt="avatar">
          <div class="cReports-studentInfo">
            <strong id="studentName"></strong>
            <span id="studentMeta"></span>
          </div>
        </div>

        <textarea
          class="cReports-textarea"
          id="notesTextarea"
          placeholder="Write session notes here..."
        ></textarea>

        <button class="cReports-btn" id="sendBtn" onclick="saveNotes()" disabled>
          <i class="fa fa-paper-plane"></i> Send to Student
        </button>

        <div class="cReports-status" id="notesStatus"></div>
      </div>
    </div>

    <!-- ── CARD 2: SENT NOTES ── -->
    <div class="cReports-card">

      <div style="display:flex; justify-content:space-between; align-items:center;">
        <h2 style="margin:0; display:flex; align-items:center; gap:8px;">
          Sent Notes
        </h2>
        <button onclick="toggleSentNotes()" id="toggleSentBtn" class="cReports-toggle-btn">
          <i class="fa fa-chevron-down"></i> Show
        </button>
      </div>

      <div id="sentNotesWrapper" style="display:none; margin-top:16px;">
        <p style="font-size:13px; color:var(--text-muted); margin:0 0 12px;">
          <?= count($sentNotes) ?> note<?= count($sentNotes) !== 1 ? 's' : '' ?> sent
        </p>

        <?php if (!empty($sentNotes)): ?>
          <ul class="cReports-notes-list">
            <?php foreach ($sentNotes as $sn):
              $ref       = 'SN-' . str_pad($sn['note_id'], 5, '0', STR_PAD_LEFT);
              $snDate    = date('M d, Y', strtotime($sn['created_at']));
              $snTime    = date('g:i A',  strtotime($sn['created_at']));
              $preview   = mb_strimwidth(strip_tags($sn['notes']), 0, 120, '…');
              $jsRef     = json_encode($ref);
              $jsStudent = json_encode($sn['student_name']);
              $jsMeta    = json_encode($sn['year_level'] . ' — ' . $sn['course']);
              $jsDate    = json_encode(date('F d, Y g:i A', strtotime($sn['created_at'])));
              $jsNotes   = json_encode($sn['notes']);
            ?>
            <li class="cReports-notes-item">

              <div class="cReports-notes-thumb">
                <i class="fa fa-file-medical"></i>
              </div>

              <div class="cReports-notes-body">
                <p class="cReports-notes-ref"><?= $ref ?></p>
                <p class="cReports-notes-student">
                  <i class="fa fa-user-graduate"></i>
                  <?= htmlspecialchars($sn['student_name']) ?>
                  &mdash; <?= htmlspecialchars($sn['year_level']) ?> &bull; <?= htmlspecialchars($sn['course']) ?>
                </p>
                <p class="cReports-notes-preview"><?= htmlspecialchars($preview) ?></p>
                <div class="cReports-notes-meta">
                  <span><i class="fa fa-calendar"></i><?= $snDate ?></span>
                  <span><i class="fa fa-clock"></i><?= $snTime ?></span>
                </div>
              </div>

              <div class="cReports-notes-actions">
                <button class="cReports-notes-btn-view"
                  data-ref=<?= $jsRef ?>
                  data-student=<?= $jsStudent ?>
                  data-meta='<?= htmlspecialchars($sn['year_level'] . ' — ' . $sn['course'], ENT_QUOTES) ?>'
                  data-date=<?= $jsDate ?>
                  data-notes=<?= $jsNotes ?>
                  onclick="viewNote(this)">
                  <i class="fa fa-eye"></i> View
                </button>
              </div>

            </li>
            <?php endforeach; ?>
          </ul>

        <?php else: ?>
          <div class="cReports-notes-empty">
            <i class="fa fa-notes-medical"></i>
            <p>No session notes sent yet.</p>
          </div>
        <?php endif; ?>
      </div>

    </div>
    <!-- ── END CARD 2 ── -->

  </div>
</main>

<!-- VIEW NOTE MODAL -->
<div class="cReports-notes-modal-overlay" id="viewNoteModal" onclick="closeViewNoteModal(event)">
  <div class="cReports-notes-modal-box">
    <button class="cReports-notes-modal-close" onclick="closeViewNoteModalDirect()">&#x2715;</button>
    <p class="cReports-notes-modal-ref" id="vnRef"></p>
    <h3 class="cReports-notes-modal-title" id="vnStudent"></h3>
    <p class="cReports-notes-modal-message" id="vnMeta" style="font-size:12px; margin:0;"></p>
    <p class="cReports-notes-modal-message" id="vnNotes"></p>
    <div class="cReports-notes-modal-footer">
      <span id="vnDate"></span>
    </div>
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
(function() {
  const saved = localStorage.getItem("theme") || "light";
  document.documentElement.setAttribute("data-theme", saved);
})();

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

// ── Student ID Lookup ──
let lookupTimer  = null;
let validStudent = false;

function lookupStudent(val) {
  const preview  = document.getElementById('studentPreview');
  const notFound = document.getElementById('notFound');
  const sendBtn  = document.getElementById('sendBtn');

  preview.classList.remove('visible');
  notFound.classList.remove('visible');
  validStudent     = false;
  sendBtn.disabled = true;

  const id = val.trim();
  if (!id || id.length < 3) return;

  clearTimeout(lookupTimer);
  lookupTimer = setTimeout(() => {
    fetch(`creports.php?action=lookup_student&student_id=${encodeURIComponent(id)}`)
      .then(r => r.json())
      .then(json => {
        if (json.found) {
          document.getElementById('studentName').textContent = json.name;
          document.getElementById('studentMeta').textContent = json.year_level + ' — ' + json.course;
          document.getElementById('studentAvatar').src =
            'https://ui-avatars.com/api/?name=' + encodeURIComponent(json.name) + '&background=113f67&color=fff';
          preview.classList.add('visible');
          notFound.classList.remove('visible');
          validStudent     = true;
          sendBtn.disabled = false;
        } else {
          notFound.classList.add('visible');
          preview.classList.remove('visible');
        }
      })
      .catch(() => { notFound.classList.add('visible'); });
  }, 500);
}

// ── Save / Send Notes ──
function saveNotes() {
  const status    = document.getElementById('notesStatus');
  const notes     = document.getElementById('notesTextarea').value.trim();
  const studentId = document.getElementById('studentIdInput').value.trim();
  const sendBtn   = document.getElementById('sendBtn');

  if (!validStudent) {
    status.innerHTML = "<span style='color:#fca5a5;'>⚠ Please enter a valid Student ID.</span>";
    return;
  }
  if (!notes) {
    status.innerHTML = "<span style='color:#fca5a5;'>⚠ Please write your notes first.</span>";
    return;
  }

  sendBtn.disabled  = true;
  sendBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';

  const fd = new FormData();
  fd.append('action',     'save_notes');
  fd.append('notes',      notes);
  fd.append('student_id', studentId);

  fetch('creports.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      sendBtn.disabled  = false;
      sendBtn.innerHTML = '<i class="fa fa-paper-plane"></i> Send to Student';

      if (json.success) {
        status.innerHTML = "<span style='color:#4ade80;'>✔ Note sent successfully.</span>";
        document.getElementById('notesTextarea').value  = '';
        document.getElementById('studentIdInput').value = '';
        document.getElementById('studentPreview').classList.remove('visible');
        document.getElementById('notFound').classList.remove('visible');
        validStudent     = false;
        sendBtn.disabled = true;
        setTimeout(() => location.reload(), 1200);
      } else {
        status.innerHTML = `<span style='color:#fca5a5;'>❌ ${json.message}</span>`;
      }
    })
    .catch(() => {
      sendBtn.disabled  = false;
      sendBtn.innerHTML = '<i class="fa fa-paper-plane"></i> Send to Student';
      status.innerHTML  = "<span style='color:#fca5a5;'>❌ Something went wrong.</span>";
    });
}

// ── Toggle Sent Notes ──
function toggleSentNotes() {
  const wrapper  = document.getElementById('sentNotesWrapper');
  const btn      = document.getElementById('toggleSentBtn');
  const isHidden = wrapper.style.display === 'none';
  wrapper.style.display = isHidden ? 'block' : 'none';
  btn.innerHTML = isHidden
    ? '<i class="fa fa-chevron-up"></i> Hide'
    : '<i class="fa fa-chevron-down"></i> Show';
}

// ── View Note Modal ──
function viewNote(btn) {
  document.getElementById('vnRef').textContent     = btn.dataset.ref;
  document.getElementById('vnStudent').textContent = btn.dataset.student;
  document.getElementById('vnMeta').textContent    = btn.dataset.meta;
  document.getElementById('vnNotes').textContent   = btn.dataset.notes;
  document.getElementById('vnDate').textContent    = '📅 ' + btn.dataset.date;
  document.getElementById('viewNoteModal').classList.add('show');
}
function closeViewNoteModalDirect() {
  document.getElementById('viewNoteModal').classList.remove('show');
}
function closeViewNoteModal(e) {
  if (e.target === document.getElementById('viewNoteModal')) closeViewNoteModalDirect();
}
</script>
</body>
</html>
<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counselor') {
    header("Location: slogin.php");
    exit;
}

// ── DB CONNECTION ──────────────────────────────────────────────────────────────
// If you get a localhost error:
//   1. Make sure XAMPP/WAMP is running (Apache + MySQL).
//   2. Import gcs_db__7_.sql via phpMyAdmin.
//   3. Create the MySQL user:
//        CREATE USER 'System_User'@'localhost' IDENTIFIED BY 'gcs_db2026';
//        GRANT ALL PRIVILEGES ON gcs_db.* TO 'System_User'@'localhost';
//        FLUSH PRIVILEGES;
//   OR change the credentials below to match your local setup (e.g. root / "").
$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");
if ($conn->connect_error) {
    // Show a friendly error instead of a blank page
    die('<!DOCTYPE html><html><body style="font-family:sans-serif;padding:40px;">
        <h2 style="color:#c0392b;">Database connection failed</h2>
        <p><strong>Error:</strong> ' . htmlspecialchars($conn->connect_error) . '</p>
        <p>Make sure XAMPP/WAMP is running and the database <code>gcs_db</code> has been imported.<br>
        Then ensure the user <code>System_User</code> exists with password <code>gcs_db2026</code>.</p>
    </body></html>');
}

$cid = $conn->real_escape_string($_SESSION['user_id']);

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

// ── AJAX: Add availability slot ────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_slot') {
    header('Content-Type: application/json');
    $dow  = (int)($_POST['day_of_week'] ?? -1);
    $time = $conn->real_escape_string($_POST['slot_time'] ?? '');
    if ($dow < 0 || $dow > 6 || !$time) {
        echo json_encode(['success' => false, 'message' => 'Invalid data.']); exit;
    }
    $ok = $conn->query("
        INSERT IGNORE INTO counselor_availability (counselor_id, day_of_week, slot_time, is_active)
        VALUES ($cid, $dow, '$time', 1)
    ");
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

// ── AJAX: Remove availability slot ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_slot') {
    header('Content-Type: application/json');
    $avid = (int)($_POST['availability_id'] ?? 0);
    if (!$avid) { echo json_encode(['success' => false]); exit; }
    $ok = $conn->query("DELETE FROM counselor_availability WHERE availability_id=$avid AND counselor_id=$cid");
    echo json_encode(['success' => (bool)$ok]);
    exit;
}

// ── Load this counselor's availability ────────────────────────────────────────
$avRes = $conn->query("
    SELECT availability_id, day_of_week, slot_time
    FROM counselor_availability
    WHERE counselor_id=$cid AND is_active=1
    ORDER BY day_of_week, slot_time
");
$availability = [];
while ($r = $avRes->fetch_assoc()) {
    $availability[$r['day_of_week']][] = $r;
}

$dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Time Availability – UNITYCARE</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
    <a href="cavailability.php" class="active"><i class="fa fa-clock"></i> Time Availability</a>
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

<!-- ══════════════════ TOPBAR ══════════════════ -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Time Availability</h2>
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

<!-- ══════════════════ MAIN CONTENT ══════════════════ -->
<main class="cAvail-main">
  <div class="cAvail-card">
    <h3 style="margin-bottom:6px; font-size:18px;">
      <i class="fa fa-clock" style="color:var(--primary);margin-right:8px;"></i>Weekly Time Slots
    </h3>

    <div class="cAvail-banner">
      <i class="fa fa-circle-info"></i>
      <span>Set the times you are available each week. Students will only see these slots when booking an appointment with you.</span>
    </div>

    <div class="cAvail-grid" id="availGrid">
      <?php foreach (range(1, 5) as $dow): // Mon–Fri only ?>
      <?php $slotCount = count($availability[$dow] ?? []); ?>
      <div class="cAvail-day" id="day-<?= $dow ?>">
        <h4>
          <i class="fa fa-calendar-day"></i>
          <?= $dayNames[$dow] ?>
          <span class="cAvail-count" id="count-<?= $dow ?>"><?= $slotCount ?></span>
        </h4>
        <div class="cAvail-slot-list" id="slotList-<?= $dow ?>">
          <?php if (!empty($availability[$dow])): ?>
            <?php foreach ($availability[$dow] as $slot): ?>
              <div class="cAvail-slot" id="slot-<?= $slot['availability_id'] ?>">
                <span>
                  <i class="fa fa-clock" style="font-size:11px;opacity:0.6;margin-right:5px;"></i>
                  <?= date('g:i A', strtotime($slot['slot_time'])) ?>
                </span>
                <button title="Remove slot"
                        onclick="confirmRemove(<?= $slot['availability_id'] ?>, <?= $dow ?>, '<?= date('g:i A', strtotime($slot['slot_time'])) ?>')">
                  <i class="fa fa-times"></i>
                </button>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <span class="cAvail-empty" id="empty-<?= $dow ?>">No slots added yet</span>
          <?php endif; ?>
        </div>
        <div class="cAvail-add">
          <input type="time" id="newTime-<?= $dow ?>" step="1800" title="Select time">
          <button onclick="confirmAdd(<?= $dow ?>)"><i class="fa fa-plus"></i> Add</button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</main>

<!-- ══════════════════ LOGOUT MODAL ══════════════════ -->
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

<!-- ══════════════════ AVAILABILITY MODALS ══════════════════ -->

<!-- Alert modal (for errors / info) -->
<div class="avail-modal-overlay" id="alertModal">
  <div class="avail-modal-box">
    <div class="avail-modal-icon info"><i class="fa fa-circle-info"></i></div>
    <div class="avail-modal-title" id="alertTitle">Notice</div>
    <div class="avail-modal-msg" id="alertMsg"></div>
    <div class="avail-modal-actions">
      <button class="avail-modal-btn ok" onclick="closeModal('alertModal')">OK</button>
    </div>
  </div>
</div>

<!-- Confirm remove modal -->
<div class="avail-modal-overlay" id="removeModal">
  <div class="avail-modal-box">
    <div class="avail-modal-icon warning"><i class="fa fa-trash-can"></i></div>
    <div class="avail-modal-title">Remove Time Slot</div>
    <div class="avail-modal-msg" id="removeModalMsg">Are you sure you want to remove this slot?</div>
    <div class="avail-modal-actions">
      <button class="avail-modal-btn cancel" onclick="closeModal('removeModal')">Cancel</button>
      <button class="avail-modal-btn confirm" id="removeConfirmBtn">Remove</button>
    </div>
  </div>
</div>

<!-- Confirm add modal -->
<div class="avail-modal-overlay" id="addModal">
  <div class="avail-modal-box">
    <div class="avail-modal-icon info"><i class="fa fa-clock"></i></div>
    <div class="avail-modal-title">Add Time Slot</div>
    <div class="avail-modal-msg" id="addModalMsg">Add this slot to your availability?</div>
    <div class="avail-modal-actions">
      <button class="avail-modal-btn cancel" onclick="closeModal('addModal')">Cancel</button>
      <button class="avail-modal-btn ok" id="addConfirmBtn">Add Slot</button>
    </div>
  </div>
</div>

<script>

(function() {
    const saved = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", saved);
})();
// ── Theme / settings ────────────────────────────────────────────────────────
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
function logout()      { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout() { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=counselor'; }
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeLogout();
});

function toggleDropdown(id, e) {
    e.stopPropagation();
    document.getElementById(id).classList.toggle("show");
}
document.addEventListener("click", e => {
    const n = document.getElementById("notifDropdown");
    if (n && !n.contains(e.target)) n.classList.remove("show");
});

// ── Modal helpers ────────────────────────────────────────────────────────────
function openModal(id) {
    document.getElementById(id).classList.add('show');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}
function showAlert(title, msg) {
    document.getElementById('alertTitle').textContent = title;
    document.getElementById('alertMsg').textContent   = msg;
    openModal('alertModal');
}

// Close modals when clicking the overlay background
['alertModal','removeModal','addModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});

// ── Badge count helper ───────────────────────────────────────────────────────
function updateCount(dow) {
    const list  = document.getElementById(`slotList-${dow}`);
    const badge = document.getElementById(`count-${dow}`);
    if (list && badge) {
        badge.textContent = list.querySelectorAll('.cAvail-slot').length;
    }
}

// ── ADD: open confirmation modal ─────────────────────────────────────────────
function confirmAdd(dow) {
    const input = document.getElementById(`newTime-${dow}`);
    const t = input.value;
    if (!t) { showAlert('No time selected', 'Please pick a time before adding a slot.'); return; }

    // Format for display
    const [h, m]  = t.split(':');
    const hour    = parseInt(h);
    const suffix  = hour >= 12 ? 'PM' : 'AM';
    const display = `${hour % 12 || 12}:${m} ${suffix}`;

    const dayNames = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    document.getElementById('addModalMsg').textContent =
        `Add ${display} to your ${dayNames[dow]} availability?`;

    const btn = document.getElementById('addConfirmBtn');
    // Remove any old listener before attaching a fresh one
    btn.replaceWith(btn.cloneNode(true));
    document.getElementById('addConfirmBtn').addEventListener('click', () => {
        closeModal('addModal');
        doAddSlot(dow, t);
    });

    openModal('addModal');
}

function doAddSlot(dow, t) {
    const fd = new FormData();
    fd.append('action',      'add_slot');
    fd.append('day_of_week', dow);
    fd.append('slot_time',   t);

    fetch('cavailability.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                location.reload();
            } else {
                showAlert('Could not add slot', json.message || 'This slot may already exist.');
            }
        })
        .catch(() => showAlert('Network error', 'Please check your connection and try again.'));
}

// ── REMOVE: open confirmation modal ──────────────────────────────────────────
function confirmRemove(avid, dow, timeLabel) {
    document.getElementById('removeModalMsg').textContent =
        `Remove the ${timeLabel} slot from your availability?`;

    const btn = document.getElementById('removeConfirmBtn');
    btn.replaceWith(btn.cloneNode(true));
    document.getElementById('removeConfirmBtn').addEventListener('click', () => {
        closeModal('removeModal');
        doRemoveSlot(avid, dow);
    });

    openModal('removeModal');
}

function doRemoveSlot(avid, dow) {
    const fd = new FormData();
    fd.append('action',          'remove_slot');
    fd.append('availability_id', avid);

    fetch('cavailability.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                const el = document.getElementById(`slot-${avid}`);
                if (el) {
                    el.style.opacity   = '0';
                    el.style.transform = 'translateX(10px)';
                    el.style.transition = '0.2s ease';
                    setTimeout(() => {
                        el.remove();
                        const list = document.getElementById(`slotList-${dow}`);
                        if (list && list.querySelectorAll('.cAvail-slot').length === 0) {
                            const empty = document.createElement('span');
                            empty.className = 'cAvail-empty';
                            empty.id        = `empty-${dow}`;
                            empty.textContent = 'No slots added yet';
                            list.appendChild(empty);
                        }
                        updateCount(dow);
                    }, 200);
                }
            } else {
                showAlert('Could not remove slot', 'Please try again.');
            }
        })
        .catch(() => showAlert('Network error', 'Please check your connection and try again.'));
}
</script>
</body>
</html>
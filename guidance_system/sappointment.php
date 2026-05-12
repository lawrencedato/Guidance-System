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
$sid  = $conn->real_escape_string($_SESSION['user_id']);

$studentRes = $conn->query("SELECT * FROM students WHERE student_id='$sid' LIMIT 1");
$student    = $studentRes->fetch_assoc();

$profileRes = $conn->query("SELECT profile_image FROM student_profiles WHERE student_id='$sid' LIMIT 1");
$profile    = $profileRes->fetch_assoc();

$fullName   = htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$email      = htmlspecialchars($student['email'] ?? '');
$profileImg = !empty($profile['profile_image'])
              ? htmlspecialchars($profile['profile_image'])
              : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';

// ── AJAX: get available slots for a counselor + date ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_slots') {
    header('Content-Type: application/json');
    $cid  = (int)($_GET['counselor_id'] ?? 0);
    $date = $conn->real_escape_string($_GET['date'] ?? '');
    if (!$cid || !$date) { echo json_encode(['slots' => []]); exit; }

    $dow = (int)date('w', strtotime($date));

    $res = $conn->query("
        SELECT slot_time FROM counselor_availability
        WHERE counselor_id = $cid AND day_of_week = $dow AND is_active = 1
        ORDER BY slot_time
    ");
    $allSlots = [];
    while ($r = $res->fetch_assoc()) $allSlots[] = $r['slot_time'];

    $bookedRes = $conn->query("
        SELECT appointment_time FROM appointments
        WHERE counselor_id = $cid
          AND appointment_date = '$date'
          AND status IN ('Pending','Approved')
    ");
    $booked = [];
    while ($r = $bookedRes->fetch_assoc()) $booked[] = $r['appointment_time'];

    $slots = [];
    foreach ($allSlots as $t) {
        $slots[] = ['time' => $t, 'taken' => in_array($t, $booked)];
    }
    echo json_encode(['slots' => $slots]);
    exit;
}

// ── AJAX: book appointment ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book') {
    header('Content-Type: application/json');
    $cid      = (int)($_POST['counselor_id'] ?? 0);
    $date     = $conn->real_escape_string($_POST['date']     ?? '');
    $time     = $conn->real_escape_string($_POST['time']     ?? '');
    $message  = $conn->real_escape_string($_POST['message']  ?? '');
    $priority = $conn->real_escape_string($_POST['priority'] ?? 'Low');

    if (!$cid || !$date || !$time) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']); exit;
    }

    $cCheck = $conn->query("SELECT counselor_id FROM counselors WHERE counselor_id=$cid AND status='Active' LIMIT 1");
    if (!$cCheck || $cCheck->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Selected counselor is not available.']); exit;
    }

    $conn->begin_transaction();
    try {
        $lock = $conn->query("
            SELECT appointment_id FROM appointments
            WHERE counselor_id=$cid AND appointment_date='$date' AND appointment_time='$time'
              AND status IN ('Pending','Approved')
            FOR UPDATE
        ");
        if ($lock && $lock->num_rows > 0) throw new Exception('That slot was just taken. Please choose another.');

        $ok = $conn->query("
            INSERT INTO appointments (student_id, counselor_id, appointment_date, appointment_time, message, priority, status, created_at)
            VALUES ('$sid', $cid, '$date', '$time', '$message', '$priority', 'Pending', NOW())
        ");
        if (!$ok) throw new Exception($conn->error);
        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ── Load counselors for the grid ──
$counselorRes = $conn->query("
    SELECT counselor_id, first_name, last_name, department
    FROM counselors WHERE status='Active' AND archived=0
    ORDER BY last_name
");
$counselors = [];
while ($c = $counselorRes->fetch_assoc()) $counselors[] = $c;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UNITYCARE | Appointment Booking</title>
  <link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>

  
/* ─────────────────────────────────────────
   SIDEBAR MENU LINK LAYOUT
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
        <a href="shistory.php"><i class="fa fa-clock"></i> History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>
  </div>
  <nav class="sidebar-menu">
    <a href="dashboard.php"><i class="fa fa-th-large"></i> Dashboard</a>
    <p class="sidebar-title">SERVICES</p>
    <a href="sappointment.php" class="active"><i class="fa fa-calendar"></i> Book Appointment</a>
    <a href="sconcerns.php"><i class="fa fa-headset"></i> Submit Concern</a>
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

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left"><h2>Book Appointment</h2></div>
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
<div class="sBooking-wrap">

  <!-- Booking Card -->
  <div class="sBooking-card">
    <h3>Schedule Appointment</h3>

    <div class="sBooking-columns">

      <!-- COL 1: Choose Counselor -->
      <div>
        <div class="sBooking-section-label">
          <span class="sBooking-section-num">1</span> Choose a Counselor
        </div>
        <div class="sBooking-counselor-grid">
          <?php foreach ($counselors as $c):
            $cName = htmlspecialchars($c['first_name'] . ' ' . $c['last_name']);
            $cDept = htmlspecialchars($c['department']);
            $cImg  = 'c_' . $c['counselor_id'] . '.jpg';
          ?>
          <div class="sBooking-counselor-option"
               data-id="<?= $c['counselor_id'] ?>"
               onclick="selectCounselor(this, <?= $c['counselor_id'] ?>)">
            <img src="<?= $cImg ?>"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($cName) ?>&background=113f67&color=fff'"
                 alt="<?= $cName ?>">
            <div class="sBooking-counselor-info">
              <strong><?= $cName ?></strong>
              <span><?= $cDept ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- COL 2: Date & Slots -->
      <div class="sBooking-col-divider">
        <div class="sBooking-section-label">
          <span class="sBooking-section-num">2</span> Pick a Date
        </div>
        <input type="date" id="date" class="sBooking-date-input">

        <div class="sBooking-section-label" style="margin-top:4px;">
          <span class="sBooking-section-num">3</span> Available Slots
        </div>
        <div id="slotsWrap">
          <p class="sBooking-slots-hint">Select a counselor and date to see available slots.</p>
        </div>
        <p id="noSlots" style="display:none; font-size:13px; color:var(--text-muted); margin-top:6px;">
          No available slots for this day. Try a different date.
        </p>
      </div>

      <!-- COL 3: Details -->
      <div class="sBooking-col-divider">
        <div class="sBooking-section-label">
          <span class="sBooking-section-num">4</span> Appointment Details
        </div>

        <div class="sBooking-field">
          <label>Selected Time</label>
          <input type="text" id="timeDisplay" readonly placeholder="No slot selected yet">
          <input type="hidden" id="time">
        </div>

        <div class="sBooking-field">
          <label>Priority</label>
          <select id="priority">
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
          </select>
        </div>

        <div class="sBooking-field">
          <label>Message</label>
          <textarea id="message" placeholder="Describe your concern..."></textarea>
        </div>

        <button class="sBooking-submit" onclick="bookAppointment()">
          <i class="fa fa-calendar-check" style="margin-right:6px;"></i> Confirm Booking
        </button>
        <div id="bookingResult"></div>
      </div>

    </div>
  </div>

  <!-- Upload Card -->
  <div class="sBooking-upload-card">
    <h3>Upload Documents</h3>
    <p>You may upload supporting documents for your appointment.</p>
    <input type="file" id="fileInput">
    <p id="fileName" style="font-size:12px; margin-top:8px;"></p>
    <button class="sBooking-upload-btn">Upload File</button>
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
function logout() { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout() { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=student'; }
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeLogout();
});
document.addEventListener("click", e => {
    const menu = document.getElementById("settingsDropdown");
    const btn  = document.querySelector(".sidebar-settingsButton");
    if (!menu.contains(e.target) && !btn.contains(e.target)) menu.classList.remove("show");
});

// ── State ──
let selectedCounselorId = null;

// Set min date to today
document.addEventListener('DOMContentLoaded', () => {
    const today = new Date();
    const yyyy  = today.getFullYear();
    const mm    = String(today.getMonth() + 1).padStart(2, '0');
    const dd    = String(today.getDate()).padStart(2, '0');
    document.getElementById('date').min = `${yyyy}-${mm}-${dd}`;

    document.getElementById('date').addEventListener('change', function () {
        if (!selectedCounselorId || !this.value) return;
        loadSlots(selectedCounselorId, this.value);
    });
});

function selectCounselor(el, cid) {
    document.querySelectorAll('.sBooking-counselor-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    selectedCounselorId = cid;

    document.getElementById('time').value        = '';
    document.getElementById('timeDisplay').value = '';
    document.getElementById('bookingResult').innerHTML = '';

    const dateVal = document.getElementById('date').value;
    if (dateVal) {
        loadSlots(cid, dateVal);
    } else {
        document.getElementById('slotsWrap').innerHTML =
            '<p class="sBooking-slots-hint">Now pick a date to see available slots.</p>';
    }
}

function loadSlots(cid, date) {
    const wrap    = document.getElementById('slotsWrap');
    const noSlots = document.getElementById('noSlots');
    wrap.innerHTML = '<span class="sBooking-slots-loading"><i class="fa fa-spinner fa-spin"></i> Loading slots…</span>';
    noSlots.style.display = 'none';
    document.getElementById('time').value        = '';
    document.getElementById('timeDisplay').value = '';

    fetch(`sappointment.php?action=get_slots&counselor_id=${cid}&date=${date}`)
        .then(r => r.json())
        .then(json => {
            wrap.innerHTML = '';
            if (!json.slots || json.slots.length === 0) {
                noSlots.style.display = '';
                return;
            }
            const container = document.createElement('div');
            container.className = 'sBooking-slots-wrap';
            json.slots.forEach(s => {
                const btn = document.createElement('button');
                btn.className = 'sBooking-slotBtn' + (s.taken ? ' taken' : '');
                btn.textContent = formatTime(s.time) + (s.taken ? ' — Taken' : '');
                btn.disabled = s.taken;
                if (!s.taken) {
                    btn.onclick = () => {
                        document.querySelectorAll('.sBooking-slotBtn').forEach(b => b.classList.remove('chosen'));
                        btn.classList.add('chosen');
                        document.getElementById('time').value        = s.time.substring(0, 5);
                        document.getElementById('timeDisplay').value = formatTime(s.time);
                    };
                }
                container.appendChild(btn);
            });
            wrap.appendChild(container);
        })
        .catch(() => {
            wrap.innerHTML = '<em style="font-size:13px;color:var(--error,#e53e3e);">Failed to load slots.</em>';
        });
}

function formatTime(t) {
    let [h, m] = t.split(':');
    const hr   = +h;
    const ampm = hr >= 12 ? 'PM' : 'AM';
    const disp = (hr % 12 || 12);
    return `${disp}:${m} ${ampm}`;
}

function bookAppointment() {
    const d        = document.getElementById('date').value;
    const t        = document.getElementById('time').value;
    const msg      = document.getElementById('message').value.trim();
    const priority = document.getElementById('priority').value;
    const result   = document.getElementById('bookingResult');

    if (!selectedCounselorId) {
        result.innerHTML = "<span style='color:var(--error,#e53e3e);'>⚠ Please select a counselor.</span>"; return;
    }
    if (!d) {
        result.innerHTML = "<span style='color:var(--error,#e53e3e);'>⚠ Please pick a date.</span>"; return;
    }
    if (!t) {
        result.innerHTML = "<span style='color:var(--error,#e53e3e);'>⚠ Please select a time slot.</span>"; return;
    }

    const fd = new FormData();
    fd.append('action',       'book');
    fd.append('counselor_id', selectedCounselorId);
    fd.append('date',         d);
    fd.append('time',         t);
    fd.append('message',      msg);
    fd.append('priority',     priority);

    fetch('sappointment.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            result.innerHTML = json.success
                ? "<span style='color:var(--success,#15803d);'>✔ Appointment submitted successfully!</span>"
                : "<span style='color:var(--error,#e53e3e);'>❌ " + (json.message || 'Failed.') + "</span>";
            if (json.success) {
                document.querySelectorAll('.sBooking-counselor-option').forEach(o => o.classList.remove('selected'));
                selectedCounselorId = null;
                document.getElementById('date').value        = '';
                document.getElementById('time').value        = '';
                document.getElementById('timeDisplay').value = '';
                document.getElementById('message').value     = '';
                document.getElementById('slotsWrap').innerHTML =
                    '<p class="sBooking-slots-hint">Select a counselor and date to see available slots.</p>';
                document.getElementById('noSlots').style.display = 'none';
                setTimeout(() => result.innerHTML = '', 5000);
            }
        })
        .catch(() => {
            result.innerHTML = "<span style='color:var(--error,#e53e3e);'>❌ Something went wrong.</span>";
        });
}

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
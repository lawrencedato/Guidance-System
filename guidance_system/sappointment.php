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
/* ── Layout ── */
.sAppt-wrap {
  margin-left: 280px;
  padding: 36px 40px;
  background: var(--bg);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  gap: 24px;
}

/* ── Main booking card ── */
.sAppt-card {
  position: relative;
  padding: 32px;
  border-radius: 20px;
  border: 1px solid var(--border);
  background: rgba(255,255,255,0.75);
  backdrop-filter: blur(14px);
  box-shadow: 0 10px 30px rgba(0,0,0,0.08);
  overflow: hidden;
}
.sAppt-card::before {
  content:"";
  position:absolute; inset:0;
  background: radial-gradient(circle at top left, rgba(73,136,196,0.15), transparent 60%);
  pointer-events:none;
}

.sAppt-card h3 {
  font-size: 20px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 24px;
}

/* ── Three-column layout ── */
.sAppt-columns {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 24px;
  align-items: start;
}

/* ── Section headers ── */
.sAppt-section-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 700;
  color: var(--primary);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-bottom: 14px;
}
.sAppt-section-num {
  width: 22px; height: 22px;
  border-radius: 50%;
  background: var(--primary);
  color: white;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; flex-shrink: 0;
}

/* ── Counselor cards ── */
.sCounselor-grid {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.sCounselor-option {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border-radius: 12px;
  border: 2px solid var(--border);
  background: rgba(255,255,255,0.5);
  cursor: pointer;
  transition: 0.2s ease;
}
.sCounselor-option:hover {
  border-color: var(--primary);
  background: rgba(73,136,196,0.07);
  transform: translateX(3px);
}
.sCounselor-option.selected {
  border-color: var(--primary);
  background: rgba(73,136,196,0.12);
  box-shadow: 0 0 0 3px rgba(73,136,196,0.15);
}
.sCounselor-option img {
  width: 46px; height: 46px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid var(--border);
  flex-shrink: 0;
}
.sCounselor-option-info strong {
  display: block;
  font-size: 13px;
  color: var(--text);
  line-height: 1.3;
}
.sCounselor-option-info span {
  font-size: 11px;
  color: var(--text-muted);
  background: var(--bg-soft);
  padding: 2px 8px;
  border-radius: 999px;
  display: inline-block;
  margin-top: 3px;
}

/* ── Date & Slots column ── */
.sAppt-date-input {
  width: 100%;
  padding: 12px 14px;
  border-radius: 12px;
  border: 1px solid var(--border);
  background: rgba(255,255,255,0.6);
  font-size: 14px;
  color: var(--text);
  outline: none;
  transition: 0.2s;
  margin-bottom: 20px;
}
.sAppt-date-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(73,136,196,0.15);
}

/* ── Slots ── */
.sAppt-slots-wrap {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.sAppt-slotBtn {
  padding: 8px 14px;
  border-radius: 10px;
  border: 1px solid var(--border);
  background: rgba(255,255,255,0.6);
  font-size: 13px;
  cursor: pointer;
  transition: 0.2s ease;
  color: var(--text);
}
.sAppt-slotBtn:hover:not(:disabled) {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
  transform: translateY(-1px);
}
.sAppt-slotBtn.chosen {
  background: var(--primary);
  color: white;
  border-color: var(--primary);
}
.sAppt-slotBtn.taken {
  opacity: 0.4;
  cursor: not-allowed;
  text-decoration: line-through;
}
.sAppt-slots-hint {
  font-size: 12px;
  color: var(--text-muted);
  font-style: italic;
}

/* ── Details column ── */
.sAppt-field {
  margin-bottom: 14px;
}
.sAppt-field label {
  display: block;
  font-size: 13px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 6px;
}
.sAppt-field input,
.sAppt-field select,
.sAppt-field textarea {
  width: 100%;
  padding: 11px 14px;
  border-radius: 12px;
  border: 1px solid var(--border);
  background: rgba(255,255,255,0.6);
  font-size: 14px;
  color: var(--text);
  outline: none;
  transition: 0.2s;
}
.sAppt-field input:focus,
.sAppt-field select:focus,
.sAppt-field textarea:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(73,136,196,0.15);
}
.sAppt-field textarea {
  min-height: 110px;
  resize: vertical;
}
.sAppt-field input[readonly] {
  background: var(--bg-soft);
  color: var(--text-muted);
  cursor: default;
}

/* ── Submit button ── */
.sAppt-submit {
  width: 100%;
  padding: 13px;
  margin-top: 4px;
  border-radius: 12px;
  border: none;
  font-size: 14px;
  font-weight: 600;
  color: white;
  cursor: pointer;
  background: linear-gradient(135deg, #113F67, #4988C4);
  box-shadow: 0 10px 20px rgba(17,63,103,0.25);
  transition: 0.2s ease;
}
.sAppt-submit:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 30px rgba(17,63,103,0.35);
}
#bookingResult { margin-top: 10px; font-size: 13px; }

/* ── Dividers between columns ── */
.sAppt-col-divider {
  border-left: 1px solid var(--border);
  padding-left: 24px;
}

/* ── Upload card ── */
.sAppt-upload-card {
  position: relative;
  padding: 28px 32px;
  border-radius: 20px;
  border: 1px solid var(--border);
  background: rgba(255,255,255,0.75);
  backdrop-filter: blur(14px);
  box-shadow: 0 10px 30px rgba(0,0,0,0.08);
  overflow: hidden;
}
.sAppt-upload-card::before {
  content:"";
  position:absolute; inset:0;
  background: radial-gradient(circle at top left, rgba(73,136,196,0.1), transparent 60%);
  pointer-events:none;
}
.sAppt-upload-card h3 {
  font-size: 16px;
  font-weight: 700;
  margin-bottom: 6px;
}
.sAppt-upload-card p {
  font-size: 13px;
  color: var(--text-muted);
  margin-bottom: 14px;
}

/* ── Slot loading state ── */
.sAppt-slots-loading {
  font-size: 13px;
  color: var(--text-muted);
  font-style: italic;
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
        <a href="shistory.php"><i class="fa fa-clock"></i> Session History</a>
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
    <a href="sreferral.php"><i class="fa fa-route"></i> Referral</a>
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
<div class="sAppt-wrap">

  <!-- Booking Card -->
  <div class="sAppt-card">
    <h3>Schedule Appointment</h3>

    <div class="sAppt-columns">

      <!-- COL 1: Choose Counselor -->
      <div>
        <div class="sAppt-section-label">
          <span class="sAppt-section-num">1</span> Choose a Counselor
        </div>
        <div class="sCounselor-grid">
          <?php foreach ($counselors as $c):
            $cName = htmlspecialchars($c['first_name'] . ' ' . $c['last_name']);
            $cDept = htmlspecialchars($c['department']);
            $cImg  = 'c_' . $c['counselor_id'] . '.jpg';
          ?>
          <div class="sCounselor-option"
               data-id="<?= $c['counselor_id'] ?>"
               onclick="selectCounselor(this, <?= $c['counselor_id'] ?>)">
            <img src="<?= $cImg ?>"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($cName) ?>&background=113f67&color=fff'"
                 alt="<?= $cName ?>">
            <div class="sCounselor-option-info">
              <strong><?= $cName ?></strong>
              <span><?= $cDept ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- COL 2: Date & Slots -->
      <div class="sAppt-col-divider">
        <div class="sAppt-section-label">
          <span class="sAppt-section-num">2</span> Pick a Date
        </div>
        <input type="date" id="date" class="sAppt-date-input">

        <div class="sAppt-section-label" style="margin-top:4px;">
          <span class="sAppt-section-num">3</span> Available Slots
        </div>
        <div id="slotsWrap">
          <p class="sAppt-slots-hint">Select a counselor and date to see available slots.</p>
        </div>
        <p id="noSlots" style="display:none; font-size:13px; color:var(--text-muted); margin-top:6px;">
          No available slots for this day. Try a different date.
        </p>
      </div>

      <!-- COL 3: Details -->
      <div class="sAppt-col-divider">
        <div class="sAppt-section-label">
          <span class="sAppt-section-num">4</span> Appointment Details
        </div>

        <div class="sAppt-field">
          <label>Selected Time</label>
          <input type="text" id="timeDisplay" readonly placeholder="No slot selected yet">
          <input type="hidden" id="time">
        </div>

        <div class="sAppt-field">
          <label>Priority</label>
          <select id="priority">
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
          </select>
        </div>

        <div class="sAppt-field">
          <label>Message</label>
          <textarea id="message" placeholder="Describe your concern..."></textarea>
        </div>

        <button class="sAppt-submit" onclick="bookAppointment()">
          <i class="fa fa-calendar-check" style="margin-right:6px;"></i> Confirm Booking
        </button>
        <div id="bookingResult"></div>
      </div>

    </div>
  </div>

  <!-- Upload Card -->
  <div class="sAppt-upload-card">
    <h3>Upload Documents</h3>
    <p>You may upload supporting documents for your appointment.</p>
    <input type="file" id="fileInput">
    <p id="fileName" style="font-size:12px; color:var(--text-muted); margin-top:8px;"></p>
    <button class="sBooking-button" style="width:auto; padding:10px 20px; margin-top:10px;">Upload File</button>
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
    const yyyy = today.getFullYear();
    const mm   = String(today.getMonth() + 1).padStart(2, '0');
    const dd   = String(today.getDate()).padStart(2, '0');
    document.getElementById('date').min = `${yyyy}-${mm}-${dd}`;

    document.getElementById('date').addEventListener('change', function () {
        if (!selectedCounselorId || !this.value) return;
        loadSlots(selectedCounselorId, this.value);
    });
});

function selectCounselor(el, cid) {
    document.querySelectorAll('.sCounselor-option').forEach(o => o.classList.remove('selected'));
    el.classList.add('selected');
    selectedCounselorId = cid;

    // Reset slot/time selections
    document.getElementById('time').value        = '';
    document.getElementById('timeDisplay').value = '';
    document.getElementById('bookingResult').innerHTML = '';

    const dateVal = document.getElementById('date').value;
    if (dateVal) {
        loadSlots(cid, dateVal);
    } else {
        document.getElementById('slotsWrap').innerHTML =
            '<p class="sAppt-slots-hint">Now pick a date to see available slots.</p>';
    }
}

function loadSlots(cid, date) {
    const wrap    = document.getElementById('slotsWrap');
    const noSlots = document.getElementById('noSlots');
    wrap.innerHTML = '<span class="sAppt-slots-loading"><i class="fa fa-spinner fa-spin"></i> Loading slots…</span>';
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
            container.className = 'sAppt-slots-wrap';
            json.slots.forEach(s => {
                const btn = document.createElement('button');
                btn.className = 'sAppt-slotBtn' + (s.taken ? ' taken' : '');
                btn.textContent = formatTime(s.time) + (s.taken ? ' — Taken' : '');
                btn.disabled = s.taken;
                if (!s.taken) {
                    btn.onclick = () => {
                        document.querySelectorAll('.sAppt-slotBtn').forEach(b => b.classList.remove('chosen'));
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
                document.querySelectorAll('.sCounselor-option').forEach(o => o.classList.remove('selected'));
                selectedCounselorId = null;
                document.getElementById('date').value        = '';
                document.getElementById('time').value        = '';
                document.getElementById('timeDisplay').value = '';
                document.getElementById('message').value     = '';
                document.getElementById('slotsWrap').innerHTML =
                    '<p class="sAppt-slots-hint">Select a counselor and date to see available slots.</p>';
                document.getElementById('noSlots').style.display = 'none';
                setTimeout(() => result.innerHTML = '', 5000);
            }
        })
        .catch(() => {
            result.innerHTML = "<span style='color:var(--error,#e53e3e);'>❌ Something went wrong.</span>";
        });
}
</script>
</body>
</html>
<?php
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: slogin.php");
    exit;
}

// archived students can only view history
if (!empty($_SESSION['is_archived'])) {
    header("Location: shistory.php");
    exit;
}

// ── DB Connection ──
$conn = @new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");
if ($conn->connect_error) {
    if (
        ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_slots') ||
        ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'book')
    ) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    } else {
        http_response_code(503);
        echo "Service temporarily unavailable.";
    }
    exit;
}

$conn->set_charset("utf8mb4");
$sid = $conn->real_escape_string((string)$_SESSION['user_id']);
require_once 'scheck_reports_badge.php';

// ── Load student info ──
$studentRes = $conn->query("SELECT * FROM students WHERE student_id='$sid' LIMIT 1");
$student    = $studentRes ? $studentRes->fetch_assoc() : [];

$profileRes = $conn->query("SELECT profile_image FROM student_profiles WHERE student_id='$sid' LIMIT 1");
$profile    = $profileRes ? $profileRes->fetch_assoc() : [];

$fullName   = htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')));
$email      = htmlspecialchars($student['email'] ?? '');
$profileImg = !empty($profile['profile_image'])
              ? htmlspecialchars($profile['profile_image'])
              : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff&size=128';

// ── AJAX: get available slots ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_slots') {
    header('Content-Type: application/json');
    $cid  = (int)($_GET['counselor_id'] ?? 0);
    $date = $conn->real_escape_string($_GET['date'] ?? '');

    if (!$cid || !$date) {
        echo json_encode(['slots' => []]);
        exit;
    }

    $ts  = strtotime($date);
    if (!$ts) { echo json_encode(['slots' => []]); exit; }
    $dow = (int)date('w', $ts);

    $res = $conn->query("
        SELECT slot_time FROM counselor_availability
        WHERE counselor_id = $cid AND day_of_week = $dow AND is_active = 1
        ORDER BY slot_time
    ");
    $allSlots = [];
    if ($res) while ($r = $res->fetch_assoc()) $allSlots[] = $r['slot_time'];

    $bookedRes = $conn->query("
        SELECT appointment_time FROM appointments
        WHERE counselor_id = $cid
          AND appointment_date = '$date'
          AND status IN ('Pending','Approved')
    ");
    $booked = [];
    if ($bookedRes) while ($r = $bookedRes->fetch_assoc()) $booked[] = $r['appointment_time'];

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
    $date     = $conn->real_escape_string($_POST['date']    ?? '');
    $time     = $conn->real_escape_string($_POST['time']    ?? '');
    $message  = $conn->real_escape_string($_POST['message'] ?? '');

    $allowedPriority = ['Low', 'Medium', 'High'];
    $priority = in_array($_POST['priority'] ?? '', $allowedPriority) ? $_POST['priority'] : 'Low';
    $priority = $conn->real_escape_string($priority);

    if (!$cid || !$date || !$time) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }

    $cCheck = $conn->query("SELECT counselor_id FROM counselors WHERE counselor_id=$cid AND status='Active' LIMIT 1");
    if (!$cCheck || $cCheck->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Selected counselor is not available.']);
        exit;
    }

    // ── Handle optional file upload ──
    $destPath = null;
    if (!empty($_FILES['document']['name'])) {
        $allowedTypes = ['application/pdf','application/msword',
                         'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                         'image/jpeg','image/png','image/gif','image/webp'];
        $maxSize = 10 * 1024 * 1024; // 10 MB

        $fileType = mime_content_type($_FILES['document']['tmp_name']);
        $fileSize = $_FILES['document']['size'];

        if (!in_array($fileType, $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: PDF, Word, JPG, PNG, GIF, WEBP.']);
            exit;
        }
        if ($fileSize > $maxSize) {
            echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size is 10 MB.']);
            exit;
        }

        $uploadDir = 'uploads/appointment_docs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $ext      = strtolower(pathinfo($_FILES['document']['name'], PATHINFO_EXTENSION));
        $filename = 'appt_' . $sid . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (!move_uploaded_file($_FILES['document']['tmp_name'], $destPath)) {
            echo json_encode(['success' => false, 'message' => 'File upload failed. Please try again.']);
            exit;
        }
    }

    $conn->begin_transaction();
    try {
        $lock = $conn->query("
            SELECT appointment_id FROM appointments
            WHERE counselor_id=$cid
              AND appointment_date='$date'
              AND appointment_time='$time'
              AND status IN ('Pending','Approved')
            FOR UPDATE
        ");
        if ($lock && $lock->num_rows > 0) {
            throw new Exception('That slot was just taken. Please choose another.');
        }

        $ok = $conn->query("
            INSERT INTO appointments
                (student_id, counselor_id, appointment_date, appointment_time, message, priority, status, created_at)
            VALUES
                ('$sid', $cid, '$date', '$time', '$message', '$priority', 'Pending', NOW())
        ");
        if (!$ok) throw new Exception('Booking failed. Please try again.');

        $newAppointmentId = $conn->insert_id;

        if (!empty($destPath)) {
            $safeFileName = $conn->real_escape_string(basename($destPath));
            $safeFilePath = $conn->real_escape_string($destPath);
            $fileOk = $conn->query("
                INSERT INTO appointment_files (appointment_id, file_name, file_path)
                VALUES ($newAppointmentId, '$safeFileName', '$safeFilePath')
            ");
            if (!$fileOk) throw new Exception('File record could not be saved. Please try again.');
        }

        $conn->commit();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        $conn->rollback();
        if (!empty($destPath) && file_exists($destPath)) {
            unlink($destPath);
        }
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// ── Load counselors ──
$counselorRes = $conn->query("
    SELECT counselor_id, first_name, last_name, department, profile_image
    FROM counselors
    WHERE status='Active' AND archived=0
    ORDER BY last_name
");
$counselors = [];
if ($counselorRes) while ($c = $counselorRes->fetch_assoc()) $counselors[] = $c;
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

    /* ── Upload zone inside booking card ── */
    .sBooking-upload-zone {
      border: 2px dashed var(--border, #d1d5db);
      border-radius: 10px;
      padding: 18px 16px;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
      background: var(--bg-soft, #f8fafc);
      position: relative;
    }
    .sBooking-upload-zone:hover,
    .sBooking-upload-zone.dragover {
      border-color: #4988C4;
      background: rgba(73,136,196,0.06);
    }
    .sBooking-upload-zone input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
      width: 100%;
      height: 100%;
    }
    .sBooking-upload-zone i {
      font-size: 22px;
      color: #4988C4;
      display: block;
      margin-bottom: 6px;
    }
    .sBooking-upload-zone p {
      font-size: 12px;
      color: var(--text-muted, #64748b);
      margin: 0;
    }
    .sBooking-upload-zone .upload-filename {
      margin-top: 8px;
      font-size: 12px;
      font-weight: 600;
      color: #113f67;
      display: none;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }
    .sBooking-upload-zone .upload-filename.visible {
      display: flex;
    }
    .sBooking-upload-zone .upload-clear {
      background: none;
      border: none;
      color: #ef4444;
      cursor: pointer;
      font-size: 13px;
      padding: 0;
      line-height: 1;
    }
    .sBooking-upload-label {
      font-size: 11px;
      color: var(--text-muted, #64748b);
      margin-top: 6px;
      display: block;
      text-align: center;
    }
  </style>
</head>
<body class="body">

<!-- SIDEBAR -->
<?php
$_totalReportUnseen = $_totalReportUnseen ?? 0;
?>
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
    <a href="sreports.php" class="<?= basename($_SERVER['PHP_SELF']) === 'sreports.php' ? 'active' : '' ?>">
      <i class="fa fa-ticket"></i> Reports
      <?php if ($_totalReportUnseen > 0): ?>
        <span class="referral-badge" style="display:inline-block;"></span>
      <?php endif; ?>
    </a>
    <p class="sidebar-title">SYSTEM</p>
    <a href="sfeedback.php"><i class="fa fa-comment"></i> Session Feedback</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left"><h2>Book Appointment</h2></div>
  <div class="topbar-right">
    <div class="topbar-user">
      <img src="<?= $profileImg ?>"
           alt="<?= $fullName ?>"
           onerror="this.onerror=null;this.src='https://ui-avatars.com/api/?name=<?= urlencode($fullName) ?>&background=113f67&color=fff&size=128'">
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
            $cName      = htmlspecialchars(trim($c['first_name'] . ' ' . $c['last_name']));
            $cDept      = htmlspecialchars($c['department'] ?? '');
            $cFallback  = 'https://ui-avatars.com/api/?name=' . urlencode($cName) . '&background=113f67&color=fff&size=128';

            if (!empty($c['profile_image'])) {
                $cImg = htmlspecialchars(trim($c['profile_image']));
            } else {
                $cImg = $cFallback;
            }
          ?>
          <div class="sBooking-counselor-option"
               data-id="<?= (int)$c['counselor_id'] ?>"
               onclick="selectCounselor(this, <?= (int)$c['counselor_id'] ?>)">
            <img src="<?= $cImg ?>"
                 onerror="this.onerror=null;this.src='<?= htmlspecialchars($cFallback) ?>'"
                 alt="<?= $cName ?>"
                 loading="lazy">
            <div class="sBooking-counselor-info">
              <strong><?= $cName ?></strong>
              <span><?= $cDept ?></span>
            </div>
          </div>
          <?php endforeach; ?>
          <?php if (empty($counselors)): ?>
            <p style="font-size:13px;color:var(--text-muted);">No counselors available at this time.</p>
          <?php endif; ?>
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

        <!-- ── Optional Document Upload ── -->
        <div class="sBooking-field">
          <label>
            Supporting Document
            <span style="font-size:10px; font-weight:400; color:var(--text-muted,#64748b); margin-left:4px;">(optional)</span>
          </label>
          <div class="sBooking-upload-zone"
               id="uploadZone"
               ondragover="handleDragOver(event)"
               ondragleave="handleDragLeave(event)"
               ondrop="handleDrop(event)">
            <input type="file"
                   id="documentFile"
                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.webp"
                   onchange="handleFileSelect(this)">
            <i class="fa fa-cloud-arrow-up"></i>
            <p>Click to browse or drag &amp; drop</p>
            <p style="margin-top:3px; font-size:11px;">PDF, Word, JPG, PNG · Max 10 MB</p>
          </div>
          <div class="upload-filename" id="uploadFilename">
            <i class="fa fa-file" style="color:#4988C4; font-size:13px;"></i>
            <span id="uploadFileLabel"></span>
            <button class="upload-clear" onclick="clearFile()" title="Remove file">
              <i class="fa fa-times-circle"></i>
            </button>
          </div>
          <span class="sBooking-upload-label" id="uploadHint" style="display:none;"></span>
        </div>

        <button class="sBooking-submit" onclick="bookAppointment()">
          <i class="fa fa-calendar-check" style="margin-right:6px;"></i> Confirm Booking
        </button>
        <div id="bookingResult"></div>
      </div>

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
(function () {
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
function logout()       { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()  { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout(){ window.location.href = 'logout.php?role=student'; }

document.getElementById('logoutOverlay').addEventListener('click', function (e) {
    if (e.target === this) closeLogout();
});
document.addEventListener("click", e => {
    const menu = document.getElementById("settingsDropdown");
    const btn  = document.querySelector(".sidebar-settingsButton");
    if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
        menu.classList.remove("show");
    }
});

// ── State ──
let selectedCounselorId = null;

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
        .then(r => {
            if (!r.ok) throw new Error('Server error');
            return r.json();
        })
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
            wrap.innerHTML = '<em style="font-size:13px;color:var(--error,#e53e3e);">Failed to load slots. Please try again.</em>';
        });
}

function formatTime(t) {
    const parts = (t || '').split(':');
    const hr    = parseInt(parts[0], 10);
    const min   = parts[1] || '00';
    const ampm  = hr >= 12 ? 'PM' : 'AM';
    const disp  = (hr % 12) || 12;
    return `${disp}:${min} ${ampm}`;
}

// ── File upload helpers ──
function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        showFileName(input.files[0].name);
    }
}
function showFileName(name) {
    document.getElementById('uploadFileLabel').textContent = name;
    document.getElementById('uploadFilename').classList.add('visible');
}
function clearFile() {
    document.getElementById('documentFile').value = '';
    document.getElementById('uploadFilename').classList.remove('visible');
    document.getElementById('uploadFileLabel').textContent = '';
}
function handleDragOver(e) {
    e.preventDefault();
    document.getElementById('uploadZone').classList.add('dragover');
}
function handleDragLeave(e) {
    document.getElementById('uploadZone').classList.remove('dragover');
}
function handleDrop(e) {
    e.preventDefault();
    document.getElementById('uploadZone').classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (!file) return;
    const dt = new DataTransfer();
    dt.items.add(file);
    document.getElementById('documentFile').files = dt.files;
    showFileName(file.name);
}

// ── Book appointment ──
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

    const submitBtn = document.querySelector('.sBooking-submit');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin" style="margin-right:6px;"></i> Submitting…';
    }

    const fd = new FormData();
    fd.append('action',       'book');
    fd.append('counselor_id', selectedCounselorId);
    fd.append('date',         d);
    fd.append('time',         t);
    fd.append('message',      msg);
    fd.append('priority',     priority);

    const fileInput = document.getElementById('documentFile');
    if (fileInput.files && fileInput.files[0]) {
        fd.append('document', fileInput.files[0]);
    }

    fetch('sappointment.php', { method: 'POST', body: fd })
        .then(r => {
            if (!r.ok) throw new Error('Server error');
            return r.json();
        })
        .then(json => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa fa-calendar-check" style="margin-right:6px;"></i> Confirm Booking';
            }
            result.innerHTML = json.success
                ? "<span style='color:var(--success,#15803d);'>✔ Appointment submitted successfully!</span>"
                : "<span style='color:var(--error,#e53e3e);'>❌ " + (json.message || 'Failed. Please try again.') + "</span>";

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
                clearFile();
                setTimeout(() => result.innerHTML = '', 5000);
            }
        })
        .catch(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fa fa-calendar-check" style="margin-right:6px;"></i> Confirm Booking';
            }
            result.innerHTML = "<span style='color:var(--error,#e53e3e);'>❌ Something went wrong. Please try again.</span>";
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
setInterval(checkReferralBadge, 60000);
</script>
<script>var SESSION_ROLE = 'student';</script>
<script src="session_timeout.js"></script>
</body>
</html>
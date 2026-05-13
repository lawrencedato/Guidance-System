<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) session_start();

// ===== GUARD: must be logged in =====
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: slogin.php");
    exit;
}

$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");
$sid  = (int)$_SESSION['user_id'];
require_once 'scheck_reports_badge.php';

// ===== LOAD STUDENT DATA =====
$studentRes = $conn->query("SELECT * FROM students WHERE student_id = $sid LIMIT 1");
$student    = $studentRes->fetch_assoc();

$profileRes = $conn->query("SELECT profile_image FROM student_profiles WHERE student_id = $sid LIMIT 1");
$profile    = $profileRes->fetch_assoc();

$fullName   = htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$email      = htmlspecialchars($student['email'] ?? '');
$profileImg = !empty($profile['profile_image'])
              ? htmlspecialchars($profile['profile_image'])
              : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';

// ===== HANDLE AJAX SAVE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_wellness') {
    header('Content-Type: application/json');

    // Double-check server side: already submitted today?
    $check = $conn->query("
        SELECT wellness_id FROM wellness_checks
        WHERE student_id = $sid AND DATE(created_at) = CURDATE()
        LIMIT 1
    ");
    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'already_done' => true, 'message' => 'You have already submitted your wellness check today.']);
        exit;
    }

    $mood   = $conn->real_escape_string($_POST['mood_label']    ?? '');
    $stress = (int)($_POST['stress_level']                      ?? 0);
    $sleep  = $conn->real_escape_string($_POST['sleep_quality'] ?? '');

    $ok = $conn->query("
        INSERT INTO wellness_checks (student_id, mood_label, stress_level, sleep_quality, created_at)
        VALUES ($sid, '$mood', $stress, '$sleep', NOW())
    ");

    echo json_encode($ok
        ? ['success' => true]
        : ['success' => false, 'message' => 'Failed to save. Please try again.']);
    exit;
}

// ===== HANDLE AJAX UPDATE =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_wellness') {
    header('Content-Type: application/json');

    // Must have a record today to update
    $checkRes = $conn->query("
        SELECT wellness_id FROM wellness_checks
        WHERE student_id = $sid AND DATE(created_at) = CURDATE()
        LIMIT 1
    ");
    $row = $checkRes->fetch_assoc();
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'No check-in found for today to update.']);
        exit;
    }

    $wid    = (int)$row['wellness_id'];
    $mood   = $conn->real_escape_string($_POST['mood_label']    ?? '');
    $stress = (int)($_POST['stress_level']                      ?? 0);
    $sleep  = $conn->real_escape_string($_POST['sleep_quality'] ?? '');

    $ok = $conn->query("
        UPDATE wellness_checks
        SET mood_label = '$mood', stress_level = $stress, sleep_quality = '$sleep'
        WHERE wellness_id = $wid AND student_id = $sid
    ");

    echo json_encode($ok
        ? ['success' => true]
        : ['success' => false, 'message' => 'Failed to update. Please try again.']);
    exit;
}

// ===== CHECK IF ALREADY SUBMITTED TODAY =====
$todayRes = $conn->query("
    SELECT mood_label, stress_level, sleep_quality, created_at
    FROM wellness_checks
    WHERE student_id = $sid AND DATE(created_at) = CURDATE()
    ORDER BY created_at DESC
    LIMIT 1
");
$todayEntry  = $todayRes->fetch_assoc();
$alreadyDone = !empty($todayEntry);

// Mood emoji map
$moodEmoji = [
    'Very Sad'   => '😢',
    'Sad'        => '😕',
    'Neutral'    => '😐',
    'Happy'      => '🙂',
    'Very Happy' => '😁',
];

function stressLabel($v) {
    if ($v < 30) return "Low 😌";
    if ($v < 70) return "Moderate 😐";
    return "High 😰";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Wellness Check</title>
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
</style>
</head>

<body class="body">
<?php
$_totalReportUnseen = $_totalReportUnseen ?? 0;
?>
<!-- ========================= SIDEBAR ========================= -->
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
    <a href="sconcerns.php"><i class="fa fa-headset"></i> Submit Concern</a>
    <a href="swellness.php" class="active"><i class="fa fa-heart"></i> Wellness Check</a>
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

<!-- ========================= TOPBAR ========================= -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Wellness Check</h2>
  </div>
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

<!-- ========================= MAIN ========================= -->
<main class="sWellness-main">
  <section class="sWellness-card">

    <h2>How are you feeling today?</h2>

    <!-- ===== ALREADY SUBMITTED BANNER ===== -->
    <?php if ($alreadyDone): ?>
    <div class="sWellness-done-banner" id="doneBanner">
      <span class="done-emoji"><?= $moodEmoji[$todayEntry['mood_label']] ?? '😐' ?></span>
      <h3>You've already checked in today!</h3>
      <p>Your wellness check has been recorded. Come back tomorrow.</p>
      <div class="sWellness-done-summary">
        <div class="sWellness-done-chip">
          <i class="fa fa-face-smile"></i> Mood: <?= htmlspecialchars($todayEntry['mood_label']) ?>
        </div>
        <div class="sWellness-done-chip">
          <i class="fa fa-gauge"></i> Stress: <?= stressLabel($todayEntry['stress_level']) ?> (<?= $todayEntry['stress_level'] ?>%)
        </div>
        <div class="sWellness-done-chip">
          <i class="fa fa-moon"></i> Sleep: <?= htmlspecialchars($todayEntry['sleep_quality']) ?>
        </div>
      </div>
      <p class="sWellness-done-time">
        Submitted at <?= date('h:i A', strtotime($todayEntry['created_at'])) ?>
        &bull; <?= date('F j, Y', strtotime($todayEntry['created_at'])) ?>
      </p>
      <p class="sWellness-done-note">
        <i class="fa fa-rotate-right"></i> Resets every day at midnight
      </p>
      <button class="sWellness-edit-btn" onclick="openEdit()">
        <i class="fa fa-pen"></i> Edit Today's Check-in
      </button>
    </div>
    <?php endif; ?>

    <!-- ===== FORM (hidden if already done today) ===== -->
    <div class="sWellness-form-section <?= $alreadyDone ? 'hidden' : '' ?>" id="wellnessForm">

      <!-- Edit mode indicator -->
      <div class="sWellness-edit-label" id="editLabel">
        <i class="fa fa-pen"></i> You are editing today's check-in
      </div>

      <!-- MOOD SELECTOR -->
      <div class="sWellness-mood-container">
        <button class="sWellness-mood-btn" onclick="setMood(this,'😢','Very Sad')" title="Very Sad">😢</button>
        <button class="sWellness-mood-btn" onclick="setMood(this,'😕','Sad')" title="Sad">😕</button>
        <button class="sWellness-mood-btn" onclick="setMood(this,'😐','Neutral')" title="Neutral">😐</button>
        <button class="sWellness-mood-btn" onclick="setMood(this,'🙂','Happy')" title="Happy">🙂</button>
        <button class="sWellness-mood-btn" onclick="setMood(this,'😁','Very Happy')" title="Very Happy">😁</button>
      </div>

      <!-- MOOD DISPLAY -->
      <div class="sWellness-mood-display">
        Selected Mood: <strong id="moodValue">😐 Neutral</strong>
      </div>

      <!-- STRESS -->
      <div class="sWellness-form-group">
        <label>Stress Level</label>
        <input type="range" id="stressRange" min="0" max="100" value="50"
               oninput="updateStress(this.value)">
        <p class="sWellness-stress-display">
          <strong id="stressValue">Moderate 😐 (50%)</strong>
        </p>
      </div>

      <!-- SLEEP -->
      <div class="sWellness-form-group">
        <label>Sleep Quality</label>
        <select id="sleepSelect">
          <option>Good</option>
          <option>Average</option>
          <option>Poor</option>
        </select>
      </div>

      <div class="sWellness-btn-row">
        <button class="sWellness-btn" id="saveBtn" onclick="submitWellness()" style="flex:2;">
          Save Check-in
        </button>
        <button class="sWellness-cancel-btn" id="cancelEditBtn" onclick="cancelEdit()" style="display:none;">
          Cancel
        </button>
      </div>

      <div id="wellnessResult" style="margin-top:10px; font-size:14px;"></div>

    </div><!-- end form section -->

  </section>
</main>

<!-- ========================= LOGOUT MODAL ========================= -->
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

<!-- ========================= SCRIPT ========================= -->
<script>
(function() {
  const saved = localStorage.getItem("theme") || "light";
  document.documentElement.setAttribute("data-theme", saved);
})();

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
  const t    = html.getAttribute("data-theme") === "light" ? "dark" : "light";
  html.setAttribute("data-theme", t);
  localStorage.setItem("theme", t);
}
function logout()        { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()   { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=student'; }
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});

// ===== STATE =====
let selectedMood = 'Neutral';
let isEditMode   = false;

// Existing today's values passed from PHP (empty strings if no entry yet)
const todayMood   = <?= json_encode($todayEntry['mood_label']    ?? '') ?>;
const todayStress = <?= json_encode((string)($todayEntry['stress_level'] ?? '50')) ?>;
const todaySleep  = <?= json_encode($todayEntry['sleep_quality'] ?? 'Good') ?>;

const moodEmojis = {
  'Very Sad':'😢', 'Sad':'😕', 'Neutral':'😐', 'Happy':'🙂', 'Very Happy':'😁'
};

window.addEventListener("load", () => {
  const defaultMood = todayMood || 'Neutral';
  preSelectMood(defaultMood);
  updateStress(todayStress || 50);
  document.getElementById("stressRange").value = todayStress || 50;
  const sleepSel = document.getElementById("sleepSelect");
  [...sleepSel.options].forEach(o => { o.selected = o.value === todaySleep; });
});

function preSelectMood(mood) {
  selectedMood = mood;
  const emoji  = moodEmojis[mood] || '😐';
  document.getElementById("moodValue").innerText = `${emoji} ${mood}`;
  document.querySelectorAll(".sWellness-mood-btn").forEach(btn => {
    btn.classList.toggle("active", btn.title === mood);
  });
}

function setMood(el, emoji, text) {
  selectedMood = text;
  document.getElementById("moodValue").innerText = `${emoji} ${text}`;
  document.querySelectorAll(".sWellness-mood-btn").forEach(b => b.classList.remove("active"));
  el.classList.add("active");
}

function updateStress(v) {
  const label = v < 30 ? "Low 😌" : v < 70 ? "Moderate 😐" : "High 😰";
  document.getElementById("stressValue").innerText = `${label} (${v}%)`;
}

// ===== EDIT MODE =====
function openEdit() {
  isEditMode = true;

  // Hide banner, show form pre-filled
  const banner = document.getElementById("doneBanner");
  if (banner) banner.style.display = "none";

  document.getElementById("wellnessForm").classList.remove("hidden");
  document.getElementById("editLabel").classList.add("show");
  document.getElementById("cancelEditBtn").style.display = "block";
  document.getElementById("saveBtn").textContent = "Update Check-in";

  // Pre-fill with today's saved values
  preSelectMood(todayMood || 'Neutral');
  document.getElementById("stressRange").value = todayStress || 50;
  updateStress(todayStress || 50);
  const sleepSel = document.getElementById("sleepSelect");
  [...sleepSel.options].forEach(o => { o.selected = o.value === todaySleep; });

  document.getElementById("wellnessResult").innerHTML = "";
}

function cancelEdit() {
  isEditMode = false;
  document.getElementById("wellnessForm").classList.add("hidden");
  document.getElementById("editLabel").classList.remove("show");
  document.getElementById("cancelEditBtn").style.display = "none";
  document.getElementById("saveBtn").textContent = "Save Check-in";

  const banner = document.getElementById("doneBanner");
  if (banner) banner.style.display = "";

  document.getElementById("wellnessResult").innerHTML = "";
}

// ===== SUBMIT / UPDATE =====
function submitWellness() {
  const stress  = document.getElementById("stressRange").value;
  const sleep   = document.getElementById("sleepSelect").value;
  const result  = document.getElementById("wellnessResult");
  const saveBtn = document.getElementById("saveBtn");

  result.innerHTML    = "";
  saveBtn.disabled    = true;
  saveBtn.textContent = isEditMode ? 'Updating...' : 'Saving...';

  const fd = new FormData();
  fd.append('action',        isEditMode ? 'update_wellness' : 'save_wellness');
  fd.append('mood_label',    selectedMood);
  fd.append('stress_level',  stress);
  fd.append('sleep_quality', sleep);

  fetch('swellness.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      saveBtn.disabled    = false;
      saveBtn.textContent = isEditMode ? 'Update Check-in' : 'Save Check-in';

      if (json.success) {
        const emoji     = moodEmojis[selectedMood] || '😐';
        const stressNum = parseInt(stress);
        const stressLbl = stressNum < 30 ? "Low 😌" : stressNum < 70 ? "Moderate 😐" : "High 😰";
        const now       = new Date();
        const timeStr   = now.toLocaleTimeString('en-PH', { hour:'2-digit', minute:'2-digit' });
        const dateStr   = now.toLocaleDateString('en-PH', { month:'long', day:'numeric', year:'numeric' });
        const heading   = isEditMode ? 'Check-in updated! ✅' : 'Check-in saved! 🎉';
        const subtext   = isEditMode
          ? 'Your wellness check has been updated for today.'
          : 'Your wellness check has been recorded. Come back tomorrow.';

        // Hide form
        document.getElementById("wellnessForm").classList.add("hidden");
        document.getElementById("editLabel").classList.remove("show");
        document.getElementById("cancelEditBtn").style.display = "none";
        saveBtn.textContent = "Save Check-in";
        isEditMode = false;

        // Remove old banner if exists
        const oldBanner = document.getElementById("doneBanner");
        if (oldBanner) oldBanner.remove();

        // Build fresh banner
        const banner = document.createElement('div');
        banner.className = 'sWellness-done-banner';
        banner.id        = 'doneBanner';
        banner.innerHTML = `
          <span class="done-emoji">${emoji}</span>
          <h3>${heading}</h3>
          <p>${subtext}</p>
          <div class="sWellness-done-summary">
            <div class="sWellness-done-chip"><i class="fa fa-face-smile"></i> Mood: ${selectedMood}</div>
            <div class="sWellness-done-chip"><i class="fa fa-gauge"></i> Stress: ${stressLbl} (${stress}%)</div>
            <div class="sWellness-done-chip"><i class="fa fa-moon"></i> Sleep: ${sleep}</div>
          </div>
          <p class="sWellness-done-time">Last saved at ${timeStr} &bull; ${dateStr}</p>
          <p class="sWellness-done-note"><i class="fa fa-rotate-right"></i> Resets every day at midnight</p>
          <button class="sWellness-edit-btn" onclick="openEdit()">
            <i class="fa fa-pen"></i> Edit Today's Check-in
          </button>
        `;

        const card = document.querySelector('.sWellness-card');
        card.insertBefore(banner, document.getElementById('wellnessForm'));

      } else if (json.already_done) {
        result.innerHTML = "<span style='color:#e53e3e;'>⚠ You have already submitted today.</span>";
      } else {
        result.innerHTML = `<span style='color:#e53e3e;'>❌ ${json.message || 'Failed to save.'}</span>`;
      }
    })
    .catch(() => {
      saveBtn.disabled    = false;
      saveBtn.textContent = isEditMode ? 'Update Check-in' : 'Save Check-in';
      result.innerHTML    = "<span style='color:#e53e3e;'>❌ Something went wrong. Please try again.</span>";
    });
}

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
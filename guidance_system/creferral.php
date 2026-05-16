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
$phone      = htmlspecialchars($counselor['contact_number'] ?? 'N/A');
$department = htmlspecialchars($counselor['department'] ?? '');

$profileImg = !empty($counselor['profile_image'])
    ? htmlspecialchars($counselor['profile_image'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';

$signaturePath = !empty($counselor['signature'])
    ? htmlspecialchars($counselor['signature'])
    : 'images/signature.png';

$pendingCount = (int)$conn->query(
    "SELECT COUNT(*) c FROM appointments WHERE counselor_id='$cid' AND status='Pending'"
)->fetch_assoc()['c'];

// ── AJAX: Upload signature ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upload_signature') {
    header('Content-Type: application/json');
    if (empty($_FILES['signature']['tmp_name'])) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded.']); exit;
    }
    $allowed = ['image/png', 'image/jpeg', 'image/jpg'];
    $mime    = mime_content_type($_FILES['signature']['tmp_name']);
    if (!in_array($mime, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Only PNG or JPG allowed.']); exit;
    }
    if (!is_dir('signatures')) mkdir('signatures', 0755, true);
    $ext     = $mime === 'image/png' ? 'png' : 'jpg';
    $sigName = 'signatures/sig_' . $cid . '.' . $ext;
    if (move_uploaded_file($_FILES['signature']['tmp_name'], $sigName)) {
        $conn->query("UPDATE counselors SET signature='$sigName' WHERE counselor_id='$cid'");
        echo json_encode(['success' => true, 'path' => $sigName]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save file.']);
    }
    exit;
}

// ── AJAX: Lookup student by ID ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['lookup_student_id'])) {
    header('Content-Type: application/json');
    $sid = (int)$_GET['lookup_student_id'];
    $row = $conn->query("
        SELECT s.student_id, s.first_name, s.last_name, s.year_level, s.course
        FROM students s
        INNER JOIN activated_students a ON a.student_id = s.student_id
        WHERE s.student_id = $sid AND a.status = 'active' AND s.archived = 0
        LIMIT 1
    ")->fetch_assoc();
    echo $row
        ? json_encode(['found' => true, 'name' => $row['first_name'] . ' ' . $row['last_name'], 'year_level' => $row['year_level'], 'course' => $row['course']])
        : json_encode(['found' => false]);
    exit;
}

// ── POST: Create referral ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_referral') {
    header('Content-Type: application/json');
    $studentId = (int)($_POST['student_id']       ?? 0);
    $date      = $conn->real_escape_string($_POST['referral_date']     ?? '');
    $year      = $conn->real_escape_string($_POST['year_level']        ?? '');
    $course    = $conn->real_escape_string($_POST['course']            ?? '');
    $reason    = $conn->real_escape_string($_POST['reason']            ?? '');
    $remarks   = $conn->real_escape_string($_POST['counselor_remarks'] ?? '');
    if (!$studentId || !$date || !$year || !$course || !$reason) {
        echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']); exit;
    }
    $verify = $conn->query("
        SELECT s.student_id FROM students s
        INNER JOIN activated_students a ON a.student_id = s.student_id
        WHERE s.student_id = $studentId AND a.status = 'active' AND s.archived = 0
        LIMIT 1
    ")->fetch_assoc();
    if (!$verify) {
        echo json_encode(['success' => false, 'message' => 'Student not found or inactive.']); exit;
    }
    $ok = $conn->query("
        INSERT INTO referrals (student_id, counselor_id, referral_date, reason, counselor_remarks, created_at)
        VALUES ($studentId, '$cid', '$date', '$reason', '$remarks', NOW())
    ");
    echo json_encode($ok ? ['success' => true] : ['success' => false, 'message' => 'Failed to save. Please try again.']);
    exit;
}

// ── Fetch referral history ──
$historyRes = $conn->query("
    SELECT r.referral_id, r.referral_date, r.reason, r.counselor_remarks, r.created_at,
           CONCAT(s.first_name,' ',s.last_name) AS student_name,
           s.course, s.year_level
    FROM referrals r
    JOIN students s ON s.student_id = r.student_id
    WHERE r.counselor_id = '$cid'
    ORDER BY r.created_at DESC
    LIMIT 20
");
$history = $historyRes ? $historyRes->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Referral</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>

</style>
</head>

<body class="body">

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
    <a href="creports.php"><i class="fa fa-file"></i> Session Notes</a>
    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.php" class="active"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- ========================= TOPBAR ========================= -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Referral</h2>
    <p class="topbar-muted">Issue referral slips to students who need further professional assistance.</p>
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

<!-- ========================= MAIN ========================= -->
<main class="cReferral-main">
  <div class="cReferral-center">

    <!-- ── CARD 1: REFERRAL SLIP ── -->
    <div class="cReferral-card">

      <h2 style="margin:0 0 20px;">REFERRAL SLIP</h2>

      <!-- DATE -->
      <div class="cReferral-slip-row">
        <label>Date:</label>
        <input type="date" class="cReferral-input" id="refDate">
      </div>

      <!-- PATIENT INFO -->
      <h3 style="margin:18px 0 10px; font-size:15px; font-weight:600; opacity:0.9;">Patient Information</h3>

      <!-- Student ID -->
      <div class="cReferral-slip-row">
        <label>Student ID:</label>
        <div style="flex:1; display:flex; flex-direction:column;">
          <div class="cReferral-id-row">
            <input
              type="number"
              class="cReferral-input"
              id="refStudentId"
              placeholder="Enter student ID"
              oninput="lookupStudent(this.value)"
            >
          </div>
          <div class="cReferral-id-status" id="idStatus"></div>
        </div>
      </div>

      <!-- Name -->
      <div class="cReferral-slip-row">
        <label>Name:</label>
        <input type="text" class="cReferral-input" id="refName" readonly
               placeholder="Auto-filled from Student ID">
      </div>

      <!-- Year Level -->
      <div class="cReferral-slip-row">
        <label>Year Level:</label>
        <input type="text" class="cReferral-input" id="refYear" readonly
               placeholder="Auto-filled from Student ID">
      </div>

      <!-- Course -->
      <div class="cReferral-slip-row">
        <label>Program / Course:</label>
        <input type="text" class="cReferral-input" id="refCourse" readonly
               placeholder="Auto-filled from Student ID">
      </div>

      <!-- REFERRAL DETAILS -->
      <h3 style="margin:18px 0 10px; font-size:15px; font-weight:600; opacity:0.9;">Reason for Referral</h3>
      <textarea class="cReferral-textarea" id="refReason"
                placeholder="Describe the reason for referral…"></textarea>

      <h3 style="margin:6px 0 10px; font-size:15px; font-weight:600; opacity:0.9;">
        Counselor's Remarks
        <span style="font-weight:400; opacity:0.6; font-size:13px;">(Optional)</span>
      </h3>
      <textarea class="cReferral-textarea" id="refRemarks"
                placeholder="Additional remarks or notes…"></textarea>

      <!-- REFERRED BY -->
      <h3 style="margin:6px 0 10px; font-size:15px; font-weight:600; opacity:0.9;">Referred By</h3>

      <div class="cReferral-sig-area">
        <?php if (!empty($counselor['signature']) && file_exists($counselor['signature'])): ?>
          <div>
            <img src="<?= $signaturePath ?>?v=<?= time() ?>"
                 class="cReferral-sig-preview"
                 id="sigPreviewImg"
                 alt="Counselor Signature">
          </div>
        <?php else: ?>
          <div class="cReferral-sig-empty" id="sigNoPreview">
            <span><i class="fa fa-pen-nib" style="margin-right:6px;"></i> No signature uploaded</span>
          </div>
          <img src="" class="cReferral-sig-preview" id="sigPreviewImg"
               alt="Counselor Signature" style="display:none;">
        <?php endif; ?>

        <label class="cReferral-sig-btn" for="sigFileInput">
          <i class="fa fa-upload"></i>
          <?= !empty($counselor['signature']) ? 'Replace Signature' : 'Upload Signature' ?>
        </label>
        <input type="file" id="sigFileInput" accept="image/png, image/jpeg"
               style="display:none;" onchange="uploadSignature(this)">

        <div class="cReferral-sig-status" id="sigUploadStatus"></div>
      </div>

      <p style="margin-top:14px; font-size:14px;">
        <strong><?= $fullName ?></strong>
      </p>
      <?php if ($department): ?>
        <p style="font-size:13px; color:var(--text-muted);"><?= $department ?></p>
      <?php endif; ?>
      <p style="font-size:13px; color:var(--text-muted);">
        <i class="fa fa-phone" style="width:14px;"></i> <?= $phone ?>
        &nbsp;|&nbsp;
        <i class="fa fa-envelope" style="width:14px;"></i> <?= $email ?>
      </p>

      <!-- SUBMIT -->
      <button class="cReferral-btn" onclick="createReferral()" style="margin-top:24px;">
        <i class="fa fa-paper-plane"></i> Create Referral
      </button>
      <div id="cReferralResult"></div>
    </div>
      <div class="cReferral-card">

        <div class="cReferral-history-header">
          <h2>
            Recent Referrals
          </h2>
          <button class="cReferral-toggle-btn" id="toggleHistoryBtn" onclick="toggleHistory()">
            <i class="fa fa-chevron-down"></i> Show
          </button>
        </div>

        <div id="historyWrapper" style="display:none; margin-top:12px;">

          <p class="cReferral-history-count">
            <?= count($history) ?> referral<?= count($history) !== 1 ? 's' : '' ?> issued
          </p>

          <?php if (!empty($history)): ?>
            <ul class="cReferral-list">
              <?php foreach ($history as $h):
                $ref     = 'REF-' . str_pad($h['referral_id'], 5, '0', STR_PAD_LEFT);
                $hDate   = date('M d, Y', strtotime($h['referral_date']));
                $hTime   = date('g:i A',  strtotime($h['created_at']));
                $preview = mb_strimwidth(strip_tags($h['reason']), 0, 120, '…');
                $jsRef     = json_encode($ref);
                $jsStudent = json_encode($h['student_name']);
                $jsMeta    = json_encode($h['year_level'] . ' — ' . $h['course']);
                $jsDate    = json_encode(date('F d, Y', strtotime($h['referral_date'])));
                $jsReason  = json_encode($h['reason']);
                $jsRemarks = json_encode($h['counselor_remarks'] ?? '');
              ?>
              <li class="cReferral-list-item">

                <div class="cReferral-list-thumb">
                  <i class="fa fa-route"></i>
                </div>

                <div class="cReferral-list-body">
                  <p class="cReferral-list-ref"><?= $ref ?></p>
                  <p class="cReferral-list-student">
                    <i class="fa fa-user-graduate"></i>
                    <?= htmlspecialchars($h['student_name']) ?>
                    &mdash; <?= htmlspecialchars($h['year_level']) ?> &bull; <?= htmlspecialchars($h['course']) ?>
                  </p>
                  <p class="cReferral-list-preview"><?= htmlspecialchars($preview) ?></p>
                  <div class="cReferral-list-meta">
                    <span><i class="fa fa-calendar"></i><?= $hDate ?></span>
                    <span><i class="fa fa-clock"></i><?= $hTime ?></span>
                  </div>
                </div>

                <div class="cReferral-list-actions">
                  <button class="cReferral-list-btn-view"
                    data-ref=<?= $jsRef ?>
                    data-student=<?= $jsStudent ?>
                    data-meta='<?= htmlspecialchars($h['year_level'] . ' — ' . $h['course'], ENT_QUOTES) ?>'
                    data-date=<?= $jsDate ?>
                    data-reason=<?= $jsReason ?>
                    data-remarks=<?= $jsRemarks ?>
                    onclick="viewReferral(this)">
                    <i class="fa fa-eye"></i> View
                  </button>
                </div>

              </li>
              <?php endforeach; ?>
            </ul>

          <?php else: ?>
            <div class="cReferral-list-empty">
              <i class="fa fa-inbox"></i>
              <p>No referrals issued yet.</p>
            </div>
          <?php endif; ?>

        </div>
      </div>
      <!-- END HISTORY -->

    </div>
    <!-- END CARD 1 -->
  </div>
  </div>
</main>

<!-- ── VIEW REFERRAL MODAL ── -->
<div class="cReferral-modal-overlay" id="viewReferralModal"
     onclick="closeReferralModal(event)">
  <div class="cReferral-modal-box">
    <button class="cReferral-modal-close" onclick="closeReferralModalDirect()">&#x2715;</button>
    <p class="cReferral-modal-ref"     id="vrRef"></p>
    <h3 class="cReferral-modal-title"  id="vrStudent"></h3>
    <p class="cReferral-modal-message" id="vrMeta"
       style="font-size:12px; margin:0 0 4px;"></p>
    <p class="cReferral-modal-message" style="font-size:13px; font-weight:600; margin:12px 0 4px; color:var(--text);">
      Reason for Referral
    </p>
    <p class="cReferral-modal-message" id="vrReason"></p>
    <p class="cReferral-modal-message" id="vrRemarksLabel"
       style="font-size:13px; font-weight:600; margin:10px 0 4px; color:var(--text);">
      Counselor's Remarks
    </p>
    <p class="cReferral-modal-message" id="vrRemarks"></p>
    <div class="cReferral-modal-footer">
      <span id="vrDate"></span>
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

<!-- ========================= SCRIPT ========================= -->
<script>
(function() {
  const saved = localStorage.getItem("theme") || "light";
  document.documentElement.setAttribute("data-theme", saved);
})();

// ── Student ID lookup ──
let lookupTimer = null;

function lookupStudent(val) {
  const idStatus    = document.getElementById('idStatus');
  const nameField   = document.getElementById('refName');
  const yearField   = document.getElementById('refYear');
  const courseField = document.getElementById('refCourse');

  clearTimeout(lookupTimer);
  nameField.value   = '';
  yearField.value   = '';
  courseField.value = '';

  const id = val.trim();
  if (!id || isNaN(id) || parseInt(id) <= 0) {
    idStatus.textContent = '';
    idStatus.className   = 'cReferral-id-status';
    return;
  }

  idStatus.textContent = 'Searching…';
  idStatus.className   = 'cReferral-id-status loading';

  lookupTimer = setTimeout(() => {
    fetch(`creferral.php?lookup_student_id=${encodeURIComponent(id)}`)
      .then(r => r.json())
      .then(data => {
        if (data.found) {
          nameField.value   = data.name;
          yearField.value   = data.year_level;
          courseField.value = data.course;
          idStatus.textContent = '✔ Student found';
          idStatus.className   = 'cReferral-id-status found';
        } else {
          idStatus.textContent = '✘ No active student found with this ID';
          idStatus.className   = 'cReferral-id-status notfound';
        }
      })
      .catch(() => {
        idStatus.textContent = '✘ Lookup failed. Please try again.';
        idStatus.className   = 'cReferral-id-status notfound';
      });
  }, 500);
}

// ── Signature upload ──
function uploadSignature(input) {
  const file      = input.files[0];
  const status    = document.getElementById('sigUploadStatus');
  const preview   = document.getElementById('sigPreviewImg');
  const noPreview = document.getElementById('sigNoPreview');

  if (!file) return;
  status.textContent = 'Uploading…';
  status.className   = 'cReferral-sig-status loading';

  const fd = new FormData();
  fd.append('action',    'upload_signature');
  fd.append('signature', file);

  fetch('creferral.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        const reader = new FileReader();
        reader.onload = e => {
          preview.src           = e.target.result;
          preview.style.display = 'block';
          if (noPreview) noPreview.style.display = 'none';
        };
        reader.readAsDataURL(file);
        status.textContent = '✔ Signature saved successfully!';
        status.className   = 'cReferral-sig-status ok';
      } else {
        status.textContent = '✘ ' + (data.message || 'Upload failed.');
        status.className   = 'cReferral-sig-status err';
      }
    })
    .catch(() => {
      status.textContent = '✘ Upload failed. Please try again.';
      status.className   = 'cReferral-sig-status err';
    });
}

// ── Create referral ──
function createReferral() {
  const studentId = document.getElementById('refStudentId').value.trim();
  const date      = document.getElementById('refDate').value;
  const name      = document.getElementById('refName').value.trim();
  const year      = document.getElementById('refYear').value.trim();
  const course    = document.getElementById('refCourse').value.trim();
  const reason    = document.getElementById('refReason').value.trim();
  const remarks   = document.getElementById('refRemarks').value.trim();
  const result    = document.getElementById('cReferralResult');

  if (!studentId || !name) {
    result.innerHTML = "<span style='color:#fca5a5;'>⚠ Please enter a valid Student ID first.</span>";
    return;
  }
  if (!date || !reason) {
    result.innerHTML = "<span style='color:#fca5a5;'>⚠ Date and Reason for Referral are required.</span>";
    return;
  }

  result.innerHTML = "<span style='color:#8ba4be;'>Saving…</span>";

  const fd = new FormData();
  fd.append('action',            'create_referral');
  fd.append('student_id',        studentId);
  fd.append('referral_date',     date);
  fd.append('year_level',        year);
  fd.append('course',            course);
  fd.append('reason',            reason);
  fd.append('counselor_remarks', remarks);

  fetch('creferral.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        result.innerHTML = "<span style='color:#4ade80;'>✔ Referral created successfully!</span>";
        document.getElementById('refStudentId').value = '';
        document.getElementById('refName').value      = '';
        document.getElementById('refYear').value      = '';
        document.getElementById('refCourse').value    = '';
        document.getElementById('refReason').value    = '';
        document.getElementById('refRemarks').value   = '';
        document.getElementById('idStatus').textContent = '';
        document.getElementById('idStatus').className   = 'cReferral-id-status';
        setTimeout(() => location.reload(), 1500);
      } else {
        result.innerHTML = "<span style='color:#fca5a5;'>❌ " + (json.message || 'Something went wrong.') + "</span>";
      }
    })
    .catch(() => {
      result.innerHTML = "<span style='color:#fca5a5;'>❌ Something went wrong.</span>";
    });
}

// ── Toggle history ──
function toggleHistory() {
  const wrapper  = document.getElementById('historyWrapper');
  const btn      = document.getElementById('toggleHistoryBtn');
  const isHidden = wrapper.style.display === 'none';
  wrapper.style.display = isHidden ? 'block' : 'none';
  btn.innerHTML = isHidden
    ? '<i class="fa fa-chevron-up"></i> Hide'
    : '<i class="fa fa-chevron-down"></i> Show';
}

// ── View referral modal ──
function viewReferral(btn) {
  document.getElementById('vrRef').textContent     = btn.dataset.ref;
  document.getElementById('vrStudent').textContent = btn.dataset.student;
  document.getElementById('vrMeta').textContent    = btn.dataset.meta;
  document.getElementById('vrReason').textContent  = btn.dataset.reason;
  document.getElementById('vrDate').textContent    = '📅 ' + btn.dataset.date;

  const remarks      = btn.dataset.remarks;
  const remarksLabel = document.getElementById('vrRemarksLabel');
  const remarksEl    = document.getElementById('vrRemarks');

  if (remarks && remarks.trim()) {
    remarksLabel.style.display = 'block';
    remarksEl.textContent      = remarks;
    remarksEl.style.display    = 'block';
  } else {
    remarksLabel.style.display = 'none';
    remarksEl.style.display    = 'none';
  }

  document.getElementById('viewReferralModal').classList.add('show');
}

function closeReferralModalDirect() {
  document.getElementById('viewReferralModal').classList.remove('show');
}

function closeReferralModal(e) {
  if (e.target === document.getElementById('viewReferralModal')) closeReferralModalDirect();
}

// ── Notification dropdown ──
function toggleDropdown(id, e) {
  e.stopPropagation();
  document.getElementById(id).classList.toggle('show');
}
document.addEventListener('click', () => {
  document.querySelectorAll('.icon-dropdown').forEach(d => d.classList.remove('show'));
});

// ── Settings dropdown ──
function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById('settingsDropdown').classList.toggle('show');
}
document.addEventListener('click', e => {
  const menu = document.getElementById('settingsDropdown');
  const btn  = document.querySelector('.sidebar-settingsButton');
  if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove('show');
  }
});

// ── Theme ──
function toggleTheme() {
  const html     = document.documentElement;
  const newTheme = html.getAttribute("data-theme") === "light" ? "dark" : "light";
  html.setAttribute("data-theme", newTheme);
  localStorage.setItem("theme", newTheme);
}

// ── Logout ──
function logout()        { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()   { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=counselor'; }

document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});
</script>
<script>var SESSION_ROLE = 'counselor';</script>
<script src="session_timeout.js"></script>
</body>
</html>
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

// ── Signature path ──────────────────────────────────────────────────────────
$signaturePath = !empty($counselor['signature'])
    ? htmlspecialchars($counselor['signature'])
    : 'images/signature.png';

$pendingCount = (int)$conn->query(
    "SELECT COUNT(*) c FROM appointments WHERE counselor_id='$cid' AND status='Pending'"
)->fetch_assoc()['c'];

// ── AJAX: Upload signature ──────────────────────────────────────────────────
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

// ── AJAX: Lookup student by ID ──────────────────────────────────────────────
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
        ? json_encode([
            'found'      => true,
            'name'       => $row['first_name'] . ' ' . $row['last_name'],
            'year_level' => $row['year_level'],
            'course'     => $row['course'],
          ])
        : json_encode(['found' => false]);
    exit;
}

// ── POST: Create referral ───────────────────────────────────────────────────
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
    
    $_SESSION['new_referral_' . $student_id] = true;

    echo json_encode($ok
        ? ['success' => true]
        : ['success' => false, 'message' => 'Failed to save. Please try again.']);
    exit;
}
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

  /* ── Student ID lookup ── */
  .slip-id-row {
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .slip-id-row .slip-input { flex: 1; }

  .slip-id-status {
    font-size: 13px;
    min-height: 20px;
    margin-top: 4px;
    margin-bottom: 8px;
    padding-left: 2px;
  }
  .slip-id-status.found    { color: #15803d; }
  .slip-id-status.notfound { color: #e53e3e; }
  .slip-id-status.loading  { color: #888;    }

  /* readonly fields */
  input[readonly].slip-input {
    background: rgba(0,0,0,0.04);
    color: var(--text-muted, #555);
    cursor: not-allowed;
    opacity: 0.85;
  }

  /* ── Signature upload area ── */
  .sig-upload-area {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 6px;
  }

  .sig-preview-wrap {
    position: relative;
    display: inline-block;
  }

  .sig-preview {
    width: 200px;
    max-height: 90px;
    object-fit: contain;
    border: 1px dashed rgba(0,0,0,0.12);
    border-radius: 10px;
    padding: 6px;
    background: rgba(255,255,255,0.6);
    display: block;
  }

  .sig-no-preview {
    width: 200px;
    height: 70px;
    border: 1px dashed rgba(0,0,0,0.15);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: var(--text-muted, #888);
    background: rgba(0,0,0,0.02);
  }

  .sig-upload-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 9px 16px;
    border-radius: 10px;
    border: 1px solid rgba(73,136,196,0.3);
    background: rgba(73,136,196,0.08);
    color: #113f67;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: 0.2s ease;
    width: fit-content;
  }

  .sig-upload-btn:hover {
    background: rgba(73,136,196,0.15);
    border-color: rgba(73,136,196,0.5);
    transform: translateY(-1px);
  }

  .sig-upload-status {
    font-size: 12px;
    min-height: 18px;
  }
  .sig-upload-status.ok  { color: #15803d; }
  .sig-upload-status.err { color: #e53e3e; }
  .sig-upload-status.loading { color: #888; }

  /* ── Referral history table ── */
  .cReferral-history {
    margin-top: 28px;
  }

  .cReferral-history h3 {
    font-size: 15px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 14px;
    opacity: 0.9;
  }

  .cReferral-table-wrap {
    overflow-x: auto;
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,0.07);
  }

  .cReferral-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
  }

  .cReferral-table thead {
    background: rgba(17,63,103,0.07);
  }

  .cReferral-table th {
    padding: 12px 14px;
    text-align: left;
    font-weight: 700;
    color: #113f67;
    white-space: nowrap;
  }

  .cReferral-table td {
    padding: 11px 14px;
    border-top: 1px solid rgba(0,0,0,0.05);
    color: var(--text);
    vertical-align: top;
  }

  .cReferral-table tbody tr:hover {
    background: rgba(73,136,196,0.05);
  }

  .cReferral-table .no-data {
    text-align: center;
    color: var(--text-muted, #888);
    padding: 24px;
  }

  /* ── Result message ── */
  #referralResult {
    margin-top: 12px;
    font-size: 14px;
    font-weight: 500;
    min-height: 22px;
  }

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
  <div class="cReferral-card">

    <h2>REFERRAL SLIP</h2>

    <!-- DATE -->
    <div class="slip-row">
      <label>Date:</label>
      <input type="date" class="slip-input" id="refDate">
    </div>

    <!-- ══ PATIENT INFORMATION ══ -->
    <h3>Patient Information</h3>

    <!-- Student ID lookup -->
    <div class="slip-row">
      <label>Student ID:</label>
      <div style="flex:1; display:flex; flex-direction:column;">
        <div class="slip-id-row">
          <input
            type="number"
            class="slip-input"
            id="refStudentId"
            placeholder="Enter student ID"
            oninput="lookupStudent(this.value)"
          >
        </div>
        <div class="slip-id-status" id="idStatus"></div>
      </div>
    </div>

    <!-- Name (auto-filled) -->
    <div class="slip-row">
      <label>Name:</label>
      <input type="text" class="slip-input" id="refName" readonly
             placeholder="Auto-filled from Student ID">
    </div>

    <!-- Year Level (auto-filled) -->
    <div class="slip-row">
      <label>Year Level:</label>
      <input type="text" class="slip-input" id="refYear" readonly
             placeholder="Auto-filled from Student ID">
    </div>

    <!-- Course (auto-filled) -->
    <div class="slip-row">
      <label>Program / Course:</label>
      <input type="text" class="slip-input" id="refCourse" readonly
             placeholder="Auto-filled from Student ID">
    </div>

    <!-- ══ REFERRAL DETAILS ══ -->
    <h3>Reason for Referral</h3>
    <textarea class="slip-textarea" id="refReason"
              placeholder="Describe the reason for referral…"></textarea>

    <h3>Counselor's Remarks <span style="font-weight:400; opacity:0.6;">(Optional)</span></h3>
    <textarea class="slip-textarea" id="refRemarks"
              placeholder="Additional remarks or notes…"></textarea>

    <!-- ══ REFERRED BY ══ -->
    <h3>Referred By</h3>

    <!-- Signature upload -->
    <div class="sig-upload-area">

      <!-- Preview -->
      <?php if (!empty($counselor['signature']) && file_exists($counselor['signature'])): ?>
        <div class="sig-preview-wrap">
          <img src="<?= $signaturePath ?>?v=<?= time() ?>"
               class="sig-preview"
               id="sigPreviewImg"
               alt="Counselor Signature">
        </div>
      <?php else: ?>
        <div class="sig-no-preview" id="sigNoPreview">
          <span><i class="fa fa-pen-nib" style="margin-right:6px;"></i> No signature uploaded</span>
        </div>
        <img src="" class="sig-preview" id="sigPreviewImg"
             alt="Counselor Signature" style="display:none;">
      <?php endif; ?>

      <!-- Upload button -->
      <label class="sig-upload-btn" for="sigFileInput">
        <i class="fa fa-upload"></i>
        <?= !empty($counselor['signature']) ? 'Replace Signature' : 'Upload Signature' ?>
      </label>
      <input type="file" id="sigFileInput" accept="image/png, image/jpeg"
             style="display:none;" onchange="uploadSignature(this)">

      <div class="sig-upload-status" id="sigUploadStatus"></div>
    </div>

    <!-- Counselor info -->
    <p style="margin-top: 14px; font-size:14px;">
      <strong><?= $fullName ?></strong>
    </p>
    <?php if ($department): ?>
      <p style="font-size:13px; color:var(--text-muted, #64748b);"><?= $department ?></p>
    <?php endif; ?>
    <p style="font-size:13px; color:var(--text-muted, #64748b);">
      <i class="fa fa-phone" style="width:14px;"></i> <?= $phone ?>
      &nbsp;|&nbsp;
      <i class="fa fa-envelope" style="width:14px;"></i> <?= $email ?>
    </p>

    <!-- ══ SUBMIT ══ -->
    <button class="cReferral-btn" onclick="createReferral()" style="margin-top:24px;">
      <i class="fa fa-paper-plane"></i> Create Referral
    </button>
    <div id="referralResult"></div>

    <!-- ══ REFERRAL HISTORY ══ -->
    <?php
      $historyRes = $conn->query("
          SELECT r.referral_date, r.reason, r.counselor_remarks,
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
    <div class="cReferral-history">
      <h3><i class="fa fa-clock-rotate-left" style="margin-right:8px; color:#4988c4;"></i>Recent Referrals</h3>

      <div class="cReferral-table-wrap">
        <table class="cReferral-table">
          <thead>
            <tr>
              <th>Date</th>
              <th>Student</th>
              <th>Course / Year</th>
              <th>Reason</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($history)): ?>
              <tr>
                <td colspan="5" class="no-data">
                  <i class="fa fa-inbox" style="font-size:1.5rem; display:block; margin-bottom:8px; opacity:0.3;"></i>
                  No referrals issued yet.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($history as $h): ?>
                <tr>
                  <td style="white-space:nowrap;">
                    <?= htmlspecialchars(date('M d, Y', strtotime($h['referral_date']))) ?>
                  </td>
                  <td><?= htmlspecialchars($h['student_name']) ?></td>
                  <td style="white-space:nowrap;">
                    <?= htmlspecialchars($h['course']) ?><br>
                    <span style="font-size:12px; opacity:0.7;"><?= htmlspecialchars($h['year_level']) ?></span>
                  </td>
                  <td style="max-width:220px;">
                    <?= nl2br(htmlspecialchars($h['reason'])) ?>
                  </td>
                  <td style="max-width:180px;">
                    <?= !empty($h['counselor_remarks'])
                          ? nl2br(htmlspecialchars($h['counselor_remarks']))
                          : '<span style="opacity:0.4;">—</span>' ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <!-- END HISTORY -->

  </div><!-- /cReferral-card -->

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

</main>

<!-- ========================= SCRIPT ========================= -->
<script>
(function() {
    const saved = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", saved);
})();

// ── Student ID lookup ────────────────────────────────────────────────────────
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
    idStatus.className   = 'slip-id-status';
    return;
  }

  idStatus.textContent = 'Searching…';
  idStatus.className   = 'slip-id-status loading';

  lookupTimer = setTimeout(() => {
    fetch(`creferral.php?lookup_student_id=${encodeURIComponent(id)}`)
      .then(r => r.json())
      .then(data => {
        if (data.found) {
          nameField.value   = data.name;
          yearField.value   = data.year_level;
          courseField.value = data.course;
          idStatus.textContent = '✔ Student found';
          idStatus.className   = 'slip-id-status found';
        } else {
          idStatus.textContent = '✘ No active student found with this ID';
          idStatus.className   = 'slip-id-status notfound';
        }
      })
      .catch(() => {
        idStatus.textContent = '✘ Lookup failed. Please try again.';
        idStatus.className   = 'slip-id-status notfound';
      });
  }, 500);
}

// ── Signature upload ─────────────────────────────────────────────────────────
function uploadSignature(input) {
  const file     = input.files[0];
  const status   = document.getElementById('sigUploadStatus');
  const preview  = document.getElementById('sigPreviewImg');
  const noPreview = document.getElementById('sigNoPreview');

  if (!file) return;

  status.textContent = 'Uploading…';
  status.className   = 'sig-upload-status loading';

  const fd = new FormData();
  fd.append('action',    'upload_signature');
  fd.append('signature', file);

  fetch('creferral.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        // Show preview immediately
        const reader = new FileReader();
        reader.onload = e => {
          preview.src          = e.target.result;
          preview.style.display = 'block';
          if (noPreview) noPreview.style.display = 'none';
        };
        reader.readAsDataURL(file);

        status.textContent = '✔ Signature saved successfully!';
        status.className   = 'sig-upload-status ok';
      } else {
        status.textContent = '✘ ' + (data.message || 'Upload failed.');
        status.className   = 'sig-upload-status err';
      }
    })
    .catch(() => {
      status.textContent = '✘ Upload failed. Please try again.';
      status.className   = 'sig-upload-status err';
    });
}

// ── Create referral ──────────────────────────────────────────────────────────
function createReferral() {
  const studentId = document.getElementById('refStudentId').value.trim();
  const date      = document.getElementById('refDate').value;
  const name      = document.getElementById('refName').value.trim();
  const year      = document.getElementById('refYear').value.trim();
  const course    = document.getElementById('refCourse').value.trim();
  const reason    = document.getElementById('refReason').value.trim();
  const remarks   = document.getElementById('refRemarks').value.trim();
  const result    = document.getElementById('referralResult');

  if (!studentId || !name) {
    result.innerHTML = "<span style='color:#e53e3e;'>⚠ Please enter a valid Student ID first.</span>";
    return;
  }
  if (!date || !reason) {
    result.innerHTML = "<span style='color:#e53e3e;'>⚠ Date and Reason for Referral are required.</span>";
    return;
  }

  result.innerHTML = "<span style='color:#888;'>Saving…</span>";

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
        result.innerHTML = "<span style='color:#15803d;'>✔ Referral created successfully!</span>";
        // Clear form
        document.getElementById('refStudentId').value = '';
        document.getElementById('refName').value      = '';
        document.getElementById('refYear').value      = '';
        document.getElementById('refCourse').value    = '';
        document.getElementById('refReason').value    = '';
        document.getElementById('refRemarks').value   = '';
        document.getElementById('idStatus').textContent = '';
        document.getElementById('idStatus').className   = 'slip-id-status';
        // Reload after 1.5s to refresh history table
        setTimeout(() => location.reload(), 1500);
      } else {
        result.innerHTML = "<span style='color:#e53e3e;'>❌ " + (json.message || 'Something went wrong.') + "</span>";
      }
    })
    .catch(() => {
      result.innerHTML = "<span style='color:#e53e3e;'>❌ Something went wrong.</span>";
    });
}

// ── Notification dropdown ─────────────────────────────────────────────────────
function toggleDropdown(id, e) {
  e.stopPropagation();
  document.getElementById(id).classList.toggle('show');
}
document.addEventListener('click', () => {
  document.querySelectorAll('.icon-dropdown').forEach(d => d.classList.remove('show'));
});

// ── Settings dropdown ─────────────────────────────────────────────────────────
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

// ── Theme toggle ──────────────────────────────────────────────────────────────
function toggleTheme() {
    const html = document.documentElement;
    const newTheme = html.getAttribute("data-theme") === "light" ? "dark" : "light";
    html.setAttribute("data-theme", newTheme);
    localStorage.setItem("theme", newTheme);
}

// ── Logout ────────────────────────────────────────────────────────────────────
function logout()        { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()   { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=counselor'; }

document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});

</script>
</body>
</html>
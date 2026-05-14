<?php
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) session_start();

// ===== GUARD: must be logged in =====
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: slogin.php");
    exit;
}

// archived students can only view history
if (!empty($_SESSION['is_archived'])) {
    header("Location: shistory.php");
    exit;
}

$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");
$sid  = $conn->real_escape_string($_SESSION['user_id']);
require_once 'scheck_reports_badge.php';

// ===== HANDLE REMOVE PHOTO (AJAX) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_photo') {
    header('Content-Type: application/json');
    // Get current image path
    $cur = $conn->query("SELECT profile_image FROM student_profiles WHERE student_id='$sid' LIMIT 1");
    $row = $cur ? $cur->fetch_assoc() : null;
    if ($row && !empty($row['profile_image']) && file_exists($row['profile_image'])) {
        @unlink($row['profile_image']);
    }
    $ok = $conn->query("UPDATE student_profiles SET profile_image=NULL WHERE student_id='$sid'");
    echo json_encode($ok
        ? ['success' => true, 'message' => 'Profile photo removed.']
        : ['success' => false, 'message' => 'Failed to remove photo.']);
    exit;
}

// ===== HANDLE PROFILE UPDATE (AJAX) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    header('Content-Type: application/json');

    $phone           = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $emergencyName   = $conn->real_escape_string(trim($_POST['emergency_name'] ?? ''));
    $emergencyRel    = $conn->real_escape_string(trim($_POST['emergency_relation'] ?? ''));
    $emergencyNumber = $conn->real_escape_string(trim($_POST['emergency_number'] ?? ''));

    // Validate phone format
    if ($phone && !preg_match('/^09\d{9}$/', $phone)) {
        echo json_encode(["success" => false, "message" => "Phone number must be 11 digits starting with 09."]);
        exit;
    }
    if ($emergencyNumber && !preg_match('/^09\d{9}$/', $emergencyNumber)) {
        echo json_encode(["success" => false, "message" => "Emergency contact number must be 11 digits starting with 09."]);
        exit;
    }

    // Validate relationship enum
    $allowedRel = ['Mother', 'Father', 'Guardian'];
    if ($emergencyRel && !in_array($emergencyRel, $allowedRel)) {
        echo json_encode(["success" => false, "message" => "Relationship must be Mother, Father, or Guardian."]);
        exit;
    }

    // Handle profile image upload
    $profileImage = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $uploadDir = 'uploads/profiles/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext     = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            echo json_encode(["success" => false, "message" => "Only JPG, PNG, or WEBP images are allowed."]);
            exit;
        }
        if ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
            echo json_encode(["success" => false, "message" => "Image must be under 2MB."]);
            exit;
        }

        $fileName     = 'student_' . $sid . '_' . time() . '.' . $ext;
        $profileImage = $uploadDir . $fileName;
        move_uploaded_file($_FILES['profile_image']['tmp_name'], $profileImage);
    }

    // Check if student_profiles row already exists
    $check = $conn->query("SELECT profile_id FROM student_profiles WHERE student_id='$sid' LIMIT 1");

    if ($check->num_rows > 0) {
        // UPDATE existing row
        $imgSql = $profileImage ? ", profile_image='$profileImage'" : "";
        $relVal = $emergencyRel ? "'$emergencyRel'" : "NULL";
        $ok = $conn->query(
            "UPDATE student_profiles SET
                contact_details='$phone',
                emergency_contact_name='$emergencyName',
                relationship_to_emergency_contact=$relVal,
                emergency_contact_number='$emergencyNumber'
                $imgSql
             WHERE student_id='$sid'"
        );
    } else {
        // INSERT new row
        $pid    = strtoupper(substr(uniqid(), -6));
        $imgVal = $profileImage ? "'$profileImage'" : "NULL";
        $relVal = $emergencyRel ? "'$emergencyRel'" : "NULL";
        $ok = $conn->query(
            "INSERT INTO student_profiles
                (profile_id, student_id, contact_details, emergency_contact_name,
                 relationship_to_emergency_contact, emergency_contact_number, profile_image)
             VALUES ('$pid','$sid','$phone','$emergencyName',$relVal,'$emergencyNumber',$imgVal)"
        );
    }

    if ($ok) {
        $imgPath = $profileImage ?? null;
        echo json_encode(["success" => true, "message" => "Profile updated successfully.", "image" => $imgPath]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to save. Please try again."]);
    }
    exit;
}

// ===== LOAD STUDENT DATA =====
$studentRes = $conn->query("SELECT * FROM students WHERE student_id='$sid' LIMIT 1");
$student    = $studentRes->fetch_assoc();

$profileRes = $conn->query("SELECT * FROM student_profiles WHERE student_id='$sid' LIMIT 1");
$profile    = $profileRes->fetch_assoc();

$fullName    = htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$email       = htmlspecialchars($student['email'] ?? '');
$course      = htmlspecialchars($student['course'] ?? '');
$yearLevel   = htmlspecialchars($student['year_level'] ?? '');
$phone       = htmlspecialchars($profile['contact_details'] ?? '');
$emergName   = htmlspecialchars($profile['emergency_contact_name'] ?? '');
$emergRel    = htmlspecialchars($profile['relationship_to_emergency_contact'] ?? '');
$emergNumber = htmlspecialchars($profile['emergency_contact_number'] ?? '');
$profileImg  = !empty($profile['profile_image'])
               ? htmlspecialchars($profile['profile_image'])
               : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff&size=120';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Student Profile</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
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

<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="body">
<?php
$_totalReportUnseen = $_totalReportUnseen ?? 0;
?>
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
        <a href="sprofile.php" class="active"><i class="fa fa-user"></i> Profile</a>
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
  <div class="topbar-left">
    <h2>Student Profile</h2>
  </div>
  <div class="topbar-right">
    <div class="topbar-user">
      <img id="topbarAvatar" src="<?= $profileImg ?>" alt="user"
           onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($fullName) ?>&background=113f67&color=fff'">
      <div>
        <strong><?= $fullName ?></strong>
        <p><?= $email ?></p>
      </div>
    </div>
  </div>
</header>

<!-- PROFILE -->
<main class="sProfile-main">
  <div class="sTicket-container">
    <div class="card sProfile-card">

      <div class="sProfile-header">
        <div style="display:flex; flex-direction:column; align-items:center; gap:8px; flex-shrink:0;">
          <div class="sProfile-avatar">
            <img id="preview" src="<?= $profileImg ?>"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($fullName) ?>&background=113f67&color=fff&size=120'">
            <label for="fileUpload" class="sProfile-upload" title="Change photo">
              <i class="fa fa-camera"></i>
            </label>
            <input type="file" id="fileUpload" accept="image/*" hidden onchange="loadImage(event)">
          </div>
          <button type="button" id="removePhotoBtn" onclick="removePhoto()" title="Remove photo"
            style="background:none; border:1px solid #e53e3e; color:#e53e3e; border-radius:8px; padding:5px 14px; font-size:12px; cursor:pointer; align-items:center; justify-content:center; gap:6px; transition:0.2s; width:120px; display:<?= (!empty($profile['profile_image']) && file_exists($profile['profile_image'])) ? 'flex' : 'none' ?>;">
            <i class="fa fa-trash"></i> Remove Photo
          </button>
        </div>
        <div>
          <h3><?= $fullName ?></h3>
          <p class="sProfile-muted">
            You can update your phone number, emergency contact, and profile picture.
          </p>
        </div>
      </div>

      <div class="sProfile-form">

        <div class="form-group">
          <label>Full Name</label>
          <input type="text" value="<?= $fullName ?>" readonly>
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" value="<?= $email ?>" readonly>
        </div>

        <div class="form-group">
          <label>Course</label>
          <input type="text" value="<?= $course ?>" readonly>
        </div>

        <div class="form-group">
          <label>Year Level</label>
          <input type="text" value="<?= $yearLevel ?>" readonly>
        </div>

        <div class="form-group">
          <label>Phone Number</label>
          <input id="phone" type="text" value="<?= $phone ?>" placeholder="09XX-XXX-XXXX" maxlength="11" inputmode="numeric">
        </div>

        <div class="form-group">
          <label>Emergency Contact Name</label>
          <input id="emergencyName" type="text" value="<?= $emergName ?>" placeholder="Enter emergency contact name">
        </div>

        <div class="form-group">
          <label>Relationship</label>
          <select id="emergencyRelation">
            <option value="">-- Select Relationship --</option>
            <option value="Mother"   <?= $emergRel === 'Mother'   ? 'selected' : '' ?>>Mother</option>
            <option value="Father"   <?= $emergRel === 'Father'   ? 'selected' : '' ?>>Father</option>
            <option value="Guardian" <?= $emergRel === 'Guardian' ? 'selected' : '' ?>>Guardian</option>
          </select>
        </div>

        <div class="form-group">
          <label>Emergency Contact Number</label>
          <input id="emergencyNumber" type="text" value="<?= $emergNumber ?>" placeholder="09XX-XXX-XXXX" maxlength="11" inputmode="numeric">
        </div>

        <button class="btn sProfile-saveBtn" onclick="saveProfile()">
          Save Changes
        </button>

        <div id="status" class="sProfile-status"></div>

      </div>
    </div>
  </div>
<!-- LOGOUT MODAL -->
  <div class="logout-overlay" id="logoutOverlay">
    <div class="logout-modal">
      <div class="logout-icon">
        <i class="fa fa-right-from-bracket"></i>
      </div>
      <h3>Logout</h3>
      <p>Are you sure you want to logout?</p>
      <div class="logout-actions">
        <button class="logout-btn logout-btn--cancel" onclick="closeLogout()">Cancel</button>
        <button class="logout-btn logout-btn--confirm" onclick="confirmLogout()">Yes, Logout</button>
      </div>
    </div>
  </div>
</main>

<script>
(function() {
    const saved = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", saved);
})();

// ===== SIDEBAR =====
function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}
function toggleTheme() {
    const html = document.documentElement;
    const newTheme = html.getAttribute("data-theme") === "light" ? "dark" : "light";
    html.setAttribute("data-theme", newTheme);
    localStorage.setItem("theme", newTheme);
}
function logout() {
  document.getElementById('logoutOverlay').classList.add('show');
}
function closeLogout() {
  document.getElementById('logoutOverlay').classList.remove('show');
}
function confirmLogout() {
    window.location.href = 'logout.php?role=student';
}

// Close when clicking outside
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});

document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn  = document.querySelector(".sidebar-settingsButton");
  if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target))
    menu.classList.remove("show");
});

// ===== PHONE NUMBER ENFORCEMENT =====
function enforcePhone(input) {
  // Strip all non-digits
  let val = input.value.replace(/\D/g, '');
  // Always force leading "09"
  if (val.length === 0) { val = '09'; }
  else if (val.length === 1) { val = val === '0' ? '09' : '09'; }
  else if (val.substring(0, 2) !== '09') { val = '09' + val.replace(/^0*9*/, ''); }
  // Cap at 11 digits
  val = val.substring(0, 11);
  input.value = val;
}

function validatePhone(value, label) {
  if (!value) return label + ' is required.';
  if (!/^09\d{9}$/.test(value)) return label + ' must be 11 digits starting with 09.';
  return null;
}

// Attach enforcement to both phone inputs on page load
document.addEventListener('DOMContentLoaded', function() {
  ['phone', 'emergencyNumber'].forEach(function(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', function() { enforcePhone(this); });
    el.addEventListener('focus', function() { if (!this.value) this.value = '09'; });
    el.addEventListener('blur',  function() { if (this.value === '09') this.value = ''; });
    el.addEventListener('keydown', function(e) {
      // Allow: backspace, delete, tab, arrows, home, end
      const allowed = [8,9,37,38,39,40,46,35,36];
      if (allowed.includes(e.keyCode)) return;
      // Block non-digit keys
      if (e.key < '0' || e.key > '9') e.preventDefault();
    });
  });
});

// ===== IMAGE PREVIEW =====
function loadImage(event) {
  const file = event.target.files[0];
  if (!file) return;
  document.getElementById("preview").src = URL.createObjectURL(file);
  // Show remove button when a new file is selected
  const removeBtn = document.getElementById("removePhotoBtn");
  if (removeBtn) { removeBtn.style.display = 'flex'; }
}

// ===== REMOVE PHOTO =====
function removePhoto() {
  if (!confirm('Remove your profile photo?')) return;
  const statusEl = document.getElementById("status");
  const removeBtn = document.getElementById("removePhotoBtn");
  statusEl.innerHTML = "<span class='tag info'>Removing...</span>";

  const fd = new FormData();
  fd.append('action', 'remove_photo');

  fetch('sprofile.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        const fallback = 'https://ui-avatars.com/api/?name=<?= urlencode($fullName) ?>&background=113f67&color=fff&size=120';
        document.getElementById("preview").src = fallback;
        document.getElementById("topbarAvatar").src = fallback;
        // Clear file input so upload still works
        document.getElementById("fileUpload").value = '';
        if (removeBtn) removeBtn.style.display = 'none';
        statusEl.innerHTML = "<span class='tag info'>" + json.message + "</span>";
      } else {
        statusEl.innerHTML = "<span class='tag warning'>" + json.message + "</span>";
      }
    })
    .catch(() => {
      statusEl.innerHTML = "<span class='tag warning'>Something went wrong. Please try again.</span>";
    });
}

// ===== SAVE PROFILE =====
function saveProfile() {
  const phone          = document.getElementById("phone").value.trim();
  const emergencyName  = document.getElementById("emergencyName").value.trim();
  const emergencyRel   = document.getElementById("emergencyRelation").value;
  const emergencyNumber= document.getElementById("emergencyNumber").value.trim();
  const fileInput      = document.getElementById("fileUpload");
  const statusEl       = document.getElementById("status");

  if (!phone) {
    statusEl.innerHTML = "<span class='tag warning'>Please enter your phone number.</span>";
    return;
  }
  const phoneErr = validatePhone(phone, 'Phone number');
  if (phoneErr) {
    statusEl.innerHTML = "<span class='tag warning'>" + phoneErr + "</span>";
    return;
  }
  if (emergencyNumber) {
    const emergErr = validatePhone(emergencyNumber, 'Emergency contact number');
    if (emergErr) {
      statusEl.innerHTML = "<span class='tag warning'>" + emergErr + "</span>";
      return;
    }
  }

  const fd = new FormData();
  fd.append('action',             'update_profile');
  fd.append('phone',              phone);
  fd.append('emergency_name',     emergencyName);
  fd.append('emergency_relation', emergencyRel);
  fd.append('emergency_number',   emergencyNumber);

  if (fileInput.files[0]) {
    fd.append('profile_image', fileInput.files[0]);
  }

  statusEl.innerHTML = "<span class='tag info'>Saving...</span>";

  fetch('sprofile.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      if (json.success) {
        statusEl.innerHTML = "<span class='tag info'>" + json.message + "</span>";
        if (fileInput.files[0]) {
          const newSrc = document.getElementById("preview").src;
          document.getElementById("topbarAvatar").src = newSrc;
          // Show remove button after successful upload
          const removeBtn = document.getElementById("removePhotoBtn");
          if (removeBtn) removeBtn.style.display = 'flex';
        }
      } else {
        statusEl.innerHTML = "<span class='tag warning'>" + json.message + "</span>";
      }
    })
    .catch(() => {
      statusEl.innerHTML = "<span class='tag warning'>Something went wrong. Please try again.</span>";
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
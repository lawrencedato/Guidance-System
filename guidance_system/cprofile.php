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

// ── HANDLE REMOVE PHOTO (AJAX) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove_photo') {
    header('Content-Type: application/json');
    $currentImg = $counselor['profile_image'] ?? '';
    if ($currentImg && file_exists($currentImg)) {
        @unlink($currentImg);
    }
    $ok = $conn->query("UPDATE counselors SET profile_image=NULL WHERE counselor_id='$cid'");
    echo json_encode($ok
        ? ['success' => true, 'message' => 'Profile photo removed.']
        : ['success' => false, 'message' => 'Failed to remove photo.']);
    exit;
}

// ── HANDLE PROFILE UPDATE (AJAX) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    header('Content-Type: application/json');

    $phone = $conn->real_escape_string(trim($_POST['phone'] ?? ''));

    if ($phone && !preg_match('/^09\d{9}$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Contact number must be 11 digits starting with 09.']);
        exit;
    }

    $profileImage = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $uploadDir = 'uploads/profiles/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $ext     = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, or WEBP images allowed.']);
            exit;
        }
        if ($_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
            echo json_encode(['success' => false, 'message' => 'Image must be under 2MB.']);
            exit;
        }

        $fileName     = 'counselor_' . $cid . '_' . time() . '.' . $ext;
        $profileImage = $uploadDir . $fileName;
        move_uploaded_file($_FILES['profile_image']['tmp_name'], $profileImage);
    }

    $imgSql = $profileImage ? ", profile_image='" . $conn->real_escape_string($profileImage) . "'" : "";
    $ok = $conn->query(
        "UPDATE counselors SET contact_number='$phone' $imgSql WHERE counselor_id='$cid'"
    );

    echo json_encode($ok
        ? ['success' => true, 'message' => 'Profile updated successfully.', 'image' => $profileImage]
        : ['success' => false, 'message' => 'Failed to save. Please try again.']);
    exit;
}

$fullName   = htmlspecialchars(($counselor['first_name'] ?? '') . ' ' . ($counselor['last_name'] ?? ''));
$email      = htmlspecialchars($counselor['email'] ?? '');
$profileImg = !empty($counselor['profile_image'])
    ? htmlspecialchars($counselor['profile_image'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';

$hasPhoto = !empty($counselor['profile_image']) && file_exists($counselor['profile_image']);

$pendingCount = (int)$conn->query(
    "SELECT COUNT(*) c FROM appointments WHERE counselor_id='$cid' AND status='Pending'"
)->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Profile</title>
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
        <a href="cprofile.php" class="active"><i class="fa fa-user"></i> Profile</a>
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
    <a href="creferral.php"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Profile</h2>
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
<main class="cProfile-main">

  <div class="cProfile-container">
    <div class="card cProfile-card">

      <div class="cProfile-header">

        <div class="cProfile-avatar-section">
          <div class="cProfile-avatar">
            <img id="preview" src="<?= $profileImg ?>"
                 onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($fullName) ?>&background=113f67&color=fff&size=120'">

            <label for="fileUpload" class="cProfile-upload" title="Change photo">
              <i class="fa fa-camera"></i>
            </label>

            <input type="file" id="fileUpload" hidden onchange="loadImage(event)">
          </div>

          <button type="button"
                  id="removePhotoBtn"
                  class="cProfile-remove-btn<?= $hasPhoto ? '' : ' cProfile-remove-btn--hidden' ?>"
                  onclick="removePhoto()">
            <i class="fa fa-trash"></i> Remove Photo
          </button>
        </div>

        <div>
          <h3 id="displayName"><?= $fullName ?></h3>
          <p class="cProfile-muted">Counselor account</p>
        </div>

      </div>

      <div class="cProfile-form">

        <div class="form-group">
          <label>Full Name</label>
          <input type="text" value="<?= $fullName ?>" readonly>
        </div>

        <div class="form-group">
          <label>Email</label>
          <input type="email" value="<?= $email ?>" readonly>
        </div>

        <div class="form-group">
          <label>Department</label>
          <input type="text" value="<?= htmlspecialchars($counselor['department'] ?? '') ?>" readonly>
        </div>

        <div class="form-group">
          <label>Contact Number</label>
          <input id="phone" type="text" placeholder="09XX-XXX-XXXX" maxlength="11" inputmode="numeric"
                 value="<?= htmlspecialchars($counselor['contact_number'] ?? '') ?>">
        </div>

        <button class="btn cProfile-saveBtn" onclick="saveProfile()">
          Save Changes
        </button>

        <div id="status" class="cProfile-status"></div>

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
(function () {
  const saved = localStorage.getItem("theme") || "light";
  document.documentElement.setAttribute("data-theme", saved);
})();

/* ── Settings dropdown ── */
function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}

/* Close settings when clicking outside */
document.addEventListener("click", function (e) {
  const menu = document.getElementById("settingsDropdown");
  const btn  = document.querySelector(".sidebar-settingsButton");
  if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }

  /* Also close notification dropdown */
  document.querySelectorAll(".icon-dropdown.show")
    .forEach(function (el) { el.classList.remove("show"); });
});

/* ── Notification dropdown ── */
function toggleDropdown(id, e) {
  e.stopPropagation();
  document.getElementById(id).classList.toggle("show");
}

/* ── Theme ── */
function toggleTheme() {
  const html     = document.documentElement;
  const newTheme = html.getAttribute("data-theme") === "light" ? "dark" : "light";
  html.setAttribute("data-theme", newTheme);
  localStorage.setItem("theme", newTheme);
}

/* ── Logout ── */
function logout()      { document.getElementById("logoutOverlay").classList.add("show"); }
function closeLogout() { document.getElementById("logoutOverlay").classList.remove("show"); }
function confirmLogout() { window.location.href = "logout.php?role=counselor"; }

document.getElementById("logoutOverlay").addEventListener("click", function (e) {
  if (e.target === this) closeLogout();
});

/* ── Phone number enforcement ── */
function enforcePhone(input) {
  let val = input.value.replace(/\D/g, "");
  if (val.length === 0 || val.length === 1) { val = "09"; }
  else if (val.substring(0, 2) !== "09")    { val = "09" + val.replace(/^0*9*/, ""); }
  val = val.substring(0, 11);
  input.value = val;
}

function validatePhone(value, label) {
  if (!value)                       return label + " is required.";
  if (!/^09\d{9}$/.test(value))    return label + " must be 11 digits starting with 09.";
  return null;
}

document.addEventListener("DOMContentLoaded", function () {
  const el = document.getElementById("phone");
  if (!el) return;
  el.addEventListener("input",   function () { enforcePhone(this); });
  el.addEventListener("focus",   function () { if (!this.value) this.value = "09"; });
  el.addEventListener("blur",    function () { if (this.value === "09") this.value = ""; });
  el.addEventListener("keydown", function (e) {
    const allowed = [8, 9, 37, 38, 39, 40, 46, 35, 36];
    if (allowed.includes(e.keyCode)) return;
    if (e.key < "0" || e.key > "9") e.preventDefault();
  });
});

/* ── Image preview ── */
function loadImage(event) {
  document.getElementById("preview").src = URL.createObjectURL(event.target.files[0]);
  const removeBtn = document.getElementById("removePhotoBtn");
  if (removeBtn) removeBtn.classList.remove("cProfile-remove-btn--hidden");
}

/* ── Remove photo ── */
function removePhoto() {
  if (!confirm("Remove your profile photo?")) return;

  const statusEl  = document.getElementById("status");
  const removeBtn = document.getElementById("removePhotoBtn");
  statusEl.innerHTML = "<span class='tag info'>Removing...</span>";

  const fd = new FormData();
  fd.append("action", "remove_photo");

  fetch("cprofile.php", { method: "POST", body: fd })
    .then(function (r) { return r.json(); })
    .then(function (json) {
      if (json.success) {
        const fallback = "https://ui-avatars.com/api/?name=<?= urlencode($fullName) ?>&background=113f67&color=fff";
        document.getElementById("preview").src = fallback;
        const topbar = document.getElementById("topbarAvatar");
        if (topbar) topbar.src = fallback;
        document.getElementById("fileUpload").value = "";
        if (removeBtn) removeBtn.classList.add("cProfile-remove-btn--hidden");
        statusEl.innerHTML = "<span class='tag info'>" + json.message + "</span>";
      } else {
        statusEl.innerHTML = "<span class='tag warning'>" + json.message + "</span>";
      }
    })
    .catch(function () {
      statusEl.innerHTML = "<span class='tag warning'>Something went wrong. Please try again.</span>";
    });
}

/* ── Save profile ── */
function saveProfile() {
  const phone     = document.getElementById("phone").value.trim();
  const fileInput = document.getElementById("fileUpload");
  const statusEl  = document.getElementById("status");

  if (!phone) {
    statusEl.innerHTML = "<span class='tag warning'>Please enter your contact number.</span>";
    return;
  }
  const phoneErr = validatePhone(phone, "Contact number");
  if (phoneErr) {
    statusEl.innerHTML = "<span class='tag warning'>" + phoneErr + "</span>";
    return;
  }

  const fd = new FormData();
  fd.append("action", "update_profile");
  fd.append("phone",  phone);
  if (fileInput.files[0]) fd.append("profile_image", fileInput.files[0]);

  statusEl.innerHTML = "<span class='tag info'>Saving...</span>";

  fetch("cprofile.php", { method: "POST", body: fd })
    .then(function (r) { return r.json(); })
    .then(function (json) {
      if (json.success) {
        statusEl.innerHTML = "<span class='tag info'>" + json.message + "</span>";
        if (json.image) {
          document.getElementById("preview").src = json.image;
          const topbar = document.getElementById("topbarAvatar");
          if (topbar) topbar.src = json.image;
          const removeBtn = document.getElementById("removePhotoBtn");
          if (removeBtn) removeBtn.classList.remove("cProfile-remove-btn--hidden");
        }
      } else {
        statusEl.innerHTML = "<span class='tag warning'>" + json.message + "</span>";
      }
    })
    .catch(function () {
      statusEl.innerHTML = "<span class='tag warning'>Something went wrong. Please try again.</span>";
    });
}
</script>
<script>var SESSION_ROLE = 'counselor';</script>
<script src="session_timeout.js"></script>
</body>
</html>
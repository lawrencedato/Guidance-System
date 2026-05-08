<?php
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) session_start();

// ===== GUARD: must be logged in =====
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: slogin.php");
    exit;
}

$conn = new mysqli("127.0.0.1", "root", "", "gcs_db");
$sid  = $conn->real_escape_string($_SESSION['user_id']);

// ===== HANDLE PROFILE UPDATE (AJAX) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    header('Content-Type: application/json');

    $phone           = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $emergencyName   = $conn->real_escape_string(trim($_POST['emergency_name'] ?? ''));
    $emergencyRel    = $conn->real_escape_string(trim($_POST['emergency_relation'] ?? ''));
    $emergencyNumber = $conn->real_escape_string(trim($_POST['emergency_number'] ?? ''));

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
        <a href="sprofile.php" class="active"><i class="fa fa-user"></i> Profile</a>
        <a href="shistory.php"><i class="fa fa-clock"></i> Session History</a>
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
        <div class="sProfile-avatar">
          <img id="preview" src="<?= $profileImg ?>"
               onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($fullName) ?>&background=113f67&color=fff&size=120'">
          <label for="fileUpload" class="sProfile-upload">
            <i class="fa fa-camera"></i>
          </label>
          <input type="file" id="fileUpload" accept="image/*" hidden onchange="loadImage(event)">
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
          <input id="phone" type="text" value="<?= $phone ?>" placeholder="Enter phone number">
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
          <input id="emergencyNumber" type="text" value="<?= $emergNumber ?>" placeholder="Enter emergency contact number">
        </div>

        <button class="btn sProfile-saveBtn" onclick="saveProfile()">
          Save Changes
        </button>

        <div id="status" class="sProfile-status"></div>

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

// ===== IMAGE PREVIEW =====
function loadImage(event) {
  const file = event.target.files[0];
  if (!file) return;
  document.getElementById("preview").src = URL.createObjectURL(file);
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
        // Update topbar avatar if a new image was uploaded
        if (fileInput.files[0]) {
          const newSrc = document.getElementById("preview").src;
          document.getElementById("topbarAvatar").src = newSrc;
        }
      } else {
        statusEl.innerHTML = "<span class='tag warning'>" + json.message + "</span>";
      }
    })
    .catch(() => {
      statusEl.innerHTML = "<span class='tag warning'>Something went wrong. Please try again.</span>";
    });
}
</script>
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

</body>
</html>
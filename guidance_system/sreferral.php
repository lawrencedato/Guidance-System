<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);
 
if (session_status() === PHP_SESSION_NONE) session_start();
 
// ===== GUARD =====
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: slogin.php");
    exit;
}

// archived students can only view history
if (!empty($_SESSION['is_archived'])) {
    header("Location: shistory.php");
    exit;
}
 
// ===== DB CONNECTION =====
$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");
$sid  = $conn->real_escape_string($_SESSION['user_id']);
require_once 'scheck_reports_badge.php';

$conn->query("UPDATE referrals SET is_seen=1 WHERE student_id='$sid'");
 
// ===== LOAD STUDENT DATA =====
$studentRes = $conn->query("SELECT * FROM students WHERE student_id='$sid' LIMIT 1");
$student    = $studentRes->fetch_assoc();
 
$profileRes = $conn->query("SELECT contact_details, profile_image FROM student_profiles WHERE student_id='$sid' LIMIT 1");
$profile    = $profileRes->fetch_assoc();
 
$fullName   = htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$email      = htmlspecialchars($student['email'] ?? '');
$yearLevel  = htmlspecialchars($student['year_level'] ?? '');
$course     = htmlspecialchars($student['course'] ?? '');
$contact    = htmlspecialchars($profile['contact_details'] ?? 'N/A');
$profileImg = !empty($profile['profile_image'])
              ? htmlspecialchars($profile['profile_image'])
              : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';
 
// ===== LOAD LATEST REFERRAL (include counselor signature) =====
$referralRes = $conn->query("
    SELECT r.referral_date, r.reason, r.counselor_remarks,
           CONCAT(c.first_name, ' ', c.last_name) AS counselor_name,
           c.department,
           c.contact_number,
           c.signature
    FROM referrals r
    JOIN counselors c ON r.counselor_id = c.counselor_id
    WHERE r.student_id='$sid'
    ORDER BY r.created_at DESC
    LIMIT 1
");
$referral = $referralRes ? $referralRes->fetch_assoc() : null;
 
// ===== REFERRAL COUNT (badge) =====
$countRes      = $conn->query("SELECT COUNT(*) AS total FROM referrals WHERE student_id='$sid'");
$referralCount = $countRes ? (int)$countRes->fetch_assoc()['total'] : 0;
 
// ===== SIGNATURE PATH =====
$signaturePath = '';
if (!empty($referral['signature']) && file_exists($referral['signature'])) {
    $signaturePath = htmlspecialchars($referral['signature']) . '?v=' . filemtime($referral['signature']);
} elseif (file_exists('images/signature.png')) {
    $signaturePath = 'images/signature.png';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Referral</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<style>
  .referral-badge {
    display: inline-block;
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
 
  /* ── Signature container ── */
  .sReferral-sig-wrap {
    margin: 14px 0 6px;
  }
 
  .sReferral-sig-wrap img {
    width: 180px;
    max-height: 85px;
    object-fit: contain;
    display: block;
  }
 
  .sReferral-sig-placeholder {
    width: 180px;
    height: 60px;
    border-bottom: 1px solid #0f172a;
    margin-bottom: 6px;
  }
 
  /* ── Info rows inside card ── */
  .sReferral-info-row {
    display: flex;
    gap: 6px;
    margin: 5px 0;
    font-size: 14px;
    line-height: 1.5;
  }
 
  .sReferral-info-row b {
    min-width: 120px;
    color: #113f67;
    flex-shrink: 0;
  }
  
  /* Softer version — slightly warm tone */
  [data-theme="dark"] .sReferral-sig-wrap img {
    filter: invert(1) brightness(1.5) opacity(0.85);
  }

  [data-theme="dark"] .sReferral-sig-placeholder {
    border-bottom-color: rgba(255, 255, 255, 0.3);
  }

  [data-theme="dark"] .sReferral-card h3 {
    color: #6daadf;
  }

  [data-theme="dark"] .sReferral-info-row b {
    color: #6daadf;
  }
</style>
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

<!-- ========================= TOPBAR ========================= -->
<header class="topbar">

  <div class="topbar-left">
    <h2>Referral Slip</h2>
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
<main class="sReferral-main">

  <?php if ($referral): ?>

  <!-- REFERRAL CARD -->
  <section class="sReferral-card" id="sReferral-slip">

    <h2 class="sReferral-title">REFERRAL SLIP</h2>

    <p class="sReferral-date">
      <b>Date:</b> <span><?= htmlspecialchars(date('F d, Y', strtotime($referral['referral_date']))) ?></span>
    </p>

    <hr>

    <!-- STUDENT INFO -->
    <h3>Student Information</h3>
    <p><b>Name:</b> <span><?= $fullName ?></span></p>
    <p><b>Year Level:</b> <span><?= $yearLevel ?></span></p>
    <p><b>Program:</b> <span><?= $course ?></span></p>
    <p><b>Contact:</b> <span><?= $contact ?></span></p>

    <hr>

    <!-- REFERRAL DETAILS -->
    <h3>Referral Details</h3>
    <p><b>Reason:</b></p>
    <p><?= nl2br(htmlspecialchars($referral['reason'])) ?></p>

    <?php if (!empty($referral['counselor_remarks'])): ?>
    <p><b>Counselor Remarks:</b></p>
    <p><?= nl2br(htmlspecialchars($referral['counselor_remarks'])) ?></p>
    <?php endif; ?>

    <hr>

        <!-- REFERRED BY -->
    <h3>Referred By</h3>
 
    <!-- Dynamic counselor signature -->
    <div class="sReferral-sig-wrap">
      <?php if ($signaturePath): ?>
        <img src="<?= $signaturePath ?>" alt="Counselor Signature">
      <?php else: ?>
        <div class="sReferral-sig-placeholder"></div>
      <?php endif; ?>
    </div>
 
    <div class="sReferral-info-row">
      <b>Counselor</b>
      <span>: <?= htmlspecialchars($referral['counselor_name']) ?></span>
    </div>
    <div class="sReferral-info-row">
      <b>Office</b>
      <span>: <?= htmlspecialchars($referral['department']) ?></span>
    </div>
    <?php if (!empty($referral['contact_number'])): ?>
    <div class="sReferral-info-row">
      <b>Contact</b>
      <span>: <?= htmlspecialchars($referral['contact_number']) ?></span>
    </div>
    <?php endif; ?>
 
    <hr>
      <a href="sreferral_export.php" class="sReferral-btn" style="display:block; text-align:center; text-decoration:none; margin-top:20px;">
        <i class="fa fa-file-pdf"></i> Export PDF
      </a>
  

  </section>


  <?php else: ?>

  <!-- NO REFERRAL STATE -->
  <section class="sReferral-card">
    <div style="text-align: center; padding: 2rem 0;">
      <i class="fa fa-route" style="font-size: 3rem; opacity: 0.3; display: block; margin-bottom: 1rem;"></i>
      <h3>No Referral Found</h3>
      <p style="opacity: 0.6;">You have no referral slip on record yet.</p>
    </div>
  </section>

  <?php endif; ?>

</main>
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

  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});

function exportPDF() {
  const element = document.getElementById("sReferral-slip");
  if (!element) return;

  html2pdf().set({
    margin: 10,
    filename: "Referral_Slip.pdf",
    image: { type: "jpeg", quality: 1 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: "mm", format: "a4", orientation: "portrait" }
  }).from(element).save();
}
</script>

</body>
</html>
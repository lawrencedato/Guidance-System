<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    header("Location: slogin.php");
    exit;
}

$conn = new mysqli("127.0.0.1", "root", "", "gcs_db");

/* =========================
   AJAX: PARTICIPATE TOGGLE
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement_id'])) {

    header('Content-Type: application/json');

    $student_id = $_SESSION['user_id'];
    $announcement_id = $_POST['announcement_id'];

    $check = $conn->query("
        SELECT * FROM announcement_responses
        WHERE announcement_id='$announcement_id'
        AND student_id='$student_id'
        AND response='interested'
    ");

    if ($check->num_rows > 0) {

        $conn->query("
            DELETE FROM announcement_responses
            WHERE announcement_id='$announcement_id'
            AND student_id='$student_id'
            AND response='interested'
        ");

        echo json_encode(["action" => "removed"]);
        exit;

    } else {

        $conn->query("
            INSERT INTO announcement_responses
            (announcement_id, student_id, response, responded_at)
            VALUES
            ('$announcement_id', '$student_id', 'interested', NOW())
        ");
        echo json_encode(["action" => "added"]);
        exit;
    }
    
}
// ===== DB CONNECTION =====

$sid  = $conn->real_escape_string($_SESSION['user_id']);

// ===== LOAD STUDENT DATA =====
$studentRes = $conn->query("SELECT * FROM students WHERE student_id='$sid' LIMIT 1");
$student    = $studentRes->fetch_assoc();

$profileRes = $conn->query("SELECT profile_image FROM student_profiles WHERE student_id='$sid' LIMIT 1");
$profile    = $profileRes->fetch_assoc();

$fullName   = htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$email      = htmlspecialchars($student['email'] ?? '');
$profileImg = !empty($profile['profile_image'])
              ? htmlspecialchars($profile['profile_image'])
              : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';

$announcements = $conn->query("
SELECT 
    a.*,
    c.first_name,
    c.last_name,
    COALESCE(r.interested_count, 0) AS interested_count
FROM announcements a
JOIN counselors c 
    ON a.counselor_id = c.counselor_id
LEFT JOIN (
    SELECT 
        announcement_id,
        COUNT(*) AS interested_count
    FROM announcement_responses
    WHERE response = 'interested'
    GROUP BY announcement_id
) r 
ON a.announcement_id = r.announcement_id
ORDER BY a.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Announcements</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link rel="stylesheet" href="sAnnouncements.css">
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
    <a href="sappointment.php"><i class="fa fa-calendar"></i> Book Appointment</a>
    <a href="sconcerns.php"><i class="fa fa-headset"></i> Submit Concern</a>
    <a href="swellness.php"><i class="fa fa-heart"></i> Wellness Check</a>
    <a href="sreferral.php"><i class="fa fa-route"></i> Referral</a>

    <p class="sidebar-title">UPDATES</p>
    <a href="sannouncements.php" class="active"><i class="fa fa-bullhorn"></i> Announcements</a>

    <p class="sidebar-title">RECORDS</p>
    <a href="sreports.php"><i class="fa fa-ticket"></i> Reports</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="sfeedback.php"><i class="fa fa-comment"></i> Session Feedback</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">

  <div class="topbar-left">
    <h2>Announcements</h2>
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


<main class="sAnnouncements-main">

  <div class="sAnnouncements-container">

<?php while($a = $announcements->fetch_assoc()): ?>

<div class="sAnnouncements-card"
     onclick="openModalFromCard(this)"
     data-id="<?= $a['announcement_id'] ?>"
     data-title="<?= htmlspecialchars($a['title']) ?>"
     data-message="<?= htmlspecialchars($a['message']) ?>"
     data-author="<?= htmlspecialchars($a['first_name']." ".$a['last_name']) ?>"
     data-date="<?= date("F j, Y g:i A", strtotime($a['created_at'])) ?>"
     data-file="<?= !empty($a['file_path']) ? htmlspecialchars($a['file_path']) : "" ?>"
     data-count="<?= $a['interested_count'] ?>">

  <h3><?= htmlspecialchars($a['title']) ?></h3>

  <h6 class="announcement-author">
    Posted by <?= htmlspecialchars($a['first_name']." ".$a['last_name']) ?>
  </h6>

  <p>
    <?= substr(htmlspecialchars($a['message']),0,120) ?>...
  </p>

  <p class="interest-count">
    👥 <?= $a['interested_count'] ?> interested
  </p>

  <small>Click for details</small>

</div>

<?php endwhile; ?>

<?php if ($announcements->num_rows === 0): ?>
  <div style="text-align:center; padding:3rem; color:var(--text-muted); width:100%;">
    <i class="fa fa-bullhorn" style="font-size:2.5rem; opacity:0.3; display:block; margin-bottom:1rem;"></i>
    <p>No announcements yet. Check back later!</p>
  </div>
<?php endif; ?>

</div>

</main>

<!-- ================= SCRIPT ================= -->
<script>
(function() {
    const saved = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", saved);
})();

function toggleSettingsMenu(e){
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
  const btn = document.querySelector(".sidebar-settingsButton");

  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});

/* AUTO SCROLL FROM DASHBOARD */
window.addEventListener("load", () => {

  const hash = window.location.hash;
  if (hash) {
    const target = document.querySelector(hash);
    if (target) {
      target.scrollIntoView({ behavior: "smooth", block: "center" });

      target.style.border = "2px solid var(--primary)";
      setTimeout(() => {
        target.style.border = "none";
      }, 2000);
    }
  }
});

function openModalFromCard(card) {

  // title + message
  document.getElementById("modalTitle").innerText = card.dataset.title;
  document.getElementById("modalBody").innerText = card.dataset.message;

  // author + date
  document.getElementById("modalExtra").innerHTML =
    card.dataset.author + "<br>" + card.dataset.date;

  // image/file
  const img = document.getElementById("modalImage");

  if (card.dataset.file && card.dataset.file.trim() !== "") {
    img.src = encodeURI(card.dataset.file);
    img.style.display = "block";
  } else {
    img.style.display = "none";
  }

  // set interest count
  const count = parseInt(card.dataset.count) || 0;
  const countEl = document.getElementById("modalCount");

  countEl.innerText = count + " interested";
  countEl.dataset.count = count;

  // attach announcement ID to button
  const btn = document.getElementById("participateBtn");
  btn.dataset.id = card.dataset.id;
  btn.innerText = "⭐ Participate";
  btn.disabled = false;

  // show modal
  document.getElementById("announcementModal").style.display = "flex";
}

function closeModal() {
  document.getElementById("announcementModal").style.display = "none";
}

/* AUTO OPEN FROM DASHBOARD */
document.addEventListener("DOMContentLoaded", () => {

  const params = new URLSearchParams(window.location.search);
  const openId = params.get("open");

  if (!openId) return;

  const map = {
    "mental-health-seminar": {
      title: "Mental Health Seminar",
      body: "A full session focused on emotional resilience and stress management.",
      extra: "📅 April 25, 2026 <br> ⏰ 2:00 PM – 4:00 PM <br> 📍 Auditorium",
      image: "https://images.unsplash.com/photo-1521737604893-d14cc237f11d"
    }
  };

  if (map[openId]) {
    const d = map[openId];
    const fakeCard = {
  dataset: {
    id: openId,
    title: d.title,
    message: d.body,
    author: "",
    date: d.extra,
    file: d.image,
    count: 0
  }
};

openModalFromCard(fakeCard);
  }

});
document.getElementById("participateBtn").addEventListener("click", function(e) {
  e.stopPropagation();

  const btn            = this;
  const announcementId = btn.dataset.id;

  if (!announcementId) return;

  btn.disabled = true;

  fetch("sannouncements.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "announcement_id=" + encodeURIComponent(announcementId)
  })
  .then(res => res.json())
  .then(data => {
    btn.disabled = false;

    const countEl = document.getElementById("modalCount");
    let current   = parseInt(countEl.dataset.count) || 0;

    if (data.action === "added") {
      current++;
      btn.innerText = "⭐ Participating";
    } else {
      current = Math.max(0, current - 1);
      btn.innerText = "⭐ Participate";
    }

    countEl.dataset.count = current;
    countEl.innerText     = current + " interested";
  })
  .catch(err => {
    btn.disabled = false;
    console.log("AJAX ERROR:", err);
  });
});
</script>


<div id="announcementModal" class="announcement-modal">
  <div class="announcement-modal-content">

    <div class="announcement-header">
      <h2 id="modalTitle"></h2>
      <span class="announcement-close" onclick="closeModal()">&times;</span>
    </div>

    <img id="modalImage">

    <p id="modalBody"></p>

    <div id="modalExtra"></div>

    <div class="modal-interest">
<button id="participateBtn">
  Participate
</button>

      <p id="modalCount">0 interested</p>
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

</body>
</html>
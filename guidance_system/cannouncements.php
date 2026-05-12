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
$cid  = (int)$_SESSION['user_id'];

$counselorRes = $conn->query("SELECT * FROM counselors WHERE counselor_id=$cid LIMIT 1");
$counselor    = $counselorRes->fetch_assoc();

$fullName   = htmlspecialchars(($counselor['first_name'] ?? '') . ' ' . ($counselor['last_name'] ?? ''));
$email      = htmlspecialchars($counselor['email'] ?? '');
$profileImg = !empty($counselor['profile_image'])
    ? htmlspecialchars($counselor['profile_image'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';

$pendingCount = (int)$conn->query(
    "SELECT COUNT(*) c FROM appointments WHERE counselor_id=$cid AND status='Pending'"
)->fetch_assoc()['c'];

// ── POST ANNOUNCEMENT ──
if (isset($_POST['action']) && $_POST['action'] === 'post_announcement') {
    header('Content-Type: application/json');
    $title    = $conn->real_escape_string($_POST['title']   ?? '');
    $message  = $conn->real_escape_string($_POST['message'] ?? '');
    $fileName = "";
    $filePath = "";

    if (!empty($_FILES['image']['name'])) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName = time() . "_" . basename($_FILES['image']['name']);
        $filePath = $uploadDir . $fileName;
        move_uploaded_file($_FILES['image']['tmp_name'], $filePath);
    }

    $conn->begin_transaction();
    try {
        $ok = $conn->query("INSERT INTO announcements
            (counselor_id, title, message, file_name, file_path)
            VALUES ($cid, '$title', '$message', '$fileName', '$filePath')");
        if (!$ok) throw new Exception($conn->error);
        $conn->commit();
        echo json_encode(["success" => true]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["success" => false, "message" => "Failed to post: " . $e->getMessage()]);
    }
    exit;
}

// ── DELETE ANNOUNCEMENT ──
if (isset($_POST['action']) && $_POST['action'] === 'delete_announcement') {
    header('Content-Type: application/json');
    $aid = (int)($_POST['announcement_id'] ?? 0);
    if (!$aid) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }

    $ok = $conn->query(
        "DELETE FROM announcements WHERE announcement_id=$aid AND counselor_id=$cid"
    );
    echo json_encode([
        'success' => ($conn->affected_rows > 0),
        'message' => $conn->affected_rows > 0 ? '' : 'Not found or not yours.'
    ]);
    exit;
}

// ── LOAD THIS COUNSELOR'S ANNOUNCEMENTS ──
$myAnnouncements = [];
$annRes = $conn->query("
    SELECT a.announcement_id, a.title, a.message, a.file_path, a.created_at,
           COALESCE(r.cnt, 0) AS interested_count
    FROM announcements a
    LEFT JOIN (
        SELECT announcement_id, COUNT(*) AS cnt
        FROM announcement_responses
        WHERE response = 'interested'
        GROUP BY announcement_id
    ) r ON r.announcement_id = a.announcement_id
    WHERE a.counselor_id = $cid
    ORDER BY a.created_at DESC
");
while ($row = $annRes->fetch_assoc()) $myAnnouncements[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>UNITYCARE | Announcements</title>
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
    <a href="cannouncements.php" class="active"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.php"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Announcements</h2>
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

<!-- MAIN -->
<main class="cAnnouncements-main">
<div style="display:flex; flex-direction:column; gap:24px; width:100%; max-width:900px; margin:0 auto;">

  <!-- CREATE FORM -->
  <div class="cAnnouncements-card">
    <h2>Create Announcement</h2>
    <input id="ann-title" placeholder="Announcement Title" class="cAnnouncements-input">
    <textarea id="ann-message" placeholder="Write announcement..." class="cAnnouncements-textarea"></textarea>
    <input type="file" id="imageFile" accept="image/*" class="cAnnouncements-input">
    <button class="cAnnouncements-btn" onclick="postAnnouncement()">Post Announcement</button>
    <div id="postResult" style="margin-top:10px; font-size:13px;"></div>
  </div>

  <!-- POSTED ANNOUNCEMENTS -->
  <div class="cAnnouncements-card">

    <div style="display:flex; justify-content:space-between; align-items:center;">
      <h2 style="margin:0;">My Posted Announcements</h2>
      <button onclick="togglePostedList()" id="togglePostedBtn"
        style="padding:8px 16px; border-radius:10px; border:1px solid var(--primary);
               background:transparent; color:var(--primary); font-size:13px;
               font-weight:600; cursor:pointer; transition:0.2s ease; white-space:nowrap;">
        <i class="fa fa-chevron-down" id="togglePostedIcon"></i> Show
      </button>
    </div>

    <div id="postedListWrapper" style="display:none; margin-top:16px;">
      <p style="font-size:13px; color:var(--text-muted); margin:0 0 12px;">
        <?= count($myAnnouncements) ?> announcement<?= count($myAnnouncements) !== 1 ? 's' : '' ?> posted
      </p>

      <div class="cAnnouncements-list">
        <?php if (empty($myAnnouncements)): ?>
          <div class="cAnnouncements-empty">
            <i class="fa fa-bullhorn" style="font-size:2rem; display:block; margin-bottom:10px;"></i>
            <p>You haven't posted any announcements yet.</p>
          </div>
        <?php else: ?>
          <?php foreach ($myAnnouncements as $a):
            $jsTitle   = json_encode($a['title']);
            $jsMessage = json_encode($a['message']);
            $jsFile    = json_encode(!empty($a['file_path']) ? $a['file_path'] : '');
            $jsDate    = json_encode(date('F d, Y g:i A', strtotime($a['created_at'])));
            $jsCount   = (int)$a['interested_count'];
            $jsId      = (int)$a['announcement_id'];
          ?>
          <div class="cAnnouncements-item" id="ann-<?= $jsId ?>">

            <div class="cAnnouncements-thumb">
              <?php if (!empty($a['file_path'])): ?>
                <img src="<?= htmlspecialchars($a['file_path']) ?>" alt="img"
                     onerror="this.parentElement.innerHTML='<i class=\'fa fa-bullhorn\'></i>'">
              <?php else: ?>
                <i class="fa fa-bullhorn"></i>
              <?php endif; ?>
            </div>

            <div class="cAnnouncements-body">
              <h4><?= htmlspecialchars($a['title']) ?></h4>
              <p><?= htmlspecialchars($a['message']) ?></p>
              <div class="cAnnouncements-meta">
                <span><i class="fa fa-clock"></i> <?= date('M d, Y g:i A', strtotime($a['created_at'])) ?></span>
                <span><i class="fa fa-users"></i> <?= $jsCount ?> interested</span>
              </div>
            </div>

            <div class="cAnnouncements-actions">
              <button class="cAnnouncements-btn-view"
                data-id="<?= $jsId ?>"
                data-title=<?= $jsTitle ?>
                data-message=<?= $jsMessage ?>
                data-file=<?= $jsFile ?>
                data-date=<?= $jsDate ?>
                data-count="<?= $jsCount ?>"
                onclick="viewAnnouncement(this)">
                <i class="fa fa-eye"></i> View
              </button>
              <button class="cAnnouncements-btn-del" onclick="deleteAnnouncement(<?= $jsId ?>)">
                <i class="fa fa-trash"></i> Delete
              </button>
            </div>

          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div><!-- end postedListWrapper -->
  </div>

</div><!-- end column wrapper -->

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

<!-- VIEW MODAL -->
<div class="cAnnouncements-modal-overlay" id="viewModal" onclick="closeViewModal(event)">
  <div class="cAnnouncements-modal-box">
    <button class="cAnnouncements-modal-close" onclick="closeViewModalDirect()">&#x2715;</button>
    <img id="vModalImg" class="cAnnouncements-modal-img" style="display:none;" alt="">
    <h3 class="cAnnouncements-modal-title" id="vModalTitle"></h3>
    <p class="cAnnouncements-modal-message" id="vModalMessage"></p>
    <div class="cAnnouncements-modal-footer">
      <span id="vModalDate"></span>
      <span id="vModalCount"></span>
    </div>
  </div>
</div>

<script>
(function() {
    const saved = localStorage.getItem("theme") || "light";
    document.documentElement.setAttribute("data-theme", saved);
})();

function togglePostedList() {
  const wrapper  = document.getElementById("postedListWrapper");
  const btn      = document.getElementById("togglePostedBtn");
  const isHidden = wrapper.style.display === "none";
  wrapper.style.display = isHidden ? "block" : "none";
  btn.innerHTML = isHidden
    ? '<i class="fa fa-chevron-up"></i> Hide'
    : '<i class="fa fa-chevron-down"></i> Show';
}

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
    const newTheme = html.getAttribute("data-theme") === "light" ? "dark" : "light";
    html.setAttribute("data-theme", newTheme);
    localStorage.setItem("theme", newTheme);
}
function logout()        { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()   { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=counselor'; }
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});
function toggleDropdown(id, e) {
  e.stopPropagation();
  document.getElementById(id).classList.toggle("show");
}
document.addEventListener("click", e => {
  const dd = document.getElementById("notifDropdown");
  if (dd && !dd.contains(e.target)) dd.classList.remove("show");
});

// ── POST ANNOUNCEMENT ──
function postAnnouncement() {
  const title   = document.getElementById("ann-title").value.trim();
  const message = document.getElementById("ann-message").value.trim();
  const file    = document.getElementById("imageFile").files[0];
  const result  = document.getElementById("postResult");

  if (!title || !message) {
    result.innerHTML = "<span style='color:#e53e3e;'>⚠ Please fill in the title and message.</span>";
    return;
  }

  const fd = new FormData();
  fd.append("action",  "post_announcement");
  fd.append("title",   title);
  fd.append("message", message);
  if (file) fd.append("image", file);

  result.innerHTML = "<span style='color:var(--text-muted);'>Posting...</span>";

  fetch("cannouncements.php", { method: "POST", body: fd })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        result.innerHTML = "<span style='color:#15803d;'>✔ Announcement posted!</span>";
        setTimeout(() => location.reload(), 900);
      } else {
        result.innerHTML = "<span style='color:#e53e3e;'>❌ " + (data.message || "Failed to post.") + "</span>";
      }
    })
    .catch(() => {
      result.innerHTML = "<span style='color:#e53e3e;'>❌ Something went wrong.</span>";
    });
}

// ── VIEW ANNOUNCEMENT ──
function viewAnnouncement(btn) {
  const title   = btn.dataset.title;
  const message = btn.dataset.message;
  const imgPath = btn.dataset.file;
  const date    = btn.dataset.date;
  const count   = btn.dataset.count;

  document.getElementById("vModalTitle").textContent   = title;
  document.getElementById("vModalMessage").innerHTML = message
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\\r\\n|\\r|\\n/g, "<br>")
    .replace(/\r\n|\r|\n/g, "<br>");
  document.getElementById("vModalDate").textContent    = '📅 ' + date;
  document.getElementById("vModalCount").textContent   = '👥 ' + count + ' interested';

  const img = document.getElementById("vModalImg");
  if (imgPath && imgPath.trim() !== '') {
    img.src           = imgPath;
    img.style.display = 'block';
  } else {
    img.style.display = 'none';
  }

  document.getElementById("viewModal").classList.add("show");
}
function closeViewModalDirect() {
  document.getElementById("viewModal").classList.remove("show");
}
function closeViewModal(e) {
  if (e.target === document.getElementById("viewModal")) closeViewModalDirect();
}

// ── DELETE ANNOUNCEMENT ──
function deleteAnnouncement(id) {
  if (!confirm("Delete this announcement? This cannot be undone.")) return;

  const fd = new FormData();
  fd.append("action",          "delete_announcement");
  fd.append("announcement_id", id);

  fetch("cannouncements.php", { method: "POST", body: fd })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const el = document.getElementById("ann-" + id);
        if (el) {
          el.style.transition = "opacity 0.3s ease";
          el.style.opacity    = "0";
          setTimeout(() => el.remove(), 320);
        }
      } else {
        alert("Failed to delete: " + (data.message || "Please try again."));
      }
    })
    .catch(() => alert("Network error. Please try again."));
}
</script>

</body>
</html>
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

// ── ARCHIVE ANNOUNCEMENT (soft delete) ──
if (isset($_POST['action']) && $_POST['action'] === 'archive_announcement') {
    header('Content-Type: application/json');
    $aid = (int)($_POST['announcement_id'] ?? 0);
    if (!$aid) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }
    $ok = $conn->query(
        "UPDATE announcements SET is_archived=1 WHERE announcement_id=$aid AND counselor_id=$cid"
    );
    echo json_encode([
        'success' => ($conn->affected_rows > 0),
        'message' => $conn->affected_rows > 0 ? '' : 'Not found or not yours.'
    ]);
    exit;
}

// ── RESTORE ANNOUNCEMENT ──
if (isset($_POST['action']) && $_POST['action'] === 'restore_announcement') {
    header('Content-Type: application/json');
    $aid = (int)($_POST['announcement_id'] ?? 0);
    if (!$aid) { echo json_encode(['success' => false, 'message' => 'Invalid ID.']); exit; }
    $ok = $conn->query(
        "UPDATE announcements SET is_archived=0 WHERE announcement_id=$aid AND counselor_id=$cid"
    );
    echo json_encode([
        'success' => ($conn->affected_rows > 0),
        'message' => $conn->affected_rows > 0 ? '' : 'Not found or not yours.'
    ]);
    exit;
}


// ── LOAD ANNOUNCEMENTS ──
$myAnnouncements = [];
$annRes = $conn->query("
    SELECT a.announcement_id, a.title, a.message, a.file_path, a.created_at, a.is_archived,
           COALESCE(r.cnt, 0) AS interested_count
    FROM announcements a
    LEFT JOIN (
        SELECT announcement_id, COUNT(*) AS cnt
        FROM announcement_responses
        WHERE response = 'Interested'
        GROUP BY announcement_id
    ) r ON r.announcement_id = a.announcement_id
    WHERE a.counselor_id = $cid
    ORDER BY a.created_at DESC
");
while ($row = $annRes->fetch_assoc()) $myAnnouncements[] = $row;

$activeAnnouncements   = array_filter($myAnnouncements, fn($a) => !$a['is_archived']);
$archivedAnnouncements = array_filter($myAnnouncements, fn($a) =>  $a['is_archived']);

// ── EDIT ANNOUNCEMENT ──
if (isset($_POST['action']) && $_POST['action'] === 'edit_announcement') {
    header('Content-Type: application/json');
    $aid     = (int)($_POST['announcement_id'] ?? 0);
    $title   = $conn->real_escape_string($_POST['title']   ?? '');
    $message = $conn->real_escape_string($_POST['message'] ?? '');
    if (!$aid || !$title || !$message) {
        echo json_encode(['success' => false, 'message' => 'Missing fields.']); exit;
    }

    // Handle optional new image
    $fileClause = "";
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        $fileName  = time() . "_" . basename($_FILES['image']['name']);
        $filePath  = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
            $safeFileName = $conn->real_escape_string($fileName);
            $safeFilePath = $conn->real_escape_string($filePath);
            $fileClause   = ", file_name='$safeFileName', file_path='$safeFilePath'";
        }
    }

    // Handle remove image flag
    if (!empty($_POST['remove_image'])) {
        $fileClause = ", file_name='', file_path=''";
    }

    $ok = $conn->query(
        "UPDATE announcements SET title='$title', message='$message' $fileClause
         WHERE announcement_id=$aid AND counselor_id=$cid"
    );
    
    // Get updated file path to return
    $updatedFilePath = '';
    if ($ok) {
        $row = $conn->query("SELECT file_path FROM announcements WHERE announcement_id=$aid")->fetch_assoc();
        $updatedFilePath = $row['file_path'] ?? '';
    }

    echo json_encode([
        'success'   => (bool)$ok,
        'file_path' => $updatedFilePath,
        'message'   => $ok ? '' : $conn->error
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Announcements</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
/* ── Edit Modal — matches view modal styling ── */
.cAnn-edit-overlay {
  display: none;
  position: fixed; inset: 0;
  background: rgba(17,63,103,0.25);
  backdrop-filter: blur(6px);
  z-index: 9999;
  justify-content: center;
  align-items: center;
}
.cAnn-edit-overlay.show { display: flex; }

.cAnn-edit-box {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 18px;
  padding: 28px;
  width: 90%;
  max-width: 520px;
  max-height: 85vh;
  overflow-y: auto;
  box-shadow: var(--shadow-lg);
  animation: modalPop 0.22s ease;
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.cAnn-edit-box h3 {
  font-size: 18px;
  font-weight: 700;
  color: var(--text);
  margin: 0 0 4px;
  padding-right: 30px;
}
.cAnn-edit-close {
  position: absolute;
  top: 14px; right: 14px;
  width: 32px; height: 32px;
  border-radius: 8px;
  border: none;
  background: var(--bg-soft);
  cursor: pointer;
  font-size: 16px;
  color: var(--text-muted);
  display: flex;
  align-items: center;
  justify-content: center;
  transition: 0.2s ease;
}
.cAnn-edit-close:hover { background: var(--border); color: var(--text); }

.cAnn-edit-box input,
.cAnn-edit-box textarea {
  width: 100%; box-sizing: border-box;
  padding: 12px 14px;
  border: 1px solid rgba(0,0,0,0.08);
  border-radius: 14px;
  font-size: 14px;
  background: rgba(255,255,255,0.6);
  backdrop-filter: blur(8px);
  color: var(--text);
  resize: vertical;
  outline: none;
  transition: 0.2s ease;
  margin: 0;
}
.cAnn-edit-box input:focus,
.cAnn-edit-box textarea:focus {
  border-color: #4988C4;
  box-shadow: 0 0 0 4px rgba(73,136,196,0.15);
}
.cAnn-edit-box textarea { min-height: 130px; }

.cAnn-edit-divider {
  border: none;
  border-top: 1px solid var(--border);
  margin: 4px 0;
}
.cAnn-edit-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  padding-top: 4px;
}
.cAnn-edit-save {
  padding: 10px 22px;
  background: linear-gradient(135deg, #113F67, #4988C4);
  color: #fff;
  border: none; border-radius: 14px;
  font-size: 14px;
  font-weight: 600; cursor: pointer;
  box-shadow: 0 10px 20px rgba(17,63,103,0.25);
  transition: 0.2s ease;
}
.cAnn-edit-save:hover { transform: translateY(-2px); box-shadow: 0 14px 30px rgba(17,63,103,0.35); }
.cAnn-edit-save:active { transform: scale(0.98); }
.cAnn-edit-cancel {
  padding: 10px 18px;
  background: transparent;
  border: 1px solid var(--border);
  border-radius: 14px;
  font-size: 14px;
  cursor: pointer;
  color: var(--text);
  transition: 0.2s ease;
}
.cAnn-edit-cancel:hover { background: var(--hover); }
.cAnn-edit-result { font-size: 13px; min-height: 18px; }
.cAnn-edit-result.ok  { color: #15803d; }
.cAnn-edit-result.err { color: #e53e3e; }
.cAnn-edit-label {
  font-size: 12px;
  color: var(--text-muted);
  margin: 0 0 4px;
  display: block;
}

/* ── Current image preview inside edit modal ── */
#edit-current-img-wrap { position: relative; }
#edit-current-img {
  width: 100%;
  border-radius: 12px;
  max-height: 180px;
  object-fit: cover;
  border: 1px solid var(--border);
  display: block;
}
#edit-new-img-wrap { position: relative; margin-top: 6px; }
#edit-new-img-preview {
  width: 100%;
  max-height: 140px;
  border-radius: 12px;
  object-fit: cover;
  border: 1px solid var(--border);
  display: block;
}
.cAnn-img-remove-btn {
  position: absolute; top: 6px; right: 6px;
  background: rgba(0,0,0,0.55); border: none;
  border-radius: 50%; color: #fff;
  width: 26px; height: 26px;
  cursor: pointer; font-size: 13px;
  display: flex; align-items: center; justify-content: center;
  transition: 0.2s ease;
}
.cAnn-img-remove-btn:hover { background: rgba(229,62,62,0.85); }

/* ── Archive badge ── */
.cAnnouncements-item.archived-item { opacity: .75; }
.cAnn-archived-badge {
  font-size: .72rem; font-weight: 700;
  background: #e57373; color: #fff;
  border-radius: 4px; padding: 1px 6px;
  margin-left: 6px; vertical-align: middle;
}

/* ── Restore button ── */
.cAnnouncements-btn-restore {
  padding: 7px 14px;
  border-radius: 10px;
  border: 1px solid #2e7d32;
  background: transparent;
  color: #2e7d32;
  font-size: 12px; font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: 0.2s ease;
}
.cAnnouncements-btn-restore:hover { background: #2e7d32; color: #fff; }

/* ── Edit button ── */
.cAnnouncements-btn-edit {
  padding: 7px 14px;
  border-radius: 10px;
  border: 1px solid var(--primary);
  background: transparent;
  color: var(--primary);
  font-size: 12px; font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: 0.2s ease;
}
.cAnnouncements-btn-edit:hover { background: var(--primary); color: #fff; }

/* ── Archive confirm modal ── */
.cAnn-archive-overlay {
  display: none;
  position: fixed; inset: 0;
  background: rgba(17,63,103,0.25);
  backdrop-filter: blur(6px);
  z-index: 9999;
  justify-content: center;
  align-items: center;
}
.cAnn-archive-overlay.show { display: flex; }
.cAnn-archive-box {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 18px;
  padding: 32px 28px;
  width: 90%;
  max-width: 400px;
  box-shadow: var(--shadow-lg);
  animation: modalPop 0.22s ease;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.cAnn-archive-icon {
  width: 56px; height: 56px;
  border-radius: 50%;
  background: rgba(229,62,62,0.1);
  display: flex; align-items: center; justify-content: center;
  font-size: 24px;
  color: #e53e3e;
}
.cAnn-archive-box h3 {
  font-size: 18px; font-weight: 700;
  color: var(--text); margin: 0;
}
.cAnn-archive-box p {
  font-size: 14px; color: var(--text-muted); margin: 0; line-height: 1.6;
}
.cAnn-archive-actions {
  display: flex; gap: 10px; width: 100%; margin-top: 4px;
}
.cAnn-archive-cancel {
  flex: 1; padding: 11px;
  background: transparent;
  border: 1px solid var(--border);
  border-radius: 14px;
  font-size: 14px; font-weight: 600;
  cursor: pointer; color: var(--text);
  transition: 0.2s ease;
}
.cAnn-archive-cancel:hover { background: var(--hover); }
.cAnn-archive-confirm {
  flex: 1; padding: 11px;
  background: linear-gradient(135deg, #c0392b, #e53e3e);
  border: none; border-radius: 14px;
  font-size: 14px; font-weight: 600;
  color: #fff; cursor: pointer;
  box-shadow: 0 8px 18px rgba(229,62,62,0.25);
  transition: 0.2s ease;
}
.cAnn-archive-confirm:hover { transform: translateY(-2px); box-shadow: 0 12px 24px rgba(229,62,62,0.35); }
.cAnn-archive-confirm:active { transform: scale(0.98); }

/* ════ DARK MODE ════ */
[data-theme="dark"] .cAnn-edit-overlay,
[data-theme="dark"] .cAnn-archive-overlay { background: rgba(0,0,0,0.55); }

[data-theme="dark"] .cAnn-edit-box,
[data-theme="dark"] .cAnn-archive-box {
  background: rgba(10,18,35,0.97);
  border-color: rgba(255,255,255,0.09);
  box-shadow: 0 22px 60px rgba(0,0,0,0.6);
}
[data-theme="dark"] .cAnn-edit-box h3,
[data-theme="dark"] .cAnn-archive-box h3 { color: #e2eaf4; }
[data-theme="dark"] .cAnn-archive-box p { color: #8ba4be; }

[data-theme="dark"] .cAnn-edit-close {
  background: rgba(255,255,255,0.06); color: #94a3b8;
}
[data-theme="dark"] .cAnn-edit-close:hover {
  background: rgba(255,255,255,0.12); color: #e2e8f0;
}

[data-theme="dark"] .cAnn-edit-box input,
[data-theme="dark"] .cAnn-edit-box textarea {
  background: rgba(255,255,255,0.05);
  border-color: rgba(255,255,255,0.1);
  color: #dce8f5;
}
[data-theme="dark"] .cAnn-edit-box input::placeholder,
[data-theme="dark"] .cAnn-edit-box textarea::placeholder { color: #4a6680; }
[data-theme="dark"] .cAnn-edit-box input:focus,
[data-theme="dark"] .cAnn-edit-box textarea:focus {
  border-color: #4988C4;
  box-shadow: 0 0 0 4px rgba(73,136,196,0.2);
}
[data-theme="dark"] .cAnn-edit-box input[type="file"] { color: #8ba4be; }

[data-theme="dark"] .cAnn-edit-save {
  background: linear-gradient(135deg, #0d3254, #3a7ab8);
  box-shadow: 0 10px 20px rgba(0,0,0,0.4);
}
[data-theme="dark"] .cAnn-edit-save:hover { box-shadow: 0 14px 30px rgba(0,0,0,0.5); }

[data-theme="dark"] .cAnn-edit-cancel,
[data-theme="dark"] .cAnn-archive-cancel {
  border-color: rgba(255,255,255,0.12); color: #dce8f5;
}
[data-theme="dark"] .cAnn-edit-cancel:hover,
[data-theme="dark"] .cAnn-archive-cancel:hover { background: rgba(255,255,255,0.06); }

[data-theme="dark"] .cAnn-edit-label { color: #6e8ea8; }
[data-theme="dark"] .cAnn-edit-divider { border-top-color: rgba(255,255,255,0.08); }
[data-theme="dark"] .cAnn-edit-result.ok  { color: #4ade80; }
[data-theme="dark"] .cAnn-edit-result.err { color: #fca5a5; }

[data-theme="dark"] #edit-current-img,
[data-theme="dark"] #edit-new-img-preview { border-color: rgba(255,255,255,0.1); }

[data-theme="dark"] .cAnn-archived-badge {
  background: rgba(239,68,68,0.25); color: #fca5a5;
}
[data-theme="dark"] .cAnn-archive-icon {
  background: rgba(239,68,68,0.15); color: #fca5a5;
}
[data-theme="dark"] .cAnn-archive-confirm {
  background: linear-gradient(135deg, #7f1d1d, #dc2626);
  box-shadow: 0 8px 18px rgba(0,0,0,0.4);
}
[data-theme="dark"] .cAnnouncements-btn-restore {
  border-color: rgba(34,197,94,0.4); color: #4ade80;
}
[data-theme="dark"] .cAnnouncements-btn-restore:hover { background: rgba(34,197,94,0.2); color: #4ade80; border-color: #4ade80; }
[data-theme="dark"] .cAnnouncements-btn-edit {
  border-color: rgba(73,136,196,0.5); color: #93c5fd;
}
[data-theme="dark"] .cAnnouncements-btn-edit:hover { background: #4988C4; color: #fff; border-color: #4988C4; }
</style>
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
  <div class="cAnnouncements-center">

    <!-- ── CARD 1: CREATE ── -->
    <div class="cAnnouncements-card">
      <h2>Create Announcement</h2>
      <input id="ann-title"   placeholder="Announcement Title" class="cAnnouncements-input">
      <textarea id="ann-message" placeholder="Write announcement..." class="cAnnouncements-textarea"></textarea>
      <input type="file" id="imageFile" accept="image/*" class="cAnnouncements-input">
      <button class="cAnnouncements-btn" onclick="postAnnouncement()">
        <i class="fa fa-bullhorn"></i> Post Announcement
      </button>
      <div class="cAnnouncements-result" id="postResult"></div>
    </div>

    <!-- ── CARD 2: ACTIVE LIST ── -->
    <div class="cAnnouncements-card">
      <div class="cAnnouncements-card-header">
        <h2>My Posted Announcements</h2>
        <button class="cAnnouncements-toggle-btn" id="togglePostedBtn" onclick="togglePostedList()">
          <i class="fa fa-chevron-down"></i> Show
        </button>
      </div>

      <div class="cAnnouncements-list-wrapper" id="postedListWrapper">
        <p class="cAnnouncements-count">
          <?= count($activeAnnouncements) ?> announcement<?= count($activeAnnouncements) !== 1 ? 's' : '' ?> posted
        </p>

        <div class="cAnnouncements-list" id="activeList">
          <?php if (empty($activeAnnouncements)): ?>
            <div class="cAnnouncements-empty">
              <i class="fa fa-bullhorn"></i>
              <p>You haven't posted any announcements yet.</p>
            </div>
          <?php else: ?>
            <?php foreach ($activeAnnouncements as $a):
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
              <button class="cAnnouncements-btn-edit"
                data-id="<?= $jsId ?>"
                data-title=<?= $jsTitle ?>
                data-message=<?= $jsMessage ?>
                data-file=<?= $jsFile ?>
                onclick="openEditModal(this)">
                <i class="fa fa-pen"></i> Edit
              </button>
                <button class="cAnnouncements-btn-del" onclick="archiveAnnouncement(<?= $jsId ?>)">
                  <i class="fa fa-archive"></i> Archive
                </button>
              </div>

            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <!-- END CARD 2 -->

    <!-- ── CARD 3: ARCHIVED LIST ── -->
    <div class="cAnnouncements-card">
      <div class="cAnnouncements-card-header">
        <h2>Archived Announcements</h2>
        <button class="cAnnouncements-toggle-btn" id="toggleArchivedBtn" onclick="toggleArchivedList()">
          <i class="fa fa-chevron-down"></i> Show
        </button>
      </div>

      <div class="cAnnouncements-list-wrapper" id="archivedListWrapper">
        <p class="cAnnouncements-count">
          <?= count($archivedAnnouncements) ?> archived announcement<?= count($archivedAnnouncements) !== 1 ? 's' : '' ?>
        </p>

        <div class="cAnnouncements-list">
          <?php if (empty($archivedAnnouncements)): ?>
            <div class="cAnnouncements-empty">
              <i class="fa fa-box-archive"></i>
              <p>No archived announcements.</p>
            </div>
          <?php else: ?>
            <?php foreach ($archivedAnnouncements as $a):
              $jsTitle   = json_encode($a['title']);
              $jsMessage = json_encode($a['message']);
              $jsFile    = json_encode(!empty($a['file_path']) ? $a['file_path'] : '');
              $jsDate    = json_encode(date('F d, Y g:i A', strtotime($a['created_at'])));
              $jsCount   = (int)$a['interested_count'];
              $jsId      = (int)$a['announcement_id'];
            ?>
            <div class="cAnnouncements-item archived-item" id="ann-<?= $jsId ?>">

              <div class="cAnnouncements-thumb">
                <?php if (!empty($a['file_path'])): ?>
                  <img src="<?= htmlspecialchars($a['file_path']) ?>" alt="img"
                       onerror="this.parentElement.innerHTML='<i class=\'fa fa-bullhorn\'></i>'">
                <?php else: ?>
                  <i class="fa fa-bullhorn"></i>
                <?php endif; ?>
              </div>

              <div class="cAnnouncements-body">
                <h4>
                  <?= htmlspecialchars($a['title']) ?>
                  <span class="cAnn-archived-badge">Archived</span>
                </h4>
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
                <button class="cAnnouncements-btn-restore" onclick="restoreAnnouncement(<?= $jsId ?>)">
                  <i class="fa fa-rotate-left"></i> Restore
                </button>
              </div>

            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <!-- END CARD 3 -->

  </div>
</main>

<!-- VIEW MODAL -->
<div class="cAnnouncements-modal-overlay" id="viewModal" onclick="closeViewModal(event)">
  <div class="cAnnouncements-modal-box">
    <button class="cAnnouncements-modal-close" onclick="closeViewModalDirect()">&#x2715;</button>
    <img id="vModalImg" class="cAnnouncements-modal-img" style="display:none;" alt="">
    <h3 class="cAnnouncements-modal-title"   id="vModalTitle"></h3>
    <p  class="cAnnouncements-modal-message" id="vModalMessage"></p>
    <div class="cAnnouncements-modal-footer">
      <span id="vModalDate"></span>
      <span id="vModalCount"></span>
    </div>
  </div>
</div>

<!-- EDIT MODAL -->
<div class="cAnn-edit-overlay" id="editModal" onclick="closeEditModalOverlay(event)">
  <div class="cAnn-edit-box">
    <button class="cAnn-edit-close" onclick="closeEditModal()">&#x2715;</button>
    <h3><i class="fa fa-pen" style="margin-right:6px;font-size:15px;"></i>Edit Announcement</h3>

    <input id="edit-title" placeholder="Announcement Title">
    <textarea id="edit-message" placeholder="Announcement message..."></textarea>

    <!-- Current image preview -->
    <div id="edit-current-img-wrap" style="display:none;">
      <span class="cAnn-edit-label"><i class="fa fa-image"></i> Current image</span>
      <div style="position:relative;">
        <img id="edit-current-img" src="" alt="current image">
        <button class="cAnn-img-remove-btn" onclick="removeCurrentImage()" title="Remove image">
          <i class="fa fa-times"></i>
        </button>
      </div>
    </div>

    <!-- New image upload -->
    <div>
      <span class="cAnn-edit-label"><i class="fa fa-upload"></i> Replace / Add image (optional)</span>
      <input type="file" id="edit-image-file" accept="image/*" onchange="previewNewEditImage(event)">
      <div id="edit-new-img-wrap" style="display:none;">
        <div style="position:relative; margin-top:6px;">
          <img id="edit-new-img-preview" src="" alt="new preview">
          <button class="cAnn-img-remove-btn" onclick="clearNewEditImage()" title="Clear selection">
            <i class="fa fa-times"></i>
          </button>
        </div>
      </div>
    </div>

    <hr class="cAnn-edit-divider">
    <div class="cAnn-edit-result" id="editResult"></div>
    <div class="cAnn-edit-actions">
      <button class="cAnn-edit-cancel" onclick="closeEditModal()">Cancel</button>
      <button class="cAnn-edit-save" onclick="saveEdit()">
        <i class="fa fa-floppy-disk"></i> Save Changes
      </button>
    </div>
  </div>
</div>

<!-- ARCHIVE CONFIRM MODAL -->
<div class="cAnn-archive-overlay" id="archiveModal" onclick="closeArchiveModalOverlay(event)">
  <div class="cAnn-archive-box">
    <div class="cAnn-archive-icon"><i class="fa fa-box-archive"></i></div>
    <h3>Archive Announcement?</h3>
    <p>This announcement will be hidden from students. You can restore it anytime from the Archived section.</p>
    <div class="cAnn-archive-actions">
      <button class="cAnn-archive-cancel" onclick="closeArchiveModal()">Cancel</button>
      <button class="cAnn-archive-confirm" onclick="confirmArchive()">
        <i class="fa fa-box-archive"></i> Yes, Archive
      </button>
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
(function() {
  const saved = localStorage.getItem("theme") || "light";
  document.documentElement.setAttribute("data-theme", saved);
})();

// ── Toggle lists ──
function togglePostedList() {
  const wrapper  = document.getElementById("postedListWrapper");
  const btn      = document.getElementById("togglePostedBtn");
  const isHidden = !wrapper.classList.contains("visible");
  wrapper.classList.toggle("visible", isHidden);
  btn.innerHTML = isHidden
    ? '<i class="fa fa-chevron-up"></i> Hide'
    : '<i class="fa fa-chevron-down"></i> Show';
}
function toggleArchivedList() {
  const wrapper  = document.getElementById("archivedListWrapper");
  const btn      = document.getElementById("toggleArchivedBtn");
  const isHidden = !wrapper.classList.contains("visible");
  wrapper.classList.toggle("visible", isHidden);
  btn.innerHTML = isHidden
    ? '<i class="fa fa-chevron-up"></i> Hide'
    : '<i class="fa fa-chevron-down"></i> Show';
}

// ── Settings dropdown ──
function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}
document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn  = document.querySelector(".sidebar-settingsButton");
  if (!menu.contains(e.target) && !btn.contains(e.target)) menu.classList.remove("show");
});

// ── Theme ──
function toggleTheme() {
  const html     = document.documentElement;
  const newTheme = html.getAttribute("data-theme") === "light" ? "dark" : "light";
  html.setAttribute("data-theme", newTheme);
  localStorage.setItem("theme", newTheme);
}

// ── Notification dropdown ──
function toggleDropdown(id, e) {
  e.stopPropagation();
  document.getElementById(id).classList.toggle("show");
}
document.addEventListener("click", e => {
  const dd = document.getElementById("notifDropdown");
  if (dd && !dd.contains(e.target)) dd.classList.remove("show");
});

// ── Logout ──
function logout()        { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()   { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=counselor'; }
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});

// ── Post announcement ──
function postAnnouncement() {
  const title   = document.getElementById("ann-title").value.trim();
  const message = document.getElementById("ann-message").value.trim();
  const file    = document.getElementById("imageFile").files[0];
  const result  = document.getElementById("postResult");

  if (!title || !message) {
    result.textContent = "⚠ Please fill in the title and message.";
    result.className   = "cAnnouncements-result err";
    return;
  }

  const fd = new FormData();
  fd.append("action",  "post_announcement");
  fd.append("title",   title);
  fd.append("message", message);
  if (file) fd.append("image", file);

  result.textContent = "Posting...";
  result.className   = "cAnnouncements-result";

  fetch("cannouncements.php", { method: "POST", body: fd })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        result.textContent = "✔ Announcement posted!";
        result.className   = "cAnnouncements-result ok";
        setTimeout(() => location.reload(), 900);
      } else {
        result.textContent = "❌ " + (data.message || "Failed to post.");
        result.className   = "cAnnouncements-result err";
      }
    })
    .catch(() => {
      result.textContent = "❌ Something went wrong.";
      result.className   = "cAnnouncements-result err";
    });
}

// ── View announcement ──
function viewAnnouncement(btn) {
  const title   = btn.dataset.title;
  const message = btn.dataset.message;
  const imgPath = btn.dataset.file;
  const date    = btn.dataset.date;
  const count   = btn.dataset.count;

  document.getElementById("vModalTitle").textContent = title;
  document.getElementById("vModalMessage").innerHTML = message
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/\\r\\n|\\r|\\n/g, "<br>")
    .replace(/\r\n|\r|\n/g, "<br>");
  document.getElementById("vModalDate").textContent  = '📅 ' + date;
  document.getElementById("vModalCount").textContent = '👥 ' + count + ' interested';

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

// ── Archive announcement ──
// ── Archive modal ──
let _archiveId = null;

function archiveAnnouncement(id) {
  _archiveId = id;
  document.getElementById("archiveModal").classList.add("show");
}
function closeArchiveModal() {
  document.getElementById("archiveModal").classList.remove("show");
  _archiveId = null;
}
function closeArchiveModalOverlay(e) {
  if (e.target === document.getElementById("archiveModal")) closeArchiveModal();
}
function confirmArchive() {
  if (!_archiveId) return;
  const id = _archiveId;
  closeArchiveModal();

  const fd = new FormData();
  fd.append("action", "archive_announcement");
  fd.append("announcement_id", id);

  fetch("cannouncements.php", { method: "POST", body: fd })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const el = document.getElementById("ann-" + id);
        if (el) {
          el.style.transition = "opacity 0.3s ease";
          el.style.opacity    = "0";
          setTimeout(() => { el.remove(); location.reload(); }, 320);
        }
      } else {
        alert("Failed to archive: " + (data.message || "Please try again."));
      }
    })
    .catch(() => alert("Network error. Please try again."));
}

// ── Restore announcement ──
function restoreAnnouncement(id) {
  if (!confirm("Restore this announcement? It will be moved back to active.")) return;

  const fd = new FormData();
  fd.append("action",          "restore_announcement");
  fd.append("announcement_id", id);

  fetch("cannouncements.php", { method: "POST", body: fd })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        const el = document.getElementById("ann-" + id);
        if (el) {
          el.style.transition = "opacity 0.3s ease";
          el.style.opacity    = "0";
          setTimeout(() => { el.remove(); location.reload(); }, 320);
        }
      } else {
        alert("Failed to restore: " + (data.message || "Please try again."));
      }
    })
    .catch(() => alert("Network error. Please try again."));
}

// ── Edit modal ──
// ── Edit modal ──
let _editId        = null;
let _removeImage   = false;

function openEditModal(btn) {
  _editId      = btn.dataset.id;
  _removeImage = false;

  document.getElementById("edit-title").value       = btn.dataset.title;
  document.getElementById("edit-message").value     = btn.dataset.message;
  document.getElementById("editResult").textContent = "";
  document.getElementById("editResult").className   = "cAnn-edit-result";

  // Reset file input & new preview
  document.getElementById("edit-image-file").value = "";
  document.getElementById("edit-new-img-wrap").style.display = "none";

  // Show current image if exists
  const filePath = btn.dataset.file || "";
  const imgWrap  = document.getElementById("edit-current-img-wrap");
  const imgEl    = document.getElementById("edit-current-img");
  if (filePath.trim() !== "") {
    imgEl.src          = filePath;
    imgWrap.style.display = "block";
  } else {
    imgWrap.style.display = "none";
  }

  document.getElementById("editModal").classList.add("show");
}

function closeEditModal() {
  document.getElementById("editModal").classList.remove("show");
  _editId      = null;
  _removeImage = false;
}

function closeEditModalOverlay(e) {
  if (e.target === document.getElementById("editModal")) closeEditModal();
}

function removeCurrentImage() {
  _removeImage = true;
  document.getElementById("edit-current-img-wrap").style.display = "none";
}

function previewNewEditImage(e) {
  const file = e.target.files[0];
  if (!file) return;
  _removeImage = false; // uploading new image, no need to remove flag
  document.getElementById("edit-current-img-wrap").style.display = "none"; // hide old preview
  const reader = new FileReader();
  reader.onload = ev => {
    document.getElementById("edit-new-img-preview").src = ev.target.result;
    document.getElementById("edit-new-img-wrap").style.display = "block";
  };
  reader.readAsDataURL(file);
}

function clearNewEditImage() {
  document.getElementById("edit-image-file").value             = "";
  document.getElementById("edit-new-img-wrap").style.display   = "none";
  // Restore current image preview if not removed
  if (!_removeImage) {
    const item    = document.getElementById("ann-" + _editId);
    const viewBtn = item ? item.querySelector(".cAnnouncements-btn-view") : null;
    const fp      = viewBtn ? (viewBtn.dataset.file || "") : "";
    if (fp.trim() !== "") {
      document.getElementById("edit-current-img").src           = fp;
      document.getElementById("edit-current-img-wrap").style.display = "block";
    }
  }
}

function saveEdit() {
  const title   = document.getElementById("edit-title").value.trim();
  const message = document.getElementById("edit-message").value.trim();
  const file    = document.getElementById("edit-image-file").files[0];
  const result  = document.getElementById("editResult");

  if (!title || !message) {
    result.textContent = "⚠ Title and message are required.";
    result.className   = "cAnn-edit-result err";
    return;
  }

  const fd = new FormData();
  fd.append("action",          "edit_announcement");
  fd.append("announcement_id", _editId);
  fd.append("title",           title);
  fd.append("message",         message);
  if (file)         fd.append("image",        file);
  if (_removeImage) fd.append("remove_image", "1");

  result.textContent = "Saving...";
  result.className   = "cAnn-edit-result";

  fetch("cannouncements.php", { method: "POST", body: fd })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        result.textContent = "✔ Saved!";
        result.className   = "cAnn-edit-result ok";

        // Update DOM in place
        const item = document.getElementById("ann-" + _editId);
        if (item) {
          item.querySelector(".cAnnouncements-body h4").textContent = title;
          item.querySelector(".cAnnouncements-body p").textContent  = message;

          // Update thumb
          const thumb    = item.querySelector(".cAnnouncements-thumb");
          const newFp    = data.file_path || "";
          if (newFp.trim() !== "") {
            thumb.innerHTML = `<img src="${newFp}" alt="img"
              onerror="this.parentElement.innerHTML='<i class=\\'fa fa-bullhorn\\'></i>'">`;
          } else {
            thumb.innerHTML = `<i class="fa fa-bullhorn"></i>`;
          }

          // Update view & edit button data attrs
          const viewBtn = item.querySelector(".cAnnouncements-btn-view");
          if (viewBtn) {
            viewBtn.dataset.title   = title;
            viewBtn.dataset.message = message;
            viewBtn.dataset.file    = newFp;
          }
          const editBtn = item.querySelector(".cAnnouncements-btn-edit");
          if (editBtn) {
            editBtn.dataset.title   = title;
            editBtn.dataset.message = message;
            editBtn.dataset.file    = newFp;
          }
        }
        setTimeout(() => closeEditModal(), 700);
      } else {
        result.textContent = "❌ " + (data.message || "Failed to save.");
        result.className   = "cAnn-edit-result err";
      }
    })
    .catch(() => {
      result.textContent = "❌ Something went wrong.";
      result.className   = "cAnn-edit-result err";
    });
}
</script>
</body>
</html>
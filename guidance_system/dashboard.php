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

// ===== HANDLE PASSWORD RESET AJAX =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {
    header('Content-Type: application/json');

    $new_pw     = $_POST['new_password']     ?? '';
    $confirm_pw = $_POST['confirm_password'] ?? '';

    if (!$new_pw || !$confirm_pw) {
        echo json_encode(["success" => false, "message" => "Please fill in all fields."]);
        exit;
    }
    if (strlen($new_pw) < 8) {
        echo json_encode(["success" => false, "message" => "Password must be at least 8 characters."]);
        exit;
    }
    if ($new_pw !== $confirm_pw) {
        echo json_encode(["success" => false, "message" => "Passwords do not match."]);
        exit;
    }
    if (!preg_match('/[A-Z]/', $new_pw) || !preg_match('/[a-z]/', $new_pw)
        || !preg_match('/[0-9]/', $new_pw) || !preg_match('/[!@#$%^&*]/', $new_pw)) {
        echo json_encode(["success" => false, "message" => "Password does not meet all requirements."]);
        exit;
    }

    $hashed = password_hash($new_pw, PASSWORD_BCRYPT);
    $ok     = $conn->query(
        "UPDATE activated_students SET password='$hashed', is_temp_password=0 WHERE student_id=$sid"
    );

    if ($ok) {
        session_unset();
        session_destroy();
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to save password. Please try again."]);
    }
    exit;
}

// ===== HANDLE MOOD SAVE (save_wellness) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_wellness') {
    header('Content-Type: application/json');

    // Check if already submitted today
    $check = $conn->query(
        "SELECT wellness_id FROM wellness_checks
         WHERE student_id = $sid AND DATE(created_at) = CURDATE()
         LIMIT 1"
    );
    if ($check->num_rows > 0) {
        echo json_encode(['success' => false, 'already_done' => true, 'message' => 'Already submitted today.']);
        exit;
    }

    $mood   = $conn->real_escape_string($_POST['mood_label']    ?? 'Neutral');
    $stress = (int)($_POST['stress_level']                      ?? 50);
    $sleep  = $conn->real_escape_string($_POST['sleep_quality'] ?? 'Average');

    $ok = $conn->query(
        "INSERT INTO wellness_checks (student_id, mood_label, stress_level, sleep_quality, created_at)
         VALUES ($sid, '$mood', $stress, '$sleep', NOW())"
    );

    echo json_encode($ok
        ? ['success' => true]
        : ['success' => false, 'message' => 'Failed to save. Please try again.']
    );
    exit;
}

// ===== HANDLE MOOD UPDATE (update_wellness) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_wellness') {
    header('Content-Type: application/json');

    $checkRes = $conn->query(
        "SELECT wellness_id FROM wellness_checks
         WHERE student_id = $sid AND DATE(created_at) = CURDATE()
         LIMIT 1"
    );
    $row = $checkRes->fetch_assoc();
    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'No check-in found for today to update.']);
        exit;
    }

    $wid    = (int)$row['wellness_id'];
    $mood   = $conn->real_escape_string($_POST['mood_label']    ?? 'Neutral');
    $stress = (int)($_POST['stress_level']                      ?? 50);
    $sleep  = $conn->real_escape_string($_POST['sleep_quality'] ?? 'Average');

    $ok = $conn->query(
        "UPDATE wellness_checks
         SET mood_label = '$mood', stress_level = $stress, sleep_quality = '$sleep'
         WHERE wellness_id = $wid AND student_id = $sid"
    );

    echo json_encode($ok
        ? ['success' => true]
        : ['success' => false, 'message' => 'Failed to update. Please try again.']
    );
    exit;
}

// ===== LOAD PAGE DATA =====
$studentRes = $conn->query("SELECT * FROM students WHERE student_id=$sid LIMIT 1");
$student    = $studentRes->fetch_assoc();

$profileRes = $conn->query("SELECT profile_image FROM student_profiles WHERE student_id=$sid LIMIT 1");
$profile    = $profileRes->fetch_assoc();

$isTempPassword = (int)($_SESSION['is_temp_password'] ?? 0);

// Stats
$upcoming  = $conn->query("SELECT COUNT(*) c FROM appointments WHERE student_id=$sid AND status='Approved' AND appointment_date >= CURDATE()")->fetch_assoc()['c'] ?? 0;
$completed = $conn->query("SELECT COUNT(*) c FROM appointments WHERE student_id=$sid AND status='Completed'")->fetch_assoc()['c'] ?? 0;
$referrals = $conn->query("SELECT COUNT(*) c FROM referrals WHERE student_id=$sid")->fetch_assoc()['c'] ?? 0;
$concerns  = $conn->query("SELECT COUNT(*) c FROM concerns WHERE student_id=$sid AND status='Pending'")->fetch_assoc()['c'] ?? 0;

$announce = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 1")->fetch_assoc();

$actRes = $conn->query(
    "(SELECT 'Booked appointment' AS activity, created_at FROM appointments WHERE student_id=$sid ORDER BY created_at DESC LIMIT 1)
     UNION
     (SELECT 'Submitted concern', created_at FROM concerns WHERE student_id=$sid ORDER BY created_at DESC LIMIT 1)
     UNION
     (SELECT 'Wellness check', created_at FROM wellness_checks WHERE student_id=$sid ORDER BY created_at DESC LIMIT 1)
     ORDER BY created_at DESC LIMIT 5"
);
$activities = [];
while ($row = $actRes->fetch_assoc()) $activities[] = $row;

// Check if student already has a wellness entry today (used in JS to pick save vs update)
$todayWellnessRes    = $conn->query(
    "SELECT mood_label FROM wellness_checks
     WHERE student_id=$sid AND DATE(created_at) = CURDATE()
     ORDER BY created_at DESC LIMIT 1"
);
$todayWellness       = $todayWellnessRes->fetch_assoc();
$wellnessExistsToday = !empty($todayWellness);

$firstName  = htmlspecialchars($student['first_name'] ?? 'Student');
$fullName   = htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$email      = htmlspecialchars($student['email'] ?? '');
$profileImg = !empty($profile['profile_image'])
              ? htmlspecialchars($profile['profile_image'])
              : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNITYCARE | Student Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="logout.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ===== FULL PAGE BLOCK OVERLAY ===== */
        .page-block-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(17, 63, 103, 0.35);
            backdrop-filter: blur(5px);
            z-index: 9998;
        }
        .page-block-overlay.active { display: block; }

        /* ===== RESET MODAL ===== */
        .reset-modal {
            position: fixed;
            inset: 0;
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .reset-modal.active { display: flex; }

        .reset-box {
            width: 440px;
            padding: var(--spacing-xxl, 32px);
            background: var(--card, #fff);
            border: 1px solid var(--border, #e2e8f0);
            border-radius: var(--radius-lg, 16px);
            box-shadow: var(--shadow, 0 8px 40px rgba(0,0,0,0.15));
            text-align: center;
            animation: modalPop 0.25s ease;
        }
        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.95); }
            to   { opacity: 1; transform: scale(1); }
        }
        .reset-box h2 {
            margin-bottom: var(--spacing-sm, 8px);
            color: var(--primary, #113f67);
            font-weight: 700;
        }
        .reset-box > p {
            color: var(--text-muted, #666);
            font-size: 14px;
            line-height: 1.5;
            margin-bottom: var(--spacing-lg, 20px);
        }
        .reset-box .field-label {
            display: block;
            text-align: left;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text, #222);
        }
        .pw-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            margin-bottom: 4px;
        }
        .pw-wrapper input {
            width: 100%;
            padding: 12px !important;
            padding-right: 42px !important;
            border-radius: 10px;
            border: 2px solid var(--border, #e2e8f0);
            outline: none;
            font-size: 14px;
            transition: 0.2s;
            box-sizing: border-box;
        }
        .pw-wrapper input:focus {
            border-color: var(--primary, #113f67);
            box-shadow: 0 0 0 3px rgba(17,63,103,0.12);
        }
        .pw-toggle {
            position: absolute;
            right: 10px;
            background: none;
            border: none;
            cursor: pointer;
            color: var(--text-muted, #888);
            padding: 0;
            display: flex;
            align-items: center;
            transition: color 0.2s;
            width: auto !important;
            margin-top: 0 !important;
        }
        .pw-toggle:hover { color: var(--primary, #113f67); background: none !important; }
        .strength-wrap {
            height: 5px;
            background: var(--border, #e2e8f0);
            border-radius: 10px;
            margin: 8px 0 3px;
            overflow: hidden;
        }
        .strength-bar {
            height: 100%;
            border-radius: 10px;
            width: 0%;
            transition: width 0.3s, background 0.3s;
        }
        .strength-label { font-size: 11px; margin-bottom: 10px; text-align: left; }
        .pw-reqs {
            list-style: none;
            padding: 0;
            margin: 0 0 16px;
            text-align: left;
        }
        .pw-reqs li {
            font-size: 12px;
            color: var(--text-muted, #888);
            display: flex;
            align-items: center;
            gap: 7px;
            margin-bottom: 4px;
            transition: color 0.2s;
        }
        .pw-reqs li.met { color: #15803d; }
        .dot-req {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--border, #e2e8f0);
            flex-shrink: 0;
            transition: background 0.2s;
        }
        .pw-reqs li.met .dot-req { background: #15803d; }
        #resetError {
            font-size: 12px;
            min-height: 16px;
            text-align: center;
            margin: 6px 0 0;
            color: #e53e3e;
        }
        .reset-box .save-btn {
            margin-top: 14px;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: var(--primary, #113f67);
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: 0.2s;
        }
        .reset-box .save-btn:hover    { background: #0e3558; }
        .reset-box .save-btn:disabled { opacity: 0.6; cursor: not-allowed; }

        /* ===== SUCCESS MODAL ===== */
        .success-modal {
            position: fixed;
            inset: 0;
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 99999;
            background: rgba(17, 63, 103, 0.35);
            backdrop-filter: blur(5px);
        }
        .success-modal.active { display: flex; }
        .success-box {
            width: 400px;
            padding: var(--spacing-xxl, 32px);
            background: var(--card, #fff);
            border: 1px solid var(--border, #e2e8f0);
            border-radius: var(--radius-lg, 16px);
            box-shadow: var(--shadow, 0 8px 40px rgba(0,0,0,0.15));
            text-align: center;
            animation: modalPop 0.25s ease;
        }
        .success-icon {
            width: 64px; height: 64px;
            border-radius: 50%;
            background: rgba(21,128,61,0.1);
            border: 2px solid rgba(21,128,61,0.25);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 16px;
        }
        .success-box h2 { color: var(--primary, #113f67); font-weight: 700; margin-bottom: 8px; }
        .success-box p  { color: var(--text-muted, #666); font-size: 14px; line-height: 1.5; margin-bottom: 20px; }
        .success-box .login-btn {
            width: 100%; padding: 12px;
            border: none; border-radius: 10px;
            background: var(--primary, #113f67);
            color: #fff; font-weight: 600;
            font-size: 14px; cursor: pointer; transition: 0.2s;
        }
        .success-box .login-btn:hover { background: #0e3558; }
        .redirect-note { font-size: 12px; color: var(--text-muted, #888); margin-top: 10px; }

        @media (max-width: 500px) {
            .reset-box, .success-box { width: 90% !important; padding: 20px !important; }
        }
    </style>
</head>
<body class="body">

<!-- ===== FULL PAGE BLOCK ===== -->
<div class="page-block-overlay <?= $isTempPassword ? 'active' : '' ?>" id="pageBlockOverlay"></div>

<!-- ===== FORCED RESET PASSWORD MODAL ===== -->
<div class="reset-modal <?= $isTempPassword ? 'active' : '' ?>" id="resetModal">
    <div class="reset-box">
        <h2>Set Your Password</h2>
        <p>You logged in with a temporary password. Please set your own permanent password to continue using the system.</p>

        <label class="field-label">New Password</label>
        <div class="pw-wrapper">
            <input type="password" id="newPassword" placeholder="Enter new password" oninput="checkStrength()">
            <button type="button" class="pw-toggle" onclick="togglePw('newPassword', this)">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
            </button>
        </div>

        <div class="strength-wrap"><div class="strength-bar" id="strengthBar"></div></div>
        <div class="strength-label" id="strengthLabel"></div>

        <ul class="pw-reqs">
            <li id="req-len"><span class="dot-req"></span> At least 8 characters</li>
            <li id="req-upper"><span class="dot-req"></span> One uppercase letter (A–Z)</li>
            <li id="req-lower"><span class="dot-req"></span> One lowercase letter (a–z)</li>
            <li id="req-num"><span class="dot-req"></span> One number (0–9)</li>
            <li id="req-special"><span class="dot-req"></span> One special character (!@#$%^&*)</li>
        </ul>

        <label class="field-label">Confirm Password</label>
        <div class="pw-wrapper">
            <input type="password" id="confirmPassword" placeholder="Re-enter new password">
            <button type="button" class="pw-toggle" onclick="togglePw('confirmPassword', this)">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                </svg>
            </button>
        </div>

        <div id="resetError"></div>
        <button class="save-btn" id="saveBtn" onclick="saveNewPassword()">Save Password</button>
    </div>
</div>

<!-- ===== SUCCESS MODAL ===== -->
<div class="success-modal" id="successModal">
    <div class="success-box">
        <div class="success-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#15803d" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>
        <h2>Password Saved!</h2>
        <p>Your password has been updated successfully. Please log in again using your new password.</p>
        <button class="login-btn" onclick="window.location.href='slogin.php'">Go to Login</button>
        <div class="redirect-note" id="redirectNote">Redirecting in 5 seconds...</div>
    </div>
</div>

<!-- ================= SIDEBAR ================= -->
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
        <a href="dashboard.php" class="active"><i class="fa fa-th-large"></i> Dashboard</a>
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

<!-- ================= TOPBAR ================= -->
<header class="topbar">
    <div class="topbar-left">
        <h2>Hello, <?= $firstName ?>!</h2>
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

<!-- ================= MAIN ================= -->
<main class="sDashboard-main">
    <section class="sDashboard-stats">
        <div class="sDashboard-card">
            <h4>Upcoming Appointments</h4>
            <h2><?= $upcoming ?></h2>
        </div>
        <div class="sDashboard-card">
            <h4>Completed Sessions</h4>
            <h2><?= $completed ?></h2>
        </div>
        <div class="sDashboard-card">
            <h4>Active Referrals</h4>
            <h2><?= $referrals ?></h2>
        </div>
        <div class="sDashboard-card">
            <h4>Pending Concerns</h4>
            <h2><?= $concerns ?></h2>
        </div>
        <div class="card-emergency">
            <h4>Need immediate help?</h4>
            <p>Contact your counselor or hotline</p>
            <p><strong>📞 0912-345-6789</strong></p>
        </div>
    </section>

    <section class="sDashboard-content">
        <div class="sDashboard-announcement">
            <img src="https://images.unsplash.com/photo-1521737604893-d14cc237f11d"
                 class="sDashboard-announcement-img" alt="Announcement">
            <h4>Latest Announcement</h4>
            <?php if ($announce): ?>
                <h4><?= htmlspecialchars($announce['title']) ?></h4>
                <p><?= htmlspecialchars(substr($announce['message'], 0, 100)) ?>...</p>
                <a class="btn" href="sannouncements.php">View Details</a>
            <?php else: ?>
                <p>No announcements yet.</p>
            <?php endif; ?>
        </div>

        <div class="sDashboard-side">

            <!-- ===== MOOD CARD ===== -->
            <div class="sDashboard-card">
                <h4>Mood</h4>
                <div class="sDashboard-mood-display" id="moodDisplay">No mood recorded yet</div>
                <div id="moodNotif" style="display:none; font-size:12px; margin-top:6px; text-align:center;"></div>
                <div class="sDashboard-mood">
                    <button onclick="setMood('😢','Very Sad')">😢</button>
                    <button onclick="setMood('😕','Sad')">😕</button>
                    <button onclick="setMood('😐','Neutral')">😐</button>
                    <button onclick="setMood('🙂','Happy')">🙂</button>
                    <button onclick="setMood('😁','Very Happy')">😁</button>
                </div>
            </div>

            <!-- ===== RECENT ACTIVITY CARD ===== -->
            <div class="sDashboard-card">
                <h4>Recent Activity</h4>
                <?php if (count($activities) > 0): ?>
                    <?php foreach ($activities as $act): ?>
                        <div class="sDashboard-activity-item">
                            <?= htmlspecialchars($act['activity']) ?>
                            <small><?= date('M d, Y', strtotime($act['created_at'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:var(--text-muted);font-size:13px;">No recent activity.</p>
                <?php endif; ?>
            </div>

        </div>
    </section>

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
// ===== THEME =====
(function () {
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
    const t    = html.getAttribute("data-theme") === "light" ? "dark" : "light";
    html.setAttribute("data-theme", t);
    localStorage.setItem("theme", t);
}
function logout()        { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()   { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=student'; }

document.getElementById('logoutOverlay').addEventListener('click', function (e) {
    if (e.target === this) closeLogout();
});
document.addEventListener("click", e => {
    const menu = document.getElementById("settingsDropdown");
    const btn  = document.querySelector(".sidebar-settingsButton");
    if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target))
        menu.classList.remove("show");
});

// ===== MOOD =====
let wellnessExistsToday = <?= json_encode($wellnessExistsToday) ?>;

function showMoodNotif(msg, color) {
    const notif         = document.getElementById("moodNotif");
    notif.textContent   = msg;
    notif.style.color   = color;
    notif.style.display = 'block';
    setTimeout(() => { notif.style.display = 'none'; }, 3000);
}

function setMood(emoji, text) {
    document.getElementById("moodDisplay").innerHTML =
        `<div style="font-size:40px">${emoji}</div><div>${text}</div>`;

    const today = new Date().toDateString();
    localStorage.setItem("userMoodEmoji", emoji);
    localStorage.setItem("userMoodText",  text);
    localStorage.setItem("moodDate",      today);

    const savedDate   = localStorage.getItem("moodSavedDate");
    const alreadyDone = wellnessExistsToday || (savedDate === today);
    const action      = alreadyDone ? 'update_wellness' : 'save_wellness';

    const fd = new FormData();
    fd.append('action',        action);
    fd.append('mood_label',    text);
    fd.append('stress_level',  50);
    fd.append('sleep_quality', 'Average');

    fetch('dashboard.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            if (json.success) {
                localStorage.setItem("moodSavedDate", today);
                wellnessExistsToday = true; // update in-memory flag too
                showMoodNotif(
                    action === 'update_wellness'
                        ? `✔ Mood updated to "${text}"`
                        : `✔ Mood set to "${text}"`,
                    '#15803d'
                );
            } else if (json.already_done) {
                const fd2 = new FormData();
                fd2.append('action',        'update_wellness');
                fd2.append('mood_label',    text);
                fd2.append('stress_level',  50);
                fd2.append('sleep_quality', 'Average');

                fetch('dashboard.php', { method: 'POST', body: fd2 })
                    .then(r2 => r2.json())
                    .then(json2 => {
                        if (json2.success) {
                            localStorage.setItem("moodSavedDate", today);
                            wellnessExistsToday = true;
                            showMoodNotif(`✔ Mood updated to "${text}"`, '#15803d');
                        } else {
                            showMoodNotif('❌ Could not update mood.', '#e53e3e');
                        }
                    })
                    .catch(() => showMoodNotif('❌ Network error.', '#e53e3e'));
            } else {
                showMoodNotif('❌ ' + (json.message || 'Could not save mood.'), '#e53e3e');
            }
        })
        .catch(() => showMoodNotif('❌ Network error. Please try again.', '#e53e3e'));
}

// Restore mood display on load — only if saved today
window.addEventListener("load", () => {
    const emoji    = localStorage.getItem("userMoodEmoji");
    const text     = localStorage.getItem("userMoodText");
    const moodDate = localStorage.getItem("moodDate");
    const today    = new Date().toDateString();

    if (emoji && text && moodDate === today) {
        document.getElementById("moodDisplay").innerHTML =
            `<div style="font-size:40px">${emoji}</div><div>${text}</div>`;
    } else if (moodDate && moodDate !== today) {
        // New day — clear stale entries
        localStorage.removeItem("userMoodEmoji");
        localStorage.removeItem("userMoodText");
        localStorage.removeItem("moodDate");
        localStorage.removeItem("moodSavedDate");
    }
});

// ===== PW TOGGLE =====
function togglePw(id, btn) {
    const input = document.getElementById(id);
    const show  = input.type === 'password';
    input.type  = show ? 'text' : 'password';
    btn.innerHTML = show
        ? `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
               <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
               <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
               <line x1="1" y1="1" x2="23" y2="23"/></svg>`
        : `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
               <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
}

// ===== PASSWORD STRENGTH =====
function checkStrength() {
    const pw  = document.getElementById('newPassword').value;
    const bar = document.getElementById('strengthBar');
    const lbl = document.getElementById('strengthLabel');

    const reqs = {
        len:     pw.length >= 8,
        upper:   /[A-Z]/.test(pw),
        lower:   /[a-z]/.test(pw),
        num:     /[0-9]/.test(pw),
        special: /[!@#$%^&*]/.test(pw)
    };
    Object.entries(reqs).forEach(([k, met]) => {
        document.getElementById('req-' + k)?.classList.toggle('met', met);
    });

    const score  = Object.values(reqs).filter(Boolean).length;
    const levels = [
        { pct: '0%',   color: '',        text: '' },
        { pct: '20%',  color: '#e53e3e', text: 'Very weak' },
        { pct: '40%',  color: '#dd6b20', text: 'Weak' },
        { pct: '60%',  color: '#d69e2e', text: 'Fair' },
        { pct: '80%',  color: '#38a169', text: 'Strong' },
        { pct: '100%', color: '#276749', text: 'Very strong' },
    ];
    bar.style.width      = levels[score].pct;
    bar.style.background = levels[score].color;
    lbl.textContent      = levels[score].text;
    lbl.style.color      = levels[score].color;
}

// ===== SAVE PASSWORD =====
function saveNewPassword() {
    const newPw  = document.getElementById('newPassword').value;
    const confPw = document.getElementById('confirmPassword').value;
    const errEl  = document.getElementById('resetError');
    const btn    = document.getElementById('saveBtn');

    errEl.textContent = '';

    if (!newPw || !confPw)   { errEl.textContent = 'Please fill in all fields.'; return; }
    if (newPw.length < 8)    { errEl.textContent = 'Password must be at least 8 characters.'; return; }
    if (newPw !== confPw)    { errEl.textContent = 'Passwords do not match.'; return; }

    const allMet = newPw.length >= 8 && /[A-Z]/.test(newPw) && /[a-z]/.test(newPw)
                && /[0-9]/.test(newPw) && /[!@#$%^&*]/.test(newPw);
    if (!allMet) { errEl.textContent = 'Password does not meet all requirements.'; return; }

    btn.disabled    = true;
    btn.textContent = 'Saving...';

    const fd = new FormData();
    fd.append('action',           'reset_password');
    fd.append('new_password',     newPw);
    fd.append('confirm_password', confPw);

    fetch('dashboard.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            btn.disabled    = false;
            btn.textContent = 'Save Password';
            if (json.success) {
                document.getElementById('resetModal').classList.remove('active');
                document.getElementById('pageBlockOverlay').classList.remove('active');
                document.getElementById('successModal').classList.add('active');
                startRedirectCountdown();
            } else {
                errEl.textContent = json.message;
            }
        })
        .catch(() => {
            btn.disabled      = false;
            btn.textContent   = 'Save Password';
            errEl.textContent = 'Something went wrong. Please try again.';
        });
}

// ===== REDIRECT COUNTDOWN =====
function startRedirectCountdown() {
    let secs     = 5;
    const note   = document.getElementById('redirectNote');
    const interval = setInterval(() => {
        secs--;
        note.textContent = `Redirecting in ${secs} second${secs !== 1 ? 's' : ''}...`;
        if (secs <= 0) {
            clearInterval(interval);
            window.location.href = 'slogin.php';
        }
    }, 1000);
}
</script>

</body>
</html>
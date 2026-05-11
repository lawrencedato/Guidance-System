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

$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");

$sid_int = (int)$_SESSION['user_id'];

// ===== HANDLE PASSWORD RESET AJAX =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_password') {
    header('Content-Type: application/json');
    $is_forced  = ($_POST['is_forced']  ?? '0') === '1';
    $current_pw = $_POST['current_password'] ?? '';
    $new_pw     = $_POST['new_password']     ?? '';
    $confirm_pw = $_POST['confirm_password'] ?? '';
    if (!$is_forced) {
        if (!$current_pw) { echo json_encode(["success"=>false,"message"=>"Please enter your current password."]); exit; }
        $pwRes = $conn->query("SELECT password FROM activated_students WHERE student_id=$sid_int LIMIT 1");
        $pwRow = $pwRes ? $pwRes->fetch_assoc() : null;
        if (!$pwRow || !password_verify($current_pw, $pwRow['password'])) {
            echo json_encode(["success"=>false,"message"=>"Current password is incorrect."]); exit;
        }
    }
    if (!$new_pw || !$confirm_pw) { echo json_encode(["success"=>false,"message"=>"Please fill in all fields."]); exit; }
    if (strlen($new_pw) < 8)      { echo json_encode(["success"=>false,"message"=>"Password must be at least 8 characters."]); exit; }
    if ($new_pw !== $confirm_pw)  { echo json_encode(["success"=>false,"message"=>"Passwords do not match."]); exit; }
    if (!preg_match('/[A-Z]/',$new_pw)||!preg_match('/[a-z]/',$new_pw)||!preg_match('/[0-9]/',$new_pw)||!preg_match('/[!@#$%^&*]/',$new_pw)) {
        echo json_encode(["success"=>false,"message"=>"Password does not meet all requirements."]); exit;
    }
    $hashed = password_hash($new_pw, PASSWORD_BCRYPT);
    $ok = $conn->query("UPDATE activated_students SET password='$hashed', is_temp_password=0 WHERE student_id=$sid_int");
    echo json_encode($ok ? ["success"=>true,"forced"=>false] : ["success"=>false,"message"=>"Failed to save password."]);
    exit;
}

/* =========================
   AJAX: PARTICIPATE TOGGLE
   ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement_id'])) {
    header('Content-Type: application/json');
    $student_id      = $_SESSION['user_id'];
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
            VALUES ('$announcement_id', '$student_id', 'interested', NOW())
        ");
        echo json_encode(["action" => "added"]);
        exit;
    }
}

// ===== LOAD STUDENT DATA =====
$sid  = $conn->real_escape_string($_SESSION['user_id']);

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
<link rel="stylesheet" href="changepass.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="body">

<!-- ===== VOLUNTARY SUCCESS TOAST ===== -->
<div class="pw-toast" id="pwToast">
    <i class="fa fa-check-circle"></i> Password changed successfully!
</div>

<!-- ===== PASSWORD MODAL BACKDROP ===== -->
<div class="modal-backdrop" id="pwModalBackdrop">
    <div class="reset-box">

        <button class="modal-close-btn" id="pwModalCloseBtn" onclick="closeChangePassword()">&#x2715;</button>

        <h2 id="pwModalTitle">Change Password</h2>
        <p class="reset-sub" id="pwModalSubtitle">Enter your current password, then choose a new one.</p>

        <div id="currentPwGroup" style="display:none;">
            <label class="field-label">Current Password</label>
            <div class="pw-wrapper">
                <input type="password" id="currentPassword" placeholder="Enter your current password">
                <button type="button" class="pw-toggle" onclick="togglePw('currentPassword', this)">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
            <hr class="pw-divider">
        </div>

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

        <label class="field-label">Confirm New Password</label>
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
<div class="success-modal-wrap" id="successModal">
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
                <button onclick="openChangePassword()"><i class="fa fa-lock"></i> Change Password</button>
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
            <p><?= substr(htmlspecialchars($a['message']),0,120) ?>...</p>
            <p class="interest-count">👥 <?= $a['interested_count'] ?> interested</p>
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

<!-- ===== ANNOUNCEMENT DETAIL MODAL ===== -->
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
            <button id="participateBtn">Participate</button>
            <p id="modalCount">0 interested</p>
        </div>
    </div>
</div>

<!-- ===== LOGOUT MODAL ===== -->
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

<!-- ================= SCRIPT ================= -->
<script>
// ===== THEME =====
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

document.addEventListener("click", e => {
    const menu = document.getElementById("settingsDropdown");
    const btn  = document.querySelector(".sidebar-settingsButton");
    if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
        menu.classList.remove("show");
    }
});

// ===== LOGOUT =====
function logout()        { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()   { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=student'; }

document.getElementById('logoutOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeLogout();
});

// ===== AUTO SCROLL FROM HASH =====
window.addEventListener("load", () => {
    const hash = window.location.hash;
    if (hash) {
        const target = document.querySelector(hash);
        if (target) {
            target.scrollIntoView({ behavior: "smooth", block: "center" });
            target.style.border = "2px solid var(--primary)";
            setTimeout(() => { target.style.border = "none"; }, 2000);
        }
    }
});

// ===== ANNOUNCEMENT MODAL =====
function openModalFromCard(card) {
    document.getElementById("modalTitle").innerText = card.dataset.title;
    document.getElementById("modalBody").innerText  = card.dataset.message;
    document.getElementById("modalExtra").innerHTML = card.dataset.author + "<br>" + card.dataset.date;

    const img = document.getElementById("modalImage");
    if (card.dataset.file && card.dataset.file.trim() !== "") {
        img.src = encodeURI(card.dataset.file);
        img.style.display = "block";
    } else {
        img.style.display = "none";
    }

    const count   = parseInt(card.dataset.count) || 0;
    const countEl = document.getElementById("modalCount");
    countEl.innerText     = count + " interested";
    countEl.dataset.count = count;

    const btn = document.getElementById("participateBtn");
    btn.dataset.id = card.dataset.id;
    btn.innerText  = "⭐ Participate";
    btn.disabled   = false;

    document.getElementById("announcementModal").style.display = "flex";
}

function closeModal() {
    document.getElementById("announcementModal").style.display = "none";
}

// Auto-open from dashboard query param
document.addEventListener("DOMContentLoaded", () => {
    const params = new URLSearchParams(window.location.search);
    const openId = params.get("open");
    if (!openId) return;

    const map = {
        "mental-health-seminar": {
            title: "Mental Health Seminar",
            body:  "A full session focused on emotional resilience and stress management.",
            extra: "📅 April 25, 2026 <br> ⏰ 2:00 PM – 4:00 PM <br> 📍 Auditorium",
            image: "https://images.unsplash.com/photo-1521737604893-d14cc237f11d"
        }
    };

    if (map[openId]) {
        const d = map[openId];
        openModalFromCard({
            dataset: {
                id: openId, title: d.title, message: d.body,
                author: "", date: d.extra, file: d.image, count: 0
            }
        });
    }
});

// Participate button
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

// ===== CHANGE PASSWORD =====
let isForcedReset = false;

function openChangePassword() {
    document.getElementById('settingsDropdown').classList.remove('show');
    isForcedReset = false;
    document.getElementById('currentPwGroup').style.display  = 'block';
    document.getElementById('pwModalTitle').textContent      = 'Change Password';
    document.getElementById('pwModalSubtitle').textContent   = 'Enter your current password, then choose a new one.';
    document.getElementById('pwModalCloseBtn').classList.add('visible');
    resetModalFields();
    document.getElementById('pwModalBackdrop').classList.add('active');
}

function closeChangePassword() {
    if (isForcedReset) return;
    document.getElementById('pwModalBackdrop').classList.remove('active');
    resetModalFields();
}

document.getElementById('pwModalBackdrop').addEventListener('click', function(e) {
    if (e.target === this && !isForcedReset) closeChangePassword();
});

function resetModalFields() {
    document.getElementById('currentPassword').value       = '';
    document.getElementById('newPassword').value           = '';
    document.getElementById('confirmPassword').value       = '';
    document.getElementById('resetError').textContent      = '';
    document.getElementById('strengthBar').style.width     = '0%';
    document.getElementById('strengthBar').style.background = '';
    document.getElementById('strengthLabel').textContent   = '';
    ['len','upper','lower','num','special'].forEach(k => {
        document.getElementById('req-' + k)?.classList.remove('met');
    });
    const btn = document.getElementById('saveBtn');
    btn.disabled    = false;
    btn.textContent = 'Save Password';
}

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

function saveNewPassword() {
    const currentPw = document.getElementById('currentPassword').value;
    const newPw     = document.getElementById('newPassword').value;
    const confPw    = document.getElementById('confirmPassword').value;
    const errEl     = document.getElementById('resetError');
    const btn       = document.getElementById('saveBtn');

    errEl.textContent = '';
    if (!isForcedReset && !currentPw) { errEl.textContent = 'Please enter your current password.'; return; }
    if (!newPw || !confPw)            { errEl.textContent = 'Please fill in all fields.'; return; }
    if (newPw.length < 8)             { errEl.textContent = 'Password must be at least 8 characters.'; return; }
    if (newPw !== confPw)             { errEl.textContent = 'Passwords do not match.'; return; }

    const allMet = newPw.length >= 8 && /[A-Z]/.test(newPw) && /[a-z]/.test(newPw)
                && /[0-9]/.test(newPw) && /[!@#$%^&*]/.test(newPw);
    if (!allMet) { errEl.textContent = 'Password does not meet all requirements.'; return; }

    btn.disabled    = true;
    btn.textContent = 'Saving...';

    const fd = new FormData();
    fd.append('action',           'reset_password');
    fd.append('current_password', currentPw);
    fd.append('new_password',     newPw);
    fd.append('confirm_password', confPw);
    fd.append('is_forced',        isForcedReset ? '1' : '0');

    fetch('sannouncements.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            btn.disabled    = false;
            btn.textContent = 'Save Password';
            if (json.success) {
                if (isForcedReset) {
                    document.getElementById('pwModalBackdrop').classList.remove('active');
                    document.getElementById('pageBlockOverlay')?.classList.remove('active');
                    document.getElementById('successModal').classList.add('active');
                    startRedirectCountdown();
                } else {
                    closeChangePassword();
                    showPwToast();
                }
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

function startRedirectCountdown() {
    let secs = 5;
    const note = document.getElementById('redirectNote');
    const interval = setInterval(() => {
        secs--;
        note.textContent = `Redirecting in ${secs} second${secs !== 1 ? 's' : ''}...`;
        if (secs <= 0) { clearInterval(interval); window.location.href = 'slogin.php'; }
    }, 1000);
}

function showPwToast() {
    const toast = document.getElementById('pwToast');
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 3500);
}
</script>

</body>
</html>
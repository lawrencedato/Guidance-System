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
$profileImg = !empty($counselor['profile_image'])
    ? htmlspecialchars($counselor['profile_image'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';

$pendingCount = (int)$conn->query(
    "SELECT COUNT(*) c FROM appointments WHERE counselor_id='$cid' AND status='Pending'"
)->fetch_assoc()['c'];

$studentsRes = $conn->query("
    SELECT 
        s.student_id, 
        s.first_name, 
        s.last_name, 
        s.course, 
        s.year_level, 
        MAX(a.appointment_date) AS last_session,
        (
            SELECT w.mood_label 
            FROM wellness_checks w 
            WHERE w.student_id = s.student_id 
            ORDER BY w.created_at DESC 
            LIMIT 1
        ) AS latest_mood
    FROM students s
    JOIN appointments a ON a.student_id = s.student_id
    WHERE a.counselor_id = '$cid'
      AND a.status NOT IN ('Rejected', 'Cancelled')
    GROUP BY 
        s.student_id, 
        s.first_name, 
        s.last_name, 
        s.course, 
        s.year_level
    ORDER BY s.last_name ASC
");


// Map mood_label → status
function moodToStatus($mood) {
    switch ($mood) {
        case 'Very Sad':                        return 'critical';
        case 'Sad':                             return 'at-risk';
        case 'Neutral':
        case 'Happy':
        case 'Very Happy':                      return 'stable';
        default:                                return 'unknown';
    }
}

// Map status slug → display label + CSS class
function statusMeta($status) {
    switch ($status) {
        case 'critical': return ['label' => 'Critical', 'class' => 'critical'];
        case 'at-risk':  return ['label' => 'At Risk',  'class' => 'at-risk'];
        case 'stable':   return ['label' => 'Stable',   'class' => 'stable'];
        default:         return ['label' => 'No Data',  'class' => 'info'];
    }
}

// HANDLE GET: student profile for modal
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_student') {
    header('Content-Type: application/json');
    $studentId = (int)($_GET['student_id'] ?? 0);
    if (!$studentId) { echo json_encode(['success' => false, 'message' => 'Missing student ID.']); exit; }
    $res = $conn->query("
        SELECT s.student_id, s.first_name, s.last_name,
               s.email, s.course, s.year_level,
               sp.profile_image,
               sp.emergency_contact_name                 AS emergency_name,
               sp.relationship_to_emergency_contact      AS emergency_relation,
               sp.emergency_contact_number               AS emergency_number,
               w.mood_label                              AS last_mood,
               w.stress_level                            AS last_stress,
               w.sleep_quality                           AS last_sleep,
               DATE_FORMAT(w.created_at, '%M %d, %Y')   AS last_wellness
        FROM students s
        LEFT JOIN student_profiles sp ON sp.student_id = s.student_id
        LEFT JOIN wellness_checks w
               ON w.wellness_id = (
                   SELECT wellness_id FROM wellness_checks
                   WHERE student_id = s.student_id
                   ORDER BY created_at DESC LIMIT 1
               )
        WHERE s.student_id = $studentId
        LIMIT 1
    ");
    $student = $res ? $res->fetch_assoc() : null;
    if (!$student) { echo json_encode(['success' => false, 'message' => 'Student not found.']); exit; }
    echo json_encode(['success' => true, 'student' => $student]);
    exit;
}

$students = [];
while ($row = $studentsRes->fetch_assoc()) $students[] = $row;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Students List</title>
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
      <button class="sidebar-settingsButton" onclick="toggleSettings(event)">
        <i class="fa fa-gear"></i>
      </button>
      <div class="sidebar-settingsDropdown" id="settingsMenu">
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
    <a href="cstudents.php" class="active"><i class="fa fa-users"></i> Students</a>

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
    <h2>Student List</h2>
  </div>

  <div class="topbar-right">

    <div class="topbar-searchBox">
      <select id="searchMode" class="cStudentList-search-mode" onchange="applyFilters()">
        <option value="all">All</option>
        <option value="name">Name</option>
        <option value="id">Student ID</option>
      </select>
      <i class="fa fa-search"></i>
      <input type="text" id="searchInput" oninput="applyFilters()" placeholder="Search students...">
    </div>

    <!-- FILTER BUTTON + DROPDOWN -->
    <div class="cStudentList-filter-wrap" id="filterWrap">
      <button class="btn" id="cStudentList-filterBtn">
        <i class="fa fa-filter"></i> Filter
        <span class="cStudentList-filter-count" id="cStudentList-filterCount"></span>
      </button>

      <div id="cStudentList-filterBox">
        <div class="filter-field">
          <label>Program</label>
          <select id="filterProgram" onchange="applyFilters()">
            <option value="all">All Programs</option>
            <option>BSIT</option>
            <option>BSBA</option>
            <option>BSA</option>
            <option>BSCS</option>
            <option>BSN</option>
            <option>BSECE</option>
          </select>
        </div>

        <div class="filter-field">
          <label>Year Level</label>
          <select id="filterYear" onchange="applyFilters()">
            <option value="all">All Year Levels</option>
            <option>1st Year</option>
            <option>2nd Year</option>
            <option>3rd Year</option>
            <option>4th Year</option>
          </select>
        </div>

        <div class="filter-field">
          <label>Wellness Status</label>
          <select id="filterStatus" onchange="applyFilters()">
            <option value="all">All Status</option>
            <option value="stable">Stable</option>
            <option value="at-risk">At Risk</option>
            <option value="critical">Critical</option>
            <option value="unknown">No Data</option>
          </select>
        </div>

        <div class="filter-field">
          <label>Last Session On</label>
          <input type="date" id="filterDate" onchange="applyFilters()">
        </div>

        <div class="filter-actions">
          <button class="btn-apply" onclick="applyFilters()">Apply</button>
          <button class="btn-clear" onclick="clearFilters()">Clear</button>
        </div>
      </div>
    </div>

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

<!-- LIST -->
<main class="cStudentList-main">
  <div class="cStudentList-container" id="studentListContainer">

    <?php if (empty($students)): ?>
      <p class="cStudentList-empty">No students yet.</p>
    <?php else: ?>

      <?php foreach ($students as $s):
        $sName    = htmlspecialchars($s['first_name'] . ' ' . $s['last_name']);
        $initials = strtoupper(substr($s['first_name'], 0, 1) . substr($s['last_name'], 0, 1));
        $lastSess = $s['last_session'] ? date('F d, Y', strtotime($s['last_session'])) : 'N/A';
        $lastSessRaw = $s['last_session'] ? date('Y-m-d', strtotime($s['last_session'])) : '';

        $status = moodToStatus($s['latest_mood'] ?? '');
        $meta   = statusMeta($status);
      ?>
      <div class="cStudentList-item"
           data-filtered="1"
           data-student-id="<?= (int)$s['student_id'] ?>"
           data-name="<?= strtolower($sName) ?>"
           data-course="<?= strtolower(htmlspecialchars($s['course'])) ?>"
           data-year="<?= strtolower(htmlspecialchars($s['year_level'])) ?>"
           data-status="<?= $status ?>"
           data-last-session="<?= $lastSessRaw ?>">
        <div class="cStudentList-info">
          <div class="cStudentList-avatar" style="overflow:hidden;padding:0;">
            <?php
              $cardImg = null;
              $imgRes = $conn->query("SELECT profile_image FROM student_profiles WHERE student_id='{$s['student_id']}' LIMIT 1");
              $imgRow = $imgRes ? $imgRes->fetch_assoc() : null;
              $cardImg = $imgRow['profile_image'] ?? null;
            ?>
            <?php if ($cardImg && file_exists($cardImg)): ?>
              <img src="<?= htmlspecialchars($cardImg) ?>" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
            <?php else: ?>
              <?= $initials ?>
            <?php endif; ?>
          </div>
          <div class="cStudentList-content">
            <div class="cStudentList-left">
              <div class="cStudentList-nameRow">
                <h3><?= $sName ?></h3>
                <span style="font-size:12px;color:var(--text-muted);font-weight:600;">#<?= $s['student_id'] ?></span>
                <button class="btn-small" onclick="openStudentModal(<?= $s['student_id'] ?>)">View Profile</button>
              </div>
              <p><?= htmlspecialchars($s['course']) ?> • <?= htmlspecialchars($s['year_level']) ?></p>
            </div>
            <div class="cStudentList-right">
              <div class="cStudentList-bottomRight" style="display:flex;flex-direction:column;align-items:flex-end;gap:6px;">
                <span class="cStudentList-status-tag <?= $meta['class'] ?>">
                  <?= $meta['label'] ?>
                  <?php if (!empty($s['latest_mood'])): ?>
                    &mdash; <?= htmlspecialchars($s['latest_mood']) ?>
                  <?php endif; ?>
                </span>
                <p style="margin:0;font-size:12px;color:var(--text-muted);">Last Session: <?= $lastSess ?></p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>

      <!-- PAGINATION -->
      <div class="cStudentList-pagination" id="paginationBar">
        <div class="cStudentList-per-page">
          Show
          <select id="perPageSelect" onchange="pagination.setPerPage(+this.value)">
            <option value="5">5</option>
            <option value="10" selected>10</option>
            <option value="20">20</option>
            <option value="50">50</option>
          </select>
          per page
        </div>
        <span class="cStudentList-pagination-info" id="pageInfo"></span>
        <div class="cStudentList-pagination-controls" id="pageControls"></div>
      </div>

    <?php endif; ?>

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

<!-- STUDENT PROFILE MODAL -->
<div class="cStudentModal" id="studentModal">
  <div class="cStudentModal-container">
    <div class="cStudentModal-header">
      <h2>Student Profile</h2>
      <button onclick="closeStudentModal()">✕</button>
    </div>
    <div class="cStudentModal-body" id="studentModalBody">
      <p class="cStudentModal-loading">Loading...</p>
    </div>
  </div>
</div>

<script>
(function () {
  const saved = localStorage.getItem("theme") || "light";
  document.documentElement.setAttribute("data-theme", saved);
})();

/* ═══════════════════════════════════════
   PAGINATION ENGINE
═══════════════════════════════════════ */
const pagination = (() => {
  let perPage = 10;
  let current = 1;

  function allCards() {
    return [...document.querySelectorAll(".cStudentList-item")];
  }

  function visibleCards() {
    return allCards().filter(el => el.dataset.filtered !== "0");
  }

  function render() {
    const visible    = visibleCards();
    const total      = visible.length;
    const totalPages = Math.max(1, Math.ceil(total / perPage));
    if (current > totalPages) current = totalPages;

    const start = (current - 1) * perPage;
    const end   = start + perPage;

    allCards().forEach(el => {
      if (el.dataset.filtered === "0") { el.style.display = "none"; return; }
      const idx = visible.indexOf(el);
      el.style.display = (idx >= start && idx < end) ? "flex" : "none";
    });

    updateUI(total, start, end, totalPages);
  }

  function updateUI(total, start, end, totalPages) {
    const info     = document.getElementById("pageInfo");
    const controls = document.getElementById("pageControls");
    const bar      = document.getElementById("paginationBar");
    if (!info || !controls || !bar) return;

    bar.style.display = total === 0 ? "none" : "flex";
    info.textContent  = total === 0
      ? "No results found"
      : `Showing ${start + 1}–${Math.min(end, total)} of ${total} student${total !== 1 ? "s" : ""}`;

    controls.innerHTML = "";

    const prev = makeBtn("‹ Prev", "prev-next", current === 1);
    prev.onclick = () => { if (current > 1) { current--; render(); } };
    controls.appendChild(prev);

    pageRange(current, totalPages).forEach(p => {
      if (p === "…") {
        const dots = document.createElement("span");
        dots.className   = "cStudentList-page-ellipsis";
        dots.textContent = "…";
        controls.appendChild(dots);
      } else {
        const b = makeBtn(p, "", false, p === current);
        b.onclick = () => { current = p; render(); };
        controls.appendChild(b);
      }
    });

    const next = makeBtn("Next ›", "prev-next", current === totalPages);
    next.onclick = () => { if (current < totalPages) { current++; render(); } };
    controls.appendChild(next);
  }

  function makeBtn(label, extraClass, disabled, active) {
    const b = document.createElement("button");
    b.className   = "cStudentList-page-btn"
      + (extraClass ? " " + extraClass : "")
      + (active     ? " active"        : "");
    b.textContent = label;
    b.disabled    = !!disabled;
    return b;
  }

  function pageRange(cur, total) {
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const out = [1];
    if (cur > 3)         out.push("…");
    for (let i = Math.max(2, cur - 1); i <= Math.min(total - 1, cur + 1); i++) out.push(i);
    if (cur < total - 2) out.push("…");
    out.push(total);
    return out;
  }

  return {
    init()        { render(); },
    refresh()     { current = 1; render(); },
    setPerPage(n) { perPage = n; current = 1; render(); }
  };
})();

/* ═══════════════════════════════════════
   FILTER BOX TOGGLE
═══════════════════════════════════════ */
document.addEventListener("DOMContentLoaded", function () {
  const btn  = document.getElementById("cStudentList-filterBtn");
  const box  = document.getElementById("cStudentList-filterBox");
  const wrap = document.getElementById("filterWrap");

  btn.addEventListener("click", function (e) {
    e.stopPropagation();
    document.querySelectorAll(".icon-dropdown.show, .sidebar-settingsDropdown.show")
      .forEach(el => el.classList.remove("show"));
    box.classList.toggle("show");
  });

  wrap.addEventListener("click", function (e) {
    e.stopPropagation();
  });
});

/* ═══════════════════════════════════════
   UNIFIED FILTER + SEARCH
═══════════════════════════════════════ */
function applyFilters() {
  const raw     = (document.getElementById("searchInput")?.value || "").trim().toLowerCase();
  const mode    = document.getElementById("searchMode")?.value || "all";
  const program = document.getElementById("filterProgram").value.toLowerCase();
  const year    = document.getElementById("filterYear").value.toLowerCase();
  const status  = document.getElementById("filterStatus").value.toLowerCase();
  const date    = document.getElementById("filterDate").value;

  document.querySelectorAll(".cStudentList-item").forEach(item => {
    const name        = item.dataset.name        || "";
    const id          = item.dataset.studentId   || "";
    const course      = item.dataset.course      || "";
    const yr          = item.dataset.year        || "";
    const itemStatus  = item.dataset.status      || "";
    const lastSession = item.dataset.lastSession || "";

    let matchSearch = true;
    if (raw) {
      if      (mode === "name") matchSearch = name.includes(raw);
      else if (mode === "id")   matchSearch = id.includes(raw);
      else                      matchSearch = name.includes(raw) || id.includes(raw);
    }

    const matchProgram = program === "all" || course.includes(program);

    let matchYear = true;
    if (year !== "all") {
      const filterNum = year.replace(/[^0-9]/g, "");
      const cardNum   = yr.replace(/[^0-9]/g, "");
      matchYear = filterNum ? cardNum === filterNum : yr.includes(year);
    }

    const matchStatus = status === "all" || itemStatus === status;
    const matchDate   = !date || lastSession === date;

    item.dataset.filtered = (matchSearch && matchProgram && matchYear && matchStatus && matchDate) ? "1" : "0";
  });

  updateFilterCount();
  pagination.refresh();
}

function updateFilterCount() {
  const program = document.getElementById("filterProgram").value;
  const year    = document.getElementById("filterYear").value;
  const status  = document.getElementById("filterStatus").value;
  const date    = document.getElementById("filterDate").value;

  const active = [program, year, status].filter(v => v !== "all").length
               + (date ? 1 : 0);

  const badge = document.getElementById("cStudentList-filterCount");
  const btn   = document.getElementById("cStudentList-filterBtn");

  if (active > 0) {
    badge.textContent = active;
    badge.classList.add("show");
    btn.classList.add("cStudentList-filter-active");
  } else {
    badge.textContent = "";
    badge.classList.remove("show");
    btn.classList.remove("cStudentList-filter-active");
  }
}

function clearFilters() {
  document.getElementById("filterProgram").value = "all";
  document.getElementById("filterYear").value    = "all";
  document.getElementById("filterStatus").value  = "all";
  document.getElementById("filterDate").value    = "";
  document.getElementById("searchInput").value   = "";
  document.getElementById("searchMode").value    = "all";
  document.querySelectorAll(".cStudentList-item")
    .forEach(el => { el.dataset.filtered = "1"; });
  updateFilterCount();
  pagination.refresh();
}

/* ═══════════════════════════════════════
   STUDENT PROFILE MODAL
═══════════════════════════════════════ */
function openStudentModal(studentId) {
  const modal = document.getElementById("studentModal");
  const body  = document.getElementById("studentModalBody");
  modal.classList.add("show");
  body.innerHTML = '<p class="cStudentModal-loading">Loading...</p>';

  fetch('cstudents.php?action=get_student&student_id=' + studentId)
    .then(r => r.json())
    .then(json => {
      if (!json.success) {
        body.innerHTML = '<p class="cStudentModal-loading">Could not load profile.</p>';
        return;
      }
      const s         = json.student;
      const initials  = (s.first_name[0] + s.last_name[0]).toUpperCase();
      const lastCheck = s.last_wellness || "No check-in yet";
      const mood      = s.last_mood     || "N/A";
      const stress    = s.last_stress   !== null ? s.last_stress + " / 10" : "N/A";
      const sleep     = s.last_sleep    || "N/A";

      const moodClass = {
        'Very Sad'  : 'critical',
        'Sad'       : 'at-risk',
        'Neutral'   : 'stable',
        'Happy'     : 'stable',
        'Very Happy': 'stable'
      }[mood] || 'info';

      body.innerHTML = `
      <div class="cStudentModal-profile">
        <div class="cStudentModal-avatar" style="overflow:hidden;">
          ${s.profile_image
            ? `<img src="${s.profile_image}" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">`
            : initials
          }
        </div>
          <div class="cStudentModal-profileText">
            <div class="cStudentModal-nameRow">
              <h3>${s.first_name} ${s.last_name}</h3>
              <span class="cStudentList-status-tag ${moodClass}">${mood}</span>
            </div>
            <p>${s.course} • ${s.year_level}</p>
          </div>
        </div>
        <div class="cStudentModal-grid">
          <div class="cStudentModal-box">
            <h4>Academic Information</h4>
            <p><b>Program:</b> ${s.course}</p>
            <p><b>Year Level:</b> ${s.year_level}</p>
            <p><b>Email:</b> ${s.email}</p>
          </div>
          <div class="cStudentModal-box">
            <h4>Emergency Contact</h4>
            <p><b>Name:</b> ${s.emergency_name || "N/A"}</p>
            <p><b>Relation:</b> ${s.emergency_relation || "N/A"}</p>
            <p><b>Contact:</b> ${s.emergency_number || "N/A"}</p>
          </div>
        </div>
        <div class="cStudentModal-box cStudentModal-box--wellness">
          <h4>Latest Wellness Check-in</h4>
          <p><b>Date:</b> ${lastCheck}</p>
          <p><b>Mood:</b> ${mood}</p>
          <p><b>Stress Level:</b> ${stress}</p>
          <p><b>Sleep Quality:</b> ${sleep}</p>
        </div>`;
    })
    .catch(() => {
      body.innerHTML = '<p class="cStudentModal-loading">Could not load profile.</p>';
    });
}

function closeStudentModal() {
  document.getElementById("studentModal").classList.remove("show");
}

/* ═══════════════════════════════════════
   SETTINGS / THEME / LOGOUT / DROPDOWNS
═══════════════════════════════════════ */
function toggleSettings(e) {
  e.stopPropagation();
  document.getElementById("settingsMenu").classList.toggle("show");
}

function toggleTheme() {
  const html     = document.documentElement;
  const newTheme = html.getAttribute("data-theme") === "light" ? "dark" : "light";
  html.setAttribute("data-theme", newTheme);
  localStorage.setItem("theme", newTheme);
}

function toggleDropdown(id, e) {
  e.stopPropagation();
  document.getElementById(id).classList.toggle("show");
}

function logout()        { document.getElementById("logoutOverlay").classList.add("show"); }
function closeLogout()   { document.getElementById("logoutOverlay").classList.remove("show"); }
function confirmLogout() { window.location.href = "logout.php?role=counselor"; }

document.getElementById("logoutOverlay").addEventListener("click", function (e) {
  if (e.target === this) closeLogout();
});

document.addEventListener("click", function () {
  document.querySelectorAll(".icon-dropdown.show, .sidebar-settingsDropdown.show, #cStudentList-filterBox.show")
    .forEach(el => el.classList.remove("show"));
});

/* ═══════════════════════════════════════
   INIT
═══════════════════════════════════════ */
document.addEventListener("DOMContentLoaded", function () {
  document.querySelectorAll(".cStudentList-item")
    .forEach(el => { el.dataset.filtered = "1"; });
  pagination.init();
});
</script>
<script>var SESSION_ROLE = 'counselor';</script>
<script src="session_timeout.js"></script>
</body>
</html>
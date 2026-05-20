<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: slogin.php");
    exit;
}

$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");

// ── STUDENTS (activated) ──
$studentRows = [];
$sResult = $conn->query("
    SELECT s.student_id, s.first_name, s.last_name, s.email, s.year_level, a.status
    FROM activated_students a
    JOIN students s ON a.student_id = s.student_id
    ORDER BY s.student_id ASC
");
while ($row = $sResult->fetch_assoc()) $studentRows[] = $row;

// ── COUNSELORS ──
$counselorRows = [];
$cResult = $conn->query("
    SELECT counselor_id, first_name, last_name, email, department, status
    FROM counselors
    ORDER BY counselor_id ASC
");
while ($row = $cResult->fetch_assoc()) $counselorRows[] = $row;

// ── ADMINS ──
$adminRows = [];
$aResult = $conn->query("
    SELECT admin_id, name, email, status
    FROM admins
    ORDER BY admin_id ASC
");
while ($row = $aResult->fetch_assoc()) $adminRows[] = $row;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Users</title>

<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* ── PAGINATION ── */
.aUsers-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 18px;
  padding-top: 14px;
  border-top: 1px solid var(--border);
}

.aUsers-pagination-info {
  font-size: 13px;
  color: var(--text-muted);
}

.aUsers-pagination-controls {
  display: flex;
  align-items: center;
  gap: 5px;
}

.aUsers-page-btn {
  min-width: 34px;
  height: 34px;
  padding: 0 9px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  background: var(--card);
  color: var(--text);
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  transition: var(--transition);
  font-family: inherit;
}

.aUsers-page-btn:hover:not(:disabled) {
  background: var(--hover);
  border-color: var(--primary);
  color: var(--primary);
  transform: translateY(-1px);
}

.aUsers-page-btn.active {
  background: linear-gradient(135deg, #113F67, #4988C4);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 4px 12px rgba(17,63,103,0.28);
}

.aUsers-page-btn:disabled {
  opacity: 0.38;
  cursor: not-allowed;
  transform: none;
}

.aUsers-page-ellipsis {
  font-size: 13px;
  color: var(--text-muted);
  padding: 0 3px;
  user-select: none;
}

.aUsers-per-page {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--text-muted);
}

.aUsers-per-page select {
  padding: 5px 9px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  background: var(--card);
  color: var(--text);
  font-size: 13px;
  font-family: inherit;
  outline: none;
  cursor: pointer;
  transition: var(--transition);
}

.aUsers-per-page select:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(73,136,196,0.15);
}

/* dark mode */
[data-theme="dark"] .aUsers-pagination {
  border-top-color: rgba(255,255,255,0.07);
}
[data-theme="dark"] .aUsers-pagination-info { color: #6e8ea8; }
[data-theme="dark"] .aUsers-page-btn {
  background: rgba(255,255,255,0.04);
  border-color: rgba(255,255,255,0.1);
  color: #dce8f5;
}
[data-theme="dark"] .aUsers-page-btn:hover:not(:disabled) {
  background: rgba(73,136,196,0.15);
  border-color: #4988C4;
  color: #93c5fd;
}
[data-theme="dark"] .aUsers-page-btn.active {
  background: linear-gradient(135deg, #0d3254, #3a7ab8);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 4px 12px rgba(0,0,0,0.4);
}
[data-theme="dark"] .aUsers-page-btn:disabled { opacity: 0.3; }
[data-theme="dark"] .aUsers-page-ellipsis { color: #4a6680; }
[data-theme="dark"] .aUsers-per-page { color: #6e8ea8; }
[data-theme="dark"] .aUsers-per-page select {
  background: rgba(255,255,255,0.05);
  border-color: rgba(255,255,255,0.1);
  color: #dce8f5;
}
[data-theme="dark"] .aUsers-per-page select:focus {
  border-color: #4988C4;
  box-shadow: 0 0 0 3px rgba(73,136,196,0.2);
}
</style>
</head>

<body class="body">

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
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>
  </div>

  <nav class="sidebar-menu">
    <a href="admin.php"><i class="fa fa-gauge"></i> Dashboard</a>
    <p class="sidebar-title">MANAGEMENT</p>
    <a href="ausers.php" class="active"><i class="fa fa-users"></i> Users</a>
    <a href="astudents.php"><i class="fa fa-user-graduate"></i> Students</a>
    <a href="acounselors.php"><i class="fa fa-user-doctor"></i> Counselors</a>
    <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>
    <p class="sidebar-title">SYSTEM</p>
    <a href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>
    <a href="aauditlogs.php"><i class="fa fa-clipboard-list"></i> Audit Logs</a>
  </nav>
</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Users</h2>
    <p class="topbar-muted">Manage system account by role.</p>
  </div>
</header>

<!-- ================= MAIN WRAPPER ================= -->
<main class="aUsers-main">

  <!-- ================= TABS ================= -->
  <div class="aUsers-tabs">
    <button class="active" onclick="showTab(event,'students')">
      <i class="fa fa-user-graduate"></i> Students
    </button>
    <button onclick="showTab(event,'counselors')">
      <i class="fa fa-user-doctor"></i> Counselors
    </button>
    <button onclick="showTab(event,'admins')">
      <i class="fa fa-user-tie"></i> Admins
    </button>
  </div>

  <!-- ================= STUDENTS ================= -->
  <section id="students" class="aUsers-tab active aUsers-card">
    <div class="aUsers-header">
      <div>
        <h3>Student Accounts</h3>
        <p class="muted">Activated student users</p>
      </div>
      <div class="record-actions">
        <button class="btn-export" style="position:relative; z-index:1;" onclick="exportCsv('students')">
            <i class="fa fa-file-export"></i> Export CSV
        </button>
      </div>
    </div>

    <div class="table-wrapper">
      <table id="students-table">
        <thead>
          <tr>
            <th>Student ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Year Level</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="students-tbody">
          <?php if (empty($studentRows)): ?>
            <tr><td colspan="5" style="text-align:center;">No student accounts found.</td></tr>
          <?php else: ?>
            <?php foreach ($studentRows as $s): ?>
              <tr>
                <td><?= htmlspecialchars($s['student_id']) ?></td>
                <td><?= htmlspecialchars($s['first_name'] . ' ' . $s['last_name']) ?></td>
                <td><?= htmlspecialchars($s['email']) ?></td>
                <td><?= htmlspecialchars($s['year_level']) ?></td>
                <td>
                  <span class="aBadge <?= $s['status'] === 'active' ? 'aBadge-success' : 'aBadge-danger' ?>">
                    <?= ucfirst($s['status']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="aUsers-pagination" id="students-pagination">
      <div class="aUsers-per-page">
        <label for="students-per-page">Rows per page:</label>
        <select id="students-per-page" onchange="changePerPage('students', this.value)">
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
      </div>
      <span class="aUsers-pagination-info" id="students-info"></span>
      <div class="aUsers-pagination-controls" id="students-controls"></div>
    </div>
  </section>

  <!-- ================= COUNSELORS ================= -->
  <section id="counselors" class="aUsers-tab aUsers-card">
    <div class="aUsers-header">
      <div>
        <h3>Counselor Accounts</h3>
        <p class="muted">System counselors and staff</p>
      </div>
      <div class="record-actions">
        <button class="btn-export" style="position:relative; z-index:1;" onclick="exportCsv('counselors')">
            <i class="fa fa-file-export"></i> Export CSV
        </button>
      </div>
    </div>

    <div class="table-wrapper">
      <table id="counselors-table">
        <thead>
          <tr>
            <th>Counselor ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Department</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="counselors-tbody">
          <?php if (empty($counselorRows)): ?>
            <tr><td colspan="5" style="text-align:center;">No counselor accounts found.</td></tr>
          <?php else: ?>
            <?php foreach ($counselorRows as $c): ?>
              <tr>
                <td><?= htmlspecialchars($c['counselor_id']) ?></td>
                <td><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['department']) ?></td>
                <td>
                  <span class="aBadge <?= $c['status'] === 'active' ? 'aBadge-success' : 'aBadge-danger' ?>">
                    <?= ucfirst($c['status']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
 </section>
  <!-- ================= ADMINS ================= -->
  <section id="admins" class="aUsers-tab aUsers-card">
    <div class="aUsers-header">
      <div>
        <h3>Admin Accounts</h3>
        <p class="muted">System administrators</p>
      </div>
      <div class="record-actions">
        <button class="btn-export" style="position:relative; z-index:1;" onclick="exportCsv('admins')">
            <i class="fa fa-file-export"></i> Export CSV
        </button>
      </div>
    </div>

    <div class="table-wrapper">
      <table id="admins-table">
        <thead>
          <tr>
            <th>Admin ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="admins-tbody">
          <?php if (empty($adminRows)): ?>
            <tr><td colspan="5" style="text-align:center;">No admin accounts found.</td></tr>
          <?php else: ?>
            <?php foreach ($adminRows as $a): ?>
              <tr>
                <td><?= htmlspecialchars($a['admin_id']) ?></td>
                <td><?= htmlspecialchars($a['name']) ?></td>
                <td><?= htmlspecialchars($a['email']) ?></td>
                <td>Administrator</td>
                <td>
                  <span class="aBadge <?= $a['status'] === 'active' ? 'aBadge-success' : 'aBadge-danger' ?>">
                    <?= ucfirst($a['status']) ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </section>

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

<!-- ================= SCRIPT ================= -->
<script>

(function() {
  const saved = localStorage.getItem("theme") || "light";
  document.documentElement.setAttribute("data-theme", saved);
})();

function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}

document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");
  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});

function toggleTheme() {
  const html = document.documentElement;
  const newTheme = html.getAttribute("data-theme") === "light" ? "dark" : "light";
  html.setAttribute("data-theme", newTheme);
  localStorage.setItem("theme", newTheme);
}

function showTab(event, tab) {
  document.querySelectorAll(".aUsers-tab").forEach(s => s.classList.remove("active"));
  document.querySelectorAll(".aUsers-tabs button").forEach(b => b.classList.remove("active"));
  document.getElementById(tab).classList.add("active");
  event.currentTarget.classList.add("active");
}

/* ── PAGINATION ENGINE ── */

const paginationState = {
  students:   { page: 1, perPage: 10, allRows: [] },
  counselors: { page: 1, perPage: 10, allRows: [] },
  admins:     { page: 1, perPage: 10, allRows: [] }
};

function initPagination(name) {
  const tbody = document.getElementById(name + "-tbody");
  const rows = Array.from(tbody.querySelectorAll("tr"));
  paginationState[name].allRows = rows;
  renderPage(name);
}

function renderPage(name) {
  const state = paginationState[name];
  const { page, perPage, allRows } = state;
  const total = allRows.length;

  /* hide/show rows */
  const start = (page - 1) * perPage;
  const end   = Math.min(start + perPage, total);
  allRows.forEach((row, i) => {
    row.style.display = (i >= start && i < end) ? "" : "none";
  });

  /* info text */
  const infoEl = document.getElementById(name + "-info");
  if (total === 0) {
    infoEl.textContent = "No records found";
  } else {
    infoEl.textContent = "Showing " + (start + 1) + "–" + end + " of " + total;
  }

  /* controls */
  const ctrl = document.getElementById(name + "-controls");
  const totalPages = Math.ceil(total / perPage) || 1;
  ctrl.innerHTML = "";

  /* prev button */
  const prev = makeBtn("‹", page === 1, () => goPage(name, page - 1));
  prev.title = "Previous";
  ctrl.appendChild(prev);

  /* page numbers */
  const pages = buildPageRange(page, totalPages);
  pages.forEach(p => {
    if (p === "…") {
      const el = document.createElement("span");
      el.className = "aUsers-page-ellipsis";
      el.textContent = "…";
      ctrl.appendChild(el);
    } else {
      const btn = makeBtn(p, false, () => goPage(name, p));
      if (p === page) btn.classList.add("active");
      ctrl.appendChild(btn);
    }
  });

  /* next button */
  const next = makeBtn("›", page === totalPages || totalPages === 0, () => goPage(name, page + 1));
  next.title = "Next";
  ctrl.appendChild(next);
}

function makeBtn(label, disabled, onClick) {
  const btn = document.createElement("button");
  btn.className = "aUsers-page-btn";
  btn.textContent = label;
  btn.disabled = disabled;
  if (!disabled) btn.addEventListener("click", onClick);
  return btn;
}

function buildPageRange(current, total) {
  if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
  const pages = [];
  if (current <= 4) {
    for (let i = 1; i <= 5; i++) pages.push(i);
    pages.push("…");
    pages.push(total);
  } else if (current >= total - 3) {
    pages.push(1);
    pages.push("…");
    for (let i = total - 4; i <= total; i++) pages.push(i);
  } else {
    pages.push(1);
    pages.push("…");
    for (let i = current - 1; i <= current + 1; i++) pages.push(i);
    pages.push("…");
    pages.push(total);
  }
  return pages;
}

function goPage(name, page) {
  const state = paginationState[name];
  const total = state.allRows.length;
  const totalPages = Math.ceil(total / state.perPage) || 1;
  state.page = Math.max(1, Math.min(page, totalPages));
  renderPage(name);
}

function changePerPage(name, value) {
  paginationState[name].perPage = parseInt(value, 10);
  paginationState[name].page = 1;
  renderPage(name);
}

/* ── CSV EXPORT (exports all rows, not just current page) ── */
function exportCsv(type) {
  const section = document.getElementById(type);
  const table   = section.querySelector("table");
  const allRows = Array.from(table.querySelectorAll("tr"));

  const csvLines = allRows.map(row => {
    const cells = row.querySelectorAll("th, td");
    return Array.from(cells).map(cell => {
      let text = cell.innerText.trim().replace(/\n+/g, " ");
      if (text.includes(",") || text.includes('"')) {
        text = '"' + text.replace(/"/g, '""') + '"';
      }
      return text;
    }).join(",");
  });

  const a = document.createElement("a");
  a.href = "data:text/csv;charset=utf-8," + encodeURIComponent(csvLines.join("\n"));
  a.download = type + "_export.csv";
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

/* ── LOGOUT ── */
function logout() {
  document.getElementById('logoutOverlay').classList.add('show');
}
function closeLogout() {
  document.getElementById('logoutOverlay').classList.remove('show');
}
function confirmLogout() {
  window.location.href = 'logout.php?role=admin';
}
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});

/* ── INIT ── */
document.addEventListener("DOMContentLoaded", () => {
  initPagination("students");
});

</script>

</body>
</html>
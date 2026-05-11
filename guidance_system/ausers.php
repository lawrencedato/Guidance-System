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

      <table>
        <thead>
          <tr>
            <th>Student ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Year Level</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
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

      <table>
        <thead>
          <tr>
            <th>Counselor ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Department</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
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

      <table>
        <thead>
          <tr>
            <th>Admin ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
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
  html.setAttribute(
    "data-theme",
    html.getAttribute("data-theme") === "light" ? "dark" : "light"
  );
}

function showTab(event, tab) {
  const sections = document.querySelectorAll(".aUsers-tab");
  const buttons = document.querySelectorAll(".aUsers-tabs button");

  sections.forEach(s => s.classList.remove("active"));
  buttons.forEach(b => b.classList.remove("active"));

  document.getElementById(tab).classList.add("active");
  event.currentTarget.classList.add("active");
}

function exportCsv(type) {
  const section = document.getElementById(type);
  const table = section.querySelector("table");
  const rows = table.querySelectorAll("tr");

  const csvLines = [];

  rows.forEach(row => {
    const cells = row.querySelectorAll("th, td");
    const line = Array.from(cells).map(cell => {
      let text = cell.innerText.trim().replace(/\n+/g, " ");
      if (text.includes(",") || text.includes('"')) {
        text = `"${text.replace(/"/g, '""')}"`;
      }
      return text;
    });
    csvLines.push(line.join(","));
  });

  const csv = csvLines.join("\n");
  const a = document.createElement("a");
  a.href = "data:text/csv;charset=utf-8," + encodeURIComponent(csv);
  a.download = type + "_export.csv";
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

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

</script>

</body>
</html>
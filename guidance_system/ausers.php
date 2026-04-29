<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Users</title>

<link rel="stylesheet" href="styles.css">
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
        <button onclick="toggleTheme()">
          <i class="fa fa-moon"></i> Theme
        </button>

        <button onclick="logout()">
          <i class="fa fa-right-from-bracket"></i> Logout
        </button>
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
        <button class="btn-export" onclick="exportCsv('students')">
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
          <tr>
            <td>S-001</td>
            <td>Juan Dela Cruz</td>
            <td>juan@email.com</td>
            <td>2nd Year</td>
            <td><span class="aBadge aBadge-success">Active</span></td>
          </tr>
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
        <button class="btn-export" onclick="exportCsv('counselors')">
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
            <th>Specialization</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td>C-001</td>
            <td>Dr. Reyes</td>
            <td>reyes@unitycare.com</td>
            <td>Mental Health</td>
            <td><span class="aBadge aBadge-success">Active</span></td>
          </tr>
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
        <button class="btn-export" onclick="exportCsv('admins')">
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
          <tr>
            <td>A-001</td>
            <td>System Admin</td>
            <td>admin@unitycare.com</td>
            <td>Super Admin</td>
            <td><span class="aBadge aBadge-success">Active</span></td>
          </tr>
        </tbody>

      </table>

    </div>

  </section>

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
  alert(`Export ${type} to CSV - to be implemented`);
}

function logout() {
  if (confirm("Are you sure you want to logout?")) {
    localStorage.clear();
    window.location.href = "login.html";
  }
}

</script>

</body>
</html>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>UNITYCARE | History</title>

<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="history.css">
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
      <button class="sidebar-settingsButton" onclick="toggleSettingsMenu()">
        <i class="fa fa-gear"></i>
      </button>

      <div class="sidebar-settingsDropdown" id="settingsMenu">
        <a href="cprofile.html"><i class="fa fa-user"></i> Profile</a>
        <a href="chistory.html" class="active"><i class="fa fa-clock"></i> History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">
    <a href="counselor.html"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">SESSIONS</p>
    <a href="cappointments.html"><i class="fa fa-calendar-plus"></i> Appointment Requests</a>
    <a href="cconcerns.html"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>
    <a href="cfeedback.html"><i class="fa fa-comment"></i> Session Feedback</a>

    <p class="sidebar-title">STUDENTS</p>
    <a href="cstudents.html"><i class="fa fa-users"></i> Student List</a>

    <p class="sidebar-title">REPORTS</p>
    <a href="creports.html"><i class="fa fa-file"></i> Reports</a>

    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.html"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.html"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left">
    <h3>History</h3>
  </div>

  <div class="topbar-right">

    <div class="topbar-icons">
      <div class="topbar-icon">
        <i class="fa fa-envelope"></i>
        <span class="badge">2</span>
      </div>

      <div class="topbar-icon">
        <i class="fa fa-bell"></i>
        <span class="badge">4</span>
      </div>
    </div>

    <div class="topbar-user">
      <img src="counselor.jpg" alt="user">
      <div>
        <strong>Dr. Lawrence Dato</strong>
        <p>lawrencedato@gmail.com</p>
      </div>
    </div>

  </div>
</header>

<!-- MAIN -->
<main class="cHistory-main">

  <!-- TABS -->
  <div class="cHistory-tabs">
    <button class="active" onclick="switchTab(event,'sessions')">Past Sessions</button>
    <button onclick="switchTab(event,'announcements')">Announcements</button>
    <button onclick="switchTab(event,'referrals')">Referrals</button>
  </div>

  <!-- FILTERS -->
  <div class="cHistory-filterBar">

    <input type="date">

    <!-- YEAR LEVEL -->
    <select>
      <option value="">Year Levels</option>
      <option>1st Year</option>
      <option>2nd Year</option>
      <option>3rd Year</option>
      <option>4th Year</option>
    </select>

    <!-- PROGRAM -->
    <select>
      <option value="">Programs</option>
      <option>BSIT</option>
      <option>BSCS</option>
      <option>BSA</option>
      <option>BSBA</option>
      <option>BEED</option>
    </select>

    <select>
      <option>Status</option>
      <option>Completed</option>
      <option>Expired</option>
    </select>

  </div>

  <!-- SESSIONS -->
  <div id="sessions" class="cHistory-tabContent">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Date</th>
          <th>Time</th>
          <th>Type</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr><td colspan="5" class="empty">No past sessions found</td></tr>
      </tbody>
    </table>
  </div>

  <!-- ANNOUNCEMENTS -->
  <div id="announcements" class="cHistory-tabContent hidden">
    <table>
      <thead>
        <tr>
          <th>Title</th>
          <th>Post Date</th>
          <th>Year Level</th>
          <th>Reach</th>
        </tr>
      </thead>
      <tbody>
        <tr><td colspan="4" class="empty">No past announcements found</td></tr>
      </tbody>
    </table>
  </div>

  <!-- REFERRALS -->
  <div id="referrals" class="cHistory-tabContent hidden">
    <table>
      <thead>
        <tr>
          <th>Student</th>
          <th>Referred To</th>
          <th>Reason</th>
          <th>Date</th>
        </tr>
      </thead>
      <tbody>
        <tr><td colspan="4" class="empty">No past referrals found</td></tr>
      </tbody>
    </table>
  </div>

</main>

<script>
function toggleSettingsMenu() {
  const menu = document.getElementById("settingsDropdown");
  menu.style.display = menu.style.display === "flex" ? "none" : "flex";
}

function switchTab(event, tabId) {
  document.querySelectorAll(".cHistory-tabContent")
    .forEach(t => t.classList.add("hidden"));

  document.getElementById(tabId).classList.remove("hidden");

  document.querySelectorAll(".cHistory-tabs button")
    .forEach(b => b.classList.remove("active"));

  event.target.classList.add("active");
}

function toggleTheme() {
  const html = document.documentElement;
  html.setAttribute("data-theme",
    html.getAttribute("data-theme") === "dark" ? "light" : "dark"
  );
}

function logout() {
  window.location.href = "role.html";
}
</script>

</body>
</html>
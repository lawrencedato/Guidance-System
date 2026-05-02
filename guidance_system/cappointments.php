<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Appointment Requests - UNITYCARE</title>

<link rel="stylesheet" href="styles.css">
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
    <a href="cappointments.php" class="active"><i class="fa fa-calendar-plus"></i> Appointment Requests</a>
    <a href="cconcerns.php"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>
    <a href="cfeedback.php"><i class="fa fa-comment"></i> Session Feedback</a>

    <p class="sidebar-title">STUDENTS</p>
    <a href="cstudents.php"><i class="fa fa-users"></i> Students</a>

    <p class="sidebar-title">REPORTS</p>
    <a href="creports.php"><i class="fa fa-file"></i> Reports</a>

    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.php"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Appointment Requests</h2>
  </div>

  <div class="topbar-right">

    <div class="topbar-searchBox">
      <i class="fa fa-search"></i>
      <input type="text" placeholder="Search...">
    </div>

    <div class="filter-wrapper">

      <button class="btn" onclick="toggleFilterBox()">
        <i class="fa fa-filter"></i> Filter
      </button>

      <div id="filterBox" class="filter-box">

        <select id="filterPriority">
          <option value="all">Priority</option>
          <option>Low</option>
          <option>Medium</option>
          <option>High</option>
        </select>

        <input type="date" id="filterDate">

        <div class="filter-actions">
          <button onclick="applyFilter()" class="btn-apply">Apply</button>
          <button onclick="clearFilter()" class="btn-clear">Clear</button>
        </div>

      </div>

    </div>

    <div class="topbar-icon" onclick="toggleDropdown('notifDropdown', event)">
      <i class="fa fa-bell"></i>
      <span class="badge">4</span>

      <div class="icon-dropdown" id="notifDropdown">
        <p>No new notifications</p>
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
<main class="cAppointment-main">

  <section class="cAppointment-grid">

    <div class="cAppointment-card"
         data-file="uploads/chie-roque-file.pdf"
         data-id="app-001">

      <h3>
        <i class="fa fa-user"></i> Chie Roque
        <button class="btn-small" onclick="openStudentModal()">View Profile</button>
      </h3>

      <p><b>Reason:</b> Stress Counseling</p>
      <p><b>Department:</b> Sophomore - BSIT</p>

      <p><b>Date:</b> April 25, 2026</p>
      <p><b>Time:</b> 10:30 AM</p>

      <p><b>Status:</b> Pending</p>

      <div class="cAppointment-actions">

        <button class="cAppointment-btn approve">
          <i class="fa fa-check"></i> Approve
        </button>

        <button class="cAppointment-btn decline">
          <i class="fa fa-times"></i> Decline
        </button>

        <button class="cAppointment-exportBtn" onclick="exportAppointment(this)" style="display:none;">
          <i class="fa fa-download"></i>
        </button>

      </div>

    </div>

  </section>

</main>

<div class="cStudentModal" id="studentModal">

  <div class="cStudentModal-container">

    <div class="cStudentModal-header">
      <h2>Student Profile</h2>
      <button onclick="closeStudentModal()">✕</button>
    </div>

    <div class="cStudentModal-body">

      <div class="cStudentModal-profile">

        <div class="cStudentModal-avatar">JS</div>

        <div class="cStudentModal-profileText">

          <div class="cStudentModal-nameRow">
            <h3>Adolf</h3>

            <span id="studentStatusTag" class="tag stable">
              Stable
            </span>
          </div>

          <p>BSIT • 2nd Year</p>

        </div>

      </div>

      <div class="cStudentModal-box">
        <h4>Wellness Progress: Good</h4>
        <p><b>Overall Score:</b> 82%</p>
        <p><b>Recent Check-in:</b> April 22</p>
      </div>

    </div>

  </div>
</div>

<script>
function toggleSettingsMenu(e){
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

function toggleTheme(){
  const html = document.documentElement;
  html.setAttribute("data-theme",
    html.getAttribute("data-theme") === "light" ? "dark" : "light"
  );
}

function logout(){
  localStorage.clear();
  window.location.href = "clogin.php";
}

function openStudentModal(){
  document.getElementById("studentModal").classList.add("show");
}

function closeStudentModal(){
  document.getElementById("studentModal").classList.remove("show");
}

function toggleFilterBox(){
  document.getElementById("filterBox").classList.toggle("show");
}

function applyFilter(){
  alert("Filter applied");
}

function clearFilter(){
  alert("Filter cleared");
}

function exportAppointment(btn){
  const card = btn.closest(".cAppointment-card");
  const file = card.getAttribute("data-file");

  if(!file){
    alert("No file found");
    return;
  }

  const a = document.createElement("a");
  a.href = file;
  a.download = file.split("/").pop();
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

</script>

</body>
</html>
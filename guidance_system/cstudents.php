<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>UNITYCARE | Students List</title>

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
      <button class="sidebar-settingsButton" onclick="toggleSettings()">
        <i class="fa fa-gear"></i>
      </button>

      <div class="sidebar-settingsDropdown" id="settingsMenu">
        <a href="profile.html"><i class="fa fa-user"></i> Profile</a>
        <a href="chistory.html"><i class="fa fa-clock"></i> Session History</a>
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

    <p class="sidebar-title">STUDENTS</p>
    <a href="students.html" class="active"><i class="fa fa-users"></i> Students</a>

    <p class="sidebar-title">REPORTS</p>
    <a href="creports.html"><i class="fa fa-file"></i> Reports</a>

    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.html"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.html"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">
  <div class="topbar-left">
    <h3>Student List</h3>
  </div>

  <div class="topbar-right">

    <div class="topbar-searchBox">
      <i class="fa fa-search"></i>
      <input type="text" placeholder="Search...">
    </div>

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


<!-- LIST -->
<main class="cStudentList-main">

  <div class="cStudentList-container">

    <div class="cStudentList-item">
  <div class="cStudentList-info">

    <div class="cStudentList-avatar">JS</div>

    <!-- CONTENT WRAPPER (IMPORTANT) -->
    <div class="cStudentList-content">

      <!-- LEFT -->
      <div class="cStudentList-left">
        <div class="cStudentList-nameRow">
          <h3>Adolf</h3>
          <button class="btn-small" onclick="openStudentModal()">
            View Profile
          </button>
        </div>

        <p>BSIT • 2nd Year</p>
      </div>

      <!-- RIGHT -->
      <div class="cStudentList-right">
        <span class="tag stable">Stable</span>
        <div class="cStudentList-meta">
          Last Session: Apr 10, 2026
        </div>
      </div>

    </div> <!-- END content -->

  </div>
</div>
</main>

<!-- =========================
     MODAL (WELLNESS VIEW ONLY)
========================= -->
<div class="cStudentModal" id="studentModal">

  <div class="cStudentModal-container">

    <div class="cStudentModal-header">
      <h2>Student Profile</h2>
      <button onclick="closeStudentModal()">✕</button>
    </div>

    <div class="cStudentModal-body">

      <div class="cStudentModal-profile">
        <div class="cStudentModal-avatar">JS</div>
        <div>
          <h3>Adolf</h3>
          <p>BSIT • 2nd Year</p>
          <span class="tag stable">Stable</span>
        </div>
      </div>

      <!-- =========================
           WELLNESS PROGRESS SECTION
      ========================== -->
      <div class="cStudentModal-box">
        <h4>Wellness Progress: Good</h4>

        <p><b>Overall Score:</b> 82%</p>

        <div class="cStudentModal-progressBar">
          <div class="cStudentModal-progressFill"></div>
        </div>
        <p><b>Recent Check-in:</b> April 22</p>
      </div>

      <div class="cStudentModal-grid">

        <div class="cStudentModal-box">
          <h4>Academic Information</h4>
          <p><b>Program:</b> BSIT</p>
          <p><b>Year Level:</b> 2nd Year</p>
        </div>


        <div class="cStudentModal-box">
          <h4>Emergency Contact</h4>
          <p><b>Name:</b> Maria L.</p>
          <p><b>Relation:</b> Mother</p>
          <p><b>Contact:</b> 0918-222-3333</p>
        </div>

      </div>

    </div>

  </div>

</div>

</div>

<script>

function openStudentModal() {
  document.getElementById("studentModal").classList.add("show");
}

function closeStudentModal() {
  document.getElementById("studentModal").classList.remove("show");
}

function searchStudents() {
  const input = document.getElementById("searchInput").value.toLowerCase();
  document.querySelectorAll(".cStudentList-item").forEach(item => {
    item.style.display = item.innerText.toLowerCase().includes(input)
      ? "flex"
      : "none";
  });
}

function toggleSettings() {
  const menu = document.getElementById("settingsMenu");
  menu.style.display = menu.style.display === "flex" ? "none" : "flex";
}

function toggleTheme() {
  document.documentElement.setAttribute(
    "data-theme",
    document.documentElement.getAttribute("data-theme") === "dark" ? "light" : "dark"
  );
}

function logout() {
  window.location.href = "role.html";
}

</script>

</body>
</html>
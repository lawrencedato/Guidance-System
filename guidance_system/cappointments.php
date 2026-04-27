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
        <a href="profile.html"><i class="fa fa-user"></i> Profile</a>
        <a href="chistory.html"><i class="fa fa-clock"></i> Session History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">
    <a href="counselor.php"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">SESSIONS</p>
    <a href="cappointments.html" class="active"><i class="fa fa-calendar-plus"></i> Appointment Requests</a>
    <a href="cconcerns.html"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>

    <p class="sidebar-title">STUDENTS</p>
    <a href="students.html"><i class="fa fa-users"></i> Students</a>

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
    <h3>Appointment Request</h3>
  </div>

  <div class="topbar-right">

    <div class="topbar-searchBox">
      <i class="fa fa-search"></i>
      <input type="text" placeholder="Search...">
    </div>

    <!-- FEEDBACK NOTIFICATIONS -->
<div class="topbar-icon" onclick="toggleDropdown('feedbackDropdown', event)">
  <i class="fa fa-envelope"></i>
  <span class="badge" id="feedbackCount">0</span>

  <div class="icon-dropdown" id="feedbackDropdown">
    <div class="notif-header">New Feedback</div>
    <div id="notifList">
      <div class="notif-empty">No new feedback</div>
    </div>
  </div>
</div>

<!-- BELL NOTIFICATIONS -->
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

    <!-- APPOINTMENT CARD -->
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

      <!-- ACTIONS -->
      <div class="cAppointment-actions">

        <button class="cAppointment-btn approve">
          <i class="fa fa-check"></i> Approve
        </button>

        <button class="cAppointment-btn decline">
          <i class="fa fa-times"></i> Decline
        </button>

        <!-- EXPORT (hidden by default) -->
        <button class="cAppointment-exportBtn" onclick="exportAppointment(this)" style="display:none;">
          <i class="fa fa-download"></i>
        </button>

      </div>

    </div>

  </section>

</main>

 <script>
  function toggleSettingsMenu(e){
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}

function toggleTheme(){
  const html = document.documentElement;
  html.setAttribute(
    "data-theme",
    html.getAttribute("data-theme") === "light" ? "dark" : "light"
  );
}

function logout(){
  localStorage.clear();
  window.location.href = "login.html";
}

document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");

  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});


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


/* =========================
   EXPORT FUNCTION
========================= */
function exportAppointment(btn) {
  const card = btn.closest(".cAppointment-card");

  const fileUrl = card.getAttribute("data-file");

  if (!fileUrl || fileUrl.trim() === "") {
    alert("No uploaded file found for this student.");
    return;
  }

  const link = document.createElement("a");
  link.href = fileUrl;
  link.download = fileUrl.split("/").pop();
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

/* =========================
   SHOW/HIDE EXPORT BUTTON
========================= */
function checkFiles() {
  document.querySelectorAll(".cAppointment-card").forEach(card => {
    const file = card.getAttribute("data-file");
    const btn = card.querySelector(".cAppointment-exportBtn");

    if (file && file.trim() !== "") {
      btn.style.display = "inline-flex";
    } else {
      btn.style.display = "none";
    }
  });
}

window.addEventListener("load", checkFiles);

</script>

</body>
</html>
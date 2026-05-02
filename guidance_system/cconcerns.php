<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>UNITYCARE | Student Concerns</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>

<body class="body">

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
    <a href="cappointments.php"><i class="fa fa-calendar-plus"></i> Appointment Requests</a>
    <a href="cconcerns.php" class="active"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>
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

<!-- ================= TOPBAR ================= -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Student Concerns</h2>
  </div>

  <div class="topbar-right">

    <!-- FILTER -->
    <div class="filter-wrapper">

      <button class="btn" onclick="toggleFilterBox()">
        <i class="fa fa-filter"></i> Filter
      </button>

      <div id="filterBox" class="filter-box">
        <input type="date" id="filterDate">

        <div class="filter-actions">
          <button onclick="applyConcernFilter()" class="btn-apply">Apply</button>
          <button onclick="clearConcernFilter()" class="btn-clear">Clear</button>
        </div>
      </div>

    </div>

    <!-- BELL -->
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
<main class="cConcerns-main">

  <div class="cConcerns-container">

    <!-- CONCERN CARD -->
    <div class="cConcerns-card">

      <h3><i class="fa fa-user"></i> Law Guemo</h3>

      <p><b>Subject:</b> Academic Stress</p>
      <p><b>Message:</b> I am feeling overwhelmed with school work and deadlines.</p>
      <p><b>Status:</b> Pending</p>

      <textarea class="cConcerns-replyBox" placeholder="Write your reply..."></textarea>

      <button class="cConcerns-btn" onclick="sendReply(this)">
        Send Reply
      </button>

      <div class="cConcerns-result"></div>

    </div>

    <!-- SECOND CARD -->
    <div class="cConcerns-card">

      <h3><i class="fa fa-user"></i> Trish Rondolo</h3>

      <p><b>Subject:</b> Anxiety</p>
      <p><b>Message:</b> I have been feeling anxious lately.</p>
      <p><b>Status:</b> Pending</p>

      <textarea class="cConcerns-replyBox" placeholder="Write your reply..."></textarea>

      <button class="cConcerns-btn" onclick="sendReply(this)">
        Send Reply
      </button>

      <div class="cConcerns-result"></div>

    </div>

  </div>

</main>

<script>

function toggleFilterBox() {
  document.getElementById("filterBox").classList.toggle("show");
}

function applyConcernFilter() {
  const date = document.getElementById("filterDate").value;
  const items = document.querySelectorAll(".cConcerns-card");

  items.forEach(item => {
    const matchDate = true; 
    item.style.display = matchDate ? "block" : "none";
  });
}

function clearConcernFilter() {
  document.getElementById("filterDate").value = "";
  document.querySelectorAll(".cConcerns-card").forEach(item => {
    item.style.display = "block";
  });
}

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
  window.location.href = "clogin.php";
}

document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");

  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});

function sendReply(btn){
  const card = btn.closest(".cConcerns-card");
  const textarea = card.querySelector(".cConcerns-replyBox");
  const result = card.querySelector(".cConcerns-result");

  if (!textarea.value.trim()) {
    alert("Please write a reply first.");
    return;
  }

  result.innerHTML = "✔ Reply sent successfully.";
  textarea.value = "";
}

</script>

</body>
</html>
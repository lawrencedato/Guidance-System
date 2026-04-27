<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Session History</title>

<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="sHistory.css">

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
        <a href="sprofile.php"><i class="fa fa-user"></i> Profile</a>
        <a href="shistory.php" class="active"><i class="fa fa-clock"></i> Session History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">
    <a href="dashboard.php"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">SERVICES</p>
    <a href="sappointment.php"><i class="fa fa-calendar"></i> Book Appointment</a>
    <a href="sconcerns.php"><i class="fa fa-headset"></i> Submit Concern</a>
    <a href="swellness.php"><i class="fa fa-heart"></i> Wellness Check</a>
    <a href="sreferral.php"><i class="fa fa-route"></i> Referral</a>

    <p class="sidebar-title">UPDATES</p>
    <a href="sannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>

    <p class="sidebar-title">RECORDS</p>
    <a href="sreports.php"><i class="fa fa-ticket"></i> Tickets</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="sfeedback.php"><i class="fa fa-comment"></i> Feedback</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">

  <div class="topbar-left">
    <h1>Student Profile</h1>
  </div>

  <div class="topbar-right">

    <div class="topbar-user">
      <img src="student.jpg" alt="user">
      <div>
        <strong>Vincent Aldolf Sablay</strong>
        <p>vincentsablay@gmail.com</p>
      </div>
    </div>

  </div>

</header>

<!-- MAIN -->
<main class="sHistory-main">

  <div class="sHistory-container">

    <!-- CARD 1 -->
    <div class="sHistory-card">
      <h3>Guidance Counseling</h3>
      <span class="tag info">Completed</span>

      <p><b>Date:</b> January 10, 2026</p>
      <p><b>Counselor:</b> Dr. Maria Santos</p>
    </div>

    <!-- CARD 2 -->
    <div class="sHistory-card">
      <h3>Wellness Check</h3>
      <span class="tag warning">Completed</span>

      <p><b>Date:</b> February 02, 2026</p>
      <p><b>Status:</b> Stable</p>
    </div>

    <!-- CARD 3 -->
    <div class="sHistory-card">
      <h3>Follow-up Session</h3>
      <span class="tag info">Completed</span>

      <p><b>Date:</b> March 15, 2026</p>
      <p><b>Notes:</b> Improvement observed</p>
    </div>

  </div>

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
/* dropdown close fix */
document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");

  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});
</script>

</body>
</html>
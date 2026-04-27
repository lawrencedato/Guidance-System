<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Submit Concern</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
        <a href="profile.html"><i class="fa fa-user"></i> Profile</a>
        <a href="history.html"><i class="fa fa-clock"></i> Session History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">

    <a href="dashboard.html"><i class="fa fa-th-large"></i> Dashboard</a>

    <p class="sidebar-title">SERVICES</p>
    <a href="booking.html"><i class="fa fa-calendar"></i> Book Appointment</a>
    <a href="sconcerns.html" class="active"><i class="fa fa-headset"></i> Submit Concern</a>
    <a href="wellness.html"><i class="fa fa-heart"></i> Wellness Check</a>
    <a href="referral.html"><i class="fa fa-route"></i> Referral</a>

    <p class="sidebar-title">UPDATES</p>
    <a href="announcements.html"><i class="fa fa-bullhorn"></i> Announcements</a>

    <p class="sidebar-title">RECORDS</p>
    <a href="sreports.php"><i class="fa fa-ticket"></i> Reports</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="feedback.html"><i class="fa fa-comment"></i> Session Feedback</a>

  </nav>
</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">

  <div class="topbar-left">
    <h1>Submit Concern</h1>
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


<!-- ================= CONTENT ================= -->
<main class="sConcern-main">

  <div class="sConcern-container">

    <!-- EMERGENCY -->
    <section class="card-emergency">
      <h3><i class="fa fa-triangle-exclamation"></i> Emergency Support</h3>

      <p class="sConcern-text-muted">
        If you are in urgent distress, contact the hotline instead of submitting a form.
      </p>

      <div class="sConcern-emergency-details">
        <p><strong>📞 Hotline:</strong> 0912-345-6789</p>
        <p><strong>🕒 Hours:</strong> Mon–Fri (8:00 AM – 5:00 PM)</p>
        <p><strong>⚡ Response:</strong> Immediate during office hours</p>
      </div>
    </section>

    <!-- FORM -->
    <section class="sConcern-card sConcern-card-form">

      <h3><i class="fa fa-headset"></i> Contact Counselor</h3>

      <p class="sConcern-text-muted">
        Submit your concern and a counselor will respond as soon as possible.
      </p>

      <div class="sConcern-formGroup">

        <label>Subject</label>
        <input type="text" id="subject" placeholder="e.g. Academic Stress">

        <label>Message</label>
        <textarea id="message" rows="6" placeholder="Describe your concern..."></textarea>

        <button class="sConcern-button" onclick="submitConcern()">
          Submit Concern
        </button>

        <div id="result" class="sConcern-result"></div>

      </div>

    </section>

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

document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");

  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});

function submitConcern() {
  const subject = document.getElementById("subject").value;
  const message = document.getElementById("message").value;

  if (!subject || !message) {
    alert("Please complete all fields.");
    return;
  }

  document.getElementById("result").innerHTML =
    "✔ Concern submitted successfully.";
}
</script>

</body>
</html>
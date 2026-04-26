<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Referral</title>

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
    <a href="sconcerns.html"><i class="fa fa-headset"></i> Submit Concern</a>
    <a href="wellness.html"><i class="fa fa-heart"></i> Wellness Check</a>
    <a href="referral.html" class="active">
      <i class="fa fa-route"></i> Referral
      <span class="referral-badge" id="referralBadge">1</span>
    </a>

    <p class="sidebar-title">UPDATES</p>
    <a href="announcements.html"><i class="fa fa-bullhorn"></i> Announcements</a>

    <p class="sidebar-title">RECORDS</p>
    <a href="sreports.html"><i class="fa fa-ticket"></i> Reports</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="feedback.html"><i class="fa fa-comment"></i> Session Feedback</a>
  </nav>

</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">
  <div class="topbar-left">
    <h1>Referral Slip</h1>
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

<!-- ================= MAIN ================= -->
<main class="sReferral-main">

  <!-- REFERRAL CARD -->
  <section class="sReferral-card" id="sReferral-slip">

    <h2 class="sReferral-title">REFERRAL SLIP</h2>

    <p class="sReferral-date">
      <b>Date:</b> <span id="sReferral-date"></span>
    </p>

    <hr>

    <!-- STUDENT INFO -->
    <h3>Student Information</h3>
    <p><b>Name:</b> <span id="sReferral-name"></span></p>
    <p><b>Year Level:</b> <span id="sReferral-year"></span></p>
    <p><b>Program:</b> <span id="sReferral-course"></span></p>
    <p><b>Contact:</b> <span id="sReferral-contact"></span></p>

    <hr>

    <!-- DETAILS -->
    <h3>Referral Details</h3>
    <p><b>Reason:</b></p>
    <p id="sReferral-reason"></p>

    <p><b>Concern:</b></p>
    <p id="sReferral-concern"></p>

    <hr>

    <!-- SIGNATURE -->
    <h3>Referred By</h3>
    <img src="images/signature.png" class="sReferral-signature">

    <p><b>Counselor:</b> Dr. Lawrence Dato</p>
    <p><b>Office:</b> Guidance Office</p>

    <!-- ACTION -->
    <button class="sReferral-btn" onclick="exportPDF()">
      Export as PDF
    </button>

  </section>

</main>

<!-- ================= SCRIPT ================= -->
<script>
function loadReferral() {
  let data = JSON.parse(localStorage.getItem("referrals")) || [];
  if (!data.length) return;

  let r = data[0];

  document.getElementById("sReferral-name").textContent = r.studentName;
  document.getElementById("sReferral-year").textContent = r.yearLevel;
  document.getElementById("sReferral-course").textContent = r.course;
  document.getElementById("sReferral-contact").textContent = r.contact;
  document.getElementById("sReferral-reason").textContent = r.reason;
  document.getElementById("sReferral-concern").textContent = r.concern;
  document.getElementById("sReferral-date").textContent = r.date;
}

function exportPDF() {
  const element = document.getElementById("sReferral-slip");

  html2pdf().set({
    margin: 10,
    filename: "Referral_Slip.pdf",
    image: { type: "jpeg", quality: 1 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: "mm", format: "a4", orientation: "portrait" }
  }).from(element).save();
}

function updateReferralBadge() {
  let count = parseInt(localStorage.getItem("referralCount")) || 0;
  const badge = document.getElementById("referralBadge");

  if (badge) {
    badge.style.display = count > 0 ? "flex" : "none";
    badge.textContent = count;
  }
}

// ================= UI HELPERS =================
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

loadReferral();
updateReferralBadge();
</script>

</body>
</html>
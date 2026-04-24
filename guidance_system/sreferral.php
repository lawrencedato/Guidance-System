<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE - Referral</title>

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
    <a href="referral.html" class="active"><i class="fa fa-route"></i> Referral</a>

    <p class="sidebar-title">UPDATES</p>
    <a href="announcements.html"><i class="fa fa-bullhorn"></i> Announcements</a>

    <p class="sidebar-title">RECORDS</p>
    <a href="ticket.html"><i class="fa fa-ticket"></i> Tickets</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="feedback.html"><i class="fa fa-comment"></i> Feedback</a>

  </nav>

</aside>

<!-- ================= TOPBAR ================= -->
 <header class="topbar">

  <div class="topbar-left">
    <h1>Referral</h1>
  </div>

  <div class="topbar-right">

    <div class="topbar-searchBox">
      <i class="fa fa-search"></i>
      <input type="text" placeholder="Search...">
    </div>

    <div class="topbar-icons">
      <div class="topbar-icon">
        <i class="fa fa-envelope"></i>
        <span class="badge">3</span>
      </div>

      <div class="topbar-icon">
        <i class="fa fa-bell"></i>
        <span class="badge">5</span>
      </div>
    </div>

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

  <!-- ================= CONTENT ================= -->
  <section class="sReferral-container">
    <p class="sReferral-subtext">
This referral was made by your assigned counselor to a recommended professional. You may choose to proceed with or decline the referral.
</p>
    
     <div class="sReferral-actions">
    <button class="sReferralexp-button" onclick="exportReferralPDF()">
     Export
    </button>
  </div>

    <div class="sReferral-card">

      <h3>Assigned Counselor</h3>

      <div class="sReferral-counselor">

        <div class="sReferral-avatar">DR</div>

        <div>
          <h4>Dr. Maria Santos</h4>
          <p>Licensed Counselor</p>
        </div>

      </div>

      <div class="sReferral-info">
        <p><b>Status:</b> Active</p>
        <p><b>Availability:</b> Mon–Fri 9AM–5PM</p>
      </div>

      <hr>

      <h3>Schedule Appointment</h3>

      <label>Date</label>
      <input type="date" id="date">

      <label>Time</label>
      <select id="time">
        <option value="">Select Time</option>
        <option>9:00 AM</option>
        <option>10:00 AM</option>
        <option>11:00 AM</option>
        <option>1:00 PM</option>
        <option>2:00 PM</option>
        <option>3:00 PM</option>
      </select>

      <button class="sReferral-button" onclick="submitReferral()">
        Request Appointment
      </button>

      <p id="status"></p>

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

function exportReferralPDF() {
  const element = document.querySelector(".sReferral-card");

  const opt = {
    margin: 10,
    filename: 'Referral.pdf',
    image: { type: 'jpeg', quality: 1 },
    html2canvas: { scale: 2 },
    jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
  };

  html2pdf().set(opt).from(element).save();
}

function submitReferral(){
  const d=document.getElementById("date").value;
  const t=document.getElementById("time").value;
  const status=document.getElementById("status");

  if(!d||!t){
    status.style.color="red";
    status.textContent="Select date and time.";
    return;
  }

  status.style.color="green";
  status.textContent="Referral request submitted.";
}
</script>

</body>
</html>
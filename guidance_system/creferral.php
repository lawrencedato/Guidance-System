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
        <a href="cprofile.html"><i class="fa fa-user"></i> Profile</a>
        <a href="chistory.html"><i class="fa fa-clock"></i> Session History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">
    <a href="counselor.php"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">SESSIONS</p>
    <a href="cappointments.html"><i class="fa fa-calendar-plus"></i> Appointment Requests</a>
    <a href="cconcerns.html"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>

    <p class="sidebar-title">STUDENTS</p>
    <a href="students.html"><i class="fa fa-users"></i> Students</a>

    <p class="sidebar-title">REPORTS</p>
    <a href="creports.php"><i class="fa fa-file"></i> Reports</a>

    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.php" class="active"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">
  <div class="topbar-left">
    <h3>Welcome!</h3>
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
<main class="cReferral-main">

  <div class="cReferral-card slip">

    <h2>REFERRAL SLIP</h2>

    <!-- DATE -->
    <div class="slip-row">
      <label>Date:</label>
      <input type="date" class="slip-input">
    </div>

    <h3>Patient Information</h3>

    <div class="slip-row">
      <label>Name:</label>
      <input type="text" class="slip-input">
    </div>

    <div class="slip-row">
      <label>Year Level:</label>
      <input type="text" class="slip-input">
    </div>

    <div class="slip-row">
      <label>Program / Course:</label>
      <input type="text" class="slip-input">
    </div>

    <h3>Reason for Referral</h3>
    <textarea class="slip-textarea"></textarea>

    <h3>Counselor’s Remarks (Optional)</h3>
    <textarea class="slip-textarea"></textarea>

    <h3>Referred by</h3>

<img src="images/signature.png" class="signature-img" alt="Counselor Signature">

<p>Dr. Lawrence Dato</p>

<p><b>Contact:</b> 0912345678910 | @lawrencedato@gmail.com</p>

    <button class="cReferral-btn" onclick="window.print()">
      Create Referral
    </button>

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


/* SUBMIT REFERRAL */
function submitReferralForm() {

  const studentName = document.getElementById("studentName").value;
  const studentId = document.getElementById("studentId").value;
  const course = document.getElementById("course").value;
  const yearLevel = document.getElementById("yearLevel").value;
  const contact = document.getElementById("contact").value;

  const reason = document.getElementById("reason").value;
  const concern = document.getElementById("concern").value;
  const professional = document.getElementById("professional").value;

  const scheduleDate = document.getElementById("scheduleDate").value;
  const scheduleTime = document.getElementById("scheduleTime").value;

  const msg = document.getElementById("msg");

  if (!studentName || !studentId || !course || !yearLevel || !contact || !reason || !concern || !scheduleDate || !scheduleTime) {
    msg.style.color = "red";
    msg.textContent = "Please complete all fields.";
    return;
  }

  let data = JSON.parse(localStorage.getItem("referrals")) || [];

  data.unshift({
    studentName,
    studentId,
    course,
    yearLevel,
    contact,
    reason,
    concern,
    professional: professional || "General Referral",
    schedule: `${scheduleDate} - ${scheduleTime}`,
    date: new Date().toLocaleString(),
    status: "Active"
  });

  localStorage.setItem("referrals", JSON.stringify(data));

  let referralCount = parseInt(localStorage.getItem("referralCount")) || 0;
  referralCount++;
  localStorage.setItem("referralCount", referralCount);

  msg.style.color = "green";
  msg.textContent = "Referral submitted successfully!";
}

</script>

</body>
</html>
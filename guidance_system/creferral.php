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
    <a href="cconcerns.php"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>
    <a href="cfeedback.php"><i class="fa fa-comment"></i> Session Feedback</a>

    <p class="sidebar-title">STUDENTS</p>
    <a href="cstudents.php"><i class="fa fa-users"></i> Students</a>

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
    <h2>Referral</h2>
    <p class="topbar-muted">
      Counselors refer students to a more appropriate professional for further assistance.
    </p>
  </div>

  <div class="topbar-right">

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

<!-- ================= MAIN ================= -->
<main class="cReferral-main">

  <div class="cReferral-card slip">

    <h2>REFERRAL SLIP</h2>

    <div class="slip-row">
      <label>Date:</label>
      <input type="date" class="slip-input" id="refDate">
    </div>

    <h3>Patient Information</h3>

    <div class="slip-row">
      <label>Name:</label>
      <input type="text" class="slip-input" id="refName">
    </div>

    <div class="slip-row">
      <label>Year Level:</label>
      <select class="slip-input" id="refYear">
        <option value="" disabled selected>Select Year Level</option>
        <option value="1st Year">1st Year</option>
        <option value="2nd Year">2nd Year</option>
        <option value="3rd Year">3rd Year</option>
        <option value="4th Year">4th Year</option>
      </select>
    </div>

    <div class="slip-row">
      <label>Program / Course:</label>
      <select class="slip-input" id="refCourse">
        <option value="" disabled selected>Select Program</option>
        <option value="BSIT">BS Information Technology</option>
        <option value="BSCS">BS Computer Science</option>
        <option value="BSA">BS Accountancy</option>
        <option value="BSED">BS Education</option>
        <option value="BSBA">BS Business Administration</option>
        <option value="BSHM">BS Hospitality Management</option>
        <option value="BSTM">BS Tourism Management</option>
      </select>
    </div>

    <h3>Reason for Referral</h3>
    <textarea class="slip-textarea" id="refReason"></textarea>

    <h3>Counselor’s Remarks (Optional)</h3>
    <textarea class="slip-textarea" id="refRemarks"></textarea>

    <h3>Referred by</h3>

    <img src="images/signature.png" class="signature-img" alt="Counselor Signature">

    <p>Dr. Lawrence Dato</p>
    <p><b>Contact:</b> 0912345678910 | lawrencedato@gmail.com</p>

    <button class="cReferral-btn" onclick="createReferral()">
      Create Referral
    </button>

  </div>

</main>

<!-- ================= SCRIPT ================= -->
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
  html.setAttribute(
    "data-theme",
    html.getAttribute("data-theme") === "light" ? "dark" : "light"
  );
}

function logout(){
  localStorage.clear();
  window.location.href = "login.html";
}

function createReferral() {

  const referral = {
    date: document.getElementById("refDate").value,
    studentName: document.getElementById("refName").value,
    yearLevel: document.getElementById("refYear").value,
    course: document.getElementById("refCourse").value,
    reason: document.getElementById("refReason").value,
    concern: document.getElementById("refRemarks").value || "",
    createdAt: new Date().toLocaleString(),
    status: "Active"
  };

  if (
    !referral.date ||
    !referral.studentName ||
    !referral.yearLevel ||
    !referral.course ||
    !referral.reason
  ) {
    alert("Please complete all required fields.");
    return;
  }

  let data = JSON.parse(localStorage.getItem("referrals")) || [];
  data.unshift(referral);
  localStorage.setItem("referrals", JSON.stringify(data));

  let count = parseInt(localStorage.getItem("referralCount")) || 0;
  localStorage.setItem("referralCount", count + 1);
}

</script>

</body>
</html>
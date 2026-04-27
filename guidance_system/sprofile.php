<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Student dProfile</title>

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
        <a href="sprofile.html" class="active"><i class="fa fa-user"></i> Profile</a>
        <a href="shistory.html"><i class="fa fa-clock"></i> Session History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">
    <a href="dashboard.html"><i class="fa fa-th-large"></i> Dashboard</a>

    <p class="sidebar-title">SERVICES</p>
    <a href="sappointment.html"><i class="fa fa-calendar"></i> Book Appointment</a>
    <a href="sconcerns.html"><i class="fa fa-headset"></i> Submit Concern</a>
    <a href="swellness.html"><i class="fa fa-heart"></i> Wellness Check</a>
    <a href="sreferral.html"><i class="fa fa-route"></i> Referral</a>

    <p class="sidebar-title">UPDATES</p>
    <a href="sannouncements.html"><i class="fa fa-bullhorn"></i> Announcements</a>

    <p class="sidebar-title">RECORDS</p>
    <a href="sreports.html"><i class="fa fa-ticket"></i> Reports</a>

    <p class="sidebar-title">SYSTEM</p>
    <a href="sfeedback.html"><i class="fa fa-comment"></i> Session Feedback</a>
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

<!-- PROFILE -->
<main class="sProfile-main">

  <div class="sTicket-container">
    <div class="card sProfile-card">

      <div class="sProfile-header">

        <div class="sProfile-avatar">
          <img id="preview" src="https://via.placeholder.com/120">

          <!-- ONLY PROFILE PICTURE CAN BE CHANGED -->
          <label for="fileUpload" class="sProfile-upload">
            <i class="fa fa-camera"></i>
          </label>

          <input
            type="file"
            id="fileUpload"
            hidden
            onchange="loadImage(event)"
          >
        </div>

        <div>
          <h3 id="displayName">Vincent Aldolf Sablay</h3>
          <p class="sProfile-muted">
            You can only update your phone number and profile picture
          </p>
        </div>

      </div>

      <div class="sProfile-form">

        <!-- UNCHANGEABLE -->
        <div class="form-group">
          <label>Full Name</label>
          <input
            id="name"
            type="text"
            value="Vincent Aldolf Sablay"
            readonly
          >
        </div>

        <!-- UNCHANGEABLE -->
        <div class="form-group">
          <label>Email</label>
          <input
            id="email"
            type="email"
            value="vincentsablay@gmail.com"
            readonly
          >
        </div>

        <!-- UNCHANGEABLE -->
        <div class="form-group">
          <label>Program</label>
          <input
            id="program"
            type="text"
            value="BS Information Technology"
            readonly
          >
        </div>

        <!-- EDITABLE -->
        <div class="form-group">
          <label>Phone Number</label>
          <input
            id="phone"
            type="text"
            placeholder="Change phone number"
          >
        </div>

        <button
          class="btn sProfile-saveBtn"
          onclick="saveProfile()"
        >
          Save Changes
        </button>

        <div id="status" class="sProfile-status"></div>

      </div>

    </div>
  </div>

</main>

<script>
function toggleSettingsMenu(e) {
  e.stopPropagation();
  document
    .getElementById("settingsDropdown")
    .classList.toggle("show");
}

function toggleTheme() {
  const html = document.documentElement;

  html.setAttribute(
    "data-theme",
    html.getAttribute("data-theme") === "light"
      ? "dark"
      : "light"
  );
}

function logout() {
  localStorage.clear();
  window.location.href = "login.html";
}

/* dropdown close fix */
document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");

  if (
    !menu.contains(e.target) &&
    !btn.contains(e.target)
  ) {
    menu.classList.remove("show");
  }
});

/* profile picture preview */
function loadImage(event) {
  document.getElementById("preview").src =
    URL.createObjectURL(event.target.files[0]);
}

/* only phone number is saved */
function saveProfile() {
  const phone = document.getElementById("phone").value;

  if (phone.trim() === "") {
    document.getElementById("status").innerHTML =
      "<span class='tag warning'>Please enter your phone number</span>";
    return;
  }

  document.getElementById("status").innerHTML =
    "<span class='tag info'>Phone number updated successfully</span>";
}
</script>

</body>
</html>
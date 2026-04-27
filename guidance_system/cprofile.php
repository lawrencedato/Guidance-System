<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Counselor Profile</title>

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
      <button class="sidebar-settingsButton" onclick="toggleSettingsMenu()">
        <i class="fa fa-gear"></i>
      </button>

      <div class="sidebar-settingsDropdown" id="settingsMenu">
        <a href="cprofile.html" class="active"><i class="fa fa-user"></i> Profile</a>
        <a href="chistory.html"><i class="fa fa-clock"></i> History</a>
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
    <a href="cfeedback.html"><i class="fa fa-comment"></i> Session Feedback</a>

    <p class="sidebar-title">STUDENTS</p>
    <a href="cstudents.html"><i class="fa fa-users"></i> Student List</a>

    <p class="sidebar-title">REPORTS</p>
    <a href="creports.html"><i class="fa fa-file"></i> Reports</a>

    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.html"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.html"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left">
    <h3>History</h3>
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

<!-- PROFILE -->
<main class="cProfile-main">

  <div class="cProfile-container">
    <div class="card cProfile-card">

      <div class="cProfile-header">

        <div class="cProfile-avatar">
          <img id="preview" src="https://via.placeholder.com/120">

          <label for="fileUpload" class="cProfile-upload">
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
          <h3 id="displayName">Dr. Maria Santos</h3>
          <p class="cProfile-muted">
            Counselor account
          </p>
        </div>

      </div>

      <div class="cProfile-form">

        <!-- UNCHANGEABLE -->
        <div class="form-group">
          <label>Full Name</label>
          <input type="text" value="Dr. Maria Santos" readonly>
        </div>

        <!-- UNCHANGEABLE -->
        <div class="form-group">
          <label>Email</label>
          <input type="email" value="maria.santos@unitycare.edu" readonly>
        </div>

        <!-- UNCHANGEABLE -->
        <div class="form-group">
          <label>Department</label>
          <input type="text" value="Guidance & Counseling Office" readonly>
        </div>

        <!-- EDITABLE -->
        <div class="form-group">
          <label>Contact Number</label>
          <input id="phone" type="text" placeholder="Change contact number">
        </div>

        <button class="btn cProfile-saveBtn" onclick="saveProfile()">
          Save Changes
        </button>

        <div id="status" class="cProfile-status"></div>

      </div>

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

document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");

  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});


/* image preview */
function loadImage(event) {
  document.getElementById("preview").src =
    URL.createObjectURL(event.target.files[0]);
}

/* save only phone */
function saveProfile() {
  const phone = document.getElementById("phone").value;

  if (phone.trim() === "") {
    document.getElementById("status").innerHTML =
      "<span class='tag warning'>Please enter contact number</span>";
    return;
  }

  document.getElementById("status").innerHTML =
    "<span class='tag info'>Profile updated successfully</span>";
}
</script>

</body>
</html>
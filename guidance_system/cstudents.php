<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>UNITYCARE | Students List</title>

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
      <button class="sidebar-settingsButton" onclick="toggleSettings()">
        <i class="fa fa-gear"></i>
      </button>

      <div class="sidebar-settingsDropdown" id="settingsMenu">
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
    <a href="cstudents.php" class="active"><i class="fa fa-users"></i> Students</a>

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
    <h2>Student List</h2>
  </div>

  <div class="topbar-right">

    <div class="topbar-searchBox">
      <i class="fa fa-search"></i>
      <input type="text" id="searchInput" oninput="searchStudents()" placeholder="Search...">
    </div>

    <div class="filter-wrapper">

      <button class="btn" onclick="toggleFilterBox()">
        <i class="fa fa-filter"></i> Filter
      </button>

      <div id="filterBox">

        <select id="filterProgram">
          <option value="all">Programs</option>
          <option>BSIT</option>
          <option>BSBA</option>
          <option>BSA</option>
          <option>BSCS</option>
          <option>BSN</option>
          <option>BSECE</option>
        </select>

        <select id="filterYear">
          <option value="all">Year Levels</option>
          <option>1st Year</option>
          <option>2nd Year</option>
          <option>3rd Year</option>
          <option>4th Year</option>
        </select>

        <select id="filterStatus">
          <option value="all">Status</option>
          <option>Stable</option>
          <option>At Risk</option>
          <option>Critical</option>
        </select>

        <input type="date" id="filterDate">

        <div class="filter-actions">
          <button onclick="applyFilters()" class="btn-apply">Apply</button>
          <button onclick="clearFilters()" class="btn-clear">Clear</button>
        </div>

      </div>

    </div>

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

<!-- LIST -->
<main class="cStudentList-main">

  <div class="cStudentList-container">

    <div class="cStudentList-item">
      <div class="cStudentList-info">

        <div class="cStudentList-avatar">JS</div>

        <div class="cStudentList-content">

          <div class="cStudentList-left">
            <div class="cStudentList-nameRow">
              <h3>Vincent Adolf Sablay</h3>
              <button class="btn-small" onclick="openStudentModal()">
                View Profile
              </button>
            </div>
            <p>BSIT • 2nd Year</p>
          </div>

          <div class="cStudentList-right">

            <div class="cStudentList-topRight">
              <span class="tag stable">Stable</span>
            </div>

            <div class="cStudentList-bottomRight">
              <p data-date="2026-04-10">Last Session: April 10, 2026</p>
            </div>

          </div>

        </div>

      </div>
    </div>

  </div>
</main>

<!-- MODAL -->
<div class="cStudentModal" id="studentModal">
  <div class="cStudentModal-container">

    <div class="cStudentModal-header">
      <h2>Student Profile</h2>
      <button onclick="closeStudentModal()">✕</button>
    </div>

    <div class="cStudentModal-body">

      <div class="cStudentModal-profile">

        <div class="cStudentModal-avatar">JS</div>

        <div class="cStudentModal-profileText">

          <div class="cStudentModal-nameRow">
            <h3>Vincent Adolf Sablay</h3>
            <span id="studentStatusTag" class="tag stable">Stable</span>
          </div>

          <p>BSIT • 2nd Year</p>

        </div>
      </div>

      <div class="cStudentModal-box">
        <h4>Wellness Progress: Good</h4>
        <p><b>Overall Score:</b> 82%</p>

        <div class="cStudentModal-progressBar">
          <div class="cStudentModal-progressFill"></div>
        </div>

        <p><b>Recent Check-in:</b> April 22</p>
      </div>

      <div class="cStudentModal-grid">

        <div class="cStudentModal-box">
          <h4>Academic Information</h4>
          <p><b>Program:</b> BSIT</p>
          <p><b>Year Level:</b> 2nd Year</p>
        </div>

        <div class="cStudentModal-box">
          <h4>Emergency Contact</h4>
          <p><b>Name:</b> Maria L.</p>
          <p><b>Relation:</b> Mother</p>
          <p><b>Contact:</b> 0918-222-3333</p>
        </div>

      </div>

    </div>

  </div>
</div>

<script>
function applyFilters() {
  const program = document.getElementById("filterProgram").value.toLowerCase();
  const year = document.getElementById("filterYear").value.toLowerCase();
  const status = document.getElementById("filterStatus").value.toLowerCase();
  const filterDate = document.getElementById("filterDate").value;

  document.querySelectorAll(".cStudentList-item").forEach(item => {
    const text = item.innerText.toLowerCase();

    const matchProgram = program === "all" || text.includes(program);
    const matchYear = year === "all" || text.includes(year);

    const itemStatus = item.querySelector(".tag")?.innerText.toLowerCase();
    const matchStatus = status === "all" || itemStatus === status;

    let matchDate = true;
    const dateValue = item.querySelector("[data-date]")?.dataset.date;

    if (filterDate && dateValue) {
      matchDate =
        new Date(dateValue).toDateString() ===
        new Date(filterDate).toDateString();
    }

    item.style.display = (matchProgram && matchYear && matchStatus && matchDate)
      ? "flex"
      : "none";
  });
}

function toggleFilterBox() {
  document.getElementById("filterBox").classList.toggle("show");
}

function clearFilters() {
  document.getElementById("filterProgram").value = "all";
  document.getElementById("filterYear").value = "all";
  document.getElementById("filterStatus").value = "all";
  document.getElementById("filterDate").value = "";

  document.querySelectorAll(".cStudentList-item").forEach(item => {
    item.style.display = "flex";
  });
}

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

function toggleSettings() {
  document.getElementById("settingsMenu").classList.toggle("show");
}

function toggleTheme() {
  document.documentElement.setAttribute(
    "data-theme",
    document.documentElement.getAttribute("data-theme") === "dark"
      ? "light"
      : "dark"
  );
}

function logout() {
  window.location.href = "clogin.php";
}
</script>

</body>
</html>
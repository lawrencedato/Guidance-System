<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Administrator Dashboard</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        <button onclick="toggleTheme()">
          <i class="fa fa-moon"></i> Theme
        </button>

        <button onclick="logout()">
          <i class="fa fa-right-from-bracket"></i> Logout
        </button>
      </div>
    </div>

  </div>

  <nav class="sidebar-menu">

    <a href="admin.php" class="active"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">MANAGEMENT</p>

    <a href="ausers.php"><i class="fa fa-users"></i> Users</a>
    <a href="astudents.php"><i class="fa fa-user-graduate"></i> Students</a>
    <a href="acounselors.php"><i class="fa fa-user-doctor"></i> Counselors</a>
    <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>

    <p class="sidebar-title">SYSTEM</p>

    <a href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>

  </nav>

</aside>


<!-- ================= TOPBAR ================= -->
<header class="topbar">

  <div class="topbar-left">
      <h2>Administrator Dashboard</h2>
    <p class="topbar-muted">
      System overview & performance monitoring
    </p>
  </div>

  <div class="aDashboard-live-status">
    <span class="aDashboard-pulse"></span>
    System Active
  </div>

</header>


<!-- ================= MAIN ================= -->
<main class="aDashboard-main">

  <!-- KPI CARDS -->
  <section class="aDashboard-stats">

    <div class="aDashboard-card">
      <h3><i class="fa fa-user-graduate"></i> Students</h3>
      <h2 id="studentsCount">245</h2>
      <p class="aDashboard-muted">Total students</p>
    </div>

    <div class="aDashboard-card">
      <h3><i class="fa fa-user-doctor"></i> Counselors</h3>
      <h2 id="counselorsCount">12</h2>
      <p class="aDashboard-muted">Active guidance counselors</p>
    </div>

    <div class="aDashboard-card">
      <h3><i class="fa fa-user-check"></i> Accounts</h3>
      <h2 id="accountsCount">180</h2>
      <p class="aDashboard-muted">Activated system users</p>
    </div>

    <div class="aDashboard-card">
      <h3><i class="fa fa-calendar"></i> Appointments</h3>
      <h2 id="appointmentsCount">128</h2>
      <p class="aDashboard-muted">Total bookings</p>
    </div>

  </section>


  <!-- QUICK ACTIONS -->
  <section class="aDashboard-card aDashboard-actions">

    <h3>Quick Actions</h3>
    <p class="aDashboard-muted">
      Fast access to common admin tasks
    </p>

    <div class="aDashboard-actions-wrapper">
      <div class="aDashboard-actions-group">

        <button class="aDashboard-btn" onclick="openAddStudentModal()">
          <i class="fa fa-user-graduate"></i>
          Add Student
        </button>
        <div id="studentModal" class="aStudents-modal">
  <div class="aStudents-modal-content">

    <div class="aStudents-modal-header">
      <div>
        <h3>Add New Student</h3>
        <p>Fill in all the student's information below</p>
      </div>
      <button class="aStudents-modal-close" onclick="closeStudentModal()">✕</button>
    </div>

    <div class="aStudents-modal-body">

      <div class="aStudents-sec-label">PERSONAL INFORMATION</div>

      <div class="aStudents-field-grid">

        <div class="aStudents-field">
          <label>First Name</label>
          <input type="text" id="firstName">
        </div>

        <div class="aStudents-field">
          <label>Last Name</label>
          <input type="text" id="lastName">
        </div>

        <div class="aStudents-field">
          <label>Gender</label>
          <select id="gender">
            <option value="" disabled selected>Select Gender</option>
            <option>Male</option>
            <option>Female</option>
            <option>Prefer not to say</option>
          </select>
        </div>

        <div class="aStudents-field">
          <label>Birthday</label>
          <input type="date" id="birthday">
        </div>

        <div class="aStudents-field">
          <label>Age</label>
          <input type="number" id="studentAge" readonly>
        </div>

      </div>

      <div class="aStudents-sec-label">ACADEMIC INFORMATION</div>

      <div class="aStudents-field-grid">

        <div class="aStudents-field">
          <label>Student ID</label>
          <input type="text" id="studentId">
        </div>

        <div class="aStudents-field">
          <label>Year Level</label>
          <select id="yearLevel">
            <option value="" disabled selected>Select Year</option>
            <option>1st Year</option>
            <option>2nd Year</option>
            <option>3rd Year</option>
            <option>4th Year</option>
          </select>
        </div>

        <div class="aStudents-field full">
          <label>Course</label>
          <select id="course">
            <option value="" disabled selected>Select Course</option>
            <option>AB Psychology</option>
            <option>BSBA</option>
            <option>BSA</option>
            <option>BS Entrep</option>
            <option>BEEd</option>
            <option>BSEd</option>
            <option>BSHM</option>
            <option>BSIT</option>
            <option>BSCS</option>
            <option>BSN</option>
            <option>BSECE</option>
          </select>
        </div>

        <div class="aStudents-field full">
          <label>Email Address</label>
          <input type="email" id="email">
        </div>

      </div>

    </div>

    <div class="aStudents-modal-footer">
      <button class="aStudents-btn-cancel" onclick="closeStudentModal()">Cancel</button>
      <button class="aStudents-btn-save" onclick="saveStudent()">Save Student</button>
    </div>

  </div>
</div>

        <button class="aDashboard-btn aDashboard-btn-secondary" onclick="openAddCounselorModal()">
          <i class="fa fa-user-doctor"></i>
          Add Counselor
        </button>
<div id="counselorModal" class="aCounselors-modal">
  <div class="aCounselors-modal-content">

    <div class="aCounselors-modal-header">
      <div>
        <h3>Create Counselor Account</h3>
        <p>
          Provide the counselor's details and set their initial password.
        </p>
      </div>

      <button
        class="aCounselors-modal-close"
        onclick="closeCounselorModal()">
        &#x2715;
      </button>
    </div>

    <div class="aCounselors-modal-body">

      <div class="aCounselors-sec-label">
        COUNSELOR INFORMATION
      </div>

      <div class="aCounselors-field-grid">

        <div class="aCounselors-field full">
          <label>Full Name</label>
          <input
            id="counselorName"
            type="text"
            placeholder="e.g. Maria Reyes">
        </div>

        <div class="aCounselors-field full">
          <label>Email Address</label>
          <input
            id="counselorEmail"
            type="email"
            placeholder="counselor@unitycare.org">
        </div>

        <div class="aCounselors-field">
          <label>Department</label>
          <select id="counselorDepartment">
            <option value="">Select Department</option>
            <option>Wellness</option>
            <option>Academic Support</option>
            <option>Career Guidance</option>
            <option>Student Affairs</option>
          </select>
        </div>

        <div class="aCounselors-field">
          <label>Status</label>
          <select id="counselorStatus">
            <option>Active</option>
            <option>Inactive</option>
          </select>
        </div>

        <div class="aCounselors-field full">
          <label>Initial Password</label>
          <input
            id="counselorPassword"
            type="password"
            placeholder="Enter a password">
        </div>

        <div class="aCounselors-field full">
          <label>Confirm Password</label>
          <input
            id="counselorConfirmPassword"
            type="password"
            placeholder="Confirm the password">
        </div>

      </div>
    </div>

    <div class="aCounselors-modal-footer">
      <button
        class="aCounselors-btn-cancel"
        onclick="closeCounselorModal()">
        Cancel
      </button>

      <button
        class="aCounselors-btn-save"
        onclick="saveCounselorAccount()">
        Create Account
      </button>
    </div>

  </div>
</div>
      </div>
    </div>

  </section>


  <!-- ANALYTICS -->
  <section class="aDashboard-card aDashboard-analytics">

    <h3>System Analytics</h3>
    <p class="aDashboard-muted">
      Appointment trends & system status overview
    </p>

    <div class="aDashboard-chart-grid">

      <div class="aDashboard-chart-box">
        <h4>Appointment Trends</h4>
        <div class="aDashboard-chart-container">
          <canvas id="appointmentsChart"></canvas>
        </div>
      </div>

      <div class="aDashboard-chart-box">
        <h4>Appointment Status</h4>

        <div class="aDashboard-chart-center">
          <div class="aDashboard-chart-inner">
            <canvas id="statusChart"></canvas>
          </div>
        </div>

      </div>

    </div>

  </section>

</main>

<script>
// ================= SETTINGS =================
function toggleSettingsMenu(e){
  e.stopPropagation();
  const dropdown = document.getElementById("settingsDropdown");
  if (dropdown) dropdown.classList.toggle("show");
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

// CLICK OUTSIDE SETTINGS
document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");

  if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});


// ================= STUDENT MODAL =================
function openAddStudentModal() {
  const modal = document.getElementById('studentModal');
  if (modal) modal.classList.add('open');
}

function closeStudentModal() {
  const modal = document.getElementById('studentModal');
  if (modal) modal.classList.remove('open');
}

const studentModal = document.getElementById('studentModal');
if (studentModal) {
  studentModal.addEventListener('click', function (e) {
    if (e.target === this) closeStudentModal();
  });
}


// ================= COUNSELOR MODAL =================
function openAddCounselorModal() {
  const modal = document.getElementById('counselorModal');
  if (modal) modal.classList.add('open');
}

function closeCounselorModal() {
  const modal = document.getElementById('counselorModal');
  if (modal) modal.classList.remove('open');
}

const counselorModal = document.getElementById('counselorModal');
if (counselorModal) {
  counselorModal.addEventListener('click', function(e) {
    if (e.target === this) closeCounselorModal();
  });
}


// ================= AGE COMPUTE =================
const birthdayInput = document.getElementById('birthday');
const ageInput = document.getElementById('studentAge');

if (birthdayInput && ageInput) {
  birthdayInput.addEventListener('change', function () {
    const birthDate = new Date(this.value);
    const today = new Date();

    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();

    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
      age--;
    }

    if (isNaN(age)) return;

    if (age < 17) {
      alert("Student must be at least 17 years old.");
      this.value = "";
      ageInput.value = "";
      return;
    }

    ageInput.value = age;
  });
}


// ================= SAVE STUDENT =================
function saveStudent() {

  const firstName = document.getElementById('firstName').value.trim();
  const lastName = document.getElementById('lastName').value.trim();
  const gender = document.getElementById('gender').value;
  const birthday = document.getElementById('birthday').value;
  const age = document.getElementById('studentAge').value;
  const studentId = document.getElementById('studentId').value.trim();
  const yearLevel = document.getElementById('yearLevel').value;
  const course = document.getElementById('course').value;
  const email = document.getElementById('email').value.trim();

  if (!firstName || !lastName || !gender || !birthday || !studentId || !yearLevel || !course || !email) {
    alert("Please fill in all required fields.");
    return;
  }

  const tbody = document.querySelector('.aStudents-table tbody');

  // SAFE CHECK
  if (!tbody) {
    alert("Student saved (table not found on this page).");
    closeStudentModal();
    return;
  }

  const row = document.createElement('tr');

  row.innerHTML = `
    <td>${studentId}</td>
    <td>${firstName} ${lastName}</td>
    <td>${email}</td>
    <td>${gender}</td>
    <td>${birthday}</td>
    <td>${age}</td>
    <td>${yearLevel}</td>
    <td>${course}</td>
    <td><button class="aStudents-btn aStudents-btn-sm">View</button></td>
  `;

  tbody.appendChild(row);

  alert("Student saved successfully!");
  closeStudentModal();
}


// ================= COUNTER ANIMATION =================
function animateValue(id, start, end, duration) {
  let obj = document.getElementById(id);
  if (!obj) return;

  let current = start;
  let step = (end - start) / (duration / 50);

  let timer = setInterval(() => {
    current += step;

    if (current >= end) {
      current = end;
      clearInterval(timer);
    }

    obj.innerText = Math.floor(current);
  }, 50);
}

window.onload = () => {
  animateValue("studentsCount", 200, 245, 1000);
  animateValue("counselorsCount", 5, 12, 1000);
  animateValue("accountsCount", 120, 180, 1000);
  animateValue("appointmentsCount", 80, 128, 1000);
};


// ================= CHARTS =================

// LINE CHART
const appointmentsCanvas = document.getElementById("appointmentsChart");
if (appointmentsCanvas) {
  new Chart(appointmentsCanvas, {
    type: "line",
    data: {
      labels: ["Mon","Tue","Wed","Thu","Fri","Sat","Sun"],
      datasets: [{
        label: "Appointments",
        data: [12, 19, 8, 15, 22, 18, 25],
        borderColor: "#34699A",
        backgroundColor: "rgba(52,105,154,0.15)",
        fill: true,
        tension: 0.4
      }]
    },
    options: {
      plugins: { legend: { display: false } }
    }
  });
}

// PIE CHART
const statusCanvas = document.getElementById("statusChart");
if (statusCanvas) {
  new Chart(statusCanvas, {
    type: "pie",
    data: {
      labels: ["Approved", "Pending", "Rejected"],
      datasets: [{
        data: [70, 20, 10],
        backgroundColor: ["#2ecc71","#f1c40f","#e74c3c"]
      }]
    },
    options: {
      plugins: { legend: { position: "bottom" } }
    }
  });
}

</script>

</body>
</html>
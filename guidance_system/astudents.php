<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Students - UNITYCARE</title>

<link rel="stylesheet" href="styles.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

</head>

<body>

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

    <a href="admin.php"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">MANAGEMENT</p>

    <a href="ausers.php"><i class="fa fa-users"></i> Users</a>
    <a href="astudents.php" class="active"><i class="fa fa-user-graduate"></i> Students</a>
    <a href="acounselors.php"><i class="fa fa-user-doctor"></i> Counselors</a>
    <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>

    <p class="sidebar-title">SYSTEM</p>

    <a href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>

  </nav>

</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">

  <div class="topbar-left">
      <h2>Student Records</h2>
    <p class="topbar-muted">
      Manage registered student accounts.
    </p>
  </div>

  <div class="topbar-actions">

    <input type="text"
      class="topbar-search-input"
      placeholder="Search student ID or name">

    <div class="filter-wrapper">

      <button onclick="toggleFilter(event)" class="btn btn-secondary">
        <i class="fa fa-filter"></i> Filter
      </button>

      <div id="filterBox">

        <p>Course</p>
        <select>
          <option>All Courses</option>
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

        <p>Year Level</p>
        <select>
          <option>All Years</option>
          <option>1st Year</option>
          <option>2nd Year</option>
          <option>3rd Year</option>
          <option>4th Year</option>
        </select>

      </div>

    </div>

  </div>

</header>

<!-- ================= MAIN ================= -->
<main class="aStudents-main">

  <section class="aStudents-card">

    <div class="aStudents-header">

      <div>
        <h3 class="aStudents-title">Student Records</h3>
        <p class="aStudents-muted">Complete list of registered students</p>
      </div>

      <div class="aStudents-record-actions">

        <button onclick="openAddStudentModal()" class="aStudents-add-btn">
          <i class="fa fa-user-plus"></i> Add Student
        </button>

        <div class="aStudents-csv-actions">
          <button class="btn-import" onclick="triggerImportCsv()">
            <i class="fa fa-file-import"></i> Import CSV
          </button>

          <button class="btn-export" onclick="exportStudentCsv()">
            <i class="fa fa-file-export"></i> Export CSV
          </button>
        </div>

      </div>

    </div>

    <div class="aStudents-table-wrapper">

      <table class="aStudents-table">

        <thead>
          <tr>
            <th>Student ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Gender</th>
            <th>Birthday</th>
            <th>Age</th>
            <th>Year</th>
            <th>Course</th>
            <th>Actions</th>
          </tr>
        </thead>

        <tbody>
          <tr>
            <td>240001</td>
            <td>Juan Dela Cruz</td>
            <td>juan@email.com</td>
            <td>Male</td>
            <td>2005-05-12</td>
            <td>20</td>
            <td>2nd Year</td>
            <td>BSIT</td>
            <td>
              <button class="aStudents-btn aStudents-btn-sm" onclick="viewStudent(this)">View</button>
            </td>
          </tr>
        </tbody>

      </table>

    </div>

  </section>

</main>

<!-- ================= MODAL ================= -->
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

<input type="file" id="importCsvInput" accept=".csv">

<!-- ================= SCRIPT ================= -->
<script>

// ================= MODAL =================
function openAddStudentModal() {
  document.getElementById('studentModal').classList.add('open');
}

function closeStudentModal() {
  document.getElementById('studentModal').classList.remove('open');
}

document.getElementById('studentModal').addEventListener('click', function (e) {
  if (e.target === this) closeStudentModal();
});

// ================= AGE COMPUTE =================
const birthdayInput = document.getElementById('birthday');
const ageInput = document.getElementById('studentAge');

birthdayInput.addEventListener('change', function () {
  const birthDate = new Date(this.value);
  const today = new Date();

  let age = today.getFullYear() - birthDate.getFullYear();
  const monthDiff = today.getMonth() - birthDate.getMonth();

  if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
    age--;
  }

  if (isNaN(age)) return;

  // BLOCK MINORS
  if (age < 17) {
    alert("Student must be at least 17 years old.");
    this.value = "";
    ageInput.value = "";
    return;
  }

  ageInput.value = age;
});

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

  // EMPTY CHECK
  if (!firstName || !lastName || !gender || !birthday || !studentId || !yearLevel || !course || !email) {
    alert("Please fill in all required fields.");
    return;
  }

  // BLOCK default selects
  if (gender === "" || yearLevel === "" || course === "") {
    alert("Please select valid options.");
    return;
  }

  // Student ID numeric only
  if (!/^\d+$/.test(studentId)) {
    alert("Student ID must contain numbers only.");
    return;
  }

  // AGE CHECK
  if (age < 17) {
    alert("Student must be at least 17 years old.");
    return;
  }

  const fullName = firstName + " " + lastName;

  // ADD TO TABLE
  const tbody = document.querySelector('.aStudents-table tbody');

  const row = document.createElement('tr');

  row.innerHTML = `
    <td>${studentId}</td>
    <td>${fullName}</td>
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

  // RESET FORM
  document.getElementById('firstName').value = "";
  document.getElementById('lastName').value = "";
  document.getElementById('gender').value = "";
  document.getElementById('birthday').value = "";
  document.getElementById('studentAge').value = "";
  document.getElementById('studentId').value = "";
  document.getElementById('yearLevel').value = "";
  document.getElementById('course').value = "";
}

// ================= FILTER =================
function toggleFilter(event) {
  event.stopPropagation();
  document.getElementById('filterBox').classList.toggle('show');
}

// ================= CSV =================
function triggerImportCsv() {
  document.getElementById('importCsvInput').click();
}

function exportStudentCsv() {
  const table = document.querySelector('.aStudents-table');
  if (!table) return;

  const rows = Array.from(table.querySelectorAll('thead tr, tbody tr'));

  const csv = rows.map(row => {
    const cells = Array.from(row.querySelectorAll('th, td'));
    return cells.map(cell => `"${cell.innerText.replace(/"/g, '""')}"`).join(',');
  }).join('\r\n');

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');

  link.href = URL.createObjectURL(blob);
  link.download = 'students.csv';

  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

let selectedRow = null;

function viewStudent(btn) {
  selectedRow = btn.closest("tr");

  const cells = selectedRow.children;

  document.getElementById("viewStudentId").value = cells[0].innerText;
  document.getElementById("viewName").value = cells[1].innerText;
  document.getElementById("viewEmail").value = cells[2].innerText;
  document.getElementById("viewGender").value = cells[3].innerText;
  document.getElementById("viewBirthday").value = cells[4].innerText;
  document.getElementById("viewAge").value = cells[5].innerText;
  document.getElementById("viewYear").value = cells[6].innerText;
  document.getElementById("viewCourse").value = cells[7].innerText;

  document.getElementById("viewStudentModal").classList.add("open");
}

function closeViewModal() {
  document.getElementById("viewStudentModal").classList.remove("open");
}

function enableEdit() {
  const inputs = document.querySelectorAll("#viewStudentModal input");
  inputs.forEach(input => input.removeAttribute("readonly"));
}

function saveEdit() {
  if (!selectedRow) return;

  const cells = selectedRow.children;

  cells[0].innerText = document.getElementById("viewStudentId").value;
  cells[1].innerText = document.getElementById("viewName").value;
  cells[2].innerText = document.getElementById("viewEmail").value;
  cells[3].innerText = document.getElementById("viewGender").value;
  cells[4].innerText = document.getElementById("viewBirthday").value;
  cells[5].innerText = document.getElementById("viewAge").value;
  cells[6].innerText = document.getElementById("viewYear").value;
  cells[7].innerText = document.getElementById("viewCourse").value;

  alert("Student updated successfully!");

  closeViewModal();
}
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

</script>

</body>
</html>

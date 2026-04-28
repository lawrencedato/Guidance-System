<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Counselors</title>

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

    <a href="admin.php"><i class="fa fa-gauge"></i> Dashboard</a>

    <p class="sidebar-title">MANAGEMENT</p>

    <a href="ausers.php"><i class="fa fa-users"></i> Users</a>
    <a href="astudents.php"><i class="fa fa-user-graduate"></i> Students</a>
    <a href="acounselors.php" class="active"><i class="fa fa-user-doctor"></i> Counselors</a>
    <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>

    <p class="sidebar-title">SYSTEM</p>

    <a href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>

  </nav>

</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">

  <div class="topbar-left">
      <h2>Counselor Accounts</h2>
    <p class="topbar-muted">
      Create and manage counselor login accounts.
    </p>
  </div>

</header>

<!-- ================= MAIN ================= -->

<main class="aCounselors-main">

  <section class="aCounselors-card">
    <div class="aCounselors-header">
      <div>
        <h3 class="aCounselors-title">Counselor Accounts</h3>
        <p class="aCounselors-muted">
          Admins can create accounts and assign initial passwords
        </p>
      </div>

      <div class="aCounselors-record-actions">
        <button
          onclick="openAddCounselorModal()"
          class="aCounselors-add-btn">
          <i class="fa fa-user-plus"></i> Add Counselor
        </button>

        <div class="aCounselors-csv-actions">
          <button
            type="button"
            class="aCounselors-btn-import"
            onclick="triggerImportCsv()">
            <i class="fa fa-file-import"></i> Import CSV
          </button>

          <button
            type="button"
            class="aCounselors-btn-export"
            onclick="exportCounselorCsv()">
            <i class="fa fa-file-export"></i> Export CSV
          </button>
        </div>
      </div>
    </div>

    <div class="aCounselors-table-wrapper">
      <table class="aCounselors-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Department</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>

        <tbody id="counselorTableBody">
          <tr>
            <td>Marie Santos</td>
            <td>marie.santos@unitycare.org</td>
            <td>Wellness</td>
            <td>Active</td>
            <td>
              <button class="aCounselors-btn aCounselors-btn-sm">
                View
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

</main>


<!-- MODAL -->

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

<!-- ================= SCRIPT ================= -->
<script>
function openAddCounselorModal() {
  document.getElementById('counselorModal').classList.add('open');
}

function closeCounselorModal() {
  document.getElementById('counselorModal').classList.remove('open');
}

document.getElementById('counselorModal').addEventListener('click', function(e) {
  if (e.target === this) closeCounselorModal();
});

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

function toggleFilter(event) {
  event.stopPropagation();
  document.getElementById('filterBox').classList.toggle('show');
}

function triggerImportCsv() {
  document.getElementById('importCsvInput').click();
}

function exportCounselorCsv() {
  const table = document.querySelector('table');
  if (!table) return;

  const rows = Array.from(table.querySelectorAll('thead tr, tbody tr'));
  const csv = rows.map(row => {
    const cells = Array.from(row.querySelectorAll('th, td'));
    return cells.map(cell => '"' + cell.innerText.replace(/"/g, '""') + '"').join(',');
  }).join('\r\n');

  downloadCsv('counselors.csv', csv);
}

function downloadCsv(filename, content) {
  const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.display = 'none';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

function parseCsvLine(line) {
  const result = [];
  let current = '';
  let inQuotes = false;

  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    const next = line[i + 1];

    if (char === '"') {
      if (inQuotes && next === '"') {
        current += '"';
        i++;
      } else {
        inQuotes = !inQuotes;
      }
    } else if (char === ',' && !inQuotes) {
      result.push(current.trim());
      current = '';
    } else {
      current += char;
    }
  }

  result.push(current.trim());
  return result;
}

function handleImportCsv(event) {
  const file = event.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = function(e) {
    const text = e.target.result;
    const lines = text.split(/\r?\n/).filter(line => line.trim());
    if (lines.length < 2) return;

    const tbody = document.querySelector('table tbody');
    const headers = parseCsvLine(lines[0]).map(h => h.toLowerCase());

    lines.slice(1).forEach(line => {
      const values = parseCsvLine(line);
      if (!values.length) return;
      const row = document.createElement('tr');
      values.slice(0, 8).forEach(value => {
        const cell = document.createElement('td');
        cell.textContent = value;
        row.appendChild(cell);
      });
      const actionCell = document.createElement('td');
      actionCell.innerHTML = '<button class="btn btn-sm">View</button>';
      row.appendChild(actionCell);
      tbody.appendChild(row);
    });
  };

  reader.readAsText(file);
  event.target.value = '';
}

document.getElementById('importCsvInput').addEventListener('change', handleImportCsv);

function triggerImportCsv() {
  document.getElementById('importCsvInput').click();
}

function exportCounselorCsv() {
  const table = document.querySelector('table');
  if (!table) return;

  const rows = Array.from(table.querySelectorAll('thead tr, tbody tr'));
  const csv = rows.map(row => {
    const cells = Array.from(row.querySelectorAll('th, td'));
    return cells.map(cell => '"' + cell.innerText.replace(/"/g, '""') + '"').join(',');
  }).join('\r\n');

  downloadCsv('counselors.csv', csv);
}

function downloadCsv(filename, content) {
  const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  const url = URL.createObjectURL(blob);
  link.setAttribute('href', url);
  link.setAttribute('download', filename);
  link.style.display = 'none';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
  URL.revokeObjectURL(url);
}

document.addEventListener('click', function(e) {
  const box = document.getElementById('filterBox');
  const btn = document.querySelector('.btn.btn-secondary');
  if (box && !box.contains(e.target) && btn && !btn.contains(e.target)) {
    box.classList.remove('show');
  }
});

function saveCounselorAccount() {
  const name = document.getElementById('counselorName').value.trim();
  const email = document.getElementById('counselorEmail').value.trim();
  const department = document.getElementById('counselorDepartment').value;
  const status = document.getElementById('counselorStatus').value;
  const password = document.getElementById('counselorPassword').value;
  const confirmPassword = document.getElementById('counselorConfirmPassword').value;

  if (!name || !email || !department || !password || !confirmPassword) {
    alert('Please fill in all fields.');
    return;
  }

  if (password !== confirmPassword) {
    alert('Passwords do not match.');
    return;
  }

  const tbody = document.getElementById('counselorTableBody');
  const row = document.createElement('tr');
  row.innerHTML = `
    <td>${name}</td>
    <td>${email}</td>
    <td>${department}</td>
    <td>${status}</td>
    <td><button class="btn btn-sm">View</button></td>
  `;
  tbody.appendChild(row);

  closeCounselorModal();
  document.getElementById('counselorName').value = '';
  document.getElementById('counselorEmail').value = '';
  document.getElementById('counselorDepartment').value = '';
  document.getElementById('counselorStatus').value = 'Active';
  document.getElementById('counselorPassword').value = '';
  document.getElementById('counselorConfirmPassword').value = '';
  alert('Counselor account created. The password was set by the admin.');
}
</script>
</body>
</html>
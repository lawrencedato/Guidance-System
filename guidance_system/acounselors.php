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
        <button onclick="openAddCounselorModal()" class="aCounselors-add-btn">
          <i class="fa fa-user-plus"></i> Add Counselor
        </button>

        <div class="aCounselors-csv-actions">
          <button type="button" class="aCounselors-btn-import" onclick="triggerImportCsv()">
            <i class="fa fa-file-import"></i> Import CSV
          </button>

          <button type="button" class="aCounselors-btn-export" onclick="exportCounselorCsv()">
            <i class="fa fa-file-export"></i> Export CSV
          </button>
        </div>
      </div>
    </div>

    <div class="aCounselors-table-wrapper">
      <table class="aCounselors-table">
        <thead>
          <tr>
            <th>Counselor ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Department</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>

        <tbody id="counselorTableBody">
          <tr>
            <td>C-001</td>
            <td>Marie Santos</td>
            <td>marie.santos@unitycare.org</td>
            <td>Wellness</td>
            <td>Active</td>
            <td>
              <button class="aCounselors-btn aCounselors-btn-sm" onclick="viewCounselor(this)">View</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>

</main>


<!-- ================= ADD COUNSELOR MODAL ================= -->
<div id="counselorModal" class="aCounselors-modal">
  <div class="aCounselors-modal-content">

    <div class="aCounselors-modal-header">
      <div>
        <h3>Create Counselor Account</h3>
        <p>Provide the counselor's details and set their initial password.</p>
      </div>
      <button class="aCounselors-modal-close" onclick="closeCounselorModal()">&#x2715;</button>
    </div>

    <div class="aCounselors-modal-body">

      <div class="aCounselors-sec-label">COUNSELOR INFORMATION</div>

      <div class="aCounselors-field-grid">

        <div class="aCounselors-field">
          <label>First Name</label>
          <input id="counselorFirstName" type="text" placeholder="e.g. Maria">
        </div>

        <div class="aCounselors-field">
          <label>Last Name</label>
          <input id="counselorLastName" type="text" placeholder="e.g. Reyes">
        </div>

        <div class="aCounselors-field">
          <label>Counselor ID</label>
          <input id="counselorID" type="text" placeholder="e.g. C-001">
        </div>

        <div class="aCounselors-field">
          <label>Email Address</label>
          <input id="counselorEmail" type="email" placeholder="e.g. counselor@unitycare.org">
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
          <div class="password-wrapper">
            <input id="counselorPassword" type="password" placeholder="Enter a password">
            <span class="aCounselors-toggle-eye" onclick="togglePassword('counselorPassword', this)">
              <i class="fa-regular fa-eye"></i>
            </span>
          </div>
        </div>

        <div class="aCounselors-field full">
          <label>Confirm Password</label>
          <div class="password-wrapper">
            <input id="counselorConfirmPassword" type="password" placeholder="Confirm the password">
            <span class="aCounselors-toggle-eye" onclick="togglePassword('counselorConfirmPassword', this)">
              <i class="fa-regular fa-eye"></i>
            </span>
          </div>
        </div>

      </div>
    </div>

    <div class="aCounselors-modal-footer">
      <button class="aCounselors-btn-cancel" onclick="closeCounselorModal()">Cancel</button>
      <button class="aCounselors-btn-save" onclick="saveCounselorAccount()">Create Account</button>
    </div>

  </div>
</div>

<!-- ================= VIEW COUNSELOR MODAL ================= -->
<div id="viewCounselorModal" class="aCounselors-modal">
  <div class="aCounselors-modal-content">

    <div class="aCounselors-modal-header">
      <div>
        <h3>Counselor Details</h3>
        <p id="viewModalSubtitle">Viewing counselor information</p>
      </div>
      <button class="aCounselors-modal-close" onclick="closeViewModal()">✕</button>
    </div>

    <div class="aCounselors-modal-body">

      <div class="aCounselors-sec-label">COUNSELOR INFORMATION</div>

      <div class="aCounselors-field-grid">

        <!-- FIX #2: Added missing Counselor ID field -->

        <div class="aCounselors-field">
          <label>Full Name</label>
          <input type="text" id="viewName" readonly>
        </div>

        <div class="aCounselors-field">
          <label>Counselor ID</label>
          <input type="text" id="viewCounselorId" readonly>
        </div>

        <div class="aCounselors-field">
          <label>Email Address</label>
          <input type="text" id="viewEmail" readonly>
        </div>

        <div class="aCounselors-field">
          <label>Department</label>
          <input type="text" id="viewDepartment" readonly>
          <select id="editDepartment" style="display:none;">
            <option>Wellness</option>
            <option>Academic Support</option>
            <option>Career Guidance</option>
            <option>Student Affairs</option>
          </select>
        </div>

        <div class="aCounselors-field">
          <label>Status</label>
          <input type="text" id="viewStatus" readonly>
          <select id="editStatus" style="display:none;">
            <option>Active</option>
            <option>Inactive</option>
          </select>
        </div>

        <div class="aCounselors-field">
          <label>Password</label>
          <div class="password-wrapper">
            <input id="viewPassword" type="password" readonly>
            <span class="aCounselors-toggle-eye" onclick="togglePassword('viewPassword', this)">
              <i class="fa-regular fa-eye"></i>
            </span>
          </div>
        </div>

      </div>

    </div>

    <div class="aCounselors-modal-footer">
      <button class="aCounselors-btn-cancel" onclick="closeViewModal()">Close</button>
      <button class="aCounselors-btn-cancel" id="editBtn" onclick="enableEdit()">
        <i class="fa fa-pen"></i> Edit
      </button>
      <button class="aCounselors-btn-save" id="saveEditBtn" style="display:none;" onclick="saveEdit()">
        Save Changes
      </button>
    </div>

  </div>
</div>

<!-- FIX #1: Added missing file input -->
<input type="file" id="importCsvInput" accept=".csv" style="display:none;">

<!-- ================= SCRIPT ================= -->
<script>

// ================= SETTINGS / THEME / LOGOUT =================
function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}

function toggleTheme() {
  const html = document.documentElement;
  html.setAttribute(
    "data-theme",
    html.getAttribute("data-theme") === "light" ? "dark" : "light"
  );
}

function logout() {
  localStorage.clear();
  window.location.href = "index.php";
}

document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn = document.querySelector(".sidebar-settingsButton");
  if (!menu.contains(e.target) && !btn.contains(e.target)) {
    menu.classList.remove("show");
  }
});

// ================= ADD COUNSELOR MODAL =================
function openAddCounselorModal() {
  document.getElementById('counselorModal').classList.add('open');
}

function closeCounselorModal() {
  document.getElementById('counselorModal').classList.remove('open');
}

document.getElementById('counselorModal').addEventListener('click', function(e) {
  if (e.target === this) closeCounselorModal();
});

// ================= SAVE COUNSELOR =================
function saveCounselorAccount() {
  const counselorID       = document.getElementById('counselorID').value.trim();
  const firstName         = document.getElementById('counselorFirstName').value.trim();
  const lastName          = document.getElementById('counselorLastName').value.trim();
  const email             = document.getElementById('counselorEmail').value.trim();
  const department        = document.getElementById('counselorDepartment').value;
  const status            = document.getElementById('counselorStatus').value;
  const password          = document.getElementById('counselorPassword').value;
  const confirmPassword   = document.getElementById('counselorConfirmPassword').value;

  if (!counselorID || !firstName || !lastName || !email || !department || !password || !confirmPassword) {
    alert('Please fill in all fields.');
    return;
  }

  if (password !== confirmPassword) {
    alert('Passwords do not match.');
    return;
  }

  const fullName = firstName + " " + lastName;
  const tbody = document.getElementById('counselorTableBody');
  const row = document.createElement('tr');

  // FIX #4: Added correct class to View button
  row.innerHTML = `
    <td>${counselorID}</td>
    <td>${fullName}</td>
    <td>${email}</td>
    <td>${department}</td>
    <td>${status}</td>
    <td>
      <button class="aCounselors-btn aCounselors-btn-sm" onclick="viewCounselor(this)">View</button>
    </td>
  `;

  row.dataset.password = password;
  tbody.appendChild(row);

  // Clear fields
  document.getElementById('counselorID').value              = '';
  document.getElementById('counselorFirstName').value       = '';
  document.getElementById('counselorLastName').value        = '';
  document.getElementById('counselorEmail').value           = '';
  document.getElementById('counselorDepartment').value      = '';
  document.getElementById('counselorStatus').value          = 'Active';
  document.getElementById('counselorPassword').value        = '';
  document.getElementById('counselorConfirmPassword').value = '';

  closeCounselorModal();
  alert('Counselor account created successfully.');
}

// ================= VIEW / EDIT COUNSELOR =================
let selectedRow = null;
let isEditing   = false;

function viewCounselor(btn) {
  selectedRow = btn.closest("tr");
  const cells = selectedRow.children;

  document.getElementById("viewCounselorId").value  = cells[0].innerText;
  document.getElementById("viewName").value         = cells[1].innerText;
  document.getElementById("viewEmail").value        = cells[2].innerText;
  document.getElementById("viewDepartment").value   = cells[3].innerText;
  document.getElementById("viewStatus").value       = cells[4].innerText;
  document.getElementById("viewPassword").value     = selectedRow.dataset.password || "";

  setViewMode();
  document.getElementById("viewCounselorModal").classList.add("open");
}

function setViewMode() {
  isEditing = false;

  document.getElementById("viewDepartment").style.display = "";
  document.getElementById("editDepartment").style.display = "none";
  document.getElementById("viewStatus").style.display     = "";
  document.getElementById("editStatus").style.display     = "none";

  document.getElementById("viewCounselorId").readOnly = true;
  document.getElementById("viewName").readOnly        = true;
  document.getElementById("viewEmail").readOnly       = true;
  document.getElementById("viewPassword").readOnly    = true;

  document.getElementById("editBtn").style.display     = "";
  document.getElementById("saveEditBtn").style.display = "none";
  document.getElementById("viewModalSubtitle").innerText = "Viewing counselor information";
}

function enableEdit() {
  isEditing = true;

  document.getElementById("editDepartment").value = document.getElementById("viewDepartment").value;
  document.getElementById("editStatus").value     = document.getElementById("viewStatus").value;

  document.getElementById("viewDepartment").style.display = "none";
  document.getElementById("editDepartment").style.display = "";
  document.getElementById("viewStatus").style.display     = "none";
  document.getElementById("editStatus").style.display     = "";

  // FIX #3 + #5: All fields made editable including ID and password
  document.getElementById("viewCounselorId").readOnly = false;
  document.getElementById("viewName").readOnly        = false;
  document.getElementById("viewEmail").readOnly       = false;
  document.getElementById("viewPassword").readOnly    = false;

  document.getElementById("editBtn").style.display     = "none";
  document.getElementById("saveEditBtn").style.display = "";
  document.getElementById("viewModalSubtitle").innerText = "Editing counselor information";
}

function saveEdit() {
  if (!selectedRow) return;

  const cells = selectedRow.children;

  cells[0].innerText = document.getElementById("viewCounselorId").value;
  cells[1].innerText = document.getElementById("viewName").value;
  cells[2].innerText = document.getElementById("viewEmail").value;
  cells[3].innerText = document.getElementById("editDepartment").value;
  cells[4].innerText = document.getElementById("editStatus").value;

  // Update stored password if changed
  selectedRow.dataset.password = document.getElementById("viewPassword").value;

  alert("Counselor updated successfully!");
  closeViewModal();
}

function closeViewModal() {
  document.getElementById("viewCounselorModal").classList.remove("open");
  setViewMode();
}

document.getElementById('viewCounselorModal').addEventListener('click', function(e) {
  if (e.target === this) closeViewModal();
});

// ================= PASSWORD TOGGLE =================
function togglePassword(inputId, iconSpan) {
  const input = document.getElementById(inputId);
  const icon  = iconSpan.querySelector("i");

  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    input.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  }
}

// ================= CSV IMPORT / EXPORT =================
function triggerImportCsv() {
  document.getElementById('importCsvInput').click();
}

function exportCounselorCsv() {
  const table = document.querySelector('.aCounselors-table');
  if (!table) return;

  const rows = Array.from(table.querySelectorAll('thead tr, tbody tr'));
  const csv = rows.map(row => {
    const cells = Array.from(row.querySelectorAll('th, td'));
    return cells.map(cell => '"' + cell.innerText.replace(/"/g, '""') + '"').join(',');
  }).join('\r\n');

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'counselors.csv';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function parseCsvLine(line) {
  const result = [];
  let current = '';
  let inQuotes = false;

  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    const next = line[i + 1];

    if (char === '"') {
      if (inQuotes && next === '"') { current += '"'; i++; }
      else { inQuotes = !inQuotes; }
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

document.getElementById('importCsvInput').addEventListener('change', function(event) {
  const file = event.target.files[0];
  if (!file) return;

  const reader = new FileReader();
  reader.onload = function(e) {
    const lines = e.target.result.split(/\r?\n/).filter(line => line.trim());
    if (lines.length < 2) return;

    const tbody = document.getElementById('counselorTableBody');

    lines.slice(1).forEach(line => {
      const values = parseCsvLine(line);
      if (!values.length) return;

      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${values[0] || ''}</td>
        <td>${values[1] || ''}</td>
        <td>${values[2] || ''}</td>
        <td>${values[3] || ''}</td>
        <td>${values[4] || ''}</td>
        <td><button class="aCounselors-btn aCounselors-btn-sm" onclick="viewCounselor(this)">View</button></td>
      `;
      tbody.appendChild(row);
    });
  };

  reader.readAsText(file);
  event.target.value = '';
});

</script>

</body>
</html>

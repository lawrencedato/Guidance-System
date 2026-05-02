<?php
$host = "localhost";
$db   = "gcs_db";
$user = "root";
$pass = "";

$conn = new mysqli($host, $user, $pass, $db);

// ── HANDLE POST ACTIONS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    // ---------- ADD COUNSELOR ----------
    if ($action === 'add') {
        $counselor_id  = $conn->real_escape_string(trim($_POST['counselor_id']));
        $first_name    = $conn->real_escape_string(trim($_POST['first_name']));
        $last_name     = $conn->real_escape_string(trim($_POST['last_name']));
        $email         = $conn->real_escape_string(trim($_POST['email']));
        $department    = $conn->real_escape_string(trim($_POST['department']));
        $status        = $conn->real_escape_string(trim($_POST['status']));
        $password      = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $check = $conn->query("SELECT counselor_id FROM counselors WHERE counselor_id = '$counselor_id' OR email = '$email'");
        if ($check->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Counselor ID or Email already exists."]);
            exit;
        }

        $sql = "INSERT INTO counselors (counselor_id, first_name, last_name, email, department, password, status)
                VALUES ('$counselor_id', '$first_name', '$last_name', '$email', '$department', '$password', '$status')";

        if ($conn->query($sql)) {
            echo json_encode(["success" => true, "message" => "Counselor account created successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to create counselor: " . $conn->error]);
        }
        exit;
    }

    // ---------- EDIT COUNSELOR ----------
    if ($action === 'edit') {
        $old_id     = $conn->real_escape_string(trim($_POST['old_id']));
        $counselor_id = $conn->real_escape_string(trim($_POST['counselor_id']));
        $name       = $conn->real_escape_string(trim($_POST['name']));
        $email      = $conn->real_escape_string(trim($_POST['email']));
        $department = $conn->real_escape_string(trim($_POST['department']));
        $status     = $conn->real_escape_string(trim($_POST['status']));

        // Split name into first and last
        $parts      = explode(' ', $name, 2);
        $first_name = $conn->real_escape_string($parts[0]);
        $last_name  = $conn->real_escape_string($parts[1] ?? '');

        $emailCheck = $conn->query("SELECT counselor_id FROM counselors WHERE email = '$email' AND counselor_id != '$old_id'");
        if ($emailCheck->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Email already in use by another counselor."]);
            exit;
        }

        $sql = "UPDATE counselors
                SET counselor_id = '$counselor_id',
                    first_name   = '$first_name',
                    last_name    = '$last_name',
                    email        = '$email',
                    department   = '$department',
                    status       = '$status'
                WHERE counselor_id = '$old_id'";

        if (!empty($_POST['password'])) {
            $newPass = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE counselors
                    SET counselor_id = '$counselor_id',
                        first_name   = '$first_name',
                        last_name    = '$last_name',
                        email        = '$email',
                        department   = '$department',
                        status       = '$status',
                        password     = '$newPass'
                    WHERE counselor_id = '$old_id'";
        }

        if ($conn->query($sql)) {
            echo json_encode(["success" => true, "message" => "Counselor updated successfully."]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to update counselor: " . $conn->error]);
        }
        exit;
    }

    // ---------- IMPORT CSV ----------
    if ($action === 'import_csv') {
        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(["success" => false, "message" => "No file uploaded or upload error."]);
            exit;
        }

        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        if (!$file) {
            echo json_encode(["success" => false, "message" => "Failed to open CSV file."]);
            exit;
        }

        $rowCount = 0;
        $skipped  = 0;
        fgetcsv($file); // skip header

        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            if (count($data) < 5) { $skipped++; continue; }

            $counselor_id = $conn->real_escape_string(trim($data[0]));
            $name         = explode(' ', trim($data[1]), 2);
            $first_name   = $conn->real_escape_string($name[0]);
            $last_name    = $conn->real_escape_string($name[1] ?? '');
            $email        = $conn->real_escape_string(trim($data[2]));
            $department   = $conn->real_escape_string(trim($data[3]));
            $status       = strtolower($conn->real_escape_string(trim($data[4])));
            $password     = password_hash('temp1234', PASSWORD_DEFAULT);

            $check = $conn->query("SELECT counselor_id FROM counselors WHERE counselor_id = '$counselor_id' OR email = '$email'");
            if ($check->num_rows > 0) { $skipped++; continue; }

            $sql = "INSERT INTO counselors (counselor_id, first_name, last_name, email, department, password, status)
                    VALUES ('$counselor_id', '$first_name', '$last_name', '$email', '$department', '$password', '$status')";

            if ($conn->query($sql)) { $rowCount++; } else { $skipped++; }
        }

        fclose($file);
        echo json_encode(["success" => true, "message" => "$rowCount counselors imported. $skipped skipped."]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Unknown action."]);
    exit;
}

// ── FETCH COUNSELORS ──
$counselorRows = [];
$result = $conn->query("SELECT counselor_id, first_name, last_name, email, department, status FROM counselors ORDER BY counselor_id ASC");
while ($row = $result->fetch_assoc()) $counselorRows[] = $row;

$conn->close();
?>
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
          <?php if (empty($counselorRows)): ?>
            <tr><td colspan="6" style="text-align:center;">No counselors found.</td></tr>
          <?php else: ?>
            <?php foreach ($counselorRows as $c): ?>
              <tr
                data-id="<?= htmlspecialchars($c['counselor_id']) ?>"
                data-name="<?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?>"
                data-email="<?= htmlspecialchars($c['email']) ?>"
                data-department="<?= htmlspecialchars($c['department']) ?>"
                data-status="<?= htmlspecialchars($c['status']) ?>"
              >
                <td><?= htmlspecialchars($c['counselor_id']) ?></td>
                <td><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['department']) ?></td>
                <td>
                  <span class="aBadge <?= strtolower($c['status']) === 'active' ? 'aBadge-success' : 'aBadge-danger' ?>">
                    <?= ucfirst($c['status']) ?>
                  </span>
                </td>
                <td>
                  <button class="aCounselors-btn aCounselors-btn-sm" onclick="viewCounselor(this)">View</button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
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
          <input id="counselorID" type="text" placeholder="e.g. 000002">
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
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
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
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>

        <div class="aCounselors-field">
          <label>New Password <small>(leave blank to keep current)</small></label>
          <div class="password-wrapper">
            <input id="viewPassword" type="password" placeholder="Enter new password" readonly>
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
  const btn  = document.querySelector(".sidebar-settingsButton");
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

// ================= SAVE COUNSELOR (DB) =================
function saveCounselorAccount() {
  const counselorID     = document.getElementById('counselorID').value.trim();
  const firstName       = document.getElementById('counselorFirstName').value.trim();
  const lastName        = document.getElementById('counselorLastName').value.trim();
  const email           = document.getElementById('counselorEmail').value.trim();
  const department      = document.getElementById('counselorDepartment').value;
  const status          = document.getElementById('counselorStatus').value;
  const password        = document.getElementById('counselorPassword').value;
  const confirmPassword = document.getElementById('counselorConfirmPassword').value;

  if (!counselorID || !firstName || !lastName || !email || !department || !password || !confirmPassword) {
    alert('Please fill in all fields.');
    return;
  }

  if (password !== confirmPassword) {
    alert('Passwords do not match.');
    return;
  }

  const formData = new FormData();
  formData.append('action', 'add');
  formData.append('counselor_id', counselorID);
  formData.append('first_name', firstName);
  formData.append('last_name', lastName);
  formData.append('email', email);
  formData.append('department', department);
  formData.append('status', status);
  formData.append('password', password);

  fetch('acounselors.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      alert(data.message);
      if (data.success) {
        closeCounselorModal();
        location.reload();
      }
    })
    .catch(() => alert('Request failed.'));
}

// ================= VIEW / EDIT COUNSELOR =================
let selectedRow = null;

function viewCounselor(btn) {
  selectedRow = btn.closest("tr");

  document.getElementById("viewCounselorId").value = selectedRow.dataset.id;
  document.getElementById("viewName").value        = selectedRow.dataset.name;
  document.getElementById("viewEmail").value       = selectedRow.dataset.email;
  document.getElementById("viewDepartment").value  = selectedRow.dataset.department;
  document.getElementById("viewStatus").value      = selectedRow.dataset.status;
  document.getElementById("viewPassword").value    = "";

  setViewMode();
  document.getElementById("viewCounselorModal").classList.add("open");
}

function setViewMode() {
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
  document.getElementById("editDepartment").value = document.getElementById("viewDepartment").value;
  document.getElementById("editStatus").value     = document.getElementById("viewStatus").value;

  document.getElementById("viewDepartment").style.display = "none";
  document.getElementById("editDepartment").style.display = "";
  document.getElementById("viewStatus").style.display     = "none";
  document.getElementById("editStatus").style.display     = "";

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

  const formData = new FormData();
  formData.append('action', 'edit');
  formData.append('old_id',     selectedRow.dataset.id);
  formData.append('counselor_id', document.getElementById("viewCounselorId").value);
  formData.append('name',       document.getElementById("viewName").value);
  formData.append('email',      document.getElementById("viewEmail").value);
  formData.append('department', document.getElementById("editDepartment").value);
  formData.append('status',     document.getElementById("editStatus").value);
  formData.append('password',   document.getElementById("viewPassword").value);

  fetch('acounselors.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      alert(data.message);
      if (data.success) {
        closeViewModal();
        location.reload();
      }
    })
    .catch(() => alert('Request failed.'));
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
    icon.classList.replace("fa-eye", "fa-eye-slash");
  } else {
    input.type = "password";
    icon.classList.replace("fa-eye-slash", "fa-eye");
  }
}

// ================= CSV IMPORT =================
function triggerImportCsv() {
  document.getElementById('importCsvInput').click();
}

document.getElementById('importCsvInput').addEventListener('change', function(event) {
  const file = event.target.files[0];
  if (!file) return;

  const formData = new FormData();
  formData.append('action', 'import_csv');
  formData.append('csv_file', file);

  fetch('acounselors.php', { method: 'POST', body: formData })
    .then(res => res.json())
    .then(data => {
      alert(data.message);
      if (data.success) location.reload();
    })
    .catch(() => alert('Import failed.'));

  event.target.value = '';
});

// ================= CSV EXPORT =================
function exportCounselorCsv() {
  const table = document.querySelector('.aCounselors-table');
  if (!table) return;

  const rows = Array.from(table.querySelectorAll('thead tr, tbody tr'));
  const csv  = rows.map(row => {
    const cells = Array.from(row.querySelectorAll('th, td'));
    return cells.map(cell => '"' + cell.innerText.replace(/"/g, '""') + '"').join(',');
  }).join('\r\n');

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href     = URL.createObjectURL(blob);
  link.download = 'counselors.csv';
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

</script>

</body>
</html>
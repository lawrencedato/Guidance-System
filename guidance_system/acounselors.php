<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: slogin.php");
    exit;
}

$conn = new mysqli("127.0.0.1", "root", "", "gcs_db");

function generateCounselorId($conn) {
    $res = $conn->query("SELECT counselor_id FROM counselors ORDER BY CAST(counselor_id AS UNSIGNED) DESC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $last = $res->fetch_assoc()['counselor_id'];
        $num  = (int) $last + 1;
    } else {
        $num = 1;
    }
    return str_pad($num, 6, '0', STR_PAD_LEFT);
}

// ── HANDLE POST ACTIONS ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // ── GET NEXT ID ──
    if ($action === 'get_next_id') {
        echo json_encode(["success" => true, "counselor_id" => generateCounselorId($conn)]);
        exit;
    }

    // ── CREATE ──
    if ($action === 'create') {
        $first_name  = trim($_POST['first_name']  ?? '');
        $last_name   = trim($_POST['last_name']   ?? '');
        $email       = trim($_POST['email']       ?? '');
        $department  = trim($_POST['department']  ?? '');
        $password    = $_POST['password']         ?? '';

        if (!$first_name || !$last_name || !$email || !$department || !$password) {
            echo json_encode(["success" => false, "message" => "All fields are required."]);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["success" => false, "message" => "Invalid email address."]);
            exit;
        }
        if (strlen($password) < 8 ||
            !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) ||
            !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*]/', $password)) {
            echo json_encode(["success" => false, "message" => "Password must be at least 8 chars with uppercase, lowercase, number, and symbol."]);
            exit;
        }

        $em    = $conn->real_escape_string($email);
        $check = $conn->query("SELECT counselor_id FROM counselors WHERE email='$em' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Email is already in use."]);
            exit;
        }

        $counselor_id = generateCounselorId($conn);
        $fn     = $conn->real_escape_string($first_name);
        $ln     = $conn->real_escape_string($last_name);
        $dept   = $conn->real_escape_string($department);
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $hp     = $conn->real_escape_string($hashed);

        $ok = $conn->query(
            "INSERT INTO counselors (counselor_id, first_name, last_name, email, department, password, status, archived)
             VALUES ('$counselor_id', '$fn', '$ln', '$em', '$dept', '$hp', 'Active', 0)"
        );

        echo $ok
            ? json_encode(["success" => true,  "message" => "Counselor account created.", "counselor_id" => $counselor_id])
            : json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        exit;
    }

    // ── UPDATE ──
    if ($action === 'update') {
        $counselor_id = $conn->real_escape_string($_POST['counselor_id'] ?? '');
        $first_name   = trim($_POST['first_name']  ?? '');
        $last_name    = trim($_POST['last_name']   ?? '');
        $email        = trim($_POST['email']       ?? '');
        $department   = trim($_POST['department']  ?? '');

        if (!$counselor_id || !$first_name || !$last_name || !$email || !$department) {
            echo json_encode(["success" => false, "message" => "All fields are required."]);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["success" => false, "message" => "Invalid email address."]);
            exit;
        }

        $em    = $conn->real_escape_string($email);
        $fn    = $conn->real_escape_string($first_name);
        $ln    = $conn->real_escape_string($last_name);
        $dept  = $conn->real_escape_string($department);

        $check = $conn->query(
            "SELECT counselor_id FROM counselors WHERE email='$em' AND counselor_id != '$counselor_id' LIMIT 1"
        );
        if ($check && $check->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Email already used by another counselor."]);
            exit;
        }

        $pwSql = '';
        $newPw = $_POST['new_password'] ?? '';
        if ($newPw !== '') {
            if (strlen($newPw) < 8 ||
                !preg_match('/[A-Z]/', $newPw) || !preg_match('/[a-z]/', $newPw) ||
                !preg_match('/[0-9]/', $newPw) || !preg_match('/[!@#$%^&*]/', $newPw)) {
                echo json_encode(["success" => false, "message" => "New password must be at least 8 chars with uppercase, lowercase, number, and symbol."]);
                exit;
            }
            $hp    = $conn->real_escape_string(password_hash($newPw, PASSWORD_BCRYPT));
            $pwSql = ", password='$hp'";
        }

        $ok = $conn->query(
            "UPDATE counselors
             SET first_name='$fn', last_name='$ln', email='$em', department='$dept'$pwSql
             WHERE counselor_id='$counselor_id'"
        );

        echo $ok
            ? json_encode(["success" => true,  "message" => "Counselor updated successfully."])
            : json_encode(["success" => false, "message" => "Database error: " . $conn->error]);
        exit;
    }

    // ── TOGGLE STATUS ──
    if ($action === 'toggle_status') {
        $counselor_id = $conn->real_escape_string($_POST['counselor_id'] ?? '');
        $newStatus    = ($_POST['new_status'] ?? '') === 'Active' ? 'Active' : 'Inactive';

        $ok = $conn->query("UPDATE counselors SET status='$newStatus' WHERE counselor_id='$counselor_id'");
        echo $ok
            ? json_encode(["success" => true,  "message" => "Status updated.", "new_status" => $newStatus])
            : json_encode(["success" => false, "message" => "Database error."]);
        exit;
    }

    // ── ARCHIVE ──
    if ($action === 'archive') {
        $counselor_id = $conn->real_escape_string($_POST['counselor_id'] ?? '');
        $ok = $conn->query("UPDATE counselors SET archived = 1, status = 'Inactive' WHERE counselor_id='$counselor_id'");
        echo $ok
            ? json_encode(["success" => true,  "message" => "Counselor archived successfully."])
            : json_encode(["success" => false, "message" => "Database error."]);
        exit;
    }

    // ── UNARCHIVE ──
    if ($action === 'unarchive') {
        $counselor_id = $conn->real_escape_string($_POST['counselor_id'] ?? '');
        $ok = $conn->query("UPDATE counselors SET archived = 0, status = 'Active' WHERE counselor_id='$counselor_id'");
        echo $ok
            ? json_encode(["success" => true,  "message" => "Counselor restored successfully."])
            : json_encode(["success" => false, "message" => "Database error."]);
        exit;
    }

    // ── IMPORT CSV ──
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
        $rowCount = 0; $skipped = 0;
        fgetcsv($file); // skip header
        while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
            if (count($data) < 5) { $skipped++; continue; }
            $counselor_id = $conn->real_escape_string(trim($data[0]));
            $fn           = $conn->real_escape_string(trim($data[1]));
            $ln           = $conn->real_escape_string(trim($data[2]));
            $em           = $conn->real_escape_string(trim($data[3]));
            $dept         = $conn->real_escape_string(trim($data[4]));
            $password     = password_hash('Temp@1234', PASSWORD_BCRYPT);
            $hp           = $conn->real_escape_string($password);

            $check = $conn->query("SELECT counselor_id FROM counselors WHERE counselor_id='$counselor_id' OR email='$em'");
            if ($check->num_rows > 0) { $skipped++; continue; }

            $sql = "INSERT INTO counselors (counselor_id, first_name, last_name, email, department, password, status, archived)
                    VALUES ('$counselor_id', '$fn', '$ln', '$em', '$dept', '$hp', 'Active', 0)";
            if ($conn->query($sql)) { $rowCount++; } else { $skipped++; }
        }
        fclose($file);
        echo json_encode(["success" => true, "message" => "$rowCount counselor(s) imported. $skipped skipped."]);
        exit;
    }

    echo json_encode(["success" => false, "message" => "Unknown action."]);
    exit;
}

// ── LOAD ACTIVE COUNSELORS ──
$counselorsRes = $conn->query(
    "SELECT counselor_id, first_name, last_name, email, department, status
     FROM counselors WHERE archived = 0 ORDER BY CAST(counselor_id AS UNSIGNED) ASC"
);
$counselors = [];
while ($row = $counselorsRes->fetch_assoc()) $counselors[] = $row;

// ── LOAD ARCHIVED COUNSELORS ──
$archivedRes = $conn->query(
    "SELECT counselor_id, first_name, last_name, email, department, status
     FROM counselors WHERE archived = 1 ORDER BY CAST(counselor_id AS UNSIGNED) ASC"
);
$archivedCounselors = [];
while ($row = $archivedRes->fetch_assoc()) $archivedCounselors[] = $row;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Counselors</title>
<link rel="stylesheet" href="styles.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>

.aCounselors-main {
    margin-left: 280px;
    padding: var(--spacing-xxl);
    background: var(--bg);
    min-height: 100vh;
}

.aCounselors-card {
    background: var(--cards);
    backdrop-filter: blur(14px);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow);
    transition: var(--transition);
    overflow: hidden;
    position: relative;
}
.aCounselors-card::after {
    content: "";
    position: absolute;
    inset: 0;
    pointer-events: none;
    background: radial-gradient(circle at top left, var(--glow), transparent 60%);
}

.aCounselors-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 20px;
}
.aCounselors-title { margin: 0; font-size: 1.4rem; font-weight: 700; color: var(--text); }
.aCounselors-muted { margin: 6px 0 0; color: var(--text-light); font-size: 0.95rem; }

.aCounselors-header-actions {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
}

.aCounselors-add-btn {
    background: linear-gradient(135deg, #113F67, #4988C4);
    color: #fff;
    border: none;
    padding: 10px 16px;
    border-radius: var(--radius-md);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
}
.aCounselors-add-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(17,63,103,0.25); }

.aCounselors-archive-btn {
    background: #f3f4f6;
    color: #6b7280;
    border: 1px solid #e5e7eb;
    padding: 10px 16px;
    border-radius: var(--radius-md);
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
}
.aCounselors-archive-btn:hover { background: #e5e7eb; color: #374151; transform: translateY(-2px); }
.aCounselors-archive-btn .archive-count {
    background: #9ca3af;
    color: #fff;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 1px 6px;
    border-radius: 999px;
    min-width: 18px;
    text-align: center;
}

.aCounselors-csv-actions { display: flex; gap: 8px; }
.aCounselors-btn-import, .aCounselors-btn-export {
    padding: 10px 14px;
    border-radius: var(--radius-md);
    font-weight: 600;
    cursor: pointer;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: var(--transition);
}
.aCounselors-btn-import { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; }
.aCounselors-btn-import:hover { background: #dcfce7; }
.aCounselors-btn-export { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
.aCounselors-btn-export:hover { background: #dbeafe; }

/* ── TABLE ── */
.aCounselors-table-wrapper { overflow-x: auto; margin-top: 15px; }
.aCounselors-table { width: 100%; border-collapse: collapse; min-width: 640px; }
.aCounselors-table th {
    text-align: left; padding: 12px 14px; font-size: 0.85rem;
    border-bottom: 1px solid rgba(37,99,235,0.1);
    color: var(--text-light); letter-spacing: 0.04em;
}
.aCounselors-table td {
    padding: 13px 14px;
    border-bottom: 1px solid rgba(37,99,235,0.06);
    color: var(--text); font-size: 0.92rem;
}
.aCounselors-table tbody tr:hover { background: rgba(37,99,235,0.03); }

.aBadge { display: inline-block; padding: 3px 10px; border-radius: 999px; font-size: 0.78rem; font-weight: 600; }
.aBadge-success  { background: #dcfce7; color: #15803d; }
.aBadge-danger   { background: #fee2e2; color: #b91c1c; }
.aBadge-archived { background: #f3f4f6; color: #6b7280; }

.aCounselors-btn-view {
    background: #eef4ff; color: #113F67; border: 1px solid #c7d8f5;
    padding: 5px 12px; border-radius: var(--radius-sm);
    font-size: 12px; font-weight: 600; cursor: pointer;
    transition: var(--transition); display: inline-flex; align-items: center; gap: 5px;
}
.aCounselors-btn-view:hover { background: #dbe9ff; }

.aCounselors-btn-restore {
    background: #f0fdf4; color: #15803d; border: 1px solid #86efac;
    padding: 5px 12px; border-radius: var(--radius-sm);
    font-size: 12px; font-weight: 600; cursor: pointer;
    transition: var(--transition); display: inline-flex; align-items: center; gap: 5px;
}
.aCounselors-btn-restore:hover { background: #dcfce7; }

/* ── MODAL ── */
.aCounselors-modal {
    display: none; position: fixed; inset: 0;
    background: rgba(17,63,103,0.25); backdrop-filter: blur(6px);
    justify-content: center; align-items: center; z-index: 9999;
}
.aCounselors-modal.open { display: flex; }

.aCounselors-modal-content {
    width: 92%; max-width: 700px;
    background: rgba(255,255,255,0.9); backdrop-filter: blur(18px);
    border-radius: 18px; padding: 24px;
    border: 1px solid rgba(37,99,235,0.12);
    box-shadow: 0 20px 60px rgba(17,63,103,0.18);
    animation: cModalPop 0.22s ease;
}
.aCounselors-modal-content.wide { max-width: 860px; }
@keyframes cModalPop {
    from { transform: scale(0.95); opacity: 0; }
    to   { transform: scale(1);    opacity: 1; }
}

.aCounselors-modal-header {
    display: flex; justify-content: space-between;
    align-items: flex-start; margin-bottom: 20px;
}
.aCounselors-modal-header h3 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #113F67; }
.aCounselors-modal-header p  { margin: 4px 0 0; font-size: 0.83rem; color: var(--text-light); }

.aCounselors-modal-close {
    background: rgba(17,63,103,0.07); border: 1px solid rgba(17,63,103,0.12);
    width: 32px; height: 32px; border-radius: 9px;
    cursor: pointer; font-size: 0.85rem; color: #113F67; flex-shrink: 0;
}
.aCounselors-modal-close:hover { background: rgba(17,63,103,0.14); }

.aCounselors-sec-label {
    font-size: 0.72rem; font-weight: 700; color: #4988C4;
    letter-spacing: 0.07em; margin: 4px 0 12px;
}
.aCounselors-field-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; }
.aCounselors-field.full { grid-column: span 2; }
.aCounselors-field label { display: block; font-size: 0.8rem; font-weight: 600; color: var(--text); margin-bottom: 5px; }
.aCounselors-field input,
.aCounselors-field select {
    width: 100%; padding: 10px 12px; border-radius: 10px;
    border: 1px solid rgba(37,99,235,0.18); outline: none;
    background: rgba(255,255,255,0.9); font-size: 0.9rem;
    color: var(--text); box-sizing: border-box;
    transition: border-color 0.2s, box-shadow 0.2s;
}
.aCounselors-field input:focus,
.aCounselors-field select:focus {
    border-color: #4988C4; box-shadow: 0 0 0 3px rgba(73,136,196,0.15);
}
.aCounselors-field input.readonly-field {
    background: rgba(243,244,246,0.8); color: var(--text-light); cursor: default;
}
.aCounselors-field input.editable-field {
    background: rgba(255,255,255,0.9); color: var(--text); cursor: text;
}
.aCounselors-field input[readonly] {
    background: rgba(243,244,246,0.8); color: var(--text-light); cursor: default;
}
#createCounselorId {
    background: rgba(243,244,246,0.9) !important; color: #4988C4 !important;
    font-weight: 700 !important; letter-spacing: 0.08em; cursor: default;
}

/* password wrapper */
.pw-wrapper { position: relative; }
.pw-wrapper input { padding-right: 40px; }
.pw-toggle {
    position: absolute; right: 10px; top: 50%; transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: var(--text-light); font-size: 0.85rem; padding: 4px;
}
.pw-toggle:hover { color: #113F67; }

.aCounselors-modal-footer {
    margin-top: 22px; display: flex;
    justify-content: flex-end; align-items: center; gap: 8px; flex-wrap: wrap;
}
.aCounselors-modal-footer .left-actions { margin-right: auto; display: flex; gap: 8px; }

.aCounselors-btn-cancel {
    padding: 9px 15px; border-radius: 10px; border: 1px solid rgba(0,0,0,0.1);
    background: #f3f4f6; cursor: pointer; font-size: 0.875rem; font-weight: 500;
    color: var(--text); transition: background 0.15s;
}
.aCounselors-btn-cancel:hover { background: #e5e7eb; }

.aCounselors-btn-save {
    padding: 9px 18px; border-radius: 10px; border: none;
    background: linear-gradient(135deg, #113F67, #4988C4);
    color: #fff; cursor: pointer; font-size: 0.875rem; font-weight: 600;
    transition: opacity 0.15s, transform 0.15s;
}
.aCounselors-btn-save:hover { opacity: 0.9; transform: translateY(-1px); }

.aCounselors-btn-danger {
    padding: 9px 15px; border-radius: 10px; border: 1px solid #fca5a5;
    background: #fff0f0; color: #b91c1c; cursor: pointer;
    font-size: 0.875rem; font-weight: 500; transition: background 0.15s;
}
.aCounselors-btn-danger:hover { background: #fee2e2; }

.aCounselors-btn-warning {
    padding: 9px 15px; border-radius: 10px; border: 1px solid #fcd34d;
    background: #fffbeb; color: #92400e; cursor: pointer;
    font-size: 0.875rem; font-weight: 500; transition: background 0.15s;
}
.aCounselors-btn-warning:hover { background: #fef3c7; }

.aCounselors-btn-success {
    padding: 9px 15px; border-radius: 10px; border: 1px solid #86efac;
    background: #f0fdf4; color: #15803d; cursor: pointer;
    font-size: 0.875rem; font-weight: 500; transition: background 0.15s;
}
.aCounselors-btn-success:hover { background: #dcfce7; }

button:disabled { opacity: 0.4; cursor: not-allowed !important; transform: none !important; }

/* toast */
.aCounselors-toast {
    position: fixed; bottom: 24px; right: 24px;
    background: #113F67; color: #fff;
    padding: 12px 20px; border-radius: 10px;
    font-size: 0.88rem; font-weight: 500;
    opacity: 0; pointer-events: none;
    transition: opacity 0.3s, transform 0.3s;
    transform: translateY(8px); z-index: 99999; max-width: 320px;
}
.aCounselors-toast.show { opacity: 1; transform: translateY(0); }

/* empty archives */
.aArchives-empty { text-align: center; padding: 40px 20px; color: var(--text-light); }
.aArchives-empty i { font-size: 2.5rem; opacity: 0.3; margin-bottom: 12px; display: block; }
.aArchives-empty p { margin: 0; font-size: 0.95rem; }
</style>
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
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>
  </div>
  <nav class="sidebar-menu">
    <a href="admin.php"><i class="fa fa-gauge"></i> Dashboard</a>
    <p class="sidebar-title">MANAGEMENT</p>
    <a href="ausers.php"><i class="fa fa-users"></i> Users</a>
    <a href="astudents.php"><i class="fa fa-user-graduate"></i> Students</a>
    <a href="acounselors.php" class="active"><i class="fa fa-user-doctor"></i> Counselors</a>
    <a href="aadmins.php"><i class="fa fa-user-shield"></i> Admins</a>
    <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>
    <p class="sidebar-title">SYSTEM</p>
    <a href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>
  </nav>
</aside>

<!-- ================= TOPBAR ================= -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Counselor Accounts</h2>
    <p class="topbar-muted">Create and manage counselor login accounts.</p>
  </div>
</header>

<!-- ================= MAIN ================= -->
<main class="aCounselors-main">
  <section class="aCounselors-card">

    <div class="aCounselors-header">
      <div>
        <h3 class="aCounselors-title">Counselor Accounts</h3>
        <p class="aCounselors-muted">Manage counselors and their department assignments</p>
      </div>
      <div class="aCounselors-header-actions">
        <button onclick="openArchivesModal()" class="aCounselors-archive-btn">
          <i class="fa fa-box-archive"></i>
          View Archives
          <span class="archive-count"><?= count($archivedCounselors) ?></span>
        </button>
        <div class="aCounselors-csv-actions">
          <button type="button" class="aCounselors-btn-import" onclick="triggerImportCsv()">
            <i class="fa fa-file-import"></i> Import CSV
          </button>
          <button type="button" class="aCounselors-btn-export" onclick="exportCounselorCsv()">
            <i class="fa fa-file-export"></i> Export CSV
          </button>
        </div>
        <button onclick="openCreateModal()" class="aCounselors-add-btn">
          <i class="fa fa-user-plus"></i> Add Counselor
        </button>
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
        <tbody>
          <?php if (empty($counselors)): ?>
            <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-light);">No counselors found.</td></tr>
          <?php else: ?>
            <?php foreach ($counselors as $c): ?>
              <tr
                data-id="<?= htmlspecialchars($c['counselor_id']) ?>"
                data-firstname="<?= htmlspecialchars($c['first_name']) ?>"
                data-lastname="<?= htmlspecialchars($c['last_name']) ?>"
                data-email="<?= htmlspecialchars($c['email']) ?>"
                data-department="<?= htmlspecialchars($c['department']) ?>"
                data-status="<?= htmlspecialchars($c['status']) ?>"
              >
                <td><?= htmlspecialchars($c['counselor_id']) ?></td>
                <td><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['department']) ?></td>
                <td>
                  <span class="aBadge <?= $c['status'] === 'Active' ? 'aBadge-success' : 'aBadge-danger' ?>">
                    <?= htmlspecialchars($c['status']) ?>
                  </span>
                </td>
                <td>
                  <button class="aCounselors-btn-view" onclick="openViewModal(this)">
                    <i class="fa fa-eye"></i> View
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </section>
</main>


<!-- ================= CREATE MODAL ================= -->
<div id="createModal" class="aCounselors-modal">
  <div class="aCounselors-modal-content">

    <div class="aCounselors-modal-header">
      <div>
        <h3><i class="fa fa-user-doctor" style="margin-right:6px;opacity:.7"></i>Create Counselor Account</h3>
        <p>Fill in the details below to create a new counselor account.</p>
      </div>
      <button class="aCounselors-modal-close" onclick="closeCreateModal()">✕</button>
    </div>

    <div class="aCounselors-sec-label">COUNSELOR INFORMATION</div>

    <div class="aCounselors-field-grid">

      <div class="aCounselors-field">
        <label>Counselor ID</label>
        <input id="createCounselorId" type="text" readonly placeholder="Generating...">
      </div>

      <div class="aCounselors-field">
        <label>Department</label>
        <select id="createDepartment">
          <option value="">Select Department</option>
          <option>Wellness</option>
          <option>Academic Support</option>
          <option>Career Guidance</option>
          <option>Student Affairs</option>
        </select>
      </div>

      <div class="aCounselors-field">
        <label>First Name</label>
        <input id="createFirstName" type="text" placeholder="e.g. Maria">
      </div>

      <div class="aCounselors-field">
        <label>Last Name</label>
        <input id="createLastName" type="text" placeholder="e.g. Reyes">
      </div>

      <div class="aCounselors-field full">
        <label>Email Address</label>
        <input id="createEmail" type="email" placeholder="e.g. counselor@school.edu">
      </div>

      <div class="aCounselors-field">
        <label>Password</label>
        <div class="pw-wrapper">
          <input id="createPassword" type="password" placeholder="Min 8 — upper, lower, number, symbol">
          <button type="button" class="pw-toggle" onclick="togglePw('createPassword', this)">
            <i class="fa-regular fa-eye"></i>
          </button>
        </div>
      </div>

      <div class="aCounselors-field">
        <label>Confirm Password</label>
        <div class="pw-wrapper">
          <input id="createConfirm" type="password" placeholder="Repeat password">
          <button type="button" class="pw-toggle" onclick="togglePw('createConfirm', this)">
            <i class="fa-regular fa-eye"></i>
          </button>
        </div>
      </div>

    </div>

    <div class="aCounselors-modal-footer">
      <button class="aCounselors-btn-cancel" onclick="closeCreateModal()">Cancel</button>
      <button class="aCounselors-btn-save" onclick="submitCreate()">
        <i class="fa fa-plus"></i> Create Account
      </button>
    </div>

  </div>
</div>


<!-- ================= VIEW / EDIT MODAL ================= -->
<div id="viewModal" class="aCounselors-modal">
  <div class="aCounselors-modal-content">

    <div class="aCounselors-modal-header">
      <div>
        <h3><i class="fa fa-user-doctor" style="margin-right:6px;opacity:.7"></i>Counselor Details</h3>
        <p id="viewSubtitle">Viewing counselor information</p>
      </div>
      <button class="aCounselors-modal-close" onclick="closeViewModal()">✕</button>
    </div>

    <div class="aCounselors-sec-label">COUNSELOR INFORMATION</div>

    <div class="aCounselors-field-grid">

      <div class="aCounselors-field">
        <label>Counselor ID</label>
        <input id="viewId" type="text" class="readonly-field" readonly>
      </div>

      <div class="aCounselors-field">
        <label>Status</label>
        <input id="viewStatus" type="text" class="readonly-field" readonly>
      </div>

      <div class="aCounselors-field">
        <label>First Name</label>
        <input id="viewFirstName" type="text" class="readonly-field" readonly>
      </div>

      <div class="aCounselors-field">
        <label>Last Name</label>
        <input id="viewLastName" type="text" class="readonly-field" readonly>
      </div>

      <div class="aCounselors-field">
        <label>Email Address</label>
        <input id="viewEmail" type="email" class="readonly-field" readonly>
      </div>

      <div class="aCounselors-field">
        <label>Department</label>
        <input id="viewDepartment" type="text" class="readonly-field" readonly>
        <select id="editDepartment" style="display:none;">
          <option>Wellness</option>
          <option>Academic Support</option>
          <option>Career Guidance</option>
          <option>Student Affairs</option>
        </select>
      </div>

      <div class="aCounselors-field full">
        <label>New Password <small style="font-weight:400;color:#9ca3af;margin-left:4px">(leave blank to keep current)</small></label>
        <div class="pw-wrapper">
          <input id="viewPassword" type="password" class="readonly-field" readonly placeholder="Enter new password to change it">
          <button type="button" class="pw-toggle" onclick="togglePw('viewPassword', this)">
            <i class="fa-regular fa-eye"></i>
          </button>
        </div>
      </div>

    </div>

    <div class="aCounselors-modal-footer">
      <div class="left-actions">
        <button id="btnToggleStatus" class="aCounselors-btn-warning" onclick="submitToggleStatus()">
          <i class="fa fa-toggle-off"></i> Deactivate
        </button>
        <button id="btnArchive" class="aCounselors-btn-danger" onclick="submitArchive()">
          <i class="fa fa-box-archive"></i> Archive
        </button>
      </div>
      <button class="aCounselors-btn-cancel" onclick="closeViewModal()">Close</button>
      <button id="btnEdit" class="aCounselors-btn-cancel" onclick="enableEdit()">
        <i class="fa fa-pen"></i> Edit
      </button>
      <button id="btnSave" class="aCounselors-btn-save" style="display:none;" onclick="submitUpdate()">
        <i class="fa fa-floppy-disk"></i> Save
      </button>
    </div>

  </div>
</div>


<!-- ================= ARCHIVES MODAL ================= -->
<div id="archivesModal" class="aCounselors-modal">
  <div class="aCounselors-modal-content wide">

    <div class="aCounselors-modal-header">
      <div>
        <h3><i class="fa fa-box-archive" style="margin-right:6px;opacity:.7"></i>Archived Counselors</h3>
        <p>These accounts are hidden from the main list. You can restore them anytime.</p>
      </div>
      <button class="aCounselors-modal-close" onclick="closeArchivesModal()">✕</button>
    </div>

    <?php if (empty($archivedCounselors)): ?>
      <div class="aArchives-empty">
        <i class="fa fa-box-archive"></i>
        <p>No archived counselor accounts yet.</p>
      </div>
    <?php else: ?>
      <div class="aCounselors-table-wrapper">
        <table class="aCounselors-table">
          <thead>
            <tr>
              <th>Counselor ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Department</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($archivedCounselors as $c): ?>
              <tr>
                <td><?= htmlspecialchars($c['counselor_id']) ?></td>
                <td><?= htmlspecialchars($c['first_name'] . ' ' . $c['last_name']) ?></td>
                <td><?= htmlspecialchars($c['email']) ?></td>
                <td><?= htmlspecialchars($c['department']) ?></td>
                <td>
                  <button
                    class="aCounselors-btn-restore"
                    onclick="submitUnarchive('<?= htmlspecialchars($c['counselor_id']) ?>', '<?= htmlspecialchars(addslashes($c['first_name'] . ' ' . $c['last_name'])) ?>', this)"
                  >
                    <i class="fa fa-rotate-left"></i> Restore
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <div class="aCounselors-modal-footer">
      <button class="aCounselors-btn-cancel" onclick="closeArchivesModal()">Close</button>
    </div>

  </div>
</div>


<input type="file" id="importCsvInput" accept=".csv" style="display:none;">

<!-- Logout overlay (reuse from your existing logout.css) -->
<div class="logout-overlay" id="logoutOverlay">
  <div class="logout-modal">
    <div class="logout-icon"><i class="fa fa-right-from-bracket"></i></div>
    <h3>Logout</h3>
    <p>Are you sure you want to logout?</p>
    <div class="logout-actions">
      <button class="logout-btn logout-btn--cancel" onclick="closeLogout()">Cancel</button>
      <button class="logout-btn logout-btn--confirm" onclick="confirmLogout()">Yes, Logout</button>
    </div>
  </div>
</div>

<div class="aCounselors-toast" id="toast"></div>


<!-- ================= SCRIPTS ================= -->
<script>

// ── HELPERS ──
function showToast(msg, type = 'success') {
    const t = document.getElementById('toast');
    t.textContent = msg;
    t.style.background = type === 'error' ? '#b91c1c' : '#113F67';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3200);
}

function post(data) {
    const fd = new FormData();
    for (const [k, v] of Object.entries(data)) fd.append(k, v);
    return fetch('acounselors.php', { method: 'POST', body: fd }).then(r => r.json());
}

function togglePw(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// ── SETTINGS / THEME / LOGOUT ──
function toggleSettingsMenu(e) {
    e.stopPropagation();
    document.getElementById('settingsDropdown').classList.toggle('show');
}
function toggleTheme() {
    const html = document.documentElement;
    html.setAttribute('data-theme', html.getAttribute('data-theme') === 'light' ? 'dark' : 'light');
}
function logout() { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout() { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=admin'; }
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeLogout();
});
document.addEventListener('click', e => {
    const menu = document.getElementById('settingsDropdown');
    const btn  = document.querySelector('.sidebar-settingsButton');
    if (!menu.contains(e.target) && !btn.contains(e.target)) menu.classList.remove('show');
});

// ── CREATE MODAL ──
function openCreateModal() {
    ['createFirstName','createLastName','createEmail','createPassword','createConfirm'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('createDepartment').value  = '';
    document.getElementById('createCounselorId').value = 'Generating...';
    document.getElementById('createModal').classList.add('open');

    post({ action: 'get_next_id' })
        .then(d => {
            document.getElementById('createCounselorId').value = d.success ? d.counselor_id : '—';
        })
        .catch(() => { document.getElementById('createCounselorId').value = '—'; });
}

function closeCreateModal() {
    document.getElementById('createModal').classList.remove('open');
}

function submitCreate() {
    const firstName  = document.getElementById('createFirstName').value.trim();
    const lastName   = document.getElementById('createLastName').value.trim();
    const email      = document.getElementById('createEmail').value.trim();
    const department = document.getElementById('createDepartment').value;
    const pw         = document.getElementById('createPassword').value;
    const confirm    = document.getElementById('createConfirm').value;

    if (!firstName || !lastName || !email || !department || !pw || !confirm) {
        showToast('Please fill in all fields.', 'error'); return;
    }
    if (pw !== confirm) {
        showToast('Passwords do not match.', 'error'); return;
    }

    post({ action: 'create', first_name: firstName, last_name: lastName, email, department, password: pw })
        .then(d => {
            showToast(d.message, d.success ? 'success' : 'error');
            if (d.success) setTimeout(() => location.reload(), 900);
        })
        .catch(() => showToast('Request failed.', 'error'));
}

// ── VIEW / EDIT MODAL ──
let activeRow = null;
const EDITABLE = ['viewFirstName', 'viewLastName', 'viewEmail', 'viewPassword'];

function openViewModal(btn) {
    activeRow = btn.closest('tr');
    const { id, firstname, lastname, email, department, status } = activeRow.dataset;

    document.getElementById('viewId').value         = id;
    document.getElementById('viewFirstName').value  = firstname;
    document.getElementById('viewLastName').value   = lastname;
    document.getElementById('viewEmail').value      = email;
    document.getElementById('viewDepartment').value = department;
    document.getElementById('viewStatus').value     = status;
    document.getElementById('viewPassword').value   = '';
    document.getElementById('viewSubtitle').textContent = `Viewing info for ${firstname} ${lastname}`;

    setEditMode(false);
    refreshToggleBtn(status);

    document.getElementById('viewModal').classList.add('open');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.remove('open');
    activeRow = null;
}

function refreshToggleBtn(status) {
    const isActive = status === 'Active';
    const btn      = document.getElementById('btnToggleStatus');
    btn.innerHTML  = isActive
        ? '<i class="fa fa-toggle-off"></i> Deactivate'
        : '<i class="fa fa-toggle-on"></i>  Activate';
    btn.className  = isActive ? 'aCounselors-btn-warning' : 'aCounselors-btn-success';
}

function setEditMode(on) {
    EDITABLE.forEach(id => {
        const el = document.getElementById(id);
        if (on) {
            el.removeAttribute('readonly');
            el.classList.remove('readonly-field');
            el.classList.add('editable-field');
        } else {
            el.setAttribute('readonly', '');
            el.classList.remove('editable-field');
            el.classList.add('readonly-field');
        }
    });

    document.getElementById('viewDepartment').style.display = on ? 'none' : '';
    document.getElementById('editDepartment').style.display = on ? ''     : 'none';
    if (on) {
        document.getElementById('editDepartment').value = document.getElementById('viewDepartment').value;
    }

    document.getElementById('btnEdit').style.display = on ? 'none' : '';
    document.getElementById('btnSave').style.display = on ? ''     : 'none';

    // hide action buttons while editing
    document.getElementById('btnToggleStatus').style.display = on ? 'none' : '';
    document.getElementById('btnArchive').style.display      = on ? 'none' : '';
}

function enableEdit() {
    setEditMode(true);
    document.getElementById('viewFirstName').focus();
}

function submitUpdate() {
    const id         = document.getElementById('viewId').value;
    const first_name = document.getElementById('viewFirstName').value.trim();
    const last_name  = document.getElementById('viewLastName').value.trim();
    const email      = document.getElementById('viewEmail').value.trim();
    const department = document.getElementById('editDepartment').value;
    const pw         = document.getElementById('viewPassword').value;

    if (!first_name || !last_name || !email || !department) {
        showToast('Please fill in all required fields.', 'error'); return;
    }

    post({ action: 'update', counselor_id: id, first_name, last_name, email, department, new_password: pw })
        .then(d => {
            showToast(d.message, d.success ? 'success' : 'error');
            if (d.success) setTimeout(() => location.reload(), 900);
        })
        .catch(() => showToast('Request failed.', 'error'));
}

// ── TOGGLE STATUS ──
function submitToggleStatus() {
    const id     = document.getElementById('viewId').value;
    const status = activeRow.dataset.status;
    const next   = status === 'Active' ? 'Inactive' : 'Active';
    const label  = next === 'Active' ? 'activate' : 'deactivate';

    if (!confirm(`Are you sure you want to ${label} this counselor?`)) return;

    post({ action: 'toggle_status', counselor_id: id, new_status: next })
        .then(d => {
            showToast(d.message, d.success ? 'success' : 'error');
            if (d.success) setTimeout(() => location.reload(), 900);
        })
        .catch(() => showToast('Request failed.', 'error'));
}

// ── ARCHIVE ──
function submitArchive() {
    const id   = document.getElementById('viewId').value;
    const name = document.getElementById('viewFirstName').value + ' ' + document.getElementById('viewLastName').value;

    if (!confirm(`Archive "${name}"? This will hide the account and set status to Inactive.`)) return;

    post({ action: 'archive', counselor_id: id })
        .then(d => {
            showToast(d.message, d.success ? 'success' : 'error');
            if (d.success) setTimeout(() => location.reload(), 900);
        })
        .catch(() => showToast('Request failed.', 'error'));
}

// ── ARCHIVES MODAL ──
function openArchivesModal()  { document.getElementById('archivesModal').classList.add('open'); }
function closeArchivesModal() { document.getElementById('archivesModal').classList.remove('open'); }

// ── UNARCHIVE / RESTORE ──
function submitUnarchive(counselorId, name, btn) {
    if (!confirm(`Restore "${name}"? This will move the account back to the active list.`)) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Restoring...';

    post({ action: 'unarchive', counselor_id: counselorId })
        .then(d => {
            showToast(d.message, d.success ? 'success' : 'error');
            if (d.success) setTimeout(() => location.reload(), 900);
            else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa fa-rotate-left"></i> Restore';
            }
        })
        .catch(() => {
            showToast('Request failed.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-rotate-left"></i> Restore';
        });
}

// ── CSV IMPORT ──
function triggerImportCsv() { document.getElementById('importCsvInput').click(); }

document.getElementById('importCsvInput').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    const fd = new FormData();
    fd.append('action', 'import_csv');
    fd.append('csv_file', file);
    fetch('acounselors.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
            showToast(d.message, d.success ? 'success' : 'error');
            if (d.success) setTimeout(() => location.reload(), 1200);
        })
        .catch(() => showToast('Import failed.', 'error'));
    e.target.value = '';
});

// ── CSV EXPORT ──
function exportCounselorCsv() {
    const table = document.querySelector('.aCounselors-table');
    if (!table) return;
    const rows = Array.from(table.querySelectorAll('thead tr, tbody tr'));
    const csv  = rows.map(row => {
        const cells = Array.from(row.querySelectorAll('th, td'));
        // skip last column (Action)
        return cells.slice(0, -1).map(cell => '"' + cell.innerText.replace(/"/g, '""') + '"').join(',');
    }).join('\r\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href     = URL.createObjectURL(blob);
    link.download = 'counselors.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// close modals on backdrop click
['createModal','viewModal','archivesModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('open');
    });
});

</script>
</body>
</html>
<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: slogin.php");
    exit;
}

$conn = new mysqli("127.0.0.1", "root", "", "gcs_db");

function generateAdminId($conn) {
    $res = $conn->query("SELECT admin_id FROM admins ORDER BY CAST(admin_id AS UNSIGNED) DESC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $last = $res->fetch_assoc()['admin_id'];
        $num  = (int) $last + 1;
    } else {
        $num = 1;
    }
    return str_pad($num, 6, '0', STR_PAD_LEFT);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    // ── GET NEXT ID ──
    if ($action === 'get_next_id') {
        echo json_encode(["success" => true, "admin_id" => generateAdminId($conn)]);
        exit;
    }

    // ── CREATE ──
    if ($action === 'create') {
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';

        if (!$name || !$email || !$password) {
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
        $check = $conn->query("SELECT admin_id FROM admins WHERE email='$em' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Email is already in use."]);
            exit;
        }

        $admin_id = generateAdminId($conn);
        $nm       = $conn->real_escape_string($name);
        $hashed   = password_hash($password, PASSWORD_BCRYPT);
        $hp       = $conn->real_escape_string($hashed);

        $ok = $conn->query(
            "INSERT INTO admins (admin_id, name, email, password, status)
             VALUES ('$admin_id', '$nm', '$em', '$hp', 'Active')"
        );

        echo $ok
            ? json_encode(["success" => true,  "message" => "Admin account created.", "admin_id" => $admin_id])
            : json_encode(["success" => false, "message" => "Database error. Please try again."]);
        exit;
    }

    // ── UPDATE ──
    if ($action === 'update') {
        $admin_id = $conn->real_escape_string($_POST['admin_id'] ?? '');
        $name     = trim($_POST['name']  ?? '');
        $email    = trim($_POST['email'] ?? '');

        if (!$admin_id || !$name || !$email) {
            echo json_encode(["success" => false, "message" => "All fields are required."]);
            exit;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["success" => false, "message" => "Invalid email address."]);
            exit;
        }

        $em    = $conn->real_escape_string($email);
        $nm    = $conn->real_escape_string($name);
        $check = $conn->query(
            "SELECT admin_id FROM admins WHERE email='$em' AND admin_id != '$admin_id' LIMIT 1"
        );
        if ($check && $check->num_rows > 0) {
            echo json_encode(["success" => false, "message" => "Email already used by another admin."]);
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
            "UPDATE admins SET name='$nm', email='$em'$pwSql WHERE admin_id='$admin_id'"
        );

        echo $ok
            ? json_encode(["success" => true,  "message" => "Admin account updated."])
            : json_encode(["success" => false, "message" => "Database error."]);
        exit;
    }

    // ── TOGGLE STATUS ──
    if ($action === 'toggle_status') {
        $admin_id  = $conn->real_escape_string($_POST['admin_id'] ?? '');
        $newStatus = $_POST['new_status'] ?? 'Inactive';
        $newStatus = $newStatus === 'Active' ? 'Active' : 'Inactive';

        if (isset($_SESSION['user_id']) && $admin_id === $_SESSION['user_id'] && $newStatus === 'Inactive') {
            echo json_encode(["success" => false, "message" => "You cannot deactivate your own account."]);
            exit;
        }

        $ok = $conn->query("UPDATE admins SET status='$newStatus' WHERE admin_id='$admin_id'");
        echo $ok
            ? json_encode(["success" => true,  "message" => "Status updated.", "new_status" => $newStatus])
            : json_encode(["success" => false, "message" => "Database error."]);
        exit;
    }

    // ── ARCHIVE ──
    if ($action === 'archive') {
        $admin_id = $conn->real_escape_string($_POST['admin_id'] ?? '');

        if (isset($_SESSION['user_id']) && $admin_id === $_SESSION['user_id']) {
            echo json_encode(["success" => false, "message" => "You cannot archive your own account."]);
            exit;
        }

        $ok = $conn->query("UPDATE admins SET archived = 1 WHERE admin_id='$admin_id'");
        echo $ok
            ? json_encode(["success" => true,  "message" => "Admin archived successfully."])
            : json_encode(["success" => false, "message" => "Database error."]);
        exit;
    }

    // ── UNARCHIVE ──
    if ($action === 'unarchive') {
        $admin_id = $conn->real_escape_string($_POST['admin_id'] ?? '');
        $ok = $conn->query("UPDATE admins SET archived = 0 WHERE admin_id='$admin_id'");
        echo $ok
            ? json_encode(["success" => true,  "message" => "Admin restored successfully."])
            : json_encode(["success" => false, "message" => "Database error."]);
        exit;
    }
}

// ── LOAD ACTIVE ADMINS ──
$adminsRes = $conn->query("SELECT admin_id, name, email, status FROM admins WHERE archived = 0 ORDER BY CAST(admin_id AS UNSIGNED) ASC");
$admins    = [];
while ($row = $adminsRes->fetch_assoc()) $admins[] = $row;

// ── LOAD ARCHIVED ADMINS ──
$archivedRes = $conn->query("SELECT admin_id, name, email, status FROM admins WHERE archived = 1 ORDER BY CAST(admin_id AS UNSIGNED) ASC");
$archivedAdmins = [];
while ($row = $archivedRes->fetch_assoc()) $archivedAdmins[] = $row;

$currentAdminId = $_SESSION['user_id'] ?? '';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNITYCARE | Admin Accounts</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="logout.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>

    .aAdmins-main {
        margin-left: 280px;
        padding: var(--spacing-xxl);
        background: var(--bg);
        min-height: 100vh;
    }

    .aAdmins-card {
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
    .aAdmins-card::after {
        content: "";
        position: absolute;
        inset: 0;
        pointer-events: none;
        background: radial-gradient(circle at top left, var(--glow), transparent 60%);
    }

    .aAdmins-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 16px;
        margin-bottom: 20px;
    }
    .aAdmins-title {
        margin: 0;
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--text);
    }
    .aAdmins-muted {
        margin: 6px 0 0;
        color: var(--text-light);
        font-size: 0.95rem;
    }
    .aAdmins-header-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    .aAdmins-add-btn {
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
    .aAdmins-add-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(17,63,103,0.25);
    }
    .aAdmins-archive-btn {
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
    .aAdmins-archive-btn:hover {
        background: #e5e7eb;
        color: #374151;
        transform: translateY(-2px);
    }
    .aAdmins-archive-btn .archive-count {
        background: #9ca3af;
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 1px 6px;
        border-radius: 999px;
        min-width: 18px;
        text-align: center;
    }


    .aAdmins-table-wrapper { overflow-x: auto; margin-top: 15px; }
    .aAdmins-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 600px;
    }
    .aAdmins-table th {
        text-align: left;
        padding: 12px 14px;
        font-size: 0.85rem;
        border-bottom: 1px solid rgba(37,99,235,0.1);
        color: var(--text-light);
        letter-spacing: 0.04em;
    }
    .aAdmins-table td {
        padding: 13px 14px;
        border-bottom: 1px solid rgba(37,99,235,0.06);
        color: var(--text);
        font-size: 0.92rem;
    }
    .aAdmins-table tbody tr:hover { background: rgba(37,99,235,0.03); }

    .aAdmins-btn-view {
        background: #eef4ff;
        color: #113F67;
        border: 1px solid #c7d8f5;
        padding: 5px 12px;
        border-radius: var(--radius-sm);
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .aAdmins-btn-view:hover { background: #dbe9ff; }

    .aAdmins-btn-restore {
        background: #f0fdf4;
        color: #15803d;
        border: 1px solid #86efac;
        padding: 5px 12px;
        border-radius: var(--radius-sm);
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .aAdmins-btn-restore:hover { background: #dcfce7; }

    .aBadge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 600;
    }
    .aBadge-success  { background: #dcfce7; color: #15803d; }
    .aBadge-danger   { background: #fee2e2; color: #b91c1c; }
    .aBadge-archived { background: #f3f4f6; color: #6b7280; }

    .aAdmins-modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(17,63,103,0.25);
        backdrop-filter: blur(6px);
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    .aAdmins-modal.open { display: flex; }

    .aAdmins-modal-content {
        width: 92%;
        max-width: 700px;
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(18px);
        border-radius: 18px;
        padding: 24px;
        border: 1px solid rgba(37,99,235,0.12);
        box-shadow: 0 20px 60px rgba(17,63,103,0.18);
        animation: aModalPop 0.22s ease;
    }
    .aAdmins-modal-content.wide {
        max-width: 860px;
    }
    @keyframes aModalPop {
        from { transform: scale(0.95); opacity: 0; }
        to   { transform: scale(1);    opacity: 1; }
    }

    .aAdmins-modal-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
    }
    .aAdmins-modal-header h3 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: #113F67;
    }
    .aAdmins-modal-header p {
        margin: 4px 0 0;
        font-size: 0.83rem;
        color: var(--text-light);
    }
    .aAdmins-modal-close {
        background: rgba(17,63,103,0.07);
        border: 1px solid rgba(17,63,103,0.12);
        width: 32px;
        height: 32px;
        border-radius: 9px;
        cursor: pointer;
        font-size: 0.85rem;
        color: #113F67;
        flex-shrink: 0;
    }
    .aAdmins-modal-close:hover { background: rgba(17,63,103,0.14); }

    .aAdmins-sec-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #4988C4;
        letter-spacing: 0.07em;
        margin: 4px 0 12px;
    }
    .aAdmins-field-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }
    .aAdmins-field.full { grid-column: span 2; }
    .aAdmins-field label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--text);
        margin-bottom: 5px;
    }
    .aAdmins-field input,
    .aAdmins-field select {
        width: 100%;
        padding: 10px 12px;
        border-radius: 10px;
        border: 1px solid rgba(37,99,235,0.18);
        outline: none;
        background: rgba(255,255,255,0.9);
        font-size: 0.9rem;
        color: var(--text);
        box-sizing: border-box;
        transition: border-color 0.2s, box-shadow 0.2s;
    }
    .aAdmins-field input:focus,
    .aAdmins-field select:focus {
        border-color: #4988C4;
        box-shadow: 0 0 0 3px rgba(73,136,196,0.15);
    }
    .aAdmins-field input[readonly] {
        background: rgba(243,244,246,0.8);
        color: var(--text-light);
        cursor: default;
    }

    .aAdmins-modal-footer {
        margin-top: 22px;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .aAdmins-modal-footer .left-actions {
        margin-right: auto;
        display: flex;
        gap: 8px;
    }
    .aAdmins-btn-cancel {
        padding: 9px 15px;
        border-radius: 10px;
        border: 1px solid rgba(0,0,0,0.1);
        background: #f3f4f6;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        color: var(--text);
        transition: background 0.15s;
    }
    .aAdmins-btn-cancel:hover { background: #e5e7eb; }
    .aAdmins-btn-save {
        padding: 9px 18px;
        border-radius: 10px;
        border: none;
        background: linear-gradient(135deg, #113F67, #4988C4);
        color: #fff;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 600;
        transition: opacity 0.15s, transform 0.15s;
    }
    .aAdmins-btn-save:hover { opacity: 0.9; transform: translateY(-1px); }

    .aAdmins-btn-danger {
        padding: 9px 15px;
        border-radius: 10px;
        border: 1px solid #fca5a5;
        background: #fff0f0;
        color: #b91c1c;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        transition: background 0.15s;
    }
    .aAdmins-btn-danger:hover { background: #fee2e2; }
    .aAdmins-btn-warning {
        padding: 9px 15px;
        border-radius: 10px;
        border: 1px solid #fcd34d;
        background: #fffbeb;
        color: #92400e;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        transition: background 0.15s;
    }
    .aAdmins-btn-warning:hover { background: #fef3c7; }
    .aAdmins-btn-success {
        padding: 9px 15px;
        border-radius: 10px;
        border: 1px solid #86efac;
        background: #f0fdf4;
        color: #15803d;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 500;
        transition: background 0.15s;
    }
    .aAdmins-btn-success:hover { background: #dcfce7; }

    button:disabled {
        opacity: 0.4;
        cursor: not-allowed !important;
        transform: none !important;
    }

    .aAdmins-toast {
        position: fixed;
        bottom: 24px;
        right: 24px;
        background: #113F67;
        color: #fff;
        padding: 12px 20px;
        border-radius: 10px;
        font-size: 0.88rem;
        font-weight: 500;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.3s, transform 0.3s;
        transform: translateY(8px);
        z-index: 99999;
        max-width: 320px;
    }
    .aAdmins-toast.show {
        opacity: 1;
        transform: translateY(0);
    }

    .aAdmins-id-badge {
        font-size: 0.68rem;
        background: #eef4ff;
        color: #4988C4;
        border: 1px solid #c7d8f5;
        padding: 2px 7px;
        border-radius: 999px;
        font-weight: 600;
        margin-left: 6px;
        vertical-align: middle;
    }
    #createAdminId {
        background: rgba(243,244,246,0.9) !important;
        color: #4988C4 !important;
        font-weight: 700 !important;
        letter-spacing: 0.08em;
        cursor: default;
    }

    .aAdmins-field input.readonly-field {
        background: rgba(243,244,246,0.8);
        color: var(--text-light);
        cursor: default;
    }
    .aAdmins-field input.editable-field {
        background: rgba(255,255,255,0.9);
        color: var(--text);
        cursor: text;
    }

    .aArchives-empty {
        text-align: center;
        padding: 40px 20px;
        color: var(--text-light);
    }
    .aArchives-empty i {
        font-size: 2.5rem;
        opacity: 0.3;
        margin-bottom: 12px;
        display: block;
    }
    .aArchives-empty p {
        margin: 0;
        font-size: 0.95rem;
    }
    </style>
</head>
<body class="body">

<!-- ===== SIDEBAR ===== -->
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
        <a href="acounselors.php"><i class="fa fa-user-doctor"></i> Counselors</a>
        <a href="aadmins.php" class="active"><i class="fa fa-user-shield"></i> Admins</a>
        <a href="aappointments.php"><i class="fa fa-calendar"></i> Appointments</a>
        <p class="sidebar-title">SYSTEM</p>
        <a href="areports.php"><i class="fa fa-chart-line"></i> Reports</a>
    </nav>
</aside>

<!-- ===== TOPBAR ===== -->
<header class="topbar">
    <div class="topbar-left">
        <h2>Admin Accounts</h2>
        <p class="topbar-muted">Manage system administrator accounts</p>
    </div>
</header>

<!-- ===== MAIN ===== -->
<main class="aAdmins-main">
    <section class="aAdmins-card">

        <div class="aAdmins-header">
            <div>
                <h3 class="aAdmins-title">Admin Accounts</h3>
                <p class="aAdmins-muted">Manage system administrators and their access</p>
            </div>
            <div class="aAdmins-header-actions">
                <button onclick="openArchivesModal()" class="aAdmins-archive-btn">
                    <i class="fa fa-box-archive"></i>
                    View Archives
                    <span class="archive-count"><?= count($archivedAdmins) ?></span>
                </button>
                <button onclick="openCreateModal()" class="aAdmins-add-btn">
                    <i class="fa fa-user-shield"></i> Add Admin
                </button>
            </div>
        </div>

        <div class="aAdmins-table-wrapper">
            <table class="aAdmins-table">
                <thead>
                    <tr>
                        <th>Admin ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($admins as $a): ?>
                    <tr
                        data-id="<?= htmlspecialchars($a['admin_id']) ?>"
                        data-name="<?= htmlspecialchars($a['name']) ?>"
                        data-email="<?= htmlspecialchars($a['email']) ?>"
                        data-status="<?= htmlspecialchars($a['status']) ?>"
                    >
                        <td><?= htmlspecialchars($a['admin_id']) ?></td>
                        <td><?= htmlspecialchars($a['name']) ?></td>
                        <td><?= htmlspecialchars($a['email']) ?></td>
                        <td>
                            <span class="aBadge <?= $a['status'] === 'Active' ? 'aBadge-success' : 'aBadge-danger' ?>">
                                <?= htmlspecialchars($a['status']) ?>
                            </span>
                        </td>
                        <td>
                            <button class="aAdmins-btn-view" onclick="openViewModal(this)">
                                <i class="fa fa-eye"></i> View
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </section>
</main>


<!-- ================= CREATE MODAL ================= -->
<div id="createModal" class="aAdmins-modal">
    <div class="aAdmins-modal-content">

        <div class="aAdmins-modal-header">
            <div>
                <h3><i class="fa fa-user-shield" style="margin-right:6px;opacity:.7"></i>Create Admin Account</h3>
                <p>Fill in the details below to create a new administrator</p>
            </div>
            <button class="aAdmins-modal-close" onclick="closeCreateModal()">✕</button>
        </div>

        <div class="aAdmins-sec-label">ADMIN INFORMATION</div>

        <div class="aAdmins-field-grid">

            <div class="aAdmins-field">
                <label>Admin ID</label>
                <input id="createAdminId" type="text" readonly placeholder="Generating...">
            </div>

            <div class="aAdmins-field">
                <label>Full Name</label>
                <input id="createName" type="text" placeholder="e.g. Juan dela Cruz">
            </div>

            <div class="aAdmins-field full">
                <label>Email Address</label>
                <input id="createEmail" type="email" placeholder="e.g. admin@school.edu">
            </div>

            <div class="aAdmins-field">
                <label>Password</label>
                <input id="createPassword" type="password" placeholder="Min 8 — upper, lower, number, symbol">
                <button type="button" class="auth-toggle-pw" onclick="togglePassword()">
                        <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
            </div>

            <div class="aAdmins-field">
                <label>Confirm Password</label>
                <input id="createConfirm" type="password" placeholder="Repeat password">
            </div>

        </div>

        <div class="aAdmins-modal-footer">
            <button class="aAdmins-btn-cancel" onclick="closeCreateModal()">Cancel</button>
            <button class="aAdmins-btn-save" onclick="submitCreate()">
                <i class="fa fa-plus"></i> Create Admin
            </button>
        </div>

    </div>
</div>


<!-- ================= VIEW/EDIT MODAL ================= -->
<div id="viewModal" class="aAdmins-modal">
    <div class="aAdmins-modal-content">

        <div class="aAdmins-modal-header">
            <div>
                <h3><i class="fa fa-user-shield" style="margin-right:6px;opacity:.7"></i>Admin Details</h3>
                <p id="viewSubtitle">Viewing admin information</p>
            </div>
            <button class="aAdmins-modal-close" onclick="closeViewModal()">✕</button>
        </div>

        <div class="aAdmins-sec-label">ADMIN INFORMATION</div>

        <div class="aAdmins-field-grid">
            <div class="aAdmins-field">
                <label>Admin ID</label>
                <input id="viewId" type="text" class="readonly-field" readonly>
            </div>
            <div class="aAdmins-field">
                <label>Status</label>
                <input id="viewStatus" type="text" class="readonly-field" readonly>
                <select id="editStatus" style="display:none;">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
            <div class="aAdmins-field">
                <label>Full Name</label>
                <input id="viewName" type="text" class="readonly-field" readonly>
            </div>
            <div class="aAdmins-field">
                <label>Email Address</label>
                <input id="viewEmail" type="email" class="readonly-field" readonly>
            </div>
            <div class="aAdmins-field full">
                <label>
                    New Password
                    <small style="font-weight:400;color:#9ca3af;margin-left:4px">(leave blank to keep current)</small>
                </label>
                <input id="viewPassword" type="password" class="readonly-field" readonly placeholder="Enter new password to change it">
            </div>
        </div>

        <div class="aAdmins-modal-footer">
            <div class="left-actions">
                <button id="btnToggleStatus" class="aAdmins-btn-warning" onclick="submitToggleStatus()">
                    <i class="fa fa-toggle-off"></i> Deactivate
                </button>
                <button id="btnArchive" class="aAdmins-btn-danger" onclick="submitArchive()">
                    <i class="fa fa-box-archive"></i> Archive
                </button>
            </div>
            <button class="aAdmins-btn-cancel" onclick="closeViewModal()">Close</button>
            <button id="btnEdit" class="aAdmins-btn-cancel" onclick="enableEdit()">
                <i class="fa fa-pen"></i> Edit
            </button>
            <button id="btnSave" class="aAdmins-btn-save" style="display:none;" onclick="submitUpdate()">
                <i class="fa fa-floppy-disk"></i> Save
            </button>
        </div>

    </div>
</div>


<!-- ================= ARCHIVES MODAL ================= -->
<div id="archivesModal" class="aAdmins-modal">
    <div class="aAdmins-modal-content wide">

        <div class="aAdmins-modal-header">
            <div>
                <h3><i class="fa fa-box-archive" style="margin-right:6px;opacity:.7"></i>Archived Admins</h3>
                <p>These accounts are hidden from the main list. You can restore them anytime.</p>
            </div>
            <button class="aAdmins-modal-close" onclick="closeArchivesModal()">✕</button>
        </div>

        <?php if (empty($archivedAdmins)): ?>
        <div class="aArchives-empty">
            <i class="fa fa-box-archive"></i>
            <p>No archived admin accounts yet.</p>
        </div>
        <?php else: ?>
        <div class="aAdmins-table-wrapper">
            <table class="aAdmins-table">
                <thead>
                    <tr>
                        <th>Admin ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($archivedAdmins as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['admin_id']) ?></td>
                        <td><?= htmlspecialchars($a['name']) ?></td>
                        <td><?= htmlspecialchars($a['email']) ?></td>
                        <td>
                            <span class="aBadge aBadge-archived">Archived</span>
                        </td>
                        <td>
                            <button
                                class="aAdmins-btn-restore"
                                onclick="submitUnarchive('<?= htmlspecialchars($a['admin_id']) ?>', '<?= htmlspecialchars(addslashes($a['name'])) ?>', this)"
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

        <div class="aAdmins-modal-footer">
            <button class="aAdmins-btn-cancel" onclick="closeArchivesModal()">Close</button>
        </div>

    </div>
</div>


<!-- ===== TOAST ===== -->
<div class="aAdmins-toast" id="toast"></div>


<script>
// ================= HELPERS =================
const currentAdminId = '<?= htmlspecialchars($currentAdminId) ?>';

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
    return fetch('aadmins.php', { method: 'POST', body: fd }).then(r => r.json());
}

// ================= CREATE MODAL =================
function openCreateModal() {
    document.getElementById('createName').value     = '';
    document.getElementById('createEmail').value    = '';
    document.getElementById('createPassword').value = '';
    document.getElementById('createConfirm').value  = '';
    document.getElementById('createAdminId').value  = 'Generating...';

    document.getElementById('createModal').classList.add('open');

    post({ action: 'get_next_id' })
        .then(d => {
            document.getElementById('createAdminId').value = d.success ? d.admin_id : '—';
        })
        .catch(() => {
            document.getElementById('createAdminId').value = '—';
        });
}

function closeCreateModal() {
    document.getElementById('createModal').classList.remove('open');
}

function submitCreate() {
    const name    = document.getElementById('createName').value.trim();
    const email   = document.getElementById('createEmail').value.trim();
    const pw      = document.getElementById('createPassword').value;
    const confirm = document.getElementById('createConfirm').value;

    if (!name || !email || !pw || !confirm) {
        showToast('Please fill in all fields.', 'error'); return;
    }
    if (pw !== confirm) {
        showToast('Passwords do not match.', 'error'); return;
    }

    post({ action: 'create', name, email, password: pw })
        .then(d => {
            showToast(d.message, d.success ? 'success' : 'error');
            if (d.success) setTimeout(() => location.reload(), 900);
        })
        .catch(() => showToast('Request failed.', 'error'));
}

// ================= VIEW/EDIT MODAL =================
let activeRow = null;

const EDITABLE_FIELDS = ['viewName', 'viewEmail', 'viewPassword'];

function openViewModal(btn) {
    activeRow = btn.closest('tr');
    const { id, name, email, status } = activeRow.dataset;

    document.getElementById('viewId').value       = id;
    document.getElementById('viewName').value     = name;
    document.getElementById('viewEmail').value    = email;
    document.getElementById('viewStatus').value   = status;
    document.getElementById('viewPassword').value = '';
    document.getElementById('viewSubtitle').textContent = `Viewing info for ${name}`;

    setEditMode(false);
    refreshToggleBtn(status);

    const isYou = id === currentAdminId;
    document.getElementById('btnToggleStatus').disabled = isYou;
    document.getElementById('btnArchive').disabled      = isYou;

    document.getElementById('viewModal').classList.add('open');
}

function closeViewModal() {
    document.getElementById('viewModal').classList.remove('open');
    activeRow = null;
}

function refreshToggleBtn(status) {
    // FIX: compare against capitalized 'Active'
    const isActive = status === 'Active';
    const btn      = document.getElementById('btnToggleStatus');
    btn.innerHTML  = isActive
        ? '<i class="fa fa-toggle-off"></i> Deactivate'
        : '<i class="fa fa-toggle-on"></i> Activate';
    btn.className  = isActive ? 'aAdmins-btn-warning' : 'aAdmins-btn-success';
}

function setEditMode(on) {
    EDITABLE_FIELDS.forEach(id => {
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

    document.getElementById('viewStatus').style.display = on ? 'none' : '';
    document.getElementById('editStatus').style.display = on ? ''     : 'none';
    if (on) {
        document.getElementById('editStatus').value = document.getElementById('viewStatus').value;
    }

    document.getElementById('btnEdit').style.display = on ? 'none' : '';
    document.getElementById('btnSave').style.display = on ? ''     : 'none';
}

function enableEdit() {
    setEditMode(true);
    document.getElementById('viewName').focus();
}

function submitUpdate() {
    const id    = document.getElementById('viewId').value;
    const name  = document.getElementById('viewName').value.trim();
    const email = document.getElementById('viewEmail').value.trim();
    const pw    = document.getElementById('viewPassword').value;

    if (!name || !email) {
        showToast('Name and email are required.', 'error'); return;
    }

    post({ action: 'update', admin_id: id, name, email, new_password: pw })
        .then(d => {
            showToast(d.message, d.success ? 'success' : 'error');
            if (d.success) setTimeout(() => location.reload(), 900);
        })
        .catch(() => showToast('Request failed.', 'error'));
}

// ================= TOGGLE STATUS =================
function submitToggleStatus() {
    const id     = document.getElementById('viewId').value;
    const status = activeRow.dataset.status;
    // FIX: compare against capitalized 'Active'
    const next   = status === 'Active' ? 'Inactive' : 'Active';
    const label  = next === 'Active' ? 'Activate' : 'Deactivate';

    if (!confirm(`Are you sure you want to ${label} this admin?`)) return;

    post({ action: 'toggle_status', admin_id: id, new_status: next })
        .then(d => {
            showToast(d.message, d.success ? 'success' : 'error');
            if (d.success) setTimeout(() => location.reload(), 900);
        })
        .catch(() => showToast('Request failed.', 'error'));
}

// ================= ARCHIVE =================
function submitArchive() {
    const id   = document.getElementById('viewId').value;
    const name = document.getElementById('viewName').value;

    if (!confirm(`Archive "${name}"? This will hide the account from the list.`)) return;

    post({ action: 'archive', admin_id: id })
        .then(d => {
            showToast(d.message, d.success ? 'success' : 'error');
            if (d.success) setTimeout(() => location.reload(), 900);
        })
        .catch(() => showToast('Request failed.', 'error'));
}

// ================= ARCHIVES MODAL =================
function openArchivesModal() {
    document.getElementById('archivesModal').classList.add('open');
}

function closeArchivesModal() {
    document.getElementById('archivesModal').classList.remove('open');
}

// ================= UNARCHIVE/RESTORE =================
function submitUnarchive(adminId, name, btn) {
    if (!confirm(`Restore "${name}"? This will move the account back to the active list.`)) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Restoring...';

    post({ action: 'unarchive', admin_id: adminId })
        .then(d => {
            showToast(d.message, d.success ? 'success' : 'error');
            if (d.success) setTimeout(() => location.reload(), 900);
            else { btn.disabled = false; btn.innerHTML = '<i class="fa fa-rotate-left"></i> Restore'; }
        })
        .catch(() => {
            showToast('Request failed.', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa fa-rotate-left"></i> Restore';
        });
}

// ================= SETTINGS =================
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

// ================= PASSWORD EYE ICON =================
function togglePassword() {
    const pw   = document.getElementById('createPassword');
    const icon = document.getElementById('eyeIcon');
    const show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    icon.innerHTML = show
        ? `<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
           <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
           <line x1="1" y1="1" x2="23" y2="23"/>`
        : `<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
           <circle cx="12" cy="12" r="3"/>`;
}

// ================= LOGOUT =================
function logout() {
    document.getElementById('logoutOverlay').classList.add('show');
}
function closeLogout() {
    document.getElementById('logoutOverlay').classList.remove('show');
}
function confirmLogout() {
    window.location.href = 'logout.php?role=admin';
}
document.getElementById('logoutOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeLogout();
});
</script>

</body>
</html>
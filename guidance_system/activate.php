<?php
// ================= CLEAR ANY STALE SESSION =================
if (session_status() === PHP_SESSION_NONE) session_start();
session_unset();
session_destroy();
session_write_close();


// ================= DB CONNECTION =================
mysqli_report(MYSQLI_REPORT_OFF);


$host = "localhost";
$db   = "gcs_db";
$user = "System_User";
$pass = "gcs_db2026";


$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed."]));
}


// ================= HANDLE AJAX POST =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'activate') {
    header('Content-Type: application/json');


    $student_id = trim($_POST['student_id'] ?? '');
    $email      = trim($_POST['email']      ?? '');
    $birthday   = trim($_POST['birthday']   ?? '');


    if (!$student_id || !$email || !$birthday) {
        echo json_encode(["success" => false, "message" => "Please fill in all fields."]);
        exit;
    }


    $sid = $conn->real_escape_string($student_id);
    $em  = $conn->real_escape_string($email);
    $bd  = $conn->real_escape_string($birthday);


    // ================= STEP 1: Verify student exists =================
    $check = $conn->query(
        "SELECT student_id FROM students
         WHERE student_id = '$sid'
           AND email      = '$em'
           AND birthday   = '$bd'
         LIMIT 1"
    );


    if (!$check || $check->num_rows === 0) {
        echo json_encode([
            "success" => false,
            "message" => "No matching student found. Please check your Student ID, Email, and Birthday."
        ]);
        exit;
    }


    // ================= STEP 2: Check if already activated =================
    $already = $conn->query(
        "SELECT activated_id, status FROM activated_students
         WHERE student_id = '$sid' LIMIT 1"
    );


    if ($already && $already->num_rows > 0) {
        $row = $already->fetch_assoc();
        if ($row['status'] === 'active') {
            echo json_encode([
                "success" => false,
                "message" => "This account is already activated. Please proceed to login."
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "Your account is currently inactive. Please contact your administrator."
            ]);
        }
        exit;
    }


    // ================= STEP 3: Generate temporary password =================
    function generateTempPassword(): string {
        $lower   = 'abcdefghijklmnopqrstuvwxyz';
        $upper   = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $digits  = '0123456789';
        $special = '!@#$%^&*';
        $all     = $lower . $upper . $digits . $special;


        $password  = $upper[random_int(0, strlen($upper) - 1)];
        $password .= $lower[random_int(0, strlen($lower) - 1)];
        $password .= $digits[random_int(0, strlen($digits) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];


        for ($i = 0; $i < 8; $i++) {
            $password .= $all[random_int(0, strlen($all) - 1)];
        }


        return str_shuffle($password);
    }


    $tempPassword   = generateTempPassword();
    $hashedPass     = password_hash($tempPassword, PASSWORD_BCRYPT);
    $student_id_int = (int) $student_id;



    // ================= STEP 4: Generate activated_id =================
    function generateActivatedId($conn): string {
        do {
            $id = str_pad(random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $check = $conn->query("SELECT 1 FROM activated_students WHERE activated_id = '$id' LIMIT 1");
        } while ($check && $check->num_rows > 0);
        return $id;
    }

    $activated_id = generateActivatedId($conn);

    // ================= STEP 5: Insert directly =================
    $stmt = $conn->prepare("
        INSERT INTO activated_students (activated_id, student_id, password, status, is_temp_password)
        VALUES (?, ?, ?, 'active', 1)
    ");

    if (!$stmt) {
        echo json_encode([
            "success" => false,
            "message" => "Prepare failed: " . $conn->error
        ]);
        exit;
    }

    $stmt->bind_param("sis", $activated_id, $student_id_int, $hashedPass);

    // ================= STEP 6: Execute within ACID transaction =================
    $conn->begin_transaction();
    try {
        $executed = $stmt->execute();
        if (!$executed) throw new Exception($stmt->error);
        $conn->commit();
        $stmt->close();
        echo json_encode([
            "success"       => true,
            "message"       => "Account activated successfully!",
            "temp_password" => $tempPassword
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        $stmt->close();
        echo json_encode([
            "success" => false,
            "message" => "Failed to activate account: " . $e->getMessage()
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNITYCARE | Account Activation</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(17, 63, 103, 0.25);
            backdrop-filter: blur(6px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 99999;
        }
        .modal-overlay.show { display: flex; }


        .modal-box {
            width: 420px;
            padding: var(--spacing-xxl);
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            text-align: center;
            animation: modalPop 0.25s ease;
            overflow: hidden;
        }


        @keyframes modalPop {
            from { opacity: 0; transform: scale(0.95); }
            to   { opacity: 1; transform: scale(1); }
        }


        .modal-icon-circle {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: rgba(17, 63, 103, 0.08);
            border: 2px solid rgba(17, 63, 103, 0.15);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--spacing-md);
        }
        .modal-icon-circle svg { display: block; }


        .modal-box h2 {
            margin-bottom: var(--spacing-sm);
            color: var(--primary);
            font-weight: 700;
        }
        .modal-box p {
            margin-bottom: var(--spacing-lg);
            color: var(--text-muted);
            font-size: 14px;
            line-height: 1.5;
        }


        .modal-pass-label {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            color: var(--text-muted);
            margin-bottom: 8px;
            text-align: left;
        }
        .modal-pass-box {
            display: flex;
            align-items: center;
            gap: 10px;
            background: var(--bg, #f8fafc);
            border: 2px dashed var(--border);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 12px;
        }
        .modal-pass-value {
            font-family: 'Courier New', monospace;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 3px;
            color: var(--primary);
            flex: 1;
            text-align: center;
            word-break: break-all;
        }
        .modal-copy-btn {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 7px 12px;
            border: none;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            font-weight: 600;
            font-size: 12px;
            cursor: pointer;
            white-space: nowrap;
            flex-shrink: 0;
            transition: 0.2s ease;
        }
        .modal-copy-btn:hover { background: #0e3558; }
        .modal-copy-btn.copied { background: #15803d; }


        .modal-warning {
            background: #fffbeb;
            border: 1px solid #fbbf24;
            border-radius: 10px;
            padding: 10px 14px;
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: var(--spacing-lg);
            text-align: left;
        }
        .modal-warning svg { flex-shrink: 0; margin-top: 2px; }
        .modal-warning-text {
            font-size: 12px;
            color: #92400e;
            margin: 0;
            line-height: 1.55;
        }


        .modal-login-btn {
            margin-top: 4px;
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: 0.2s ease;
        }
        .modal-login-btn:hover { background: #0e3558; }


        .modal-divider {
            border: none;
            border-top: 1px solid var(--border);
            margin: var(--spacing-lg) 0;
        }


        .auth-message {
            font-size: 13px;
            margin-top: 10px;
            min-height: 20px;
            text-align: center;
        }


        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%,60%  { transform: translateX(-6px); }
            40%,80%  { transform: translateX(6px); }
        }
        .shake { animation: shake 0.4s ease; }


        @media (max-width: 500px) {
            .modal-box { width: 90%; padding: 20px; }
        }
    </style>
</head>
<body class="auth-body">


<!-- ================= SUCCESS MODAL ================= -->
<div class="modal-overlay" id="successModal">
    <div class="modal-box">


        <div class="modal-icon-circle">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>


        <h2>Account Activated!</h2>
        <p>Your account is now active. Use the temporary password below to log in for the first time.</p>


        <hr class="modal-divider">


        <div class="modal-pass-label">Your Temporary Password</div>
        <div class="modal-pass-box">
            <span class="modal-pass-value" id="popupTempPass">—</span>
            <button class="modal-copy-btn" id="copyBtn" onclick="copyPassword()">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg>
                Copy
            </button>
        </div>


        <div class="modal-warning">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#c2410c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <p class="modal-warning-text">Save or copy this password before leaving. It will <strong>not</strong> be shown again after you close this popup.</p>
        </div>


        <a class="modal-login-btn" href="slogin.php">
            Proceed to Login
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
            </svg>
        </a>


    </div>
</div>


<!-- ================= MAIN PAGE ================= -->
<div class="auth-container">


    <section class="auth-left">
        <div class="auth-left-overlay"></div>
        <div class="auth-brand">
            <img class="auth-brand-logo" src="logo.png" alt="logo">
            <h1 class="auth-brand-title">UNITYCARE</h1>
            <p class="auth-brand-subtitle">Support • Care • Connection</p>
        </div>
    </section>


    <section class="auth-right">
        <div class="auth-box">


            <h2 class="auth-title">Activate your account</h2>
            <p class="auth-subtitle">Enter your student details to verify and activate your account.</p>


            <form class="auth-form" id="activateForm" onsubmit="event.preventDefault(); activateAccount();">


                <label class="auth-label">Student ID</label>
                <input class="auth-input" id="studentId" type="text" placeholder="e.g. 240001" required>


                <label class="auth-label">Email Address</label>
                <input class="auth-input" id="email" type="email" placeholder="e.g. juan@gmail.com" required>


                <label class="auth-label">Birthday</label>
                <input class="auth-input" id="birthday" type="date" required>


                <button class="auth-btn" type="submit" id="activateBtn">
                    Activate Account
                </button>


                <div class="auth-message" id="formMessage"></div>


            </form>


            <div class="auth-footer">
                <div class="auth-footer-text">Already have an account?</div>
                <a class="auth-footer-link" href="slogin.php">Login</a>
            </div>


        </div>
    </section>


</div>


<script>
let generatedPassword = '';


function activateAccount() {
    const studentId = document.getElementById('studentId').value.trim();
    const email     = document.getElementById('email').value.trim();
    const birthday  = document.getElementById('birthday').value;
    const msgEl     = document.getElementById('formMessage');
    const btn       = document.getElementById('activateBtn');


    msgEl.style.color = '';
    msgEl.textContent = '';


    if (!studentId || !email || !birthday) {
        showError("Please fill in all fields.");
        return;
    }


    btn.disabled    = true;
    btn.textContent = 'Activating...';


    const formData = new FormData();
    formData.append('action',     'activate');
    formData.append('student_id', studentId);
    formData.append('email',      email);
    formData.append('birthday',   birthday);


    fetch('activate.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(json => {
            btn.disabled    = false;
            btn.textContent = 'Activate Account';


            if (json.success) {
                generatedPassword = json.temp_password;
                document.getElementById('popupTempPass').textContent = generatedPassword;
                document.getElementById('successModal').classList.add('show');
                document.getElementById('activateForm').reset();
            } else {
                showError(json.message);
                ['studentId', 'email', 'birthday'].forEach(id => {
                    const el = document.getElementById(id);
                    el.classList.remove('shake');
                    void el.offsetWidth;
                    el.classList.add('shake');
                });
            }
        })
        .catch(() => {
            btn.disabled    = false;
            btn.textContent = 'Activate Account';
            showError("Something went wrong. Please try again.");
        });
}


function showError(msg) {
    const el = document.getElementById('formMessage');
    el.style.color = '#e53e3e';
    el.textContent = msg;
}


function copyPassword() {
    if (!generatedPassword) return;
    navigator.clipboard.writeText(generatedPassword).then(() => {
        const btn = document.getElementById('copyBtn');
        btn.textContent = '✅ Copied!';
        btn.classList.add('copied');
        setTimeout(() => {
            btn.innerHTML = `
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2"/>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                </svg> Copy`;
            btn.classList.remove('copied');
        }, 2500);
    });
}
</script>


</body>
</html>


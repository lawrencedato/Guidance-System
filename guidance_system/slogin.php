<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) session_start();

$conn = new mysqli("127.0.0.1", "root", "", "gcs_db");
if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "DB error: " . $conn->connect_error]);
    exit;
}

// ================= HANDLE AJAX LOGIN =================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    header('Content-Type: application/json');

    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    if (!$email || !$password) {
        echo json_encode(["success" => false, "message" => "Please fill in all fields."]);
        exit;
    }

    $lockoutSecs = 300;
    $maxAttempts = 5;
    $lockKey     = 'lock_'     . md5($email);
    $attemptsKey = 'attempts_' . md5($email);

    // ---------- CHECK LOCKOUT ----------
    if (isset($_SESSION[$lockKey])) {
        $remaining = $_SESSION[$lockKey] - time();
        if ($remaining > 0) {
            echo json_encode([
                "success"   => false,
                "locked"    => true,
                "remaining" => $remaining,
                "message"   => "Account suspended. Try again in " . ceil($remaining / 60) . " minute(s)."
            ]);
            exit;
        }
        unset($_SESSION[$lockKey], $_SESSION[$attemptsKey]);
    }

    // =============================================
    // CALL find_user_by_email procedure
    // =============================================
    $stmt = $conn->prepare("CALL find_user_by_email(?)");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();
    $stmt->close();
    $conn->next_result(); // flush multi-result from stored proc

    // ---------- HELPER: handle failed attempt ----------
    function handleFailedAttempt($attemptsKey, $lockKey, $lockoutSecs, $maxAttempts) {
        $_SESSION[$attemptsKey] = ($_SESSION[$attemptsKey] ?? 0) + 1;
        $attempts = $_SESSION[$attemptsKey];
        $left     = $maxAttempts - $attempts;

        if ($attempts >= $maxAttempts) {
            $_SESSION[$lockKey] = time() + $lockoutSecs;
            unset($_SESSION[$attemptsKey]);
            return ["success" => false, "locked" => true, "remaining" => $lockoutSecs,
                    "message" => "Too many failed attempts. Account suspended for 5 minutes."];
        }

        return ["success" => false, "locked" => false, "attempts" => $attempts,
                "left" => $left, "message" => "Invalid email or password. {$left} attempt(s) remaining."];
    }

    // ---------- EMAIL NOT FOUND ----------
    if (!$user) {
        echo json_encode(handleFailedAttempt($attemptsKey, $lockKey, $lockoutSecs, $maxAttempts));
        exit;
    }

    // ---------- CHECK STATUS ----------
    if (strtolower($user['status']) !== 'active') {
        $messages = [
            'student'   => "Account not yet activated. Please activate your account first.",
            'counselor' => "Your counselor account is inactive. Please contact the administrator.",
            'admin'     => "Your admin account is inactive. Please contact the system administrator.",
        ];
        echo json_encode([
            "success" => false,
            "message" => $messages[$user['role']] ?? "Your account is inactive."
        ]);
        exit;
    }

    // ---------- VERIFY PASSWORD ----------
    if (!password_verify($password, $user['password'])) {
        echo json_encode(handleFailedAttempt($attemptsKey, $lockKey, $lockoutSecs, $maxAttempts));
        exit;
    }

    // ---------- LOGIN SUCCESS ----------
    unset($_SESSION[$lockKey], $_SESSION[$attemptsKey]);

    $_SESSION['user_id']    = $user['user_id'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['role']       = $user['role'];

    if ($user['role'] === 'student') {
        $_SESSION['is_temp_password'] = (int) $user['is_temp_password'];
    }

    echo json_encode([
        "success"  => true,
        "message"  => "Login successful.",
        "redirect" => $user['redirect']
    ]);
    exit;
}

// ================= REDIRECT IF ALREADY LOGGED IN =================
if (isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'student':   header("Location: dashboard.php");  exit;
        case 'counselor': header("Location: counselor.php");  exit;
        case 'admin':     header("Location: admin.php");      exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UNITYCARE | Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .auth-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }
        .auth-input-wrapper .auth-input {
            width: 100%;
            padding-right: 44px;
        }
        .auth-toggle-pw {
            position: absolute;
            right: 12px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            display: flex;
            align-items: center;
            color: var(--text-muted, #888);
            transition: color 0.2s;
        }
        .auth-toggle-pw:hover { color: var(--primary, #113f67); }

        .auth-message {
            font-size: 13px;
            margin-top: 10px;
            min-height: 20px;
            text-align: center;
        }

        .attempt-dots {
            display: flex;
            justify-content: center;
            gap: 6px;
            margin-top: 10px;
        }
        .attempt-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e2e8f0;
            transition: background 0.3s, transform 0.2s;
        }
        .attempt-dot.used {
            background: #e53e3e;
            transform: scale(1.2);
        }

        .lockout-banner {
            display: none;
            background: #fff5f5;
            border: 1px solid var(--border, #feb2b2);
            border-radius: 10px;
            padding: 14px 16px;
            text-align: center;
            margin-top: 14px;
        }
        .lockout-banner.show { display: block; }
        .lockout-banner p {
            font-size: 13px;
            color: #c53030;
            margin: 0 0 6px;
        }
        .lockout-timer {
            font-size: 28px;
            font-weight: 700;
            color: #e53e3e;
            font-family: 'Courier New', monospace;
            letter-spacing: 3px;
        }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            20%,60%  { transform: translateX(-6px); }
            40%,80%  { transform: translateX(6px); }
        }
        .shake { animation: shake 0.4s ease; }
    </style>
</head>
<body class="auth-body">

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

            <h2 class="auth-title">Login</h2>
            <p class="auth-subtitle">Welcome back! Please sign in to continue.</p>

            <form class="auth-form" id="loginForm" onsubmit="event.preventDefault(); loginUser();">

                <label class="auth-label">Email</label>
                <input class="auth-input" id="email" type="email" placeholder="Enter your email" required>

                <label class="auth-label">Password</label>
                <div class="auth-input-wrapper">
                    <input class="auth-input" id="password" type="password" placeholder="Enter your password" required>
                    <button type="button" class="auth-toggle-pw" onclick="togglePassword()">
                        <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                            <circle cx="12" cy="12" r="3"/>
                        </svg>
                    </button>
                </div>

                <div class="attempt-dots" id="attemptDots">
                    <div class="attempt-dot" id="dot1"></div>
                    <div class="attempt-dot" id="dot2"></div>
                    <div class="attempt-dot" id="dot3"></div>
                    <div class="attempt-dot" id="dot4"></div>
                    <div class="attempt-dot" id="dot5"></div>
                </div>

                <button class="auth-btn" type="submit" id="loginBtn">Login</button>
                <div class="auth-message" id="formMessage"></div>

            </form>

            <div class="lockout-banner" id="lockoutBanner">
                <p>⛔ Too many failed attempts. Please wait:</p>
                <div class="lockout-timer" id="lockoutTimer">05:00</div>
                <p style="margin-top:6px; font-size:12px; margin-bottom:0;">Your account will unlock automatically.</p>
            </div>

            <div class="auth-footer">
                <div class="auth-footer-text">Don't have an account?</div>
                <a class="auth-footer-link" href="activate.php">Activate Account</a>
            </div>

        </div>
    </section>

</div>

<script>
let countdownInterval = null;
let failedAttempts    = 0;
const MAX_ATTEMPTS    = 5;

function togglePassword() {
    const pw   = document.getElementById('password');
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

function updateDots(used) {
    for (let i = 1; i <= MAX_ATTEMPTS; i++) {
        document.getElementById('dot' + i).classList.toggle('used', i <= used);
    }
}

function startCountdown(seconds) {
    clearInterval(countdownInterval);

    const banner = document.getElementById('lockoutBanner');
    const timer  = document.getElementById('lockoutTimer');
    const btn    = document.getElementById('loginBtn');
    const form   = document.getElementById('loginForm');

    form.querySelectorAll('input, button[type="submit"], button#loginBtn').forEach(el => el.disabled = true);
    banner.classList.add('show');

    let remaining = seconds;

    function tick() {
        const m = String(Math.floor(remaining / 60)).padStart(2, '0');
        const s = String(remaining % 60).padStart(2, '0');
        timer.textContent = `${m}:${s}`;
        if (remaining <= 0) {
            clearInterval(countdownInterval);
            form.querySelectorAll('input, button').forEach(el => el.disabled = false);
            banner.classList.remove('show');
            btn.textContent = 'Login';
            failedAttempts  = 0;
            updateDots(0);
            showMsg('You may try again now.', 'success');
            return;
        }
        remaining--;
    }
    tick();
    countdownInterval = setInterval(tick, 1000);
}

function loginUser() {
    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const btn      = document.getElementById('loginBtn');

    showMsg('', '');

    if (!email || !password) {
        showMsg('Please fill in all fields.', 'error');
        return;
    }

    btn.disabled    = true;
    btn.textContent = 'Signing in...';

    const fd = new FormData();
    fd.append('action',   'login');
    fd.append('email',    email);
    fd.append('password', password);

    fetch('slogin.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(json => {
            btn.disabled    = false;
            btn.textContent = 'Login';

            if (json.success) {
                showMsg('Login successful! Redirecting...', 'success');
                updateDots(0);
                setTimeout(() => { window.location.href = json.redirect; }, 700);
            } else if (json.locked) {
                updateDots(MAX_ATTEMPTS);
                startCountdown(json.remaining);
                showMsg(json.message, 'error');
            } else {
                failedAttempts = json.attempts ?? (failedAttempts + 1);
                updateDots(failedAttempts);
                showMsg(json.message, 'error');
                shakeInputs();
            }
        })
        .catch(() => {
            btn.disabled    = false;
            btn.textContent = 'Login';
            showMsg('Something went wrong. Please try again.', 'error');
        });
}

function showMsg(msg, type) {
    const el = document.getElementById('formMessage');
    el.style.color = type === 'error' ? '#e53e3e' : '#15803d';
    el.textContent = msg;
}

function shakeInputs() {
    ['email', 'password'].forEach(id => {
        const el = document.getElementById(id);
        el.classList.remove('shake');
        void el.offsetWidth;
        el.classList.add('shake');
    });
}
</script>

</body>
</html>
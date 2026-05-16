<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Clear all session variables first
$_SESSION = [];

// 2. Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// 3. Destroy the session on the server
session_destroy();

// 4. Prevent browser from caching protected pages
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Pragma: no-cache");

// 5. Redirect
header("Location: slogin.php");
exit;
?>
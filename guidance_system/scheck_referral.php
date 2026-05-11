<?php
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) session_start();

// Must be logged in as student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Content-Type: application/json');
    echo json_encode(['unseen' => 0]);
    exit;
}

$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");
$sid  = $conn->real_escape_string($_SESSION['user_id']);

$res    = $conn->query("SELECT COUNT(*) AS total FROM referrals WHERE student_id='$sid' AND is_seen=0");
$unseen = $res ? (int)$res->fetch_assoc()['total'] : 0;

header('Content-Type: application/json');
echo json_encode(['unseen' => $unseen]);
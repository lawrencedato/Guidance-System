<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counselor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

header('Content-Type: application/json');

$conn = new mysqli("127.0.0.1", "root", "", "gcs_db");
$cid  = $conn->real_escape_string($_SESSION['user_id']);

// ── APPROVE / REJECT ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $apptId = (int)($_POST['appointment_id'] ?? 0);
    $status = $_POST['status'] ?? '';

    if ($action === 'update_status') {
        $allowed = ['Approved', 'Rejected'];
        if (!$apptId || !in_array($status, $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }
        $ok = $conn->query(
            "UPDATE appointments
             SET status = '$status'
             WHERE appointment_id = $apptId
             AND counselor_id = '$cid'"
        );
        echo json_encode($ok && $conn->affected_rows > 0
            ? ['success' => true]
            : ['success' => false, 'message' => 'Could not update. Try again.']);
        exit;
    }
}

// ── GET STUDENT PROFILE ──
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    $apptId = (int)($_GET['appointment_id'] ?? 0);

    if ($action === 'get_student' && $apptId) {
        $res = $conn->query("
            SELECT s.first_name, s.last_name, s.email, s.course,
                   s.year_level, s.gender,
                   sp.contact_details, sp.emergency_contact_name,
                   sp.emergency_contact_number
            FROM appointments a
            JOIN students s  ON s.student_id  = a.student_id
            LEFT JOIN student_profiles sp ON sp.student_id = a.student_id
            WHERE a.appointment_id = $apptId
            AND   a.counselor_id   = '$cid'
            LIMIT 1
        ");
        $student = $res ? $res->fetch_assoc() : null;

        if (!$student) {
            echo json_encode(['success' => false, 'message' => 'Student not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'student' => $student]);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid action.']);
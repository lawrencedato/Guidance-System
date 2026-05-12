<?php
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: slogin.php"); exit;
}

require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");
$sid  = $conn->real_escape_string($_SESSION['user_id']);
$apptId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Load student
$studentRes = $conn->query("SELECT * FROM students WHERE student_id='$sid' LIMIT 1");
$student    = $studentRes->fetch_assoc();
$fullName   = ($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '');
$email      = $student['email'] ?? '';
$course     = $student['course'] ?? '';
$yearLevel  = $student['year_level'] ?? '';

// Load appointment — must belong to this student and be Approved
if ($apptId > 0) {
    $apptRes = $conn->query("
        SELECT a.*, CONCAT(c.first_name,' ',c.last_name) AS counselor_name
        FROM appointments a
        LEFT JOIN counselors c ON c.counselor_id = a.counselor_id
        WHERE a.appointment_id = $apptId
          AND a.student_id = '$sid'
          AND a.status = 'Approved'
        LIMIT 1
    ");
} else {
    $apptRes = $conn->query("
        SELECT a.*, CONCAT(c.first_name,' ',c.last_name) AS counselor_name
        FROM appointments a
        LEFT JOIN counselors c ON c.counselor_id = a.counselor_id
        WHERE a.student_id = '$sid'
          AND a.status = 'Approved'
        ORDER BY a.appointment_date ASC
        LIMIT 1
    ");
}

$appt = $apptRes ? $apptRes->fetch_assoc() : null;
if (!$appt) die("No approved appointment found.");

$ticketId    = 'APPT-' . $appt['appointment_id'];
$apptDate    = date('F d, Y', strtotime($appt['appointment_date']));
$apptTime    = date('g:i A', strtotime($appt['appointment_time']));
$priority    = htmlspecialchars($appt['priority'] ?? 'N/A');
$reason      = nl2br(htmlspecialchars($appt['message'] ?? 'N/A'));
$counselor   = htmlspecialchars($appt['counselor_name'] ?? 'Assigned Counselor');
$issuedDate  = date('F d, Y');

// Logo base64
$logoTag  = '';
$logoPath = __DIR__ . '/logo.png';
if (file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoTag  = '<img src="data:image/png;base64,' . $logoData . '" style="width:52px;height:52px;border-radius:10px;" alt="UNITYCARE">';
}

// Priority badge color
$pColors = [
    'High'   => ['#fee2e2', '#991b1b'],
    'Medium' => ['#fef3c7', '#92400e'],
    'Low'    => ['#d1fae5', '#065f46'],
];
[$pBg, $pFg] = $pColors[$priority] ?? ['#f1f5f9', '#475569'];

$html = '<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
  font-family: DejaVu Sans, sans-serif;
  font-size: 12px;
  color: #0f172a;
  background: #ffffff;
  padding: 40px 44px;
}

/* Header */
.header {
  display: table;
  width: 100%;
  margin-bottom: 24px;
  padding-bottom: 16px;
  border-bottom: 3px solid #113f67;
}
.header-logo { display:table-cell; width:64px; vertical-align:middle; }
.header-brand { display:table-cell; vertical-align:middle; padding-left:12px; }
.header-brand h1 { font-size:20px; font-weight:700; color:#113f67; letter-spacing:1.5px; }
.header-brand p  { font-size:10px; color:#64748b; margin-top:2px; }
.header-date { display:table-cell; vertical-align:middle; text-align:right; font-size:11px; color:#64748b; }
.header-date strong { color:#0f172a; font-size:12px; }

/* Ticket title */
.ticket-title {
  text-align: center;
  font-size: 13px;
  font-weight: 700;
  letter-spacing: 4px;
  text-transform: uppercase;
  color: #113f67;
  padding: 10px 0;
  margin-bottom: 28px;
  border-top: 1px solid #dce3ec;
  border-bottom: 1px solid #dce3ec;
}

/* Ticket box */
.ticket-box {
  border: 1.5px dashed #cbd5e1;
  border-radius: 12px;
  margin: 0 auto 28px;
  max-width: 400px;
  overflow: hidden;
}
.ticket-header {
  background: #113f67;
  color: #fff;
  text-align: center;
  padding: 16px 12px;
}
.ticket-header .sub  { font-size:9px; letter-spacing:3px; opacity:0.65; margin-bottom:4px; }
.ticket-header .main { font-size:20px; font-weight:700; letter-spacing:2px; }
.ticket-body { padding: 18px 24px; }

/* Row */
.row {
  display: table;
  width: 100%;
  margin-bottom: 8px;
}
.row-label { display:table-cell; width:110px; font-weight:700; color:#113f67; font-size:11px; vertical-align:top; }
.row-colon { display:table-cell; width:10px; color:#94a3b8; vertical-align:top; }
.row-value { display:table-cell; font-size:11px; color:#0f172a; vertical-align:top; line-height:1.5; }

/* Divider */
.divider { border-top:1px dashed #cbd5e1; margin:12px 0; }

/* Status badge */
.status-row { text-align:center; padding:12px 0; border-top:1px dashed #cbd5e1; border-bottom:1px dashed #cbd5e1; margin:4px 0 12px; }
.status-badge { display:inline-block; background:#d1fae5; color:#065f46; font-size:12px; font-weight:700; padding:5px 22px; border-radius:20px; letter-spacing:1px; }

/* Barcode area */
.barcode { text-align:center; padding:10px 0 4px; border-top:1px dashed #cbd5e1; margin-top:6px; }
.barcode-bars { font-size:14px; letter-spacing:5px; color:#94a3b8; font-family:DejaVu Sans,monospace; }
.barcode-text { font-size:9px; color:#94a3b8; letter-spacing:1.5px; margin-top:2px; }

/* Thank you strip */
.thankyou { background:#f8fafc; text-align:center; padding:10px; font-size:10px; color:#94a3b8; letter-spacing:3px; }

/* Info section */
.section-label {
  font-size:10px; font-weight:700; text-transform:uppercase;
  letter-spacing:1.2px; color:#ffffff; background:#113f67;
  padding:6px 14px; margin-bottom:14px; border-radius:6px;
}
.text-box {
  border:1px solid #dce3ec; border-left:3px solid #4988c4;
  border-radius:8px; padding:10px 14px; background:#f7f9fc;
  color:#0f172a; line-height:1.75; font-size:11px;
}

/* Footer */
.footer {
  margin-top:40px; padding-top:10px;
  border-top:2px solid #113f67;
  text-align:center; font-size:9px; color:#94a3b8;
  letter-spacing:0.4px; line-height:1.8;
}
.slip-badge {
  display:inline-block; background:#eef4fb; color:#4988c4;
  font-size:9px; font-weight:700; padding:3px 10px;
  border-radius:20px; letter-spacing:0.8px; margin-bottom:4px;
}
</style>
</head>
<body>

<div class="header">
  <div class="header-logo">' . $logoTag . '</div>
  <div class="header-brand">
    <h1>UNITYCARE</h1>
    <p>Guidance &amp; Counseling Services</p>
  </div>
  <div class="header-date">Date Issued<br><strong>' . $issuedDate . '</strong></div>
</div>

<div class="ticket-title">Session Ticket</div>

<div class="ticket-box">
  <div class="ticket-header">
    <div class="sub">UNITYCARE &bull; GCS</div>
    <div class="main">' . $ticketId . '</div>
  </div>

  <div class="status-row">
    <div class="status-badge">&#10003; APPROVED</div>
  </div>

  <div class="ticket-body">
    <div class="row">
      <div class="row-label">Student</div>
      <div class="row-colon">:</div>
      <div class="row-value">' . htmlspecialchars($fullName) . '</div>
    </div>
    <div class="row">
      <div class="row-label">Program</div>
      <div class="row-colon">:</div>
      <div class="row-value">' . htmlspecialchars($yearLevel . ' - ' . $course) . '</div>
    </div>
    <div class="row">
      <div class="row-label">Email</div>
      <div class="row-colon">:</div>
      <div class="row-value">' . htmlspecialchars($email) . '</div>
    </div>

    <div class="divider"></div>

    <div class="row">
      <div class="row-label">Date</div>
      <div class="row-colon">:</div>
      <div class="row-value">' . $apptDate . '</div>
    </div>
    <div class="row">
      <div class="row-label">Time</div>
      <div class="row-colon">:</div>
      <div class="row-value">' . $apptTime . '</div>
    </div>
    <div class="row">
      <div class="row-label">Priority</div>
      <div class="row-colon">:</div>
      <div class="row-value">
        <span style="background:' . $pBg . '; color:' . $pFg . '; font-size:10px; padding:2px 10px; border-radius:20px;">' . $priority . '</span>
      </div>
    </div>
    <div class="row">
      <div class="row-label">Counselor</div>
      <div class="row-colon">:</div>
      <div class="row-value">' . $counselor . '</div>
    </div>

    <div class="divider"></div>

    <div style="font-size:10px; font-weight:700; color:#113f67; margin-bottom:6px;">Reason for Appointment</div>
    <div class="text-box">' . $reason . '</div>

    <div class="barcode">
      <div class="barcode-bars">||||| ||||| || |||||</div>
      <div class="barcode-text">' . $ticketId . ' &bull; ' . date('Y') . '</div>
    </div>
  </div>

  <div class="thankyou">THANK YOU</div>
</div>

<div class="footer">
  <div class="slip-badge">OFFICIAL SESSION TICKET</div><br>
  This ticket confirms a counseling session scheduled through the UNITYCARE System &bull; ' . date('Y') . '
</div>

</body>
</html>';

$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'SessionTicket_' . preg_replace('/\s+/', '_', trim($fullName)) . '_' . date('Ymd') . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
?>
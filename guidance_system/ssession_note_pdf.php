<?php
/**
 * ssession_note_pdf.php
 * Generates a downloadable PDF for a session note sent to the student.
 * Usage: ssession_note_pdf.php?id=NOTE_ID
 */

error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: slogin.php");
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");
$sid  = (int)$_SESSION['user_id'];

$note_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($note_id <= 0) die("Invalid note ID.");

// Fetch note — must belong to this student and be marked sent
$res = $conn->query("
    SELECT sn.note_id, sn.notes, sn.created_at,
           CONCAT(c.first_name, ' ', c.last_name) AS counselor_name,
           c.department,
           c.contact_number,
           c.signature
    FROM session_notes sn
    JOIN counselors c ON sn.counselor_id = c.counselor_id
    WHERE sn.note_id    = $note_id
      AND sn.student_id = $sid
      AND sn.is_sent    = 1
    LIMIT 1
");
$note = $res ? $res->fetch_assoc() : null;
if (!$note) die("Session note not found or access denied.");

// Student info
$stRes   = $conn->query("SELECT * FROM students WHERE student_id = $sid LIMIT 1");
$student = $stRes->fetch_assoc();

$profRes = $conn->query("SELECT contact_details FROM student_profiles WHERE student_id = $sid LIMIT 1");
$profile = $profRes ? $profRes->fetch_assoc() : [];

$fullName  = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$email     = $student['email']      ?? '';
$yearLevel = $student['year_level'] ?? '';
$course    = $student['course']     ?? '';
$contact   = $profile['contact_details'] ?? 'N/A';

// Logo base64
$logoTag  = '';
$logoPath = __DIR__ . '/logo.png';
if (file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoTag  = '<img src="data:image/png;base64,' . $logoData . '" class="sReportsPdf-logo" alt="UNITYCARE">';
}

// Counselor signature base64
$sigTag   = '';
$sigPaths = [];
if (!empty($note['signature'])) $sigPaths[] = __DIR__ . '/' . $note['signature'];
$sigPaths[] = __DIR__ . '/images/signature.png';
foreach ($sigPaths as $sp) {
    if (file_exists($sp)) {
        $ext     = strtolower(pathinfo($sp, PATHINFO_EXTENSION));
        $mime    = ($ext === 'png') ? 'image/png' : 'image/jpeg';
        $sigData = base64_encode(file_get_contents($sp));
        $sigTag  = '<img src="data:' . $mime . ';base64,' . $sigData . '" class="sReportsPdf-sig" alt="Signature">';
        break;
    }
}

// Formatted values
$noteDate       = date('F d, Y', strtotime($note['created_at']));
$noteTime       = date('g:i A',  strtotime($note['created_at']));
$notesBody      = nl2br(htmlspecialchars($note['notes']));
$counselorName  = htmlspecialchars($note['counselor_name']);
$department     = htmlspecialchars($note['department']);
$counselorPhone = htmlspecialchars($note['contact_number'] ?? '');
$noteRef        = 'SN-' . str_pad($note['note_id'], 5, '0', STR_PAD_LEFT);

// ── HTML ──────────────────────────────────────────────────────────────────────
$html = '
<!DOCTYPE html>
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
    padding: 36px 44px;
  }

  /* ── HEADER ── */
  .sReportsPdf-header {
    display: table;
    width: 100%;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 3px solid #113f67;
  }
  .sReportsPdf-header-logo {
    display: table-cell;
    width: 58px;
    vertical-align: middle;
  }
  .sReportsPdf-logo {
    width: 52px;
    height: 52px;
    border-radius: 10px;
  }
  .sReportsPdf-header-brand {
    display: table-cell;
    vertical-align: middle;
    padding-left: 12px;
  }
  .sReportsPdf-header-brand h1 {
    font-size: 20px;
    font-weight: 700;
    color: #113f67;
    letter-spacing: 1.5px;
  }
  .sReportsPdf-header-brand p {
    font-size: 10px;
    color: #64748b;
    margin-top: 2px;
  }
  .sReportsPdf-header-meta {
    display: table-cell;
    vertical-align: middle;
    text-align: right;
    font-size: 11px;
    color: #64748b;
    line-height: 1.8;
  }
  .sReportsPdf-header-meta strong {
    display: block;
    font-size: 12px;
    color: #0f172a;
  }

  /* ── SLIP TITLE ── */
  .sReportsPdf-title {
    text-align: center;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: #113f67;
    padding: 10px 0;
    margin-bottom: 20px;
    border-top: 1px solid #dce3ec;
    border-bottom: 1px solid #dce3ec;
  }

  /* ── CONFIDENTIAL BADGE ── */
  .sReportsPdf-ref-row {
    text-align: right;
    margin-bottom: 20px;
  }
  .sReportsPdf-ref-badge {
    display: inline-block;
    background: #eef4fb;
    color: #4988c4;
    font-size: 10px;
    font-weight: 700;
    padding: 4px 12px;
    border-radius: 20px;
    letter-spacing: 0.8px;
  }

  /* ── SECTION ── */
  .sReportsPdf-section { margin-bottom: 22px; }

  .sReportsPdf-section-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.2px;
    color: #ffffff;
    background: #113f67;
    padding: 6px 14px;
    margin-bottom: 14px;
    border-radius: 6px;
  }

  .sReportsPdf-section-body { padding: 0 6px; }

  /* ── INFO ROWS ── */
  .sReportsPdf-row {
    display: table;
    width: 100%;
    margin-bottom: 9px;
  }
  .sReportsPdf-row-label {
    display: table-cell;
    width: 145px;
    font-weight: 700;
    font-size: 12px;
    color: #113f67;
    vertical-align: top;
    padding-top: 1px;
  }
  .sReportsPdf-row-colon {
    display: table-cell;
    width: 12px;
    color: #94a3b8;
    vertical-align: top;
    padding-top: 1px;
  }
  .sReportsPdf-row-value {
    display: table-cell;
    color: #0f172a;
    vertical-align: top;
    font-size: 12px;
    line-height: 1.5;
  }

  /* ── NOTES TEXT BOX ── */
  .sReportsPdf-box-label {
    font-weight: 700;
    font-size: 12px;
    color: #113f67;
    margin-bottom: 6px;
  }
  .sReportsPdf-text-box {
    border: 1px solid #dce3ec;
    border-left: 3px solid #4988c4;
    border-radius: 8px;
    padding: 12px 14px;
    background: #f7f9fc;
    color: #0f172a;
    line-height: 1.8;
    font-size: 12px;
  }

  /* ── SIGNATURE ── */
  .sReportsPdf-sig-area { margin: 8px 0 12px; }
  .sReportsPdf-sig {
    width: 160px;
    max-height: 80px;
    object-fit: contain;
  }
  .sReportsPdf-sig-line {
    width: 180px;
    border-bottom: 1px solid #0f172a;
    height: 50px;
    display: block;
  }

  /* ── FOOTER ── */
  .sReportsPdf-footer {
    margin-top: 40px;
    padding-top: 10px;
    border-top: 2px solid #113f67;
    text-align: center;
    font-size: 9px;
    color: #94a3b8;
    letter-spacing: 0.4px;
    line-height: 1.8;
  }
  .sReportsPdf-footer-badge {
    display: inline-block;
    background: #eef4fb;
    color: #4988c4;
    font-size: 9px;
    font-weight: 700;
    padding: 3px 10px;
    border-radius: 20px;
    letter-spacing: 0.8px;
    margin-bottom: 4px;
  }

</style>
</head>
<body>

  <!-- HEADER -->
  <div class="sReportsPdf-header">
    <div class="sReportsPdf-header-logo">' . $logoTag . '</div>
    <div class="sReportsPdf-header-brand">
      <h1>UNITYCARE</h1>
      <p>Guidance &amp; Counseling Services</p>
    </div>
    <div class="sReportsPdf-header-meta">
      Reference No.<br>
      <strong>' . $noteRef . '</strong>
      Date Issued<br>
      <strong>' . $noteDate . ' &bull; ' . $noteTime . '</strong>
    </div>
  </div>

  <!-- TITLE -->
  <div class="sReportsPdf-title">Session Notes</div>

  <!-- CONFIDENTIAL BADGE -->
  <div class="sReportsPdf-ref-row">
    <span class="sReportsPdf-ref-badge">CONFIDENTIAL</span>
  </div>

  <!-- STUDENT INFORMATION -->
  <div class="sReportsPdf-section">
    <div class="sReportsPdf-section-label">Student Information</div>
    <div class="sReportsPdf-section-body">

      <div class="sReportsPdf-row">
        <div class="sReportsPdf-row-label">Full Name</div>
        <div class="sReportsPdf-row-colon">:</div>
        <div class="sReportsPdf-row-value">' . htmlspecialchars($fullName) . '</div>
      </div>

      <div class="sReportsPdf-row">
        <div class="sReportsPdf-row-label">Year Level</div>
        <div class="sReportsPdf-row-colon">:</div>
        <div class="sReportsPdf-row-value">' . htmlspecialchars($yearLevel) . '</div>
      </div>

      <div class="sReportsPdf-row">
        <div class="sReportsPdf-row-label">Program / Course</div>
        <div class="sReportsPdf-row-colon">:</div>
        <div class="sReportsPdf-row-value">' . htmlspecialchars($course) . '</div>
      </div>

      <div class="sReportsPdf-row">
        <div class="sReportsPdf-row-label">Email Address</div>
        <div class="sReportsPdf-row-colon">:</div>
        <div class="sReportsPdf-row-value">' . htmlspecialchars($email) . '</div>
      </div>

      <div class="sReportsPdf-row">
        <div class="sReportsPdf-row-label">Contact Number</div>
        <div class="sReportsPdf-row-colon">:</div>
        <div class="sReportsPdf-row-value">' . htmlspecialchars($contact) . '</div>
      </div>

    </div>
  </div>

  <!-- SESSION NOTES -->
  <div class="sReportsPdf-section">
    <div class="sReportsPdf-section-label">Session Notes</div>
    <div class="sReportsPdf-section-body">
      <div class="sReportsPdf-box-label">Counselor Notes</div>
      <div class="sReportsPdf-text-box">' . $notesBody . '</div>
    </div>
  </div>

  <!-- ISSUED BY -->
  <div class="sReportsPdf-section">
    <div class="sReportsPdf-section-label">Issued By</div>
    <div class="sReportsPdf-section-body">

      <div class="sReportsPdf-sig-area">
        ' . ($sigTag ?: '<span class="sReportsPdf-sig-line"></span>') . '
      </div>

      <div class="sReportsPdf-row">
        <div class="sReportsPdf-row-label">Counselor</div>
        <div class="sReportsPdf-row-colon">:</div>
        <div class="sReportsPdf-row-value">' . $counselorName . '</div>
      </div>

      <div class="sReportsPdf-row">
        <div class="sReportsPdf-row-label">Office / Department</div>
        <div class="sReportsPdf-row-colon">:</div>
        <div class="sReportsPdf-row-value">' . $department . '</div>
      </div>

      ' . (!empty($counselorPhone) ? '
      <div class="sReportsPdf-row">
        <div class="sReportsPdf-row-label">Contact</div>
        <div class="sReportsPdf-row-colon">:</div>
        <div class="sReportsPdf-row-value">' . $counselorPhone . '</div>
      </div>
      ' : '') . '

    </div>
  </div>

  <!-- FOOTER -->
  <div class="sReportsPdf-footer">
    <div class="sReportsPdf-footer-badge">OFFICIAL DOCUMENT</div><br>
    This session note is a confidential record issued by the Guidance &amp; Counseling Services Office
    &bull; UNITYCARE System &bull; ' . date('Y') . '
  </div>

</body>
</html>
';

// ── Render via dompdf ─────────────────────────────────────────────────────────
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'SessionNote_' . preg_replace('/\s+/', '_', trim($fullName)) . '_' . $noteRef . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) session_start();

// ===== GUARD =====
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: slogin.php");
    exit;
}

// ===== DOMPDF =====
require_once __DIR__ . '/vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

// ===== DB =====
$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");
$sid  = $conn->real_escape_string($_SESSION['user_id']);

// ===== STUDENT DATA =====
$studentRes = $conn->query("SELECT * FROM students WHERE student_id='$sid' LIMIT 1");
$student    = $studentRes->fetch_assoc();

$profileRes = $conn->query("SELECT contact_details, profile_image FROM student_profiles WHERE student_id='$sid' LIMIT 1");
$profile    = $profileRes->fetch_assoc();

$fullName  = ($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '');
$email     = $student['email'] ?? '';
$yearLevel = $student['year_level'] ?? '';
$course    = $student['course'] ?? '';
$contact   = $profile['contact_details'] ?? 'N/A';

// ===== LATEST REFERRAL (include counselor signature) =====
$referralRes = $conn->query("
    SELECT r.referral_date, r.reason, r.counselor_remarks,
           CONCAT(c.first_name, ' ', c.last_name) AS counselor_name,
           c.department,
           c.contact_number,
           c.signature
    FROM referrals r
    JOIN counselors c ON r.counselor_id = c.counselor_id
    WHERE r.student_id='$sid'
    ORDER BY r.created_at DESC
    LIMIT 1
");
$referral = $referralRes ? $referralRes->fetch_assoc() : null;

if (!$referral) {
    die("No referral found.");
}

// ===== LOGO — base64 =====
$logoTag  = '';
$logoPath = __DIR__ . '/logo.png';
if (file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoTag  = '<img src="data:image/png;base64,' . $logoData . '" class="logo" alt="UNITYCARE">';
}

// ===== SIGNATURE — dynamic per counselor, base64 =====
// Priority: counselor's uploaded signature → default images/signature.png → blank line
$sigTag   = '';
$sigPaths = [];

// 1. Counselor's own signature stored in DB
if (!empty($referral['signature'])) {
    $sigPaths[] = __DIR__ . '/' . $referral['signature'];
}
// 2. Fallback to shared default
$sigPaths[] = __DIR__ . '/images/signature.png';

foreach ($sigPaths as $sigPath) {
    if (file_exists($sigPath)) {
        $ext     = strtolower(pathinfo($sigPath, PATHINFO_EXTENSION));
        $mime    = ($ext === 'png') ? 'image/png' : 'image/jpeg';
        $sigData = base64_encode(file_get_contents($sigPath));
        $sigTag  = '<img src="data:' . $mime . ';base64,' . $sigData . '" class="sig" alt="Signature">';
        break;
    }
}

// ===== FORMAT =====
$referralDate     = date('F d, Y', strtotime($referral['referral_date']));
$reason           = nl2br(htmlspecialchars($referral['reason']));
$counselorRemarks = !empty($referral['counselor_remarks'])
                    ? nl2br(htmlspecialchars($referral['counselor_remarks']))
                    : '';
$counselorName    = htmlspecialchars($referral['counselor_name']);
$department       = htmlspecialchars($referral['department']);
$counselorContact = htmlspecialchars($referral['contact_number'] ?? '');

// ===== HTML TEMPLATE =====
$html = '
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    font-family: DejaVu Sans, sans-serif;
    font-size: 12px;
    color: #0f172a;
    background: #ffffff;
    padding: 36px 44px;
  }

  /* ── HEADER ── */
  .header {
    display: table;
    width: 100%;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 3px solid #113f67;
  }
  .header-logo {
    display: table-cell;
    width: 58px;
    vertical-align: middle;
  }
  .logo {
    width: 52px;
    height: 52px;
    border-radius: 10px;
  }
  .header-brand {
    display: table-cell;
    vertical-align: middle;
    padding-left: 12px;
  }
  .header-brand h1 {
    font-size: 20px;
    font-weight: 700;
    color: #113f67;
    letter-spacing: 1.5px;
  }
  .header-brand p {
    font-size: 10px;
    color: #64748b;
    margin-top: 2px;
  }
  .header-date {
    display: table-cell;
    vertical-align: middle;
    text-align: right;
    font-size: 11px;
    color: #64748b;
  }
  .header-date strong {
    color: #0f172a;
    font-size: 12px;
  }

  /* ── SLIP TITLE ── */
  .slip-title {
    text-align: center;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 4px;
    text-transform: uppercase;
    color: #113f67;
    padding: 10px 0;
    margin-bottom: 24px;
    border-top: 1px solid #dce3ec;
    border-bottom: 1px solid #dce3ec;
  }

  /* ── SECTION ── */
  .section { margin-bottom: 22px; }

  .section-label {
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

  .section-body { padding: 0 6px; }

  /* ── INFO ROWS ── */
  .row {
    display: table;
    width: 100%;
    margin-bottom: 9px;
  }
  .row-label {
    display: table-cell;
    width: 145px;
    font-weight: 700;
    font-size: 12px;
    color: #113f67;
    vertical-align: top;
    padding-top: 1px;
  }
  .row-colon {
    display: table-cell;
    width: 12px;
    color: #94a3b8;
    vertical-align: top;
    padding-top: 1px;
  }
  .row-value {
    display: table-cell;
    color: #0f172a;
    vertical-align: top;
    font-size: 12px;
    line-height: 1.5;
  }

  /* ── TEXT BOXES ── */
  .box-label {
    font-weight: 700;
    font-size: 12px;
    color: #113f67;
    margin-bottom: 5px;
  }
  .text-box {
    border: 1px solid #dce3ec;
    border-left: 3px solid #4988c4;
    border-radius: 8px;
    padding: 10px 14px;
    background: #f7f9fc;
    color: #0f172a;
    line-height: 1.75;
    font-size: 12px;
  }

  /* ── DIVIDER ── */
  hr {
    border: none;
    border-top: 1px solid #dce3ec;
    margin: 18px 0;
  }

  /* ── SIGNATURE ── */
  .sig-area { margin: 8px 0 12px; }
  .sig {
    width: 160px;
    max-height: 80px;
    object-fit: contain;
  }
  .sig-line {
    width: 180px;
    border-bottom: 1px solid #0f172a;
    height: 50px;
    display: block;
  }

  /* ── FOOTER ── */
  .footer {
    margin-top: 40px;
    padding-top: 10px;
    border-top: 2px solid #113f67;
    text-align: center;
    font-size: 9px;
    color: #94a3b8;
    letter-spacing: 0.4px;
    line-height: 1.8;
  }
  .slip-badge {
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
  <div class="header">
    <div class="header-logo">' . $logoTag . '</div>
    <div class="header-brand">
      <h1>UNITYCARE</h1>
      <p>Guidance &amp; Counseling Services</p>
    </div>
    <div class="header-date">
      Date Issued<br>
      <strong>' . $referralDate . '</strong>
    </div>
  </div>

  <!-- TITLE -->
  <div class="slip-title">Referral Slip</div>

  <!-- STUDENT INFORMATION -->
  <div class="section">
    <div class="section-label">Student Information</div>
    <div class="section-body">

      <div class="row">
        <div class="row-label">Full Name</div>
        <div class="row-colon">:</div>
        <div class="row-value">' . htmlspecialchars($fullName) . '</div>
      </div>

      <div class="row">
        <div class="row-label">Year Level</div>
        <div class="row-colon">:</div>
        <div class="row-value">' . htmlspecialchars($yearLevel) . '</div>
      </div>

      <div class="row">
        <div class="row-label">Program / Course</div>
        <div class="row-colon">:</div>
        <div class="row-value">' . htmlspecialchars($course) . '</div>
      </div>

      <div class="row">
        <div class="row-label">Email Address</div>
        <div class="row-colon">:</div>
        <div class="row-value">' . htmlspecialchars($email) . '</div>
      </div>

      <div class="row">
        <div class="row-label">Contact Number</div>
        <div class="row-colon">:</div>
        <div class="row-value">' . htmlspecialchars($contact) . '</div>
      </div>

    </div>
  </div>

  <!-- REFERRAL DETAILS -->
  <div class="section">
    <div class="section-label">Referral Details</div>
    <div class="section-body">

      <div class="box-label">Reason for Referral</div>
      <div class="text-box">' . $reason . '</div>

      ' . (!empty($counselorRemarks) ? '
      <br>
      <div class="box-label">Counselor Remarks</div>
      <div class="text-box">' . $counselorRemarks . '</div>
      ' : '') . '

    </div>
  </div>

  <!-- REFERRED BY -->
  <div class="section">
    <div class="section-label">Referred By</div>
    <div class="section-body">

      <!-- Counselor signature (dynamic) -->
      <div class="sig-area">
        ' . ($sigTag ?: '<span class="sig-line"></span>') . '
      </div>

      <div class="row">
        <div class="row-label">Counselor</div>
        <div class="row-colon">:</div>
        <div class="row-value">' . $counselorName . '</div>
      </div>

      <div class="row">
        <div class="row-label">Office / Department</div>
        <div class="row-colon">:</div>
        <div class="row-value">' . $department . '</div>
      </div>

      ' . (!empty($counselorContact) ? '
      <div class="row">
        <div class="row-label">Contact</div>
        <div class="row-colon">:</div>
        <div class="row-value">' . $counselorContact . '</div>
      </div>
      ' : '') . '

    </div>
  </div>

  <!-- FOOTER -->
  <div class="footer">
    <div class="slip-badge">OFFICIAL DOCUMENT</div><br>
    This referral slip is an official record issued by the Guidance &amp; Counseling Services Office
    &bull; UNITYCARE System &bull; ' . date('Y') . '
  </div>

</body>
</html>
';

// ===== DOMPDF RENDER =====
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('isHtml5ParserEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = 'ReferralSlip_' . preg_replace('/\s+/', '_', trim($fullName)) . '_' . date('Ymd') . '.pdf';
$dompdf->stream($filename, ['Attachment' => true]);
exit;
?>
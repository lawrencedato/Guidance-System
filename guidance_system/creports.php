<?php
error_reporting(0);
ini_set('display_errors', 0);
mysqli_report(MYSQLI_REPORT_OFF);

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'counselor') {
    header("Location: slogin.php");
    exit;
}

$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");
$cid  = $conn->real_escape_string($_SESSION['user_id']);

$counselorRes = $conn->query("SELECT * FROM counselors WHERE counselor_id='$cid' LIMIT 1");
$counselor    = $counselorRes->fetch_assoc();

$fullName   = htmlspecialchars(($counselor['first_name'] ?? '') . ' ' . ($counselor['last_name'] ?? ''));
$email      = htmlspecialchars($counselor['email'] ?? '');
$profileImg = !empty($counselor['profile_image'])
    ? htmlspecialchars($counselor['profile_image'])
    : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=113f67&color=fff';

$pendingCount = (int)$conn->query(
    "SELECT COUNT(*) c FROM appointments WHERE counselor_id='$cid' AND status='Pending'"
)->fetch_assoc()['c'];

// ── AJAX: lookup student by ID ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'lookup_student') {
    header('Content-Type: application/json');
    $sid = (int)($_GET['student_id'] ?? 0);
    if (!$sid) { echo json_encode(['found' => false]); exit; }

    $res = $conn->query("
        SELECT s.first_name, s.last_name, s.course, s.year_level, sp.profile_image
        FROM students s
        LEFT JOIN student_profiles sp ON sp.student_id = s.student_id
        WHERE s.student_id = '$sid' AND s.archived = 0
        LIMIT 1
    ");
    $st = $res ? $res->fetch_assoc() : null;

    if ($st) {
        $noteDatesRes = $conn->query("
            SELECT DATE(created_at) AS note_date, COUNT(*) AS cnt
            FROM session_notes
            WHERE counselor_id = '$cid' AND student_id = '$sid' AND is_sent = 1
            GROUP BY DATE(created_at)
            ORDER BY note_date DESC
        ");
        $noteDates = [];
        while ($nd = $noteDatesRes->fetch_assoc()) $noteDates[] = $nd;

        echo json_encode([
            'found'         => true,
            'name'          => $st['first_name'] . ' ' . $st['last_name'],
            'course'        => $st['course'],
            'year_level'    => $st['year_level'],
            'profile_image' => $st['profile_image'] ?? null,
            'note_dates'    => $noteDates
        ]);
    } else {
        echo json_encode(['found' => false]);
    }
    exit;
}

// ── AJAX: load notes for one student ──
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'load_notes') {
    header('Content-Type: application/json');
    $sid = (int)($_GET['student_id'] ?? 0);
    if (!$sid) { echo json_encode(['notes' => []]); exit; }

    $res = $conn->query("
        SELECT sn.note_id, sn.notes, sn.created_at,
               DATE(sn.created_at) AS note_date,
               CONCAT(s.first_name, ' ', s.last_name) AS student_name,
               s.course, s.year_level
        FROM session_notes sn
        JOIN students s ON sn.student_id = s.student_id
        WHERE sn.counselor_id = '$cid'
          AND sn.student_id   = '$sid'
          AND sn.is_sent = 1
        ORDER BY sn.created_at DESC
    ");
    $notes = [];
    while ($r = $res->fetch_assoc()) $notes[] = $r;
    echo json_encode(['notes' => $notes]);
    exit;
}

// ── POST: save notes ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_notes') {
    header('Content-Type: application/json');
    $notes      = $conn->real_escape_string(trim($_POST['notes'] ?? ''));
    $student_id = (int)($_POST['student_id'] ?? 0);

    if (!$student_id) { echo json_encode(['success' => false, 'message' => 'Please enter a valid Student ID.']); exit; }
    if (!$notes)      { echo json_encode(['success' => false, 'message' => 'Notes cannot be empty.']); exit; }

    $check = $conn->query("SELECT student_id FROM students WHERE student_id='$student_id' AND archived=0 LIMIT 1");
    if (!$check || $check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Student not found.']);
        exit;
    }

    $ok = $conn->query("
        INSERT INTO session_notes (counselor_id, student_id, notes, is_sent, created_at)
        VALUES ('$cid', '$student_id', '$notes', 1, NOW())
    ");
    echo json_encode($ok ? ['success' => true] : ['success' => false, 'message' => 'Failed to save.']);
    exit;
}

// ── Fetch student list with note counts ──
$studentListRes = $conn->query("
    SELECT s.student_id,
           CONCAT(s.first_name, ' ', s.last_name) AS student_name,
           s.first_name, s.last_name,
           s.course, s.year_level,
           sp.profile_image,
           COUNT(sn.note_id) AS note_count,
           MAX(sn.created_at) AS last_note
    FROM session_notes sn
    JOIN students s ON sn.student_id = s.student_id
    LEFT JOIN student_profiles sp ON sp.student_id = s.student_id
    WHERE sn.counselor_id = '$cid'
      AND sn.is_sent = 1
    GROUP BY s.student_id
    ORDER BY s.last_name ASC, s.first_name ASC
");
$studentList = [];
while ($r = $studentListRes->fetch_assoc()) $studentList[] = $r;
$totalStudents = count($studentList);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>UNITYCARE | Session Notes</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="logout.css">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
/* ─── Layout ─── */
.cr-shell {
  display: grid;
  grid-template-columns: 280px 1fr;
  height: calc(100vh - 72px);
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 14px;
  overflow: hidden;
  background: var(--card-bg, #fff);
}

/* ─── Left panel ─── */
.cr-left {
  display: flex;
  flex-direction: column;
  border-right: 1px solid var(--border, #e2e8f0);
  background: var(--surface, #f8fafc);
  overflow: hidden;
}
.cr-left-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 13px 14px 11px;
  border-bottom: 1px solid var(--border, #e2e8f0);
  flex-shrink: 0;
}
.cr-left-header-title {
  font-size: 13px;
  font-weight: 700;
  color: var(--text, #1e293b);
}
.cr-left-write-btn {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 5px 11px;
  background: #113f67;
  color: #fff;
  border: none;
  border-radius: 7px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.15s;
}
.cr-left-write-btn:hover { opacity: 0.85; }

/* ── Toast nudge (replaces modal for "Write Note" with no student) ── */
.cr-toast {
  position: fixed;
  bottom: 30px;
  left: 50%;
  transform: translateX(-50%) translateY(20px);
  background: #1e293b;
  color: #fff;
  font-size: 13px;
  font-weight: 500;
  padding: 10px 18px;
  border-radius: 10px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.2);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.2s, transform 0.2s;
  z-index: 9999;
  white-space: nowrap;
}
.cr-toast.show {
  opacity: 1;
  transform: translateX(-50%) translateY(0);
}

.cr-search-wrap {
  padding: 10px 12px;
  border-bottom: 1px solid var(--border, #e2e8f0);
  flex-shrink: 0;
  position: relative;
}
.cr-search-wrap i {
  position: absolute;
  left: 21px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 13px;
  color: var(--text-muted, #94a3b8);
  pointer-events: none;
}
.cr-search-wrap input {
  width: 100%;
  padding: 7px 10px 7px 30px;
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 8px;
  font-size: 13px;
  background: var(--card-bg, #fff);
  color: var(--text, #1e293b);
  outline: none;
}
.cr-search-wrap input:focus { border-color: #113f67; }
.cr-student-list {
  overflow-y: auto;
  flex: 1;
}
.cr-student-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px 14px;
  cursor: pointer;
  border-bottom: 1px solid var(--border, #e2e8f0);
  transition: background 0.12s;
  user-select: none;
}
.cr-student-item:hover { background: var(--hover-bg, #f0f6ff); }
.cr-student-item.active {
  background: #e8f0fb;
  border-left: 3px solid #113f67;
}
.cr-student-item.active .cr-si-name { color: #113f67; }
.cr-si-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  object-fit: cover;
  flex-shrink: 0;
}
.cr-si-avatar-fallback {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #113f67;
  color: #fff;
  font-size: 12px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.cr-si-body { flex: 1; min-width: 0; }
.cr-si-name {
  font-size: 13px;
  font-weight: 600;
  color: var(--text, #1e293b);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.cr-si-sub {
  font-size: 11px;
  color: var(--text-muted, #64748b);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.cr-si-badge {
  font-size: 10px;
  font-weight: 700;
  background: #113f67;
  color: #fff;
  padding: 2px 7px;
  border-radius: 20px;
  flex-shrink: 0;
}

/* ─── Right panel ─── */
.cr-right {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: var(--card-bg, #fff);
}
.cr-right-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 20px;
  border-bottom: 1px solid var(--border, #e2e8f0);
  flex-shrink: 0;
  gap: 12px;
}
.cr-rh-left {
  display: flex;
  align-items: center;
  gap: 12px;
}
.cr-rh-avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  object-fit: cover;
}
.cr-rh-avatar-fallback {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  background: #113f67;
  color: #fff;
  font-size: 13px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}
.cr-rh-title {
  font-size: 15px;
  font-weight: 700;
  color: var(--text, #1e293b);
}
.cr-rh-sub {
  font-size: 12px;
  color: var(--text-muted, #64748b);
}
.cr-rh-add-btn {
  display: none;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  background: #113f67;
  color: #fff;
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: opacity 0.15s, background 0.15s;
  flex-shrink: 0;
}
.cr-rh-add-btn:hover { opacity: 0.85; }
.cr-rh-add-btn.active {
  background: #e8f0fb;
  color: #113f67;
}

/* ─── Feed ─── */
.cr-feed {
  flex: 1;
  overflow-y: auto;
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 0;
}
.cr-placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  gap: 10px;
  color: var(--text-muted, #94a3b8);
}
.cr-placeholder i { font-size: 38px; opacity: 0.35; }
.cr-placeholder p { font-size: 14px; }
.cr-loading {
  display: none;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  flex: 1;
  gap: 10px;
  color: var(--text-muted, #94a3b8);
  font-size: 13px;
}
.cr-date-divider {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 18px 0 10px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--text-muted, #94a3b8);
}
.cr-date-divider::before,
.cr-date-divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--border, #e2e8f0);
}
.cr-date-divider:first-child { margin-top: 0; }

/* Note card */
.cr-note-card {
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 10px;
  padding: 13px 15px;
  margin-bottom: 8px;
  background: var(--card-bg, #fff);
  cursor: pointer;
  transition: border-color 0.15s, box-shadow 0.15s;
}
.cr-note-card:hover {
  border-color: #113f67;
  box-shadow: 0 2px 10px rgba(17,63,103,0.08);
}
.cr-note-card.expanded { border-color: #113f67; }
.cr-note-meta {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 8px;
}
.cr-note-ref {
  font-size: 10px;
  font-weight: 700;
  color: #113f67;
  background: #e8f0fb;
  padding: 2px 8px;
  border-radius: 20px;
  letter-spacing: 0.4px;
}
.cr-note-time {
  font-size: 11px;
  color: var(--text-muted, #94a3b8);
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 4px;
}
.cr-note-body {
  font-size: 13px;
  color: var(--text, #334155);
  line-height: 1.6;
  white-space: pre-wrap;
  word-break: break-word;
}
.cr-note-body.clamped {
  display: -webkit-box;
  line-clamp: 2;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  white-space: normal;
}
.cr-note-toggle {
  margin-top: 6px;
  font-size: 11px;
  font-weight: 700;
  color: #113f67;
  background: none;
  border: none;
  cursor: pointer;
  padding: 0;
  display: block;
}
.cr-empty-feed {
  text-align: center;
  padding: 40px;
  color: var(--text-muted, #94a3b8);
  font-size: 14px;
}
.cr-list-empty {
  padding: 30px 14px;
  text-align: center;
  color: var(--text-muted, #94a3b8);
  font-size: 13px;
}

/* ─── Inline Compose Panel ─── */
.cr-compose-wrap {
  flex-shrink: 0;
  border-top: 1px solid var(--border, #e2e8f0);
  background: var(--surface, #f8fafc);
  overflow: hidden;
  max-height: 0;
  transition: max-height 0.28s cubic-bezier(0.4, 0, 0.2, 1),
              padding 0.28s cubic-bezier(0.4, 0, 0.2, 1);
}
.cr-compose-wrap.open {
  max-height: 320px;
}
.cr-compose-inner {
  padding: 14px 18px 16px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.cr-compose-label {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: var(--text-muted, #64748b);
}
.cr-compose-textarea {
  width: 100%;
  min-height: 100px;
  max-height: 160px;
  resize: vertical;
  padding: 10px 12px;
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 9px;
  font-size: 13px;
  color: var(--text, #1e293b);
  background: var(--card-bg, #fff);
  font-family: inherit;
  line-height: 1.6;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  box-sizing: border-box;
}
.cr-compose-textarea:focus {
  border-color: #113f67;
  box-shadow: 0 0 0 3px rgba(17,63,103,0.08);
}
.cr-compose-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}
.cr-compose-status {
  font-size: 12px;
  flex: 1;
}
.cr-compose-actions {
  display: flex;
  gap: 8px;
}
.cr-btn-cancel {
  padding: 7px 14px;
  border: 1px solid var(--border, #e2e8f0);
  border-radius: 8px;
  background: none;
  font-size: 12px;
  color: var(--text-muted, #64748b);
  cursor: pointer;
  font-weight: 600;
  transition: background 0.12s;
}
.cr-btn-cancel:hover { background: var(--hover-bg, #f0f6ff); }
.cr-btn-send {
  padding: 7px 16px;
  border: none;
  border-radius: 8px;
  background: #113f67;
  color: #fff;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: opacity 0.15s;
}
.cr-btn-send:disabled { opacity: 0.45; cursor: not-allowed; }
.cr-btn-send:not(:disabled):hover { opacity: 0.85; }
/* ═══════════════════════════════════════════
   cr-shell (Session Notes) — DARK MODE
═══════════════════════════════════════════ */

[data-theme="dark"] .cr-shell {
  background: rgba(20, 20, 20, 0.55);
  border-color: rgba(255, 255, 255, 0.08);
}

/* ── Left panel ── */
[data-theme="dark"] .cr-left {
  background: #0f1829;
  border-right-color: rgba(255, 255, 255, 0.07);
}

[data-theme="dark"] .cr-left-header {
  border-bottom-color: rgba(255, 255, 255, 0.07);
}

[data-theme="dark"] .cr-left-header-title {
  color: #e2eaf4;
}

[data-theme="dark"] .cr-left-write-btn {
  background: linear-gradient(135deg, #0d3254, #3a7ab8);
}

[data-theme="dark"] .cr-left-write-btn:hover {
  opacity: 1;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
}

/* ── Search ── */
[data-theme="dark"] .cr-search-wrap {
  border-bottom-color: rgba(255, 255, 255, 0.07);
}

[data-theme="dark"] .cr-search-wrap input {
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.1);
  color: #dce8f5;
}

[data-theme="dark"] .cr-search-wrap input::placeholder {
  color: #4a6680;
}

[data-theme="dark"] .cr-search-wrap input:focus {
  border-color: #4988C4;
}

[data-theme="dark"] .cr-search-wrap i {
  color: #4a6680;
}

/* ── Student list ── */
[data-theme="dark"] .cr-student-list::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .cr-student-item {
  border-bottom-color: rgba(255, 255, 255, 0.06);
}

[data-theme="dark"] .cr-student-item:hover {
  background: rgba(73, 136, 196, 0.08);
}

[data-theme="dark"] .cr-student-item.active {
  background: rgba(73, 136, 196, 0.15);
  border-left-color: #4988C4;
}

[data-theme="dark"] .cr-student-item.active .cr-si-name {
  color: #93c5fd;
}

[data-theme="dark"] .cr-si-avatar-fallback {
  background: #1a56a0;
}

[data-theme="dark"] .cr-si-name {
  color: #dce8f5;
}

[data-theme="dark"] .cr-si-sub {
  color: #6e8ea8;
}

[data-theme="dark"] .cr-si-badge {
  background: #1a56a0;
}

[data-theme="dark"] .cr-list-empty {
  color: #6e8ea8;
}

/* ── Right panel ── */
[data-theme="dark"] .cr-right {
  background: #0b1220;
}

[data-theme="dark"] .cr-right-header {
  border-bottom-color: rgba(255, 255, 255, 0.07);
  background: #0f1829;
}

[data-theme="dark"] .cr-rh-avatar-fallback {
  background: #1a56a0;
  color: #fff;
}

[data-theme="dark"] .cr-rh-title {
  color: #e2eaf4;
}

[data-theme="dark"] .cr-rh-sub {
  color: #8ba4be;
}

[data-theme="dark"] .cr-rh-add-btn {
  background: linear-gradient(135deg, #0d3254, #3a7ab8);
}

[data-theme="dark"] .cr-rh-add-btn:hover {
  opacity: 1;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
}

[data-theme="dark"] .cr-rh-add-btn.active {
  background: rgba(73, 136, 196, 0.15);
  color: #93c5fd;
}

/* ── Feed ── */
[data-theme="dark"] .cr-feed::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
}

[data-theme="dark"] .cr-placeholder {
  color: #6e8ea8;
}

[data-theme="dark"] .cr-loading {
  color: #6e8ea8;
}

[data-theme="dark"] .cr-date-divider {
  color: #6e8ea8;
}

[data-theme="dark"] .cr-date-divider::before,
[data-theme="dark"] .cr-date-divider::after {
  background: rgba(255, 255, 255, 0.08);
}

/* ── Note cards ── */
[data-theme="dark"] .cr-note-card {
  background: rgba(255, 255, 255, 0.03);
  border-color: rgba(255, 255, 255, 0.07);
}

[data-theme="dark"] .cr-note-card:hover {
  border-color: #4988C4;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}

[data-theme="dark"] .cr-note-card.expanded {
  border-color: #4988C4;
}

[data-theme="dark"] .cr-note-ref {
  background: rgba(73, 136, 196, 0.15);
  color: #93c5fd;
}

[data-theme="dark"] .cr-note-time {
  color: #6e8ea8;
}

[data-theme="dark"] .cr-note-body {
  color: #c4d6e8;
}

[data-theme="dark"] .cr-note-toggle {
  color: #60a5fa;
}

[data-theme="dark"] .cr-empty-feed {
  color: #6e8ea8;
}

/* ── Compose panel ── */
[data-theme="dark"] .cr-compose-wrap {
  background: #0f1829;
  border-top-color: rgba(255, 255, 255, 0.07);
}

[data-theme="dark"] .cr-compose-label {
  color: #6e8ea8;
}

[data-theme="dark"] .cr-compose-textarea {
  background: rgba(255, 255, 255, 0.05);
  border-color: rgba(255, 255, 255, 0.1);
  color: #dce8f5;
}

[data-theme="dark"] .cr-compose-textarea::placeholder {
  color: #4a6680;
}

[data-theme="dark"] .cr-compose-textarea:focus {
  border-color: #4988C4;
  box-shadow: 0 0 0 3px rgba(73, 136, 196, 0.2);
}

[data-theme="dark"] .cr-btn-cancel {
  border-color: rgba(255, 255, 255, 0.1);
  color: #94a3b8;
  background: rgba(255, 255, 255, 0.04);
}

[data-theme="dark"] .cr-btn-cancel:hover {
  background: rgba(255, 255, 255, 0.09);
}

[data-theme="dark"] .cr-btn-send {
  background: linear-gradient(135deg, #0d3254, #3a7ab8);
}

[data-theme="dark"] .cr-btn-send:not(:disabled):hover {
  opacity: 1;
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
}

/* ── Toast ── */
[data-theme="dark"] .cr-toast {
  background: #0f1829;
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #dce8f5;
}
</style>
</head>

<body class="body">

<!-- SIDEBAR -->
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
        <a href="cprofile.php"><i class="fa fa-user"></i> Profile</a>
        <a href="chistory.php"><i class="fa fa-clock"></i> History</a>
        <button onclick="toggleTheme()"><i class="fa fa-moon"></i> Theme</button>
        <button onclick="logout()"><i class="fa fa-right-from-bracket"></i> Logout</button>
      </div>
    </div>
  </div>
  <nav class="sidebar-menu">
    <a href="counselor.php"><i class="fa fa-gauge"></i> Dashboard</a>
    <p class="sidebar-title">SESSIONS</p>
    <a href="cappointments.php"><i class="fa fa-calendar-plus"></i> Appointment Requests</a>
    <a href="cavailability.php"><i class="fa fa-clock"></i> Time Availability</a>
    <a href="cconcerns.php"><i class="fa fa-triangle-exclamation"></i> Student Concerns</a>
    <a href="cfeedback.php"><i class="fa fa-comment"></i> Session Feedback</a>
    <p class="sidebar-title">STUDENTS</p>
    <a href="cstudents.php"><i class="fa fa-users"></i> Students</a>
    <p class="sidebar-title">REPORTS</p>
    <a href="creports.php" class="active"><i class="fa fa-file"></i> Session Notes</a>
    <p class="sidebar-title">INFORMATION</p>
    <a href="cannouncements.php"><i class="fa fa-bullhorn"></i> Announcements</a>
    <a href="creferral.php"><i class="fa fa-route"></i> Referrals</a>
  </nav>
</aside>

<!-- TOPBAR -->
<header class="topbar">
  <div class="topbar-left">
    <h2>Session Notes</h2>
  </div>
  <div class="topbar-right">
    <div class="topbar-icon" onclick="toggleDropdown('notifDropdown', event)">
      <i class="fa fa-bell"></i>
      <?php if ($pendingCount > 0): ?>
        <span class="badge"><?= $pendingCount ?></span>
      <?php endif; ?>
      <div class="icon-dropdown" id="notifDropdown">
        <?php if ($pendingCount > 0): ?>
          <p><?= $pendingCount ?> pending appointment request(s)</p>
        <?php else: ?>
          <p>No new notifications</p>
        <?php endif; ?>
      </div>
    </div>
    <div class="topbar-user">
      <img src="<?= $profileImg ?>" alt="user"
           onerror="this.src='https://ui-avatars.com/api/?name=<?= urlencode($fullName) ?>&background=113f67&color=fff'">
      <div>
        <strong><?= $fullName ?></strong>
        <p><?= $email ?></p>
      </div>
    </div>
  </div>
</header>

<!-- MAIN -->
<main class="cReports-main" style="padding: 20px; height: calc(100vh - 72px); display: flex; flex-direction: column;">

  <div class="cr-shell" style="flex: 1; min-height: 0;">

    <!-- ── LEFT PANEL ── -->
    <div class="cr-left">

      <!-- Header — Write Note prompts select-a-student if none active -->
      <div class="cr-left-header">
        <span class="cr-left-header-title">Students</span>
        <button class="cr-left-write-btn" onclick="leftWriteNote()">
          <i class="fa fa-pen"></i> Write Note
        </button>
      </div>

      <!-- Search -->
      <div class="cr-search-wrap">
        <i class="fa fa-search"></i>
        <input type="text" id="studentSearch" placeholder="Search student..." oninput="filterStudents(this.value)">
      </div>

      <!-- Student list -->
      <div class="cr-student-list" id="studentList">
        <?php if (empty($studentList)): ?>
          <div class="cr-list-empty">No sent notes yet.</div>
        <?php else: foreach ($studentList as $s):
          $sImg     = !empty($s['profile_image']) ? htmlspecialchars($s['profile_image']) : null;
          $initials = strtoupper(substr($s['first_name'],0,1) . substr($s['last_name'],0,1));
          $sMeta    = htmlspecialchars($s['year_level'] . ' · ' . $s['course']);
          $sName    = htmlspecialchars($s['student_name']);
        ?>
        <div class="cr-student-item"
             data-sid="<?= $s['student_id'] ?>"
             data-name="<?= $sName ?>"
             data-meta="<?= $sMeta ?>"
             data-initials="<?= $initials ?>"
             data-img="<?= $sImg ? htmlspecialchars($sImg) : '' ?>"
             onclick="selectStudent(this)">
          <?php if ($sImg): ?>
            <img class="cr-si-avatar" src="<?= $sImg ?>"
                 onerror="this.outerHTML='<div class=\'cr-si-avatar-fallback\'><?= $initials ?></div>'"
                 alt="avatar">
          <?php else: ?>
            <div class="cr-si-avatar-fallback"><?= $initials ?></div>
          <?php endif; ?>
          <div class="cr-si-body">
            <div class="cr-si-name"><?= $sName ?></div>
            <div class="cr-si-sub"><?= $sMeta ?></div>
          </div>
          <span class="cr-si-badge"><?= (int)$s['note_count'] ?></span>
        </div>
        <?php endforeach; endif; ?>
      </div>

    </div>
    <!-- END LEFT -->

    <!-- ── RIGHT PANEL ── -->
    <div class="cr-right">

      <!-- Header -->
      <div class="cr-right-header">
        <div class="cr-rh-left">
          <div class="cr-rh-avatar-fallback" id="rhAvatar">—</div>
          <div>
            <div class="cr-rh-title" id="rhTitle">Select a student</div>
            <div class="cr-rh-sub" id="rhSub">Click any student on the left to view their notes</div>
          </div>
        </div>
        <button class="cr-rh-add-btn" id="rhAddBtn" onclick="toggleCompose()">
          <i class="fa fa-plus" id="rhAddIcon"></i>
          <span id="rhAddLabel">Add Note</span>
        </button>
      </div>

      <!-- Feed area -->
      <div class="cr-feed" id="noteFeed">
        <div class="cr-placeholder" id="feedPlaceholder">
          <i class="fa fa-notes-medical"></i>
          <p><?= $totalStudents > 0 ? 'Choose a student to view their session notes.' : 'No session notes sent yet.' ?></p>
        </div>
        <div class="cr-loading" id="feedLoading">
          <i class="fa fa-spinner fa-spin"></i> Loading notes...
        </div>
      </div>

      <!-- ── Inline Compose Panel ── -->
      <div class="cr-compose-wrap" id="composeWrap">
        <div class="cr-compose-inner">
          <div class="cr-compose-label">New session note</div>
          <textarea class="cr-compose-textarea" id="composeTextarea"
                    placeholder="Write confidential notes for this session..."></textarea>
          <div class="cr-compose-footer">
            <div class="cr-compose-status" id="composeStatus"></div>
            <div class="cr-compose-actions">
              <button class="cr-btn-cancel" onclick="closeCompose()">Cancel</button>
              <button class="cr-btn-send" id="composeSendBtn" onclick="saveNotes()">
                <i class="fa fa-paper-plane"></i> Send to Student
              </button>
            </div>
          </div>
        </div>
      </div>

    </div>
    <!-- END RIGHT -->

  </div>
</main>

<!-- Toast nudge -->
<div class="cr-toast" id="crToast"></div>

<!-- Logout Modal -->
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

<script>
(function() {
  const saved = localStorage.getItem("theme") || "light";
  document.documentElement.setAttribute("data-theme", saved);
})();

function toggleSettingsMenu(e) {
  e.stopPropagation();
  document.getElementById("settingsDropdown").classList.toggle("show");
}
function toggleTheme() {
  const html = document.documentElement;
  const t = html.getAttribute("data-theme") === "light" ? "dark" : "light";
  html.setAttribute("data-theme", t);
  localStorage.setItem("theme", t);
}
function toggleDropdown(id, e) {
  e.stopPropagation();
  document.getElementById(id).classList.toggle("show");
}
function logout()        { document.getElementById('logoutOverlay').classList.add('show'); }
function closeLogout()   { document.getElementById('logoutOverlay').classList.remove('show'); }
function confirmLogout() { window.location.href = 'logout.php?role=counselor'; }

document.getElementById('logoutOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeLogout();
});
document.addEventListener("click", e => {
  const menu = document.getElementById("settingsDropdown");
  const btn  = document.querySelector(".sidebar-settingsButton");
  if (menu && btn && !menu.contains(e.target) && !btn.contains(e.target))
    menu.classList.remove("show");
});

// ── Toast helper ──
let toastTimer = null;
function showToast(msg) {
  const t = document.getElementById('crToast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => t.classList.remove('show'), 2800);
}

// ── Student list filtering ──
function filterStudents(q) {
  document.querySelectorAll('.cr-student-item').forEach(item => {
    item.style.display = item.dataset.name.toLowerCase().includes(q.toLowerCase()) ? '' : 'none';
  });
}

// ── Left panel "Write Note": select student first ──
function leftWriteNote() {
  if (!selectedSid) {
    showToast('👈 Select a student first to write a note.');
    return;
  }
  openCompose();
}

// ── Compose panel state ──
let composeOpen = false;

function toggleCompose() {
  composeOpen ? closeCompose() : openCompose();
}

function openCompose() {
  composeOpen = true;
  document.getElementById('composeWrap').classList.add('open');
  document.getElementById('composeStatus').innerHTML = '';
  // Update Add Note button appearance
  document.getElementById('rhAddIcon').className = 'fa fa-xmark';
  document.getElementById('rhAddLabel').textContent = 'Cancel';
  document.getElementById('rhAddBtn').classList.add('active');
  setTimeout(() => document.getElementById('composeTextarea').focus(), 30);
}

function closeCompose() {
  composeOpen = false;
  document.getElementById('composeWrap').classList.remove('open');
  document.getElementById('composeTextarea').value = '';
  document.getElementById('composeStatus').innerHTML = '';
  document.getElementById('rhAddIcon').className = 'fa fa-plus';
  document.getElementById('rhAddLabel').textContent = 'Add Note';
  document.getElementById('rhAddBtn').classList.remove('active');
}

// ── Select student → load notes ──
let selectedSid = null;

function selectStudent(el) {
  document.querySelectorAll('.cr-student-item').forEach(i => i.classList.remove('active'));
  el.classList.add('active');

  const sid      = el.dataset.sid;
  const name     = el.dataset.name;
  const meta     = el.dataset.meta;
  const initials = el.dataset.initials;
  const img      = el.dataset.img;

  selectedSid = sid;

  // Close compose if open for a different student
  if (composeOpen) closeCompose();

  // Update right header avatar
  const avatarEl = document.getElementById('rhAvatar');
  if (img) {
    avatarEl.outerHTML = `<img class="cr-rh-avatar" id="rhAvatar" src="${img}"
      onerror="this.outerHTML='<div class=\'cr-rh-avatar-fallback\' id=\'rhAvatar\'>${initials}</div>'"
      alt="avatar">`;
  } else {
    if (avatarEl.tagName === 'IMG') {
      const fallback = document.createElement('div');
      fallback.className = 'cr-rh-avatar-fallback';
      fallback.id = 'rhAvatar';
      fallback.textContent = initials;
      avatarEl.replaceWith(fallback);
    } else {
      avatarEl.textContent = initials;
    }
  }
  document.getElementById('rhTitle').textContent = name;
  document.getElementById('rhSub').textContent   = meta;
  document.getElementById('rhAddBtn').style.display = 'flex';

  // Show loading
  const feed = document.getElementById('noteFeed');
  document.getElementById('feedPlaceholder').style.display = 'none';
  document.getElementById('feedLoading').style.display     = 'flex';
  feed.querySelectorAll('.cr-date-divider, .cr-note-card, .cr-empty-feed').forEach(n => n.remove());

  fetch(`creports.php?action=load_notes&student_id=${encodeURIComponent(sid)}`)
    .then(r => r.json())
    .then(json => {
      document.getElementById('feedLoading').style.display = 'none';
      renderFeed(json.notes || []);
    })
    .catch(() => {
      document.getElementById('feedLoading').style.display = 'none';
      const err = document.createElement('div');
      err.className = 'cr-empty-feed';
      err.textContent = 'Failed to load notes. Please try again.';
      feed.appendChild(err);
    });
}

function renderFeed(notes) {
  const feed = document.getElementById('noteFeed');
  feed.querySelectorAll('.cr-date-divider, .cr-note-card, .cr-empty-feed').forEach(n => n.remove());

  if (!notes.length) {
    const empty = document.createElement('div');
    empty.className = 'cr-empty-feed';
    empty.textContent = 'No notes found for this student.';
    feed.appendChild(empty);
    return;
  }

  const grouped = {};
  notes.forEach(n => {
    if (!grouped[n.note_date]) grouped[n.note_date] = [];
    grouped[n.note_date].push(n);
  });

  Object.entries(grouped).forEach(([date, dayNotes]) => {
    const label = new Date(date + 'T00:00:00').toLocaleDateString('en-US', {
      weekday: 'short', month: 'long', day: 'numeric', year: 'numeric'
    });
    const div = document.createElement('div');
    div.className = 'cr-date-divider';
    div.textContent = label;
    feed.appendChild(div);

    dayNotes.forEach(n => {
      const ref  = 'SN-' + String(n.note_id).padStart(5, '0');
      const time = new Date(n.created_at).toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
      const card = document.createElement('div');
      card.className = 'cr-note-card';
      card.innerHTML = `
        <div class="cr-note-meta">
          <span class="cr-note-ref">${ref}</span>
          <span class="cr-note-time"><i class="fa fa-clock"></i> ${time}</span>
        </div>
        <div class="cr-note-body clamped" id="nb-${n.note_id}">${escHtml(n.notes)}</div>
        <button class="cr-note-toggle" onclick="toggleNote(${n.note_id}, this)">Show more</button>
      `;
      feed.appendChild(card);
    });
  });
}

function toggleNote(id, btn) {
  const body    = document.getElementById('nb-' + id);
  const card    = btn.closest('.cr-note-card');
  const expanded = !body.classList.contains('clamped');
  body.classList.toggle('clamped', expanded);
  card.classList.toggle('expanded', !expanded);
  btn.textContent = expanded ? 'Show more' : 'Show less';
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── Save note ──
function saveNotes() {
  const status   = document.getElementById('composeStatus');
  const textarea = document.getElementById('composeTextarea');
  const sendBtn  = document.getElementById('composeSendBtn');
  const notes    = textarea.value.trim();

  if (!selectedSid) {
    status.innerHTML = "<span style='color:#e53e3e;'>⚠ No student selected.</span>";
    return;
  }
  if (!notes) {
    status.innerHTML = "<span style='color:#e53e3e;'>⚠ Please write your notes first.</span>";
    textarea.focus();
    return;
  }

  sendBtn.disabled  = true;
  sendBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Sending...';

  const fd = new FormData();
  fd.append('action',     'save_notes');
  fd.append('notes',      notes);
  fd.append('student_id', selectedSid);

  fetch('creports.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(json => {
      sendBtn.disabled  = false;
      sendBtn.innerHTML = '<i class="fa fa-paper-plane"></i> Send to Student';

      if (json.success) {
        status.innerHTML = "<span style='color:#16a34a;'>✔ Note sent successfully.</span>";
        setTimeout(() => { closeCompose(); location.reload(); }, 900);
      } else {
        status.innerHTML = `<span style='color:#e53e3e;'>❌ ${json.message}</span>`;
      }
    })
    .catch(() => {
      sendBtn.disabled  = false;
      sendBtn.innerHTML = '<i class="fa fa-paper-plane"></i> Send to Student';
      status.innerHTML  = "<span style='color:#e53e3e;'>❌ Something went wrong.</span>";
    });
}
</script>
<script>var SESSION_ROLE = 'counselor';</script>
<script src="session_timeout.js"></script>
</body>
</html>
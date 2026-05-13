<?php
/** @var mysqli $conn */
/** @var string|int $sid */

$_unseenNotes = 0;
$_unseenTicket = 0;
$_unseenRejected = 0;

$_nr = $conn->query("
    SELECT COUNT(*) AS c
    FROM session_notes
    WHERE student_id='$sid'
      AND is_sent=1
      AND is_seen=0
");

if ($_nr && $_row = $_nr->fetch_assoc()) {
    $_unseenNotes = (int)$_row['c'];
}

$_tr = $conn->query("
    SELECT COUNT(*) AS c
    FROM appointments
    WHERE student_id='$sid'
      AND status='Approved'
      AND is_seen=0
");

if ($_tr && $_row = $_tr->fetch_assoc()) {
    $_unseenTicket = (int)$_row['c'];
}

$_rr = $conn->query("
    SELECT COUNT(*) AS c
    FROM appointments
    WHERE student_id='$sid'
      AND status='Rejected'
      AND is_seen=0
");

if ($_rr && $_row = $_rr->fetch_assoc()) {
    $_unseenRejected = (int)$_row['c'];
}

/** @noinspection PhpUnusedLocalVariableInspection */
$_totalReportUnseen =
    ($_unseenNotes > 0 ? 1 : 0) +
    ($_unseenTicket > 0 ? 1 : 0) +
    ($_unseenRejected > 0 ? 1 : 0);
?>
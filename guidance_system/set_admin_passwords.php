<?php
$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");

// ── SET YOUR PASSWORDS HERE ──
$admins = [
    '000001' => 'S3cure@Sys#2026!',
];

$counselors = [
    '000001' => 'W3llness@Andrea#91!',
    '000002' => 'AcadSup@Ramon#82!',
    '000003' => 'Career@Celeste#73!',
];

// ── UPDATE ADMINS ──
foreach ($admins as $id => $pw) {
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $hash = $conn->real_escape_string($hash);
    $conn->query("UPDATE admins SET password='$hash' WHERE admin_id='$id'");
    echo "✅ Admin <b>$id</b> password set.<br>";
}

// ── UPDATE COUNSELORS ──
foreach ($counselors as $id => $pw) {
    $hash = password_hash($pw, PASSWORD_DEFAULT);
    $hash = $conn->real_escape_string($hash);
    $conn->query("UPDATE counselors SET password='$hash' WHERE counselor_id='$id'");
    echo "✅ Counselor <b>$id</b> password set.<br>";
}

echo "<br><b style='color:green'>Done!</b>";
$conn->close();
?>
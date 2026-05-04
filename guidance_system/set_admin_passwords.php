<?php
$conn = new mysqli("localhost", "root", "", "gcs_db");

// ── SET YOUR PASSWORDS HERE ──
$admins = [
    '000001' => 'Sysadmin@123',      // sysadmin@univ.edu.ph
    '000002' => 'Guidance@123',   // guidance@univ.edu.ph
    '000003' => 'Support@123',    // support@univ.edu.ph
];

$counselors = [
    '000001' => 'Counselor1!Andrea',     // andrea.villafuerte@univ.edu.ph
    '000002' => 'Counselor2!Ramon',      // ramon.ocampo@univ.edu.ph
    '000003' => 'Counselor3!Celeste',    // celeste.navarro@univ.edu.ph
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
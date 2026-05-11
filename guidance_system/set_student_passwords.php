<?php
$conn = new mysqli("localhost", "System_User", "gcs_db2026", "gcs_db");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ── ONE PASSWORD FOR ALL STUDENTS ──
$defaultPassword = "Student@123";

// Hash password once
$hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
$hashedPassword = $conn->real_escape_string($hashedPassword);

// Update all activated students
$sql = "
    UPDATE activated_students
    SET 
        password = '$hashedPassword',
        is_temp_password = 0
";

if ($conn->query($sql)) {

    echo "✅ Password updated for all activated students.<br>";
    echo "🔑 Default Password: <b>$defaultPassword</b><br>";
    echo "📌 Temporary Password Status: DISABLED";

} else {

    echo "❌ Error: " . $conn->error;
}

$conn->close();
?>
<?php

$servername = "localhost";
$username   = "System_User";
$password   = "gcs_db2026";
$database   = "gcs_db";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection Failed: " . $conn->connect_error);
}

?>
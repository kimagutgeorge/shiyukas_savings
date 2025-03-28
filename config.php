<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "family_savings";

$conn = new mysqli($servername, $username, $password, $dbname);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

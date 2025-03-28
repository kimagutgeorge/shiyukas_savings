<?php
require 'config.php';

$username = 'admin';
$password = password_hash('password123', PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (?, ?)");
$stmt->bind_param('ss', $username, $password);

if ($stmt->execute()) {
    echo "Test user 'admin' added successfully! Password: password123";
} else {
    echo "Error adding user: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>

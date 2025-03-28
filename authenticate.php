<?php
session_start();
require 'config.php'; // Ensure this contains your DB connection setup

// Sanitize and get inputs
$username = trim($_POST['username']);
$password = trim($_POST['password']);

// Check if fields are empty
if (empty($username) || empty($password)) {
    header('Location: login.php?error=Please fill in both fields.');
    exit;
}

// Prepare the SQL statement to check user credentials
$stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    // Set session variables
    $_SESSION['loggedin'] = true;
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_id'] = $user['id'];

    header('Location: dashboard.php');
    exit;
} else {
    header('Location: login.php?error=Invalid username or password.');
    exit;
}
?>

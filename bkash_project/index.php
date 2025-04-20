<?php
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // If logged in, redirect to dashboard
    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>bKash Clone - Home</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="home-page">
    <div class="container">
        <h1>Welcome to LenDen</h1>
        <p>A simple financial transaction system.</p>
        <div class="buttons">
            <a href="login.php" class="btn login-btn">Login</a>
            <a href="register.php" class="btn register-btn">Register</a>
        </div>
    </div>
</body>
</html>


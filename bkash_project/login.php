<?php
session_start();
include 'config/db.php'; // Ensure this file correctly connects to your database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']); // Trim spaces
    $password = $_POST['password'];

    // Check if email exists in the database
    $sql = "SELECT id, name, password, balance FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Query failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // If user exists
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $hashed_password, $balance);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashed_password)) {
            // Store user info in session
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $name;
            $_SESSION['balance'] = $balance; // ✅ Store balance

            header("Location: dashboard.php");
            exit();
        } else {
            echo "<p style='color:red;'>Invalid password!</p>";
        }
    } else {
        echo "<p style='color:red;'>Email not found!</p>";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="login-page">

    <div class="auth-container">
        <h2><i class="fas fa-sign-in-alt"></i> Login</h2>
        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
        <form method="post">
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit">Login</button>
        </form>

        <!-- Forgot/Reset Password Link -->
        <div class="forgot-password-link">
            <a href="reset_password.php">Forgot/Reset Password?</a>
        </div>
    </div>

</body>
</html>


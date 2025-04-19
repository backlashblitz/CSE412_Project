<?php
include 'config/db.php'; // Ensure this file connects to your database

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $nid = $_POST['nid']; // Added NID
    $dob = $_POST['dob']; // Added Date of Birth
    $address = $_POST['address']; // Added Address
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password for storage

    // Check if email already exists
    $sql = "SELECT id FROM users WHERE email = ? OR phone = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $phone);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "<p style='color:red;'>Email or Phone already registered!</p>";
    } else {
        // Proceed with registration
        $sql = "INSERT INTO users (name, email, phone, nid, dob, address, password) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $name, $email, $phone, $nid, $dob, $address, $password);
        
        if ($stmt->execute()) {
            echo "Registration successful! <a href='login.php'>Login here</a>";
        } else {
            echo "Error: " . $stmt->error;
        }
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body class="register-page">

    <div class="auth-container">
        <h2><i class="fas fa-user-plus"></i> Register</h2>
        <?php if (isset($error)) { echo "<p class='error'>$error</p>"; } ?>
        <form method="post">
            <div class="input-group">
                <i class="fas fa-user"></i>
                <input type="text" name="name" placeholder="Full Name" required>
            </div>
            <div class="input-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Email" required>
            </div>
            <div class="input-group">
                <i class="fas fa-phone"></i>
                <input type="text" name="phone" placeholder="Phone Number" required>
            </div>
            <div class="input-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <div class="input-group">
                <i class="fas fa-id-card"></i>
                <input type="text" name="nid" placeholder="NID Number" required>
            </div> 
            <div class="input-group">
                <i class="fas fa-calendar"></i>
                <input type="date" name="dob" required>
            </div> 
            <div class="input-group">
                <i class="fas fa-map-marker"></i>
                <textarea name="address" placeholder="Enter your address" required></textarea>
            </div>
            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

</body>
</html>





<?php
session_start();
include 'config/db.php';
include 'navbar.php';

$user_id = $_SESSION['user_id'];  // The sender's ID

// Fetch all users excluding the current user (sender)
$sql = "SELECT id, name, phone FROM users WHERE id != ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Money</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="send-money-page">
    <!-- The send-money-page class will apply the background image styling -->
    <div class="send-money-container">
        <h2>Send Money</h2>
        <form action="send_money_process.php" method="post">
            <label for="receiver">Select Recipient:</label>
            <select name="receiver" required>
                <?php while ($row = $result->fetch_assoc()) { ?>
                    <option value="<?php echo $row['id']; ?>">
                        <?php echo $row['name'] . " (" . $row['phone'] . ")"; ?>
                    </option>
                <?php } ?>
            </select><br>

            <input type="number" name="amount" placeholder="Amount" required><br>

            <!-- Reference Field (Optional) -->
            <input type="text" name="reference" placeholder="Reference (Optional)"><br>

            <!-- Bkash PIN -->
            <label for="pin">Enter Your  PIN:</label>
            <input type="password" id="pin" name="pin" required><br><br>

            <button type="submit">Send Money</button>
            <br>
            <a href="dashboard.php">Go Back to Dashboard</a>
        </form>
    </div>

</body>
</html>


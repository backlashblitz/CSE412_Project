<?php
session_start();
include 'config/db.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    echo "Error: Unauthorized access!";
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $phone_number = $_POST['phone_number'];
    $amount = floatval($_POST['amount']);
    $operator = $_POST['operator']; // Capturing the operator

    // Check if the user has enough balance to perform the recharge
    $userQuery = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $userQuery->bind_param("i", $user_id);
    $userQuery->execute();
    $userResult = $userQuery->get_result();

    if ($userResult->num_rows === 0) {
        echo "Error: User not found!";
        exit();
    }

    $userData = $userResult->fetch_assoc();
    $user_balance = $userData['balance'];

    if ($user_balance < $amount) {
        echo "Error: Insufficient balance!";
        exit();
    }

    // Deduct the amount from the user's balance
    $new_balance = $user_balance - $amount;
    $updateBalanceQuery = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
    $updateBalanceQuery->bind_param("di", $new_balance, $user_id);
    $updateBalanceQuery->execute();

    // Insert the mobile recharge record into the database
    $insertRechargeQuery = $conn->prepare("INSERT INTO mobile_recharge (user_id, phone_number, amount, operator) VALUES (?, ?, ?, ?)");
    $insertRechargeQuery->bind_param("isss", $user_id, $phone_number, $amount, $operator);
    $insertRechargeQuery->execute();

    // Generate a unique transaction ID
    $transaction_id = uniqid("txn_");

    // Insert the transaction record for recharge
    $insertTransactionQuery = $conn->prepare("INSERT INTO transactions (transaction_id, sender_id, receiver_id, amount, type, description) VALUES (?, ?, ?, ?, 'recharge', ?)");
    $insertTransactionQuery->bind_param("siids", $transaction_id, $user_id, $user_id, $amount, $operator);
    $insertTransactionQuery->execute();

    echo "Mobile recharge successful! Your new balance is: ৳" . number_format($new_balance, 2);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Recharge</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="recharge-page">
    <div class="recharge-container">
        <h2>Mobile Recharge</h2>

        <form method="post">
            <label for="phone_number">Recipient Phone Number:</label>
            <input type="text" name="phone_number" required><br><br>

            <label for="amount">Amount:</label>
            <input type="number" name="amount" required><br><br>

            <label for="operator">Select Operator:</label>
            <select name="operator" required>
                <option value="Airtel">Airtel</option>
                <option value="Robi">Robi</option>
                <option value="Grameenphone">Grameenphone</option>
                <option value="Banglalink">Banglalink</option>
            </select><br><br>

            <label for="pin">Enter Your  PIN:</label>
            <input type="password" id="pin" name="pin" required><br><br>

            <button type="submit">Recharge</button>

            <br><br>
            <a href="dashboard.php">Go Back to Dashboard</a>
        </form>
    </div>

</body>
</html>


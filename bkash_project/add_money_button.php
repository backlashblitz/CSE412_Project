<?php
session_start();
include 'config/db.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please log in to add money.";
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $transaction_id = uniqid("txn_");

    // Validate payment details
    if ($payment_method == "bank") {
        $bank_account = $_POST['bank_account'];
        $bank_pin = $_POST['bank_pin'];

        if (empty($bank_account) || empty($bank_pin)) {
            echo "<p style='color:red;'>Bank account and PIN are required!</p>";
            exit();
        }
    } elseif ($payment_method == "card") {
        $card_number = $_POST['card_number'];
        $cvv = $_POST['cvv'];

        if (empty($card_number) || empty($cvv)) {
            echo "<p style='color:red;'>Card number and CVV are required!</p>";
            exit();
        }
    }

    // Insert into add_money table
    $sql = "INSERT INTO add_money (user_id, amount, payment_method, transaction_id, status) 
            VALUES (?, ?, ?, ?, 'pending')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idss", $user_id, $amount, $payment_method, $transaction_id);

    if ($stmt->execute()) {
        // Update user balance
        $conn->query("UPDATE users SET balance = balance + $amount WHERE id = $user_id");

        // Get the new balance
        $balance_result = $conn->query("SELECT balance FROM users WHERE id = $user_id");
        $row = $balance_result->fetch_assoc();
        $new_balance = $row['balance'];

        // Insert into transactions table
        $receiver_id = NULL; // Since this is an "Add Money" transaction, there's no specific receiver
        $transaction_type = "add_money";
        $description = "Added money via $payment_method";

        $transaction_sql = "INSERT INTO transactions (sender_id, receiver_id, amount, type, description, transaction_id) 
                            VALUES (?, ?, ?, ?, ?, ?)";
        $transaction_stmt = $conn->prepare($transaction_sql);
        $transaction_stmt->bind_param("iidsss", $user_id, $receiver_id, $amount, $transaction_type, $description, $transaction_id);

        if ($transaction_stmt->execute()) {
            echo "<p>Money added successfully! Your new balance is: ৳" . number_format($new_balance, 2) . "</p>";
        } else {
            echo "<p style='color:red;'>Error inserting transaction record: " . $transaction_stmt->error . "</p>";
        }
    } else {
        echo "<p style='color:red;'>Error: " . $stmt->error . "</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Money</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="add-money-page">
    <div class="add-money-container">
        <h2>Add Money</h2>

        <form method="post">
            <label for="amount">Amount to Add:</label>
            <input type="number" name="amount" required><br><br>

            <label for="payment_method">Payment Method:</label>
            <select name="payment_method" id="payment_method" required onchange="togglePaymentFields()">
                <option value="bank">Bank</option>
                <option value="card">Card</option>
            </select><br><br>

            <!-- Bank Payment Fields -->
            <div id="bank_fields">
                <label for="bank_account">Bank Account Number:</label>
                <input type="text" name="bank_account"><br><br>

                <label for="bank_pin">Bank PIN:</label>
                <input type="password" name="bank_pin"><br><br>
            </div>

            <!-- Card Payment Fields -->
            <div id="card_fields" style="display:none;">
                <label for="card_number">Card Number:</label>
                <input type="text" name="card_number"><br><br>

                <label for="cvv">CVV:</label>
                <input type="password" name="cvv"><br><br>
            </div>

            <button type="submit">Add Money</button>
            <br>
            <a href="dashboard.php">Go Back to Dashboard</a>
        </form>
    </div>

    <script>
    function togglePaymentFields() {
        var method = document.getElementById("payment_method").value;
        document.getElementById("bank_fields").style.display = (method == "bank") ? "block" : "none";
        document.getElementById("card_fields").style.display = (method == "card") ? "block" : "none";
    }
    </script>
</body>
</html>


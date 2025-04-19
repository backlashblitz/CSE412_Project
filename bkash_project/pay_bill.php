<?php
session_start();
include 'config/db.php';
include 'navbar.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Please log in to pay a bill.";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch available bill types
$bill_types = ['Electricity', 'Water', 'Gas', 'Internet'];

// Fetch user's balance from the database
$balanceQuery = "SELECT balance, password FROM users WHERE id = ?";
$stmt = $conn->prepare($balanceQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($balance, $stored_pin);
$stmt->fetch();

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bill_type = $_POST['bill_type'];
    $amount = $_POST['amount'];
    $bkash_pin = $_POST['pin']; // User entered PIN

    // Verify Bkash PIN (hashed password check)
    if (!password_verify($bkash_pin, $stored_pin)) {
        echo "❌ Error: Incorrect Bkash PIN!";
        exit();
    }

    // Check if user has sufficient balance
    if ($balance >= $amount) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Deduct the amount from user's balance
            $updateBalance = "UPDATE users SET balance = balance - ? WHERE id = ?";
            $stmt = $conn->prepare($updateBalance);
            $stmt->bind_param("di", $amount, $user_id);
            $stmt->execute();

            // Record the bill payment in the database
            $billPaymentQuery = "INSERT INTO bill_payments (user_id, bill_type, amount) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($billPaymentQuery);
            $stmt->bind_param("isd", $user_id, $bill_type, $amount);
            $stmt->execute();

            // ✅ Generate a unique transaction ID
            $transaction_id = uniqid("txn_");

            // ✅ Insert into transactions table
            $transactionQuery = "INSERT INTO transactions (transaction_id, sender_id, receiver_id, amount, type, description) 
                                 VALUES (?, ?, ?, ?, 'pay_bill', ?)";
            $stmt = $conn->prepare($transactionQuery);
            $stmt->bind_param("siids", $transaction_id, $user_id, $user_id, $amount, $bill_type);
            $stmt->execute();

            // Commit transaction
            $conn->commit();

            echo "✅ Bill payment of " . number_format($amount, 2) . " for " . htmlspecialchars($bill_type) . " was successful!";

        } catch (Exception $e) {
            $conn->rollback(); // Rollback if any issue occurs
            echo "❌ Transaction failed: " . $e->getMessage();
        }
    } else {
        echo "❌ Error: Insufficient balance!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Bill</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="pay-bill-page">
    <div class="pay-bill-container">
        <h2>Pay Bill</h2>
        <p>Your current balance: ৳<?php echo number_format($balance, 2); ?></p>

        <form method="post">
            <label for="bill_type">Select Bill Type:</label>
            <select name="bill_type" id="bill_type" required>
                <option value="">-- Select Bill Type --</option>
                <?php
                foreach ($bill_types as $bill) {
                    echo "<option value='$bill'>$bill</option>";
                }
                ?>
            </select><br><br>

            <label for="amount">Amount to Pay:</label>
            <input type="number" name="amount" id="amount" min="1" required><br><br>

            <!-- Bkash PIN -->
            <label for="pin">Enter Your bKash PIN:</label>
            <input type="password" id="pin" name="pin" required><br><br>

            <button type="submit">Pay Bill</button>
            
            <br>
            <a href="dashboard.php">Go Back to Dashboard</a>
        </form>
    </div>

</body>
</html>

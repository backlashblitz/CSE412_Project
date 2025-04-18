<?php
session_start();
include 'config/db.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    echo "Please log in to make a payment.";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch list of merchants
$sql = "SELECT id, name FROM merchants";
$result = $conn->query($sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $merchant_id = $_POST['merchant_id'];
    $amount = $_POST['amount'];
    $bkash_pin = $_POST['pin'];

    // ✅ Fetch user balance and stored PIN
    $balanceQuery = "SELECT balance, password FROM users WHERE id = ?";
    $stmt = $conn->prepare($balanceQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($balance, $stored_pin);
    $stmt->fetch();
    $stmt->close();

    // ✅ Verify bKash PIN
    if (!password_verify($bkash_pin, $stored_pin)) {
        echo "❌ Error: Incorrect bKash PIN!";
        exit();
    }

    if ($balance >= $amount) {
        // ✅ Begin transaction
        $conn->begin_transaction();

        try {
            // ✅ Deduct balance from user
            $updateBalance = "UPDATE users SET balance = balance - ? WHERE id = ?";
            $stmt = $conn->prepare($updateBalance);
            $stmt->bind_param("di", $amount, $user_id);
            $stmt->execute();
            $stmt->close();

            // ✅ Add balance to merchant
            $updateMerchantBalance = "UPDATE merchants SET balance = balance + ? WHERE id = ?";
            $stmt = $conn->prepare($updateMerchantBalance);
            $stmt->bind_param("di", $amount, $merchant_id);
            $stmt->execute();
            $stmt->close();

            // ✅ Insert into payments table
            $paymentQuery = "INSERT INTO payments (user_id, merchant_id, amount, status) VALUES (?, ?, ?, 'completed')";
            $stmt = $conn->prepare($paymentQuery);
            $stmt->bind_param("iid", $user_id, $merchant_id, $amount);
            $stmt->execute();
            $stmt->close();

            // ✅ Generate a unique transaction ID
            $transaction_id = uniqid("txn_");

            // ✅ Insert into transactions table
            $transactionQuery = "INSERT INTO transactions (transaction_id, sender_id, receiver_id, amount, type) VALUES (?, ?, ?, ?, 'payment')";
            $stmt = $conn->prepare($transactionQuery);
            $stmt->bind_param("siid", $transaction_id, $user_id, $merchant_id, $amount);
            $stmt->execute();
            $stmt->close();

            // ✅ Commit transaction
            $conn->commit();

            echo "✅ Payment of ৳" . number_format($amount, 2) . " to the merchant was successful!";
        } catch (Exception $e) {
            $conn->rollback(); // Rollback if an error occurs
            echo "❌ Payment failed: " . $e->getMessage();
        }
    } else {
        echo "❌ Insufficient balance!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Make a Payment</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="payment-page">
    <div class="payment-container">
        <h2>Make a Payment</h2>

        <form method="post">
            <label for="merchant_id">Select Merchant:</label>
            <select name="merchant_id" id="merchant_id" required>
                <option value="">-- Select Merchant --</option>
                <?php
                // Display merchants from the database
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['name']) . "</option>";
                    }
                } else {
                    echo "<option value=''>No merchants available</option>";
                }
                ?>
            </select><br><br>

            <label for="amount">Amount to Pay:</label>
            <input type="number" name="amount" id="amount" min="1" required><br><br>

            <label for="pin">Enter Your bKash PIN:</label>
            <input type="password" id="pin" name="pin" required><br><br>

            <button type="submit">Pay</button>
            <br>
            <a href="dashboard.php">Go Back to Dashboard</a>
        </form>
    </div>

</body>
</html>

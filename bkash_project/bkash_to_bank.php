<?php
session_start();
include 'config/db.php'; // Ensure the path is correct
include 'navbar.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to transfer money.";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user balance & PIN from the database
$sql = "SELECT balance, password FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($balance, $stored_pin);
$stmt->fetch();
$stmt->close();

// Handle transfer request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $transfer_amount = $_POST['amount'];
    $bank_name = $_POST['bank_name'];
    $bank_account = $_POST['bank_account'];
    $bkash_pin = $_POST['pin'];

    // ✅ Verify bKash PIN
    if (!password_verify($bkash_pin, $stored_pin)) {
        echo "❌ Error: Incorrect bKash PIN!";
        exit();
    }

    // ✅ Check if the transfer amount is valid
    if ($transfer_amount > 0 && $transfer_amount <= $balance) {
        // ✅ Begin transaction
        $conn->begin_transaction();

        try {
            // ✅ Deduct balance from user
            $new_balance = $balance - $transfer_amount;
            $sql = "UPDATE users SET balance = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("di", $new_balance, $user_id);
            $stmt->execute();
            $stmt->close();

            // ✅ Generate unique transaction ID
            $transaction_id = uniqid("txn_");

            // ✅ Insert transaction record
            $transaction_sql = "INSERT INTO transactions (transaction_id, sender_id, receiver_id, amount, type, description) 
                                VALUES (?, ?, ?, ?, ?, ?)";
            $transaction_stmt = $conn->prepare($transaction_sql);
            $receiver_id = 1; // Bank system (modify as needed)
            $transaction_type = 'add_money';
            $description = "Transfer to $bank_name (Account: $bank_account)";

            $transaction_stmt->bind_param("siidss", $transaction_id, $user_id, $receiver_id, $transfer_amount, $transaction_type, $description);
            $transaction_stmt->execute();
            $transaction_stmt->close();

            // ✅ Commit transaction
            $conn->commit();

            echo "✅ Transfer successful! Your new balance is: ৳" . number_format($new_balance, 2);
        } catch (Exception $e) {
            $conn->rollback(); // Rollback in case of failure
            echo "❌ Transfer failed: " . $e->getMessage();
        }
    } else {
        echo "❌ Insufficient balance or invalid amount.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>bKash to Bank Transfer</title>
    <link rel="stylesheet" href="css/style.css"> <!-- Add your CSS file -->
</head>
<body class="bkash-to-bank-page">
    <div class="bkash-to-bank-container">
        <h2>Transfer from LenDen to Bank</h2>

        <form method="POST" action="">
            <label for="amount">Amount to transfer (in ৳):</label>
            <input type="number" id="amount" name="amount" min="1" max="<?php echo $balance; ?>" required><br><br>

            <label for="bank_name">Select Bank:</label>
            <select id="bank_name" name="bank_name" required>
                <option value="DBBL">Dutch-Bangla Bank Ltd (DBBL)</option>
                <option value="BRAC">BRAC Bank</option>
                <option value="City">City Bank</option>
                <option value="IFIC">IFIC Bank</option>
            </select><br><br>

            <label for="bank_account">Bank Account Number:</label>
            <input type="text" id="bank_account" name="bank_account" required><br><br>

            <label for="pin">Enter Your  PIN:</label>
            <input type="password" id="pin" name="pin" required><br><br>

            <button type="submit">Transfer</button>
        </form>

        <br>
        <a href="dashboard.php">Back to Dashboard</a>
    </div>

</body>
</html>

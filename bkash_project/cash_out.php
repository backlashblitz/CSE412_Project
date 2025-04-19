<?php
session_start();
include 'config/db.php'; // Ensure database connection is correct
include 'navbar.php';
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "❌ You must be logged in to cash out.";
    exit();
}

$user_id = $_SESSION['user_id'];

// ✅ Fetch user's balance and PIN
$sql = "SELECT COALESCE(balance, 0) AS balance, password FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($balance, $stored_pin);
$stmt->fetch();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cash_out_amount = $_POST['amount'];
    $agent_id = $_POST['agent_id'];
    $bkash_pin = $_POST['pin'];

    // ✅ Verify Bkash PIN
    if (!password_verify($bkash_pin, $stored_pin)) {
        echo "❌ Error: Incorrect bKash PIN!";
        exit();
    }

    // ✅ Define transaction fee (1.85%)
    $fee = round($cash_out_amount * 0.0185, 2);
    $total_deduction = $cash_out_amount + $fee;

    // ✅ Ensure sufficient balance (amount + fee)
    if ($total_deduction > $balance) {
        echo "❌ Error: Insufficient balance! You need at least ৳" . number_format($total_deduction, 2);
        exit();
    }

    // ✅ Start Transaction
    $conn->begin_transaction();

    try {
        // Deduct balance from user
        $update_user_balance = "UPDATE users SET balance = balance - ? WHERE id = ?";
        $stmt = $conn->prepare($update_user_balance);
        $stmt->bind_param("di", $total_deduction, $user_id);
        $stmt->execute();
        $stmt->close();

        // ✅ Add money to agent's balance
        $update_agent_balance = "UPDATE merchants SET balance = balance + ? WHERE id = ?";
        $stmt = $conn->prepare($update_agent_balance);
        $stmt->bind_param("di", $cash_out_amount, $agent_id);
        $stmt->execute();
        $stmt->close();

        // ✅ Insert cash-out into cash_out table
        $cash_out_sql = "INSERT INTO cash_out (user_id, agent_id, amount, fee) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($cash_out_sql);
        $stmt->bind_param("iidd", $user_id, $agent_id, $cash_out_amount, $fee);
        $stmt->execute();
        $stmt->close();

        // ✅ Insert transaction into transactions table
        $transaction_id = uniqid("txn_");
        $transaction_type = "cash_out";
        $transaction_desc = "Cash out to agent";
        $transaction_sql = "INSERT INTO transactions (transaction_id, sender_id, receiver_id, amount, type, description) 
                    VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($transaction_sql);
        $stmt->bind_param("siidss", $transaction_id, $user_id, $agent_id, $cash_out_amount, $transaction_type, $transaction_desc);
        $stmt->execute();
        $stmt->close();

        // ✅ Commit transaction
        $conn->commit();
        echo "✅ Cash-out successful! New balance: ৳" . number_format($balance - $total_deduction, 2);

    } catch (Exception $e) {
        $conn->rollback(); // Rollback if any error occurs
        echo "❌ Transaction failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Out</title>
    <link rel="stylesheet" href="css/style.css"> <!-- Add your CSS file -->
</head>
<body class="cash-out-page">
    <div class="cash-out-container">
        <h2>Cash Out to Agent</h2>
        <p>Your current balance: ৳<?php echo number_format($balance, 2); ?></p>

        <form method="POST">
            <label for="amount">Amount to cash out (in ৳):</label>
            <input type="number" id="amount" name="amount" min="1" max="<?php echo $balance; ?>" required>
            
            <label for="agent_id">Select Agent:</label>
            <select id="agent_id" name="agent_id" required>
                <option value="">-- Select an Agent --</option>
                <?php
                // ✅ Fetch all active agents (merchants)
                $agent_sql = "SELECT id, name FROM merchants WHERE status = 'active'";
                $agent_result = $conn->query($agent_sql);
                
                if ($agent_result->num_rows > 0) {
                    while ($agent = $agent_result->fetch_assoc()) {
                        echo "<option value='" . $agent['id'] . "'>" . htmlspecialchars($agent['name']) . "</option>";
                    }
                } else {
                    echo "<option value=''>No agents available</option>";
                }
                ?>
            </select>
            <br><br>

            <!-- bKash PIN -->
            <label for="pin">Enter Your bKash PIN:</label>
            <input type="password" id="pin" name="pin" required><br><br>

            <button type="submit">Cash Out</button>
            <br>
            <a href="dashboard.php">Back to Dashboard</a>
        </form>
    </div>
</body>
</html>


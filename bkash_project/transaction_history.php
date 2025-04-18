<?php
session_start();
include 'config/db.php'; // Ensure the correct path
include 'navbar.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Error: Please log in to view your transaction history.");
}

$user_id = $_SESSION['user_id'];

// Debugging: Print user ID
// echo "User ID: " . htmlspecialchars($user_id) . "<br>";

// Retrieve transactions for the logged-in user
$sql = "SELECT id, type, COALESCE(description, 'N/A') AS description, amount, date 
        FROM transactions 
        WHERE sender_id = ? OR receiver_id = ? 
        ORDER BY date DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

// HTML starts here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History</title>
    <link rel="stylesheet" href="css/style.css"> <!-- Add your CSS file -->
</head>
<body class="transaction-history-page">
    <div class="transaction-history-container">
        <h2>Transaction History</h2>

        <table>
            <tr>
                <th>Transaction ID</th>
                <th>Type</th>
                <th>Description</th>
                <th>Amount (৳)</th>
                <th>Date</th>
            </tr>

            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['type']}</td>
                        <td>{$row['description']}</td>
                        <td>৳" . number_format($row['amount'], 2) . "</td>
                        <td>{$row['date']}</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='5'>No transactions found.</td></tr>";
            }
            ?>
        </table>

        <a href="dashboard.php">Back to Dashboard</a>
    </div>
</body>
</html>


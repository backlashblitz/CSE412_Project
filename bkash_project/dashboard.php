<?php
session_start();
include 'config/db.php'; // Ensure the path is correct

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "You must be logged in to view this page.";
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user details from the database
$sql = "SELECT name, balance FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $user_name = htmlspecialchars($row['name']);
    $balance = number_format($row['balance'], 2);
} else {
    echo "Error: User not found.";
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Background Image */
        body {
            background-image: url('images/bg.jpg'); /* Path to your image */
            background-size: cover; /* Ensure the image covers the entire screen */
            background-position: center; /* Center the image */
            background-attachment: fixed; /* Make sure the background is fixed */
            color: #333; /* Set the text color to make it visible over the background */
        }

        /* If you want to add a slight dark overlay to make the text more readable */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.3); /* Adjust the opacity for darkness */
            z-index: -1; /* Make sure it stays behind the content */
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <header class="navbar">
        <div class="logo" id="logo">Takai Jibon</div>
        <nav>
            <a href="#" id="services-link">Services</a>
            <a href="#" id="business-link">Business</a>
            <a href="#" id="help-link">Help</a>
            <a href="#" id="about-link">About</a>
        </nav>
        
        <!-- Language Toggle and Logout -->
        <div class="navbar-actions">
            <select id="language-toggle" class="language-toggle">
                <option value="en">English</option>
                <option value="bn">বাংলা</option>
            </select>
            
            <!-- Logout Button Form -->
            <form action="logout.php" method="POST" style="display:inline;">
                <button type="submit" class="logout-btn">Logout</button>
            </form>
        </div>
    </header>

    <!-- Main Content -->
    <div class="container">
        <div class="balance-card">
            <h3 id="welcome-message">Welcome, <?php echo $user_name; ?>!</h3>
            <p id="balance-label">Your current balance is:</p>
            <div class="amount" id="balance-amount">৳<?php echo $balance; ?></div>
        </div>

        <!-- Shortcut Grid -->
        <div class="shortcuts-grid">
            <a href="send_money.php" class="shortcut-item">
                <img src="icons/send_money.png" alt="Send Money">
                <p id="send-money">Send Money</p>
            </a>

            <a href="mobile_recharge.php" class="shortcut-item">
                <img src="icons/mobile_recharge.png" alt="Mobile Recharge">
                <p id="mobile-recharge">Mobile Recharge</p>
            </a>

            <a href="add_money_button.php" class="shortcut-item">
                <img src="icons/add_money.png" alt="Add Money">
                <p id="add-money">Add Money</p>
            </a>

            <a href="payment.php" class="shortcut-item">
                <img src="icons/payment.png" alt="Payment">
                <p id="payment">Payment</p>
            </a>

            <a href="pay_bill.php" class="shortcut-item">
                <img src="icons/pay_bill.png" alt="Pay Bill">
                <p id="pay-bill">Pay Bill</p>
            </a>

            <a href="bkash_to_bank.php" class="shortcut-item">
                <img src="icons/bkash_to_bank.png" alt="bKash to Bank">
                <p id="bkash-to-bank">bKash to Bank</p>
            </a>

            <a href="cash_out.php" class="shortcut-item">
                <img src="icons/cash_out.png" alt="Cash Out">
                <p id="cash-out">Cash Out</p>
            </a>

            <a href="transaction_history.php" class="shortcut-item">
                <img src="icons/receive_money.png" alt="Transaction History">
                <p id="transactions">Transactions</p>
            </a>
        </div>
    </div>

    <script>
        const translations = {
    en: {
        "welcome-message": "Welcome, ",
        "balance-label": "Your current balance is:",
        "send-money": "Send Money",
        "mobile-recharge": "Mobile Recharge",
        "add-money": "Add Money",
        "payment": "Payment",
        "pay-bill": "Pay Bill",
        "bkash-to-bank": "bKash to Bank",
        "cash-out": "Cash Out",
        "transactions": "Transactions",
        "services-link": "Services",
        "business-link": "Business",
        "help-link": "Help",
        "about-link": "About"
    },
    bn: {
        "welcome-message": "স্বাগতম, ",
        "balance-label": "আপনার বর্তমান ব্যালেন্স:",
        "send-money": "পেমেন্ট পাঠান",
        "mobile-recharge": "মোবাইল রিচার্জ",
        "add-money": "অর্থ যোগ করুন",
        "payment": "পেমেন্ট",
        "pay-bill": "বিল পরিশোধ",
        "bkash-to-bank": "বিকাশ থেকে ব্যাংকে",
        "cash-out": "ক্যাশ আউট",
        "transactions": "লেনদেন",
        "services-link": "সার্ভিস",
        "business-link": "ব্যবসা",
        "help-link": "সাহায্য",
        "about-link": "সম্পর্কিত"
    }
};

function changeLanguage(language) {
    const selectedLanguage = translations[language];

    // Update the UI text
    document.getElementById("welcome-message").textContent = selectedLanguage["welcome-message"];
    document.getElementById("balance-label").textContent = selectedLanguage["balance-label"];
    document.getElementById("send-money").textContent = selectedLanguage["send-money"];
    document.getElementById("mobile-recharge").textContent = selectedLanguage["mobile-recharge"];
    document.getElementById("add-money").textContent = selectedLanguage["add-money"];
    document.getElementById("payment").textContent = selectedLanguage["payment"];
    document.getElementById("pay-bill").textContent = selectedLanguage["pay-bill"];
    document.getElementById("bkash-to-bank").textContent = selectedLanguage["bkash-to-bank"];
    document.getElementById("cash-out").textContent = selectedLanguage["cash-out"];
    document.getElementById("transactions").textContent = selectedLanguage["transactions"];
    document.getElementById("services-link").textContent = selectedLanguage["services-link"];
    document.getElementById("business-link").textContent = selectedLanguage["business-link"];
    document.getElementById("help-link").textContent = selectedLanguage["help-link"];
    document.getElementById("about-link").textContent = selectedLanguage["about-link"];

    // Convert amount to Bangla numerals if language is Bangla
    if (language === 'bn') {
        const banglaAmount = convertToBanglaNumerals('<?php echo $balance; ?>');
        document.getElementById("balance-amount").textContent = `৳${banglaAmount}`;
    } else {
        document.getElementById("balance-amount").textContent = `৳<?php echo $balance; ?>`;
    }
}

// Function to convert numbers into Bangla numerals
function convertToBanglaNumerals(num) {
    const banglaDigits = ["০", "১", "২", "৩", "৪", "৫", "৬", "৭", "৮", "৯"];
    return num.toString().split('').map(function(digit) {
        return banglaDigits[digit] || digit;
    }).join('');
}

document.getElementById("language-toggle").addEventListener("change", function() {
    const language = this.value;
    changeLanguage(language);
});
    </script>
</body>
</html>

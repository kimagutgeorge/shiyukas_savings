<?php
include 'config.php';

$success_message = '';
$error_message = '';

// Fetch all loans for dropdown
$loans = $conn->query("
    SELECT loans.id, members.member_code, members.name, (loans.amount - IFNULL(SUM(loan_repayments.amount), 0)) AS balance
    FROM loans
    LEFT JOIN members ON loans.member_id = members.id
    LEFT JOIN loan_repayments ON loans.id = loan_repayments.loan_id
    GROUP BY loans.id
    HAVING balance > 0
    ORDER BY members.member_code
");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $loan_id = $conn->real_escape_string($_POST['loan_id']);
    $amount = $conn->real_escape_string($_POST['amount']);
    $repayment_date = date('Y-m-d');

    // Fetch the current loan balance
    $loan_query = $conn->query("
        SELECT (loans.amount - IFNULL(SUM(loan_repayments.amount), 0)) AS balance
        FROM loans
        LEFT JOIN loan_repayments ON loans.id = loan_repayments.loan_id
        WHERE loans.id = '$loan_id'
        GROUP BY loans.id
    ");
    $loan = $loan_query->fetch_assoc();
    $current_balance = $loan['balance'];

    if (empty($loan_id) || empty($amount) || $amount <= 0) {
        $error_message = "Please select a loan and enter a valid repayment amount.";
    } elseif ($amount > $current_balance) {
        $error_message = "Repayment amount exceeds the loan balance.";
    } else {
        $query = "INSERT INTO loan_repayments (loan_id, amount, repayment_date) VALUES ('$loan_id', '$amount', '$repayment_date')";
        if ($conn->query($query)) {
            $success_message = "Repayment recorded successfully!";
        } else {
            $error_message = "Error recording repayment: " . $conn->error;
        }
    }
}
include('components/header.php');?>
<div class="whole-page">
    <div class="nav-bar-wrapper">
        <?php include('components/navbar.php');?>
    </div>
    <div class="page-wrapper">
    <?php include('components/topbar.php');?>
    <?php if ($success_message): ?>
        <p class="success"><?= $success_message ?></p>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <p class="error"><?= $error_message ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="loan_id">Select Loan:</label>
        <select name="loan_id" required>
            <option value="">-- Select Loan --</option>
            <?php while ($loan = $loans->fetch_assoc()): ?>
                <option value="<?= $loan['id'] ?>">
                    <?= htmlspecialchars($loan['member_code'] . ' - ' . $loan['name']) ?> (Balance: Ksh <?= number_format($loan['balance'], 2) ?>)
                </option>
            <?php endwhile; ?>
        </select>

        <label for="amount">Repayment Amount (Ksh):</label>
        <input type="number" name="amount" min="1" required>

        <button type="submit">Record Repayment</button>
    </form>

            </div>
            </div>
</body>
</html>

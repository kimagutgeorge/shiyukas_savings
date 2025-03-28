<?php
include 'config.php';

$success_message = '';
$error_message = '';

// Fetch members for the dropdown
$members = $conn->query("SELECT id, member_code, name FROM members ORDER BY member_code");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $member_id = $conn->real_escape_string($_POST['member_id']);
    $amount = $conn->real_escape_string($_POST['amount']);
    $loan_date = date('Y-m-d');

    if (empty($member_id) || empty($amount) || $amount <= 0) {
        $error_message = "Please select a member and enter a valid loan amount.";
    } else {
        $query = "INSERT INTO loans (member_id, amount, loan_date) VALUES ('$member_id', '$amount', '$loan_date')";
        if ($conn->query($query)) {
            $success_message = "Loan added successfully!";
        } else {
            $error_message = "Error adding loan: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Loan</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Add Loan</h1>

    <?php if ($success_message): ?>
        <p class="success"><?= $success_message ?></p>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <p class="error"><?= $error_message ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="member_id">Select Member:</label>
        <select name="member_id" required>
            <option value="">-- Select Member --</option>
            <?php while ($member = $members->fetch_assoc()): ?>
                <option value="<?= $member['id'] ?>"><?= htmlspecialchars($member['member_code'] . ' - ' . $member['name']) ?></option>
            <?php endwhile; ?>
        </select>

        <label for="amount">Loan Amount (Ksh):</label>
        <input type="number" name="amount" min="1" required>

        <button type="submit">Add Loan</button>
    </form>

    <a href="index.php" class="back-btn">Back to Dashboard</a>
</body>
</html>

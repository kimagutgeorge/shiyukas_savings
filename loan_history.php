<?php
include 'config.php';

// Fetch all loans with member names
$query = "
    SELECT loans.id, members.member_code, members.name, loans.amount AS loan_amount, 
           IFNULL(SUM(loan_repayments.amount), 0) AS total_repaid,
           (loans.amount - IFNULL(SUM(loan_repayments.amount), 0)) AS balance,
           loans.loan_date
    FROM loans
    LEFT JOIN members ON loans.member_id = members.id
    LEFT JOIN loan_repayments ON loans.id = loan_repayments.loan_id
    GROUP BY loans.id
    ORDER BY loans.loan_date DESC
";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan History</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Loan History</h1>

    <table>
        <thead>
            <tr>
                <th>Member Code</th>
                <th>Member Name</th>
                <th>Loan Amount (Ksh)</th>
                <th>Total Repaid (Ksh)</th>
                <th>Balance (Ksh)</th>
                <th>Loan Date</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['member_code']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= number_format($row['loan_amount'], 2) ?></td>
                    <td><?= number_format($row['total_repaid'], 2) ?></td>
                    <td><?= number_format($row['balance'], 2) ?></td>
                    <td><?= $row['loan_date'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <a href="index.php" class="back-btn">Back to Dashboard</a>
</body>
</html>

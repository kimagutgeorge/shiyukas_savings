<?php
include 'config.php';

// Fetch member contribution sums
$contributions = $conn->query("
    SELECT members.member_code, members.name, IFNULL(SUM(contributions.amount), 0) AS total_contributed
    FROM members
    LEFT JOIN contributions ON members.id = contributions.member_id
    GROUP BY members.id
    ORDER BY members.member_code
");

// Fetch loan balances
$loans = $conn->query("
    SELECT members.member_code, members.name, 
           IFNULL(SUM(loans.amount), 0) AS total_loans,
           (IFNULL(SUM(loans.amount), 0) - IFNULL(SUM(loan_repayments.amount), 0)) AS loan_balance
    FROM members
    LEFT JOIN loans ON members.id = loans.member_id
    LEFT JOIN loan_repayments ON loans.id = loan_repayments.loan_id
    GROUP BY members.id
    ORDER BY members.member_code
");

$member_names = [];
$contribution_data = [];
$loan_data = [];
$loan_balances = [];

while ($row = $contributions->fetch_assoc()) {
    $member_names[] = $row['member_code'];
    $contribution_data[] = $row['total_contributed'];
}

while ($row = $loans->fetch_assoc()) {
    $loan_data[] = $row['total_loans'];
    $loan_balances[] = $row['loan_balance'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan and Contribution Charts</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Loan and Contribution Charts</h1>

    <div style="width: 80%; margin: auto;">
        <h2>Contributions by Member</h2>
        <canvas id="contributionsChart"></canvas>
    </div>

    <div style="width: 80%; margin: auto; margin-top: 50px;">
        <h2>Loan Balances by Member</h2>
        <canvas id="loansChart"></canvas>
    </div>

    <script>
        // Contribution Chart
        const ctxContributions = document.getElementById('contributionsChart').getContext('2d');
        new Chart(ctxContributions, {
            type: 'bar',
            data: {
                labels: <?= json_encode($member_names) ?>,
                datasets: [{
                    label: 'Total Contributions (Ksh)',
                    data: <?= json_encode($contribution_data) ?>,
                    backgroundColor: 'rgba(0, 128, 255, 0.6)',
                    borderColor: 'rgba(0, 128, 255, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });

        // Loan Balance Chart
        const ctxLoans = document.getElementById('loansChart').getContext('2d');
        new Chart(ctxLoans, {
            type: 'bar',
            data: {
                labels: <?= json_encode($member_names) ?>,
                datasets: [{
                    label: 'Total Loans (Ksh)',
                    data: <?= json_encode($loan_data) ?>,
                    backgroundColor: 'rgba(255, 102, 0, 0.6)',
                    borderColor: 'rgba(255, 102, 0, 1)',
                    borderWidth: 1
                }, {
                    label: 'Loan Balances (Ksh)',
                    data: <?= json_encode($loan_balances) ?>,
                    backgroundColor: 'rgba(255, 51, 51, 0.6)',
                    borderColor: 'rgba(255, 51, 51, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>

    <a href="index.php" class="back-btn">Back to Dashboard</a>
</body>
</html>

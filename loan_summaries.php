<?php
include 'config.php';

// Fetch loan summaries for each member
$query = "
    SELECT 
        members.member_code,
        members.name,
        IFNULL(SUM(loans.amount), 0) AS total_loans,
        IFNULL(SUM(loan_repayments.amount), 0) AS total_repaid,
        (IFNULL(SUM(loans.amount), 0) - IFNULL(SUM(loan_repayments.amount), 0)) AS balance
    FROM members
    LEFT JOIN loans ON members.id = loans.member_id
    LEFT JOIN loan_repayments ON loans.id = loan_repayments.loan_id
    GROUP BY members.id
    ORDER BY members.member_code
";

$result = $conn->query($query);
include('components/header.php');?>

<body>
<div class="whole-page">
    <div class="nav-bar-wrapper">
        <?php include('components/navbar.php');?>
    </div>
    <div class="page-wrapper">
    <?php include('components/topbar.php');?>
    <table>
        <thead>
            <tr>
                <th>Member Code</th>
                <th>Member Name</th>
                <th>Total Loans (Ksh)</th>
                <th>Total Repaid (Ksh)</th>
                <th>Loan Balance (Ksh)</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['member_code']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= number_format($row['total_loans'], 2) ?></td>
                    <td><?= number_format($row['total_repaid'], 2) ?></td>
                    <td><?= number_format($row['balance'], 2) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
            </div>
            </div>
</body>
</html>

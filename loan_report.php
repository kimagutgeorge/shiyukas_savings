<?php
include 'config.php';

$report_query = $conn->query("
    SELECT m.member_code, m.name,
           COUNT(l.id) AS total_loans,
           IFNULL(SUM(l.amount), 0) AS total_loan_amount,
           IFNULL(SUM(l.amount) - IFNULL(SUM(r.amount), 0), 0) AS total_outstanding_balance
    FROM members m
    LEFT JOIN loans l ON m.id = l.member_id
    LEFT JOIN loan_repayments r ON l.id = r.loan_id
    GROUP BY m.id, m.member_code, m.name
");
include('components/header.php');?>
<div class="whole-page">
    <div class="nav-bar-wrapper">
        <?php include('components/navbar.php');?>
    </div>
    <div class="page-wrapper">
    <?php include('components/topbar.php');?>
    <div class="page-wrapper-inner">
    <table>
        <thead>
            <tr>
                <th>Member Code</th>
                <th>Member Name</th>
                <th>Total Loans Taken</th>
                <th>Total Loan Amount (Ksh)</th>
                <th>Total Outstanding Balance (Ksh)</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $report_query->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($row['member_code']) ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= $row['total_loans'] ?></td>
                <td>Ksh <?= number_format($row['total_loan_amount'], 2) ?></td>
                <td>Ksh <?= number_format($row['total_outstanding_balance'], 2) ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
            </div>
            </div>
            </div>
</body>
</html>

<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php'); // Redirect to login if not logged in
    exit;
}
?>


<?php
include 'config.php';

// Get filter inputs (with defaults)
$filter_member = $_GET['member'] ?? '';
$start_date = $_GET['start_date'] ?? '';
$end_date = $_GET['end_date'] ?? '';

// Fetch member data
$members = $conn->query("SELECT * FROM members");

// Base queries
$contribution_query = "
    SELECT members.member_code, members.name, IFNULL(SUM(contributions.amount), 0) AS total_contributed
    FROM members
    LEFT JOIN contributions ON members.id = contributions.member_id
";

$loan_query = "
    SELECT members.member_code, members.name,
           IFNULL(SUM(loans.amount), 0) AS total_loans,
           (IFNULL(SUM(loans.amount), 0) - IFNULL(SUM(loan_repayments.amount), 0)) AS loan_balance
    FROM members
    LEFT JOIN loans ON members.id = loans.member_id
    LEFT JOIN loan_repayments ON loans.id = loan_repayments.loan_id
";

// Filters
if ($filter_member) {
    $contribution_query .= " WHERE members.member_code = '$filter_member'";
    $loan_query .= " WHERE members.member_code = '$filter_member'";
}

if ($start_date && $end_date) {
    $contribution_query .= ($filter_member ? " AND" : " WHERE") . " contributions.contribution_date BETWEEN '$start_date' AND '$end_date'";
    $loan_query .= ($filter_member ? " AND" : " WHERE") . " loans.loan_date BETWEEN '$start_date' AND '$end_date'";
}

$contribution_query .= " GROUP BY members.id ORDER BY members.member_code";
$loan_query .= " GROUP BY members.id ORDER BY members.member_code";

$contributions = $conn->query($contribution_query);
$loans = $conn->query($loan_query);

// Prepare data for charts
$member_names = [];
$contribution_data = [];
$loan_data = [];
$loan_balances = [];
$arrears_data = [];

while ($row = $contributions->fetch_assoc()) {
    $member_names[] = $row['member_code'];
    $contribution_data[] = $row['total_contributed'];
    $arrears_data[] = max(0, (date('W') * 150) - $row['total_contributed']); // Calculate arrears
}

while ($row = $loans->fetch_assoc()) {
    $loan_data[] = $row['total_loans'];
    $loan_balances[] = $row['loan_balance'];
}
//header
include('components/header.php');
?>
<body>
<div class="whole-page">
    <div class="nav-bar-wrapper">
        <?php include('components/navbar.php');?>
    </div>
    <div class="page-wrapper">
    <?php include('components/topbar.php');?>
    <div class="page-wrapper-inner">
    <!-- Filters -->
    <form method="GET" class="filter-form">
        <div class="form-group">
        <label for="member">Filter by Member:</label>
        <select name="member" id="member">
            <option value="">All Members</option>
            <?php while ($member = $members->fetch_assoc()): ?>
                <option value="<?= htmlspecialchars($member['member_code']) ?>" <?= $filter_member === $member['member_code'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($member['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        </div>
        <div class="form-group">
        <label for="start_date">From:</label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
        </div>
        <div class="form-group">
        <label for="end_date">To:</label>
        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
        </div>
        <div class="form-group">
        <button type="submit" style="margin-top: 28px;">Apply</button>
        </div>
        
    </form>

    <!-- Loan Summaries -->
    <h2>Loan Summaries</h2>
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
            <?php $loans->data_seek(0); while ($row = $loans->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['member_code']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= number_format($row['total_loans'], 2) ?></td>
                    <td><?= number_format($row['total_loans'] - $row['loan_balance'], 2) ?></td>
                    <td><?= number_format($row['loan_balance'], 2) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <!-- Charts -->
    <h2>Contributions and Loans</h2>
    <div class="chart-container">
        <canvas id="contributionsChart"></canvas>
    </div>

    <div class="chart-container">
        <canvas id="loansChart"></canvas>
    </div>

    <div class="chart-container">
        <canvas id="arrearsChart"></canvas>
    </div>
    </div>
            </div>
            </div>
    <script>
        const members = <?= json_encode($member_names) ?>;
        const contributions = <?= json_encode($contribution_data) ?>;
        const loans = <?= json_encode($loan_data) ?>;
        const balances = <?= json_encode($loan_balances) ?>;
        const arrears = <?= json_encode($arrears_data) ?>;

        // Contribution Chart
        new Chart(document.getElementById('contributionsChart'), {
            type: 'bar',
            data: {
                labels: members,
                datasets: [{
                    label: 'Total Contributions (Ksh)',
                    data: contributions,
                    backgroundColor: 'rgba(0, 128, 255, 0.6)',
                    borderColor: 'rgba(0, 128, 255, 1)',
                    borderWidth: 1
                }]
            }
        });

        // Loan Chart
        new Chart(document.getElementById('loansChart'), {
            type: 'bar',
            data: {
                labels: members,
                datasets: [{
                    label: 'Total Loans (Ksh)',
                    data: loans,
                    backgroundColor: 'rgba(255, 102, 0, 0.6)',
                    borderColor: 'rgba(255, 102, 0, 1)',
                    borderWidth: 1
                }, {
                    label: 'Loan Balances (Ksh)',
                    data: balances,
                    backgroundColor: 'rgba(255, 51, 51, 0.6)',
                    borderColor: 'rgba(255, 51, 51, 1)',
                    borderWidth: 1
                }]
            }
        });

        // Arrears Chart
        new Chart(document.getElementById('arrearsChart'), {
            type: 'bar',
            data: {
                labels: members,
                datasets: [{
                    label: 'Arrears (Ksh)',
                    data: arrears,
                    backgroundColor: 'rgba(255, 0, 0, 0.6)',
                    borderColor: 'rgba(255, 0, 0, 1)',
                    borderWidth: 1
                }]
            }
        });
    </script>


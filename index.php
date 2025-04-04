<?php
include('components/header.php');
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php'); // Redirect to login if not logged in
    exit;
}
?>
<div class="whole-page">
    <div class="nav-bar-wrapper">
        <?php include('components/navbar.php');?>
    </div>
    <div class="page-wrapper">
    <?php include('components/topbar.php');?>
    <div class="page-wrapper-inner">
    <?php
// Database connection
require 'config.php';

// Pagination setup
$limit = 100000;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get filter inputs
$filter = isset($_GET['filter']) ? $conn->real_escape_string($_GET['filter']) : '';
$loan_status = isset($_GET['loan_status']) ? $_GET['loan_status'] : 'all';

// Filter conditions
$filter_condition = $filter ? "AND (m.member_code LIKE '%$filter%' OR m.name LIKE '%$filter%')" : '';

$loan_condition = '';
if ($loan_status === 'with_loans') {
    $loan_condition = "AND m.id IN (SELECT DISTINCT member_id FROM loans)";
} elseif ($loan_status === 'without_loans') {
    $loan_condition = "AND m.id NOT IN (SELECT DISTINCT member_id FROM loans)";
}

// Count total rows for pagination
$total_query = $conn->query("
    SELECT COUNT(*) as total 
    FROM members m 
    WHERE 1=1 $loan_condition $filter_condition
");
$total_rows = $total_query->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Contributions query
$contributions_query = $conn->query("
    SELECT m.member_code, m.name, IFNULL(SUM(c.amount), 0) AS total_contributed
    FROM members m
    LEFT JOIN contributions c ON m.id = c.member_id
    WHERE 1=1 $loan_condition $filter_condition
    GROUP BY m.id
    LIMIT $limit OFFSET $offset
");

// Loan query
$loan_query = $conn->query("
    SELECT 
        m.member_code,
        m.name,
        IFNULL(SUM(l.amount), 0) AS total_loans,
        (IFNULL(SUM(l.amount), 0) - 
         IFNULL((SELECT SUM(r.amount) FROM loan_repayments r WHERE r.loan_id = l.id), 0)) AS loan_balance
    FROM members m
    LEFT JOIN loans l ON m.id = l.member_id
    WHERE 1=1 $loan_condition $filter_condition
    GROUP BY m.id
    LIMIT $limit OFFSET $offset
");

// Net savings query
$net_savings_query = $conn->query("
    SELECT 
        m.member_code,
        m.name,
        (IFNULL((SELECT SUM(c.amount) FROM contributions c WHERE c.member_id = m.id), 0) - 
        IFNULL((SELECT SUM(l.amount) - IFNULL(SUM(r.amount), 0) FROM loans l 
                LEFT JOIN loan_repayments r ON l.id = r.loan_id 
                WHERE l.member_id = m.id), 0)) AS net_savings
    FROM members m
    WHERE 1=1 $loan_condition $filter_condition
    GROUP BY m.id
    LIMIT $limit OFFSET $offset
");

// Page header

echo "
<body>
    <div class='container'>

        <form class='filter-form' method='GET'>
            <input type='text' name='filter' class='form-input' placeholder='Search by Member Code or Name' value='$filter'>
            <select name='loan_status' class='form-input'>
                <option value='all' " . ($loan_status === 'all' ? 'selected' : '') . ">All Members</option>
                <option value='with_loans' " . ($loan_status === 'with_loans' ? 'selected' : '') . ">With Loans</option>
                <option value='without_loans' " . ($loan_status === 'without_loans' ? 'selected' : '') . ">Without Loans</option>
            </select>
            <button type='submit'>Filter</button>
            <button class='btn-export'> <a class='btn-export' href='?export=true' class='csv-btn'>Export</a></button>
        </form>

        <!-- Contributions Table -->
        <div class='section'>
            <h2>Member Contributions</h2>
            <table>
                <thead>
                    <tr><th>Member Code</th><th>Name</th><th>Total Contributions (Ksh)</th></tr>
                </thead>
                <tbody>";
while ($row = $contributions_query->fetch_assoc()) {
    echo "<tr><td>{$row['member_code']}</td><td>{$row['name']}</td><td>{$row['total_contributed']}</td></tr>";
}
echo "</tbody></table></div>";

        // Loans Table
        echo "<div class='section'>
            <h2>Loan Balances</h2>
            <table>
                <thead>
                    <tr><th>Member Code</th><th>Name</th><th>Total Loans (Ksh)</th><th>Loan Balance (Ksh)</th></tr>
                </thead>
                <tbody>";
while ($row = $loan_query->fetch_assoc()) {
    echo "<tr><td>{$row['member_code']}</td><td>{$row['name']}</td><td>{$row['total_loans']}</td><td>{$row['loan_balance']}</td></tr>";
}
echo "</tbody></table></div>";

        // Net Savings Table
        echo "<div class='section'>
            <h2>Net Savings</h2>
            <table>
                <thead>
                    <tr><th>Member Code</th><th>Name</th><th>Net Savings (Ksh)</th></tr>
                </thead>
                <tbody>";
while ($row = $net_savings_query->fetch_assoc()) {
    echo "<tr><td>{$row['member_code']}</td><td>{$row['name']}</td><td>{$row['net_savings']}</td></tr>";
}
echo "</tbody></table></div></div></body></html>";
?>

    </div>
</div>
</div>
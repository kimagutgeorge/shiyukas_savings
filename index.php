<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php'); // Redirect to login if not logged in
    exit;
}
?>


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
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Family Chama Dashboard</title>
    <link rel='stylesheet' href='style.css'>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: auto;
            background: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #0056b3;
            text-align: center;
        }
        nav {
            background-color: #0056b3;
            padding: 10px;
            margin-bottom: 20px;
        }
        nav a {
            color: white;
            margin-right: 15px;
            text-decoration: none;
        }
        nav a:hover {
            text-decoration: underline;
        }
        .section {
            background: #f9f9f9;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 5px solid #0056b3;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            color: #0056b3;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #0056b3;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .filter-form {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        .filter-form input, .filter-form select, .filter-form button {
            padding: 8px;
        }
        .csv-btn {
            padding: 8px 15px;
            background-color: #ff7f50;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h1>Family Savings Dashboard</h1>

    <nav>
        <a href='dashboard.php'>Dashboard</a>
        <a href='contributions.php'>Add Contribution</a>
        <a href='loans.php'>Add Loan</a>
        <a href='loan_repayments.php'>Pay Loan</a>
        <strong>Reports:</strong>
        <a href='loan_history.php'>Loan History</a>
        <a href='loan_report.php'>Loan Repayments</a>
        <a href='loan_summaries.php'>Loan Summaries</a>
        <a href='contributions_history.php'>Contributions History</a>
        <a href='login.php'>Logout</a>
    </nav>

    <div class='container'>

        <form class='filter-form' method='GET'>
            <input type='text' name='filter' placeholder='Search by Member Code or Name' value='$filter'>
            <select name='loan_status'>
                <option value='all' " . ($loan_status === 'all' ? 'selected' : '') . ">All Members</option>
                <option value='with_loans' " . ($loan_status === 'with_loans' ? 'selected' : '') . ">With Loans</option>
                <option value='without_loans' " . ($loan_status === 'without_loans' ? 'selected' : '') . ">Without Loans</option>
            </select>
            <button type='submit'>Filter</button>
            <a href='?export=true' class='csv-btn'>Export CSV</a>
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

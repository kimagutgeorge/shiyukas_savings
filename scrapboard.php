<?php
include 'config.php';



$loan_query = "
    SELECT 
        m.member_code,
        m.name,
        IFNULL(SUM(l.amount), 0) AS total_loans,
        (IFNULL(SUM(l.amount), 0) - 
         (SELECT IFNULL(SUM(r.amount), 0) 
          FROM loan_repayments r 
          WHERE r.loan_id = l.id)) AS loan_balance
    FROM members m
    LEFT JOIN loans l ON m.id = l.member_id
    GROUP BY m.id
";


// Get all members with their total contributions and outstanding loan balances
$summary_query = $conn->query("
    SELECT m.id, m.member_code, m.name,
           IFNULL(SUM(c.amount), 0) AS total_contributions,
           IFNULL(SUM(l.amount) - IFNULL(SUM(r.amount), 0), 0) AS loan_balance
    FROM members m
    LEFT JOIN contributions c ON m.id = c.member_id
    LEFT JOIN loans l ON m.id = l.member_id
    LEFT JOIN loan_repayments r ON l.id = r.loan_id
    GROUP BY m.id, m.member_code, m.name
");

?>



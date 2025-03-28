<?php
include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = $_POST['member_id'];
    $amount = $_POST['amount'];
    $date = date('Y-m-d');

    $sql = "INSERT INTO loans (member_id, amount, loan_date) 
            VALUES ('$member_id', '$amount', '$date')";

    if ($conn->query($sql) === TRUE) {
        header("Location: index.php?success=Loan/Withdrawal recorded successfully!");
    } else {
        header("Location: index.php?error=Failed to record loan/withdrawal.");
    }
    $conn->close();
}
?>

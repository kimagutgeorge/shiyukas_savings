<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $member_id = intval($_POST['member_id']);
    $amount = floatval($_POST['amount']);

    if ($amount <= 0) {
        header("Location: index.php?error=Invalid amount.");
        exit();
    }

    // Get active loan balance
    $loan_query = $conn->prepare("SELECT id, balance FROM loans WHERE member_id = ? AND balance > 0 LIMIT 1");
    $loan_query->bind_param("i", $member_id);
    $loan_query->execute();
    $loan = $loan_query->get_result()->fetch_assoc();

    $loan_id = $loan['id'] ?? null;
    $loan_balance = floatval($loan['balance'] ?? 0);

    $repay_loan = 0;
    $to_savings = 0;

    if ($loan_balance > 0) {
        if ($amount >= $loan_balance) {
            $repay_loan = $loan_balance;
            $to_savings = $amount - $loan_balance;

            // Mark loan as fully paid
            $clear_loan_query = $conn->prepare("UPDATE loans SET balance = 0 WHERE id = ?");
            $clear_loan_query->bind_param("i", $loan_id);
            $clear_loan_query->execute();
        } else {
            $repay_loan = $amount;
            $to_savings = 0;

            // Reduce loan balance
            $reduce_loan_query = $conn->prepare("UPDATE loans SET balance = balance - ? WHERE id = ?");
            $reduce_loan_query->bind_param("di", $repay_loan, $loan_id);
            $reduce_loan_query->execute();
        }

        // Log loan repayment
        $repayment_query = $conn->prepare("INSERT INTO loan_repayments (member_id, loan_id, amount, repayment_date) VALUES (?, ?, ?, NOW())");
        $repayment_query->bind_param("iid", $member_id, $loan_id, $repay_loan);
        $repayment_query->execute();
    } else {
        $to_savings = $amount;
    }

    // Add to contributions
    if ($to_savings > 0) {
        $contribution_query = $conn->prepare("INSERT INTO contributions (member_id, amount, contribution_date) VALUES (?, ?, NOW())");
        $contribution_query->bind_param("id", $member_id, $to_savings);
        $contribution_query->execute();
    }

    header("Location: index.php?success=Deposit processed. Loan repaid: Ksh $repay_loan. Saved: Ksh $to_savings.");
    exit();
}
?>

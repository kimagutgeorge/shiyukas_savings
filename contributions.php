<?php
include 'config.php';

$success_message = '';
$error_message = '';

// Fetch members for the dropdown
$members_query = $conn->query("SELECT id, member_code, name FROM members ORDER BY member_code");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $member_id = intval($_POST['member_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 150);
    $contribution_date = date('Y-m-d', strtotime($_POST['contribution_date'] ?? 'today'));

    if ($member_id > 0 && $amount > 0) {
        $stmt = $conn->prepare("INSERT INTO contributions (member_id, amount, contribution_date) VALUES (?, ?, ?)");
        $stmt->bind_param("ids", $member_id, $amount, $contribution_date);

        if ($stmt->execute()) {
            $success_message = "Contribution of Ksh " . number_format($amount, 2) . " recorded!";
        } else {
            $error_message = "Error recording contribution: " . $conn->error;
        }
    } else {
        $error_message = "Please select a valid member and enter an amount.";
    }
}
include('components/header.php');?>
<body>
<div class="whole-page">
    <div class="nav-bar-wrapper">
        <?php include('components/navbar.php');?>
    </div>
    <div class="page-wrapper">
    <?php include('components/topbar.php');?>
    <div class="page-wrapper-inner">
    <?php if ($success_message): ?>
        <p class="success"><?= htmlspecialchars($success_message) ?></p>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <p class="error"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <form action="contributions.php" method="POST">
        <label for="member_id">Select Member:</label>
        <select name="member_id" id="member_id" required>
            <option value="">-- Select Member --</option>
            <?php while ($member = $members_query->fetch_assoc()): ?>
                <option value="<?= $member['id'] ?>">
                    <?= htmlspecialchars($member['member_code'] . " - " . $member['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label for="amount">Amount (Ksh):</label>
        <input type="number" id="amount" name="amount" value="150" min="1" step="0.01" required>

        <label for="contribution_date">Contribution Date:</label>
        <input type="date" id="contribution_date" name="contribution_date" value="<?= date('Y-m-d') ?>" required>

        <button type="submit">Submit Contribution</button>
    </form>

            </div>
            </div>
            </div>
</body>
</html>

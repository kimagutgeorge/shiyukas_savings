<?php
include 'config.php';

$weekly_target = 150;
$start_date = new DateTime('2024-01-01'); // Adjust to when your savings started
$current_date = new DateTime();
$weeks_elapsed = floor($start_date->diff($current_date)->days / 7);

// Fetch contribution history
$query = "
    SELECT m.member_code, m.name, c.amount, c.contribution_date
    FROM contributions c
    JOIN members m ON c.member_id = m.id
    ORDER BY c.contribution_date DESC
";
$result = $conn->query($query);

// Calculate total contributions and arrears
$member_totals = [];
while ($row = $result->fetch_assoc()) {
    $member_code = $row['member_code'];
    if (!isset($member_totals[$member_code])) {
        $member_totals[$member_code] = ['name' => $row['name'], 'total' => 0, 'contributions' => []];
    }
    $member_totals[$member_code]['total'] += $row['amount'];
    $member_totals[$member_code]['contributions'][] = $row;
}

// Add arrears calculation
foreach ($member_totals as $code => &$member) {
    $expected_contribution = $weeks_elapsed * $weekly_target;
    $member['arrears'] = max(0, $expected_contribution - $member['total']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contribution History</title>
    <link rel="stylesheet" href="style.css">
</head>
<!-- <body>
    <h1>Member Contribution History</h1>

    <table>
        <thead>
            <tr>
                <th>Member Code</th>
                <th>Name</th>
                <th>Total Contributions (Ksh)</th>
                <th>Arrears (Ksh)</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($member_totals as $code => $member): ?>
                <tr>
                    <td><?= htmlspecialchars($code) ?></td>
                    <td><?= htmlspecialchars($member['name']) ?></td>
                    <td><?= number_format($member['total'], 2) ?></td>
                    <td style="color: <?= $member['arrears'] > 0 ? 'red' : 'green' ?>">
                        <?= number_format($member['arrears'], 2) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table> -->

    <h2>All Contributions</h2>
    <table>
        <thead>
            <tr>
                <th>Member Code</th>
                <th>Name</th>
                <th>Amount (Ksh)</th>
                <th>Contribution Date</th>
            </tr>
        </thead>
        <tbody>
            <?php $result->data_seek(0); // Reset pointer for re-use ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['member_code']) ?></td>
                    <td><?= htmlspecialchars($row['name']) ?></td>
                    <td><?= number_format($row['amount'], 2) ?></td>
                    <td><?= htmlspecialchars($row['contribution_date']) ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <a href="index.php" class="back-btn">Back to Dashboard</a>
    <a href="contributions.php" class="back-btn">Add Contribution</a>
</body>
</html>

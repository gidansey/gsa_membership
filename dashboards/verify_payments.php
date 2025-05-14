<?php
$timeout_duration = 1800; // 30 minutes

session_start();

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Secretariat') {
    header("Location: ../index.php");
    exit;
}

// Handle payment verification
if (isset($_GET['verify_id'])) {
    $payment_id = intval($_GET['verify_id']);
    $conn->query("UPDATE payments SET verified = 1 WHERE id = $payment_id");
    header("Location: verify_payments.php");
    exit;
}

$isDashboard = true;
include '../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="secretariat_dashboard.php">Dashboard</a>
            <a href="approve_members.php">Approve Members</a>
            <a href="verify_payments.php" class="active">Verify Payments</a>
            <a href="issue_letters.php">Membership Letters</a>
            <a href="generate_reports.php">Generate Reports</a>
            <a href="view_logs.php">Audit Logs</a>
            <a href="send_notifications.php">Send Notifications</a>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>Verify Payments</h1>
            <p>Confirm payments made by members before issuing membership letters.</p>
        </header>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Amount Paid</th>
                        <th>Payment Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $query = "SELECT p.id, CONCAT(m.first_name, ' ', m.other_names, ' ', m.last_name) AS full_name, 
                                 p.amount_paid, p.payment_date, p.verified 
                          FROM payments p 
                          JOIN members m ON p.member_id = m.id 
                          ORDER BY p.payment_date DESC";
                $result = $conn->query($query);

                if ($result && $result->num_rows > 0):
                    while ($row = $result->fetch_assoc()):
                        $badgeClass = $row['verified'] ? 'success' : 'warning';
                        $statusText = $row['verified'] ? 'Verified' : 'Pending';
                ?>
                    <tr>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td>GHS <?= number_format($row['amount_paid'], 2) ?></td>
                        <td><?= date('M d, Y H:i', strtotime($row['payment_date'])) ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= $statusText ?></span></td>
                        <td>
                            <?php if (!$row['verified']): ?>
                                <a href="?verify_id=<?= $row['id'] ?>" class="btn">Verify</a>
                            <?php else: ?>
                                <span class="badge info">Done</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php
                    endwhile;
                else:
                ?>
                    <tr><td colspan="5">No payments found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

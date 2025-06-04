<?php
session_start();
require_once '../includes/db_connect.php';

// Timeout and access check
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Secretariat'])) {
    header("Location: ../index.php");
    exit;
}

$member_id = intval($_GET['member_id'] ?? 0);
if (!$member_id) {
    die("Invalid member ID.");
}

// Fetch member info
$member_stmt = $conn->prepare("
    SELECT m.member_id, m.first_name, m.other_names, m.last_name, a.branch_id, b.branch_name, mt.type_name 
    FROM members m
    JOIN affiliations a ON m.id = a.member_id
    JOIN branches b ON a.branch_id = b.id
    LEFT JOIN member_category mc ON m.id = mc.member_id
    LEFT JOIN membership_types mt ON mc.membership_type_id = mt.id
    WHERE m.id = ?
");
$member_stmt->bind_param("i", $member_id);
$member_stmt->execute();
$member = $member_stmt->get_result()->fetch_assoc();
$member_stmt->close();

if (!$member) {
    die("Member not found.");
}

// Get renewal info
$renewal_stmt = $conn->prepare("SELECT * FROM renewals WHERE member_id = ? ORDER BY renewal_date DESC LIMIT 1");
$renewal_stmt->bind_param("i", $member_id);
$renewal_stmt->execute();
$renewal = $renewal_stmt->get_result()->fetch_assoc();
$renewal_stmt->close();

// Get all payments
$payments_stmt = $conn->prepare("
    SELECT p.*, YEAR(p.payment_date) AS payment_year, mt.annual_dues 
    FROM payments p
    LEFT JOIN membership_types mt ON p.membership_type_id = mt.id
    WHERE p.member_id = ?
    ORDER BY p.payment_date DESC
");
$payments_stmt->bind_param("i", $member_id);
$payments_stmt->execute();
$payments_result = $payments_stmt->get_result();
$payments = [];
while ($row = $payments_result->fetch_assoc()) {
    $row['status'] = $row['amount_paid'] >= $row['annual_dues'] ? 'Paid' : ($row['amount_paid'] > 0 ? 'Partial' : 'Pending');
    $payments[] = $row;
}
$payments_stmt->close();

$isDashboard = true;
include '../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="manage_members.php">Manage Members</a>
                <a href="manage_payments.php" class="active">Manage Payments</a>
                <a href="manage_events.php">Events</a>
                <a href="manage_users.php">User Accounts</a>
                <a href="generate_reports.php">Generate Reports</a>
                <a href="view_logs.php" >Audit Logs</a>
                <a href="settings.php">Settings</a>
                <a href="send_notifications.php" >Send Notifications</a>
            <?php elseif ($_SESSION['role'] === 'Secretariat'): ?>
                <a href="secretariat_dashboard.php">Dashboard</a>
                <a href="manage_payments.php" class="active">Manage Payments</a>
                <a href="approve_members.php">Approve Members</a>
                <a href="verify_payments.php">Verify Payments</a>
                <a href="issue_letters.php">Membership Letters</a>
                <a href="generate_reports.php">Generate Reports</a>
                <a href="view_logs.php" >Audit Logs</a>
                <a href="send_notifications.php">Send Notifications</a>
            <?php endif; ?>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>Member Payment History</h1>
        </header>

        <div class="card">
            <h2><?= htmlspecialchars($member['first_name'] . ' ' . $member['other_names'] . ' ' . $member['last_name']) ?></h2>
            <p><strong>Member ID:</strong> <?= htmlspecialchars($member['member_id']) ?></p>
            <p><strong>Branch:</strong> <?= htmlspecialchars($member['branch_name']) ?></p>
            <p><strong>Membership Type:</strong> <?= htmlspecialchars($member['type_name'] ?? 'N/A') ?></p>
            <?php if ($renewal): ?>
                <p><strong>Renewal Date:</strong> <?= date('M d, Y', strtotime($renewal['renewal_date'])) ?></p>
                <p><strong>Expiry Date:</strong> <?= date('M d, Y', strtotime($renewal['expiry_date'])) ?></p>
            <?php endif; ?>
        </div>

        <div class="table-card">
            <h3>All Payments</h3>
            <table>
                <tr>
                    <th>Payment Date</th>
                    <th>Amount (GH¢)</th>
                    <th>For Year</th>
                    <th>Payment Mode</th>
                    <th>Status</th>
                </tr>
                <?php if ($payments): ?>
                    <?php foreach ($payments as $p): 
                        $mode = strtolower($p['payment_mode'] ?? '');
                        $mode_class = match ($mode) {
                            'mobile money' => 'badge info',
                            'bank deposit' => 'badge success',
                            'cash'         => 'badge neutral',
                            default        => 'badge warning'
                        };
                    ?>
                        <tr>
                            <td><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                            <td><?= number_format($p['amount_paid'], 2) ?></td>
                            <td><?= $p['payment_year'] ?></td>
                            <td><span class="<?= $mode_class ?>"><?= htmlspecialchars($p['payment_mode'] ?? 'Unknown') ?></span></td>
                            <td><span class="badge <?= strtolower($p['status']) ?>"><?= $p['status'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No payment history found.</td></tr>
                <?php endif; ?>
            </table>
            <a href="manage_payments.php" class="badge info">← Back to Payments</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

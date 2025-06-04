<?php
session_start();
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Secretariat'])) {
    header("Location: ../index.php");
    exit();
}

$isDashboard = true;

// Filters
$selected_year = $_GET['year'] ?? date('Y');
$search_name = $_GET['member'] ?? '';
$filter_branch = $_GET['branch_id'] ?? '';
$filter_type = $_GET['membership_type_id'] ?? '';

// Years
$years = [];
$res = $conn->query("SELECT DISTINCT YEAR(payment_date) as year FROM payments ORDER BY year DESC");
while ($row = $res->fetch_assoc()) {
    $years[] = $row['year'];
}

// Branches and Types
$branches = $conn->query("SELECT id, branch_name FROM branches ORDER BY branch_name")->fetch_all(MYSQLI_ASSOC);
$types = $conn->query("SELECT id, type_name FROM membership_types ORDER BY type_name")->fetch_all(MYSQLI_ASSOC);

// SQL Build
$where = "WHERE YEAR(p.payment_date) = ?";
$params = [$selected_year];
$typestr = 'i';

if (!empty($search_name)) {
    $where .= " AND (m.first_name LIKE ? OR m.last_name LIKE ?)";
    $params[] = "%$search_name%";
    $params[] = "%$search_name%";
    $typestr .= 'ss';
}
if ($filter_branch !== '') {
    $where .= " AND b.id = ?";
    $params[] = $filter_branch;
    $typestr .= 'i';
}
if ($filter_type !== '') {
    $where .= " AND mt.id = ?";
    $params[] = $filter_type;
    $typestr .= 'i';
}

// Query
$sql = "
SELECT p.*, m.id AS internal_id, m.member_id, m.first_name, m.last_name, b.branch_name, mt.type_name, mt.annual_dues
FROM payments p
JOIN members m ON p.member_id = m.id
JOIN affiliations a ON m.id = a.member_id
JOIN branches b ON a.branch_id = b.id
JOIN membership_types mt ON p.membership_type_id = mt.id
$where
ORDER BY p.payment_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param($typestr, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$payments = [];
while ($row = $result->fetch_assoc()) {
    $row['status'] = $row['amount_paid'] >= $row['annual_dues'] ? 'Paid' : ($row['amount_paid'] > 0 ? 'Partial' : 'Pending');
    $payments[] = $row;
}

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
        <?php if (isset($_GET['message'])): ?>
            <div class="alert success" style="margin: 1rem 0; background: #e0f8e9; padding: 10px; border: 1px solid #b2dfc1; color: #256029;">
                <?= htmlspecialchars($_GET['message']) ?>
            </div>
        <?php endif; ?>

        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>Manage Payments</h1>
        </header>

        <form method="GET" class="search-form">
            <select name="year">
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= $y == $selected_year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>

            <input type="text" name="member" placeholder="Search Member" value="<?= htmlspecialchars($search_name) ?>">

            <select name="branch_id">
                <option value="">All Branches</option>
                <?php foreach ($branches as $b): ?>
                    <option value="<?= $b['id'] ?>" <?= $b['id'] == $filter_branch ? 'selected' : '' ?>><?= $b['branch_name'] ?></option>
                <?php endforeach; ?>
            </select>

            <select name="membership_type_id">
                <option value="">All Types</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $t['id'] == $filter_type ? 'selected' : '' ?>><?= $t['type_name'] ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Filter</button>
            <a href="record_payment.php" class="btn btn-primary" style="margin-left:auto;">➕ Record Payment</a>
        </form>

        <div class="table-card">
            <h3>Payments (<?= $selected_year ?>)</h3>
            <table>
                <tr>
                    <th>Member ID</th>
                    <th>Name</th>
                    <th>Branch</th>
                    <th>Membership Type</th>
                    <th>Amount (GH¢)</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php if ($payments): ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['member_id']) ?></td>
                            <td><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></td>
                            <td><?= htmlspecialchars($p['branch_name']) ?></td>
                            <td><?= htmlspecialchars($p['type_name']) ?></td>
                            <td><?= number_format($p['amount_paid'], 2) ?></td>
                            <td><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                            <td><span class="badge <?= strtolower($p['status']) ?>"><?= $p['status'] ?></span></td>
                            <td>
                                <a href="view_payments.php?member_id=<?= $p['internal_id'] ?>" class="badge info">💰 View Payments</a>
                                <a href="edit_payment.php?id=<?= $p['id'] ?>" class="badge success">✏ Edit</a>
                                <a href="delete_payment.php?id=<?= $p['id'] ?>" class="badge danger" onclick="return confirm('Delete this payment?')">🗑 Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="8">No payment records found.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

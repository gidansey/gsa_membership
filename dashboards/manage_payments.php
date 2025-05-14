<?php
    $timeout_duration = 1800; // 30 minutes

    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        header("Location: ../includes/timeout.php");
        exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time();

    session_start();
    require_once '../includes/db_connect.php';

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
        header("Location: ../index.php");
        exit;
    }

    $isDashboard = true;

    // Get filter inputs
    $selected_year = $_GET['year'] ?? date('Y');
    $search_name = $_GET['member'] ?? '';

    // Fetch available years
    $years = [];
    $yearResult = $conn->query("SELECT DISTINCT YEAR(payment_date) as year FROM payments ORDER BY year DESC");
    while ($row = $yearResult->fetch_assoc()) {
        $years[] = $row['year'];
    }

    // Prepare filter SQL
    $where = "WHERE YEAR(p.payment_date) = ?";
    $params = [$selected_year];
    $types = 'i';

    if (!empty($search_name)) {
        $where .= " AND (m.first_name LIKE ? OR m.last_name LIKE ?)";
        $search_term = '%' . $search_name . '%';
        $params[] = $search_term;
        $params[] = $search_term;
        $types .= 'ss';
    }

    // Fetch payments
    $sql = "
        SELECT m.first_name, m.last_name, p.amount_paid, p.payment_date, mt.annual_dues
        FROM payments p
        JOIN members m ON p.member_id = m.id
        JOIN membership_types mt ON p.membership_type_id = mt.id
        $where
        ORDER BY p.payment_date DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $row['status'] = $row['amount_paid'] >= $row['annual_dues']
            ? 'Paid'
            : ($row['amount_paid'] > 0 ? 'Partial' : 'Pending');
        $payments[] = $row;
    }

    include '../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_members.php">Manage Members</a>
            <a href="manage_payments.php" class="active">Manage Payments</a>
            <a href="manage_events.php">Events</a>
            <a href="manage_users.php">User Accounts</a>
            <a href="generate_reports.php">Generate Reports</a>
            <a href="view_logs.php">Audit Logs</a>
            <a href="settings.php">Settings</a>
            <a href="send_notifications.php">Send Notifications</a>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>Manage Payments</h1>
        </header>

        <form method="GET" style="margin: 15px 0;">
            <label for="year">Year:</label>
            <select name="year" id="year">
                <?php foreach ($years as $y): ?>
                    <option value="<?= $y ?>" <?= $y == $selected_year ? 'selected' : '' ?>><?= $y ?></option>
                <?php endforeach; ?>
            </select>

            <label for="member">Search Member:</label>
            <input type="text" name="member" id="member" value="<?= htmlspecialchars($search_name) ?>" placeholder="Name">

            <button type="submit">Filter</button>
        </form>

        <div class="table-card">
            <h3>Payments (<?= $selected_year ?>)</h3>
            <table>
                <tr>
                    <th>Member</th>
                    <th>Amount (GH¢)</th>
                    <th>Date</th>
                    <th>Status</th>
                </tr>
                <?php if ($payments): ?>
                    <?php foreach ($payments as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></td>
                            <td><?= number_format($p['amount_paid'], 2) ?></td>
                            <td><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                            <td><span class="badge <?= strtolower($p['status']) ?>"><?= $p['status'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">No payment records found.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
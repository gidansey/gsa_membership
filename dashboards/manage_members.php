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
        exit();
    }

    // Filter
    $filter_status = $_GET['status'] ?? '';
    $where = $filter_status ? "WHERE status = '$filter_status'" : '';

    // Fetch members
    $sql = "SELECT * FROM members $where ORDER BY date_registered DESC";
    $result = $conn->query($sql);
    $members = $result->fetch_all(MYSQLI_ASSOC);

    $isDashboard = true;
    include '../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_members.php" class="active">Manage Members</a>
            <a href="manage_payments.php">Manage Payments</a>
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
            <h1>Manage Members</h1>
            <p>Filter and approve/reject members</p>
        </header>

        <form method="GET" style="margin: 15px 0;">
            <label>Filter by Status:</label>
            <select name="status" onchange="this.form.submit()">
                <option value="">-- All --</option>
                <option value="Pending" <?= $filter_status == 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Approved" <?= $filter_status == 'Approved' ? 'selected' : '' ?>>Approved</option>
                <option value="Rejected" <?= $filter_status == 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                <option value="Inactive" <?= $filter_status == 'Inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </form>

        <div class="table-card">
            <h3><?= $filter_status ? "$filter_status Members" : "All Members" ?></h3>
            <table>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Date Registered</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
                <?php if ($members): ?>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></td>
                            <td><?= htmlspecialchars($m['email']) ?></td>
                            <td><?= date('M d, Y', strtotime($m['date_registered'])) ?></td>
                            <td><span class="badge <?= strtolower($m['status']) ?>"><?= $m['status'] ?></span></td>
                            <td>
                                <?php if ($m['status'] === 'Pending'): ?>
                                    <a href="update_member_status.php?id=<?= $m['id'] ?>&action=approve" class="badge approved">Approve</a>
                                    <a href="update_member_status.php?id=<?= $m['id'] ?>&action=reject" class="badge rejected">Reject</a>
                                <?php endif; ?>
                                <a href="delete_member.php?id=<?= $m['id'] ?>" onclick="return confirm('Delete this member?')" class="badge pending">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No members found.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

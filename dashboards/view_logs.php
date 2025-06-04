<?php
session_start();
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

require_once '../includes/db_connect.php';

if (!in_array($_SESSION['role'], ['Admin', 'Secretariat'])) {
    header("Location: ../index.php");
    exit;
}

$isDashboard = true;

$where = [];
$params = [];
$types = '';

if (!empty($_GET['user'])) {
    $where[] = "u.username LIKE ?";
    $params[] = "%" . $_GET['user'] . "%";
    $types .= 's';
}

if (!empty($_GET['action'])) {
    $where[] = "a.action = ?";
    $params[] = $_GET['action'];
    $types .= 's';
}

$logs_per_page = 25;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $logs_per_page;

$count_sql = "SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id";
if ($where) {
    $count_sql .= " WHERE " . implode(' AND ', $where);
}
$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_logs = $count_stmt->get_result()->fetch_row()[0];
$total_pages = ceil($total_logs / $logs_per_page);

$sql = "SELECT a.*, 
               u.first_name AS user_first, u.last_name AS user_last,
               au.username AS affected_username,
               m.first_name AS member_first, m.last_name AS member_last
        FROM audit_logs a
        LEFT JOIN users u ON a.user_id = u.id
        LEFT JOIN users au ON a.affected_id = au.id AND a.table_name = 'users'
        LEFT JOIN members m ON a.affected_id = m.id AND a.table_name = 'members'";

if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY a.timestamp DESC LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if ($params) {
    $types .= 'ii';
    $params[] = $logs_per_page;
    $params[] = $offset;
    $stmt->bind_param($types, ...$params);
} else {
    $stmt->bind_param("ii", $logs_per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);

include '../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <?php if ($_SESSION['role'] === 'Admin'): ?>
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="manage_members.php">Manage Members</a>
                <a href="manage_payments.php" >Manage Payments</a>
                <a href="manage_events.php">Events</a>
                <a href="manage_users.php">User Accounts</a>
                <a href="generate_reports.php">Generate Reports</a>
                <a href="view_logs.php" class="active">Audit Logs</a>
                <a href="settings.php">Settings</a>
                <a href="send_notifications.php" >Send Notifications</a>
            <?php elseif ($_SESSION['role'] === 'Secretariat'): ?>
                <a href="secretariat_dashboard.php">Dashboard</a>
                <a href="manage_payments.php" >Manage Payments</a>
                <a href="approve_members.php">Approve Members</a>
                <a href="verify_payments.php">Verify Payments</a>
                <a href="issue_letters.php">Membership Letters</a>
                <a href="generate_reports.php">Generate Reports</a>
                <a href="view_logs.php" class="active">Audit Logs</a>
                <a href="send_notifications.php">Send Notifications</a>
            <?php endif; ?>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <h1>Audit Logs</h1>
            <p>Track system activities and user actions</p>
        </header>

        <form method="GET" style="margin-bottom: 20px;">
            <label>Filter by User:</label>
            <input type="text" name="user" value="<?= htmlspecialchars($_GET['user'] ?? '') ?>" placeholder="Username">
            <label style="margin-left: 15px;">Action Type:</label>
            <select name="action">
                <option value="">All</option>
                <option <?= ($_GET['action'] ?? '') === 'Login Success' ? 'selected' : '' ?>>Login Success</option>
                <option <?= ($_GET['action'] ?? '') === 'Login Failed' ? 'selected' : '' ?>>Login Failed</option>
                <option <?= ($_GET['action'] ?? '') === 'User Logout' ? 'selected' : '' ?>>User Logout</option>
                <option <?= ($_GET['action'] ?? '') === 'Deleted Event' ? 'selected' : '' ?>>Deleted Event</option>
                <option <?= ($_GET['action'] ?? '') === 'Deactivated member' ? 'selected' : '' ?>>Deactivated member</option>
                <option <?= ($_GET['action'] ?? '') === 'Reactivated member' ? 'selected' : '' ?>>Reactivated member</option>
            </select>
            <button type="submit">Filter</button>
        </form>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Target</th>
                        <th>Timestamp</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs): ?>
                        <?php foreach ($logs as $log): 
                            $action = $log['action'];
                            $badge = 'neutral';
                            if (stripos($action, 'Login Success') !== false) $badge = 'success';
                            elseif (stripos($action, 'Login Failed') !== false) $badge = 'warning';
                            elseif (stripos($action, 'Blocked') !== false || stripos($action, 'Locked') !== false) $badge = 'danger';
                            elseif (stripos($action, 'Unlocked') !== false) $badge = 'info';

                            $target = '';
                            if (!empty($log['affected_username'])) {
                                $target = htmlspecialchars($log['affected_username']);
                            } elseif ($log['table_name'] === 'members') {
                                $fullName = trim(($log['member_first'] ?? '') . ' ' . ($log['member_last'] ?? ''));
                                $target = $fullName ? htmlspecialchars($fullName) : "Member ID #" . $log['affected_id'];
                            } elseif ($log['table_name'] === 'events') {
                                $target = "Event ID #" . $log['affected_id'];
                            } else {
                                $target = $log['affected_id'] ?? '';
                            }
                        ?>
                            <tr>
                                <td><?= htmlspecialchars(trim(($log['user_first'] ?? '') . ' ' . ($log['user_last'] ?? '')) ?: 'Unknown') ?></td>
                                <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($action) ?></span></td>
                                <td><?= htmlspecialchars($log['table_name'] ?? '-') ?></td>
                                <td><?= $target ?></td>
                                <td><?= date('M d, Y H:i', strtotime($log['timestamp'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No audit logs found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>">« First</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">‹ Prev</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" <?= $i === $page ? 'class="active"' : '' ?>><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next ›</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>">Last »</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

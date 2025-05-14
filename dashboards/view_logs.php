<?php
session_start();

// Session timeout logic
$timeout_duration = 1800; // 30 minutes
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Database connection
require_once '../includes/db_connect.php';

// Role-based access control
if (!in_array($_SESSION['role'], ['Admin', 'Secretariat'])) {
    header("Location: ../index.php");
    exit;
}

$isDashboard = true;

// Filtering conditions
$where = [];
$params = [];
$types = '';

// Filter by user
if (!empty($_GET['user'])) {
    $where[] = "u.username LIKE ?";
    $params[] = "%" . $_GET['user'] . "%";
    $types .= 's';
}

// Filter by action
if (!empty($_GET['action'])) {
    $where[] = "a.action = ?";
    $params[] = $_GET['action'];
    $types .= 's';
}

// Pagination setup
$logs_per_page = 25;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $logs_per_page;

// Count total logs
$count_sql = "SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id";
if ($where) {
    $count_sql .= " WHERE " . implode(' AND ', $where);
}
$count_stmt = $conn->prepare($count_sql);
if ($params) {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$total_logs = $count_result->fetch_row()[0];
$total_pages = ceil($total_logs / $logs_per_page);

// Fetch paginated logs
$sql = "SELECT a.*, 
               u.first_name, u.last_name, 
               affected_user.username AS affected_username
        FROM audit_logs a 
        LEFT JOIN users u ON a.user_id = u.id 
        LEFT JOIN users affected_user ON a.affected_id = affected_user.id AND a.table_name = 'users'";

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
                <a href="manage_payments.php">Manage Payments</a>
                <a href="manage_events.php">Events</a>
                <a href="manage_users.php">User Accounts</a>
                <a href="generate_reports.php">Generate Reports</a>
                <a href="view_logs.php" class="active">Audit Logs</a>
                <a href="settings.php">Settings</a>
                <a href="send_notifications.php">Send Notifications</a>
            <?php elseif ($_SESSION['role'] === 'Secretariat'): ?>
                <a href="secretariat_dashboard.php">Dashboard</a>
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
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>Audit Logs</h1>
            <p>Track system activities and user actions</p>
        </header>

        <form method="GET" style="margin-bottom: 20px;">
            <label>Filter by User:</label>
            <input type="text" name="user" placeholder="Username" value="<?= htmlspecialchars($_GET['user'] ?? '') ?>" style="padding:6px;border-radius:4px;border:1px solid #ccc;">

            <label style="margin-left: 15px;">Action Type:</label>
            <select name="action" style="padding:6px;border-radius:4px;">
                <option value="">All</option>
                <option <?= ($_GET['action'] ?? '') === 'Login Success' ? 'selected' : '' ?>>Login Success</option>
                <option <?= ($_GET['action'] ?? '') === 'Login Failed' ? 'selected' : '' ?>>Login Failed</option>
                <option <?= ($_GET['action'] ?? '') === 'Login Blocked (Locked)' ? 'selected' : '' ?>>Login Blocked (Locked)</option>
                <option <?= ($_GET['action'] ?? '') === 'Logout' ? 'selected' : '' ?>>Logout</option>
                <option <?= ($_GET['action'] ?? '') === 'Deleted Event' ? 'selected' : '' ?>>Deleted Event</option>
                <option <?= ($_GET['action'] ?? '') === 'Unlocked User' ? 'selected' : '' ?>>Unlocked User</option>
            </select>

            <button type="submit" style="padding:6px 12px;border-radius:4px;background:#2f3640;color:white;border:none;margin-left:10px;">Filter</button>
        </form>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>Username</th>
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
                        ?>
                            <tr>
                                <td>
                                    <?php
                                        $fullName = trim(($log['first_name'] ?? '') . ' ' . ($log['last_name'] ?? ''));
                                        echo htmlspecialchars($fullName ?: 'Unknown');
                                    ?>
                                </td>
                                <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($action) ?></span></td>
                                <td><?= htmlspecialchars($log['table_name']) ?></td>
                                <td>
                                    <?php if (!empty($log['affected_username'])): ?>
                                        <?= htmlspecialchars($log['affected_username']) ?>
                                    <?php elseif ($log['table_name'] === 'members'): ?>
                                        <a href="view_member.php?id=<?= $log['affected_id'] ?>">View</a>
                                    <?php elseif ($log['table_name'] === 'events'): ?>
                                        <a href="manage_events.php?id=<?= $log['affected_id'] ?>">Event #<?= $log['affected_id'] ?></a>
                                    <?php else: ?>
                                        <?= $log['affected_id'] ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M d, Y H:i', strtotime($log['timestamp'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No audit logs available.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="pagination-item" title="First Page">«</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination-item" title="Previous">‹</a>
                    <?php endif; ?>
                    
                    <?php
                    // Show page numbers with ellipsis for large ranges
                    $visible_pages = 5;
                    $start_page = max(1, $page - floor($visible_pages/2));
                    $end_page = min($total_pages, $start_page + $visible_pages - 1);
                    
                    if ($start_page > 1) {
                        echo '<span class="pagination-ellipsis">...</span>';
                    }
                    
                    for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="pagination-item <?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor;
                    
                    if ($end_page < $total_pages) {
                        echo '<span class="pagination-ellipsis">...</span>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination-item" title="Next">›</a>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $total_pages])) ?>" class="pagination-item" title="Last Page">»</a>
                    <?php endif; ?>
                </div>
                <div class="pagination-info">
                    Showing <?= ($offset + 1) ?>-<?= min($offset + $logs_per_page, $total_logs) ?> of <?= $total_logs ?> records
                </div>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>
<!-- 
<style>
    .pagination-container {
        margin-top: 25px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }
    
    .pagination {
        display: flex;
        gap: 5px;
    }
    
    .pagination-item {
        padding: 8px 12px;
        border-radius: 4px;
        background-color: #f5f5f5;
        color: #333;
        text-decoration: none;
        transition: all 0.3s ease;
        border: 1px solid #ddd;
        min-width: 36px;
        text-align: center;
    }
    
    .pagination-item:hover {
        background-color: #e9e9e9;
        transform: translateY(-2px);
    }
    
    .pagination-item.active {
        background-color: #2f3640;
        color: white;
        border-color: #2f3640;
        font-weight: bold;
    }
    
    .pagination-ellipsis {
        padding: 8px 12px;
        color: #666;
    }
    
    .pagination-info {
        color: #666;
        font-size: 0.9em;
    }
</style> -->

<?php include '../includes/footer.php'; ?>

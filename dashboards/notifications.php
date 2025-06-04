<?php
session_start();
$timeout_duration = 1800;
date_default_timezone_set('Africa/Accra');

// Timeout check
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$isDashboard = true;

// Handle AJAX requests for marking individual notifications
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'mark_read' && isset($_POST['notification_id'])) {
        $notification_id = (int)$_POST['notification_id'];
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        $success = $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => $success]);
        exit;
    }
    
    if ($_POST['action'] === 'mark_all_read') {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $success = $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => $success]);
        exit;
    }
    
    if ($_POST['action'] === 'clear_all') {
        $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $success = $stmt->execute();
        $stmt->close();
        
        echo json_encode(['success' => $success]);
        exit;
    }
}

// Pagination setup
$per_page = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Filters & search inputs from GET
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filter_unread = isset($_GET['filter_unread']) ? true : false;
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : '';

// Build query with filters
$where_clauses = ["user_id = ?"];
$params = [$user_id];
$types = "i";

if ($filter_unread) {
    $where_clauses[] = "is_read = 0";
}

if ($filter_type && in_array($filter_type, ['event', 'membership', 'payment'])) {
    $where_clauses[] = "type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if ($search !== '') {
    $where_clauses[] = "message LIKE ?";
    $params[] = "%$search%";
    $types .= "s";
}

$where_sql = implode(" AND ", $where_clauses);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM notifications WHERE $where_sql";
$stmt = $conn->prepare($count_sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$count_result = $stmt->get_result()->fetch_assoc();
$total_notifications = $count_result['total'];
$stmt->close();

$total_pages = ceil($total_notifications / $per_page);

// Get filtered notifications with limit and offset
$sql = "SELECT id, message, link, created_at, is_read, type
        FROM notifications
        WHERE $where_sql
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

// Add limit and offset types/params
$types_with_limit = $types . "ii";
$params_with_limit = array_merge($params, [$per_page, $offset]);

// Bind params dynamically
$stmt->bind_param($types_with_limit, ...$params_with_limit);
$stmt->execute();
$result = $stmt->get_result();

include '../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <?php if ($user_role === 'Admin'): ?>
                <a href="admin_dashboard.php">Dashboard</a>
                <a href="manage_members.php">Manage Members</a>
                <a href="manage_payments.php">Manage Payments</a>
                <a href="manage_events.php">Events</a>
                <a href="manage_users.php">User Accounts</a>
                <a href="generate_reports.php">Generate Reports</a>
                <a href="view_logs.php">Audit Logs</a>
                <a href="settings.php">Settings</a>
                <a href="send_notifications.php">Send Notifications</a>

            <?php elseif ($user_role === 'Secretariat'): ?>
                <a href="secretariat_dashboard.php">Dashboard</a>
                <a href="manage_payments.php">Manage Payments</a>
                <a href="approve_members.php">Approve Members</a>
                <a href="verify_payments.php">Verify Payments</a>
                <a href="issue_letters.php">Membership Letters</a>
                <a href="generate_reports.php">Generate Reports</a>
                <a href="view_logs.php">Audit Logs</a>
                <a href="send_notifications.php">Send Notifications</a>

            <?php elseif ($user_role === 'Branch Leader'): ?>
                <a href="branch_dashboard.php">Dashboard</a>
                <a href="my_members.php">My Members</a>
                <a href="branch_events.php">Branch Events</a>
                <a href="branch_announcement.php">Announcements</a>
                <a href="branch_report.php">Reports</a>

            <?php elseif ($user_role === 'Member'): ?>
                <a href="member_dashboard.php">Dashboard</a>
                <a href="edit_profile.php">Edit Profile</a>
                <a href="pay_dues.php">Pay Dues</a>
                <a href="event_history.php">Event History & Feedback</a>
            <?php endif; ?>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>All Notifications</h1>
        </header>

        <div class="table-card">
            <h3>📬 Your Notifications</h3>

            <!-- Filters & Search Form -->
            <form method="get" style="margin-bottom: 1em;">
                <input type="text" name="search" placeholder="Search notifications..." value="<?= htmlspecialchars($search) ?>" />

                <label><input type="checkbox" name="filter_unread" <?= $filter_unread ? 'checked' : '' ?> /> Unread only</label>

                <select name="filter_type">
                    <option value="">-- Filter by type --</option>
                    <option value="event" <?= $filter_type === 'event' ? 'selected' : '' ?>>Event</option>
                    <option value="membership" <?= $filter_type === 'membership' ? 'selected' : '' ?>>Membership</option>
                    <option value="payment" <?= $filter_type === 'payment' ? 'selected' : '' ?>>Payment</option>
                </select>

                <button type="submit">Apply</button>
                <a href="notifications.php" style="margin-left: 1em;">Clear Filters</a>
            </form>

            <!-- Action Buttons -->
            <div style="margin-bottom: 1em; display: flex; gap: 10px;">
                <button id="markAllReadBtn">Mark All as Read</button>
                <button id="clearAllBtn">Clear All</button>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th style="width: 40px;"></th>
                            <th>Message</th>
                            <th>Type</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($notif = $result->fetch_assoc()): ?>
                            <tr class="notification-row <?= $notif['is_read'] ? 'read' : 'unread' ?>" data-id="<?= $notif['id'] ?>">
                                <td>
                                    <input type="checkbox" class="notification-checkbox" data-id="<?= $notif['id'] ?>">
                                </td>
                                <td>
                                    <?php if (!empty($notif['link'])): ?>
                                        <?php
                                            $link = htmlspecialchars($notif['link']);
                                            // Adjust relative paths to stay within dashboard context
                                            if (strpos($link, 'http') !== 0 && strpos($link, '/') !== 0) {
                                                // Prepend "../" if link doesn't already start with "/" or "http"
                                                $link = "../" . ltrim($link, './');
                                            }
                                        ?>
                                        <a href="<?= $link ?>" target="_blank">
                                            <?= htmlspecialchars($notif['message']) ?>
                                        </a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($notif['message']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= ucfirst(htmlspecialchars($notif['type'])) ?></td>
                                <td><?= date('M d, Y h:i A', strtotime($notif['created_at'])) ?></td>
                                <td>
                                    <span class="badge <?= $notif['is_read'] ? 'neutral' : 'info' ?>">
                                        <?= $notif['is_read'] ? 'Read' : 'Unread' ?>
                                    </span>
                                    <?php if (!$notif['is_read']): ?>
                                        <button class="mark-read-btn" data-id="<?= $notif['id'] ?>">Mark as Read</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div style="margin-top: 1em;">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">« Prev</a>
                    <?php endif; ?>

                    Page <?= $page ?> of <?= $total_pages ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Next »</a>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <div class="alert info">You have no notifications matching your criteria.</div>
            <?php endif; ?>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mark individual notification as read
    document.querySelectorAll('.mark-read-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const notificationId = this.getAttribute('data-id');
            markAsRead(notificationId);
        });
    });

    // Mark all as read
    document.getElementById('markAllReadBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to mark all notifications as read?')) {
            markAllAsRead();
        }
    });

    // Clear all notifications
    document.getElementById('clearAllBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to clear all notifications? This action cannot be undone.')) {
            clearAllNotifications();
        }
    });

    // Mark notification as read when clicking anywhere on the row
    document.querySelectorAll('.notification-row').forEach(row => {
        row.addEventListener('click', function(e) {
            // Don't trigger if clicking on a link or button
            if (e.target.tagName === 'A' || e.target.tagName === 'BUTTON' || e.target.classList.contains('notification-checkbox')) {
                return;
            }
            
            const notificationId = this.getAttribute('data-id');
            const isRead = this.classList.contains('read');
            
            if (!isRead) {
                markAsRead(notificationId);
            }
        });
    });

    function markAsRead(notificationId) {
        fetch('notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=mark_read&notification_id=${notificationId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`.notification-row[data-id="${notificationId}"]`);
                if (row) {
                    row.classList.remove('unread');
                    row.classList.add('read');
                    const statusBadge = row.querySelector('.badge');
                    if (statusBadge) {
                        statusBadge.textContent = 'Read';
                        statusBadge.classList.remove('info');
                        statusBadge.classList.add('neutral');
                    }
                    const markReadBtn = row.querySelector('.mark-read-btn');
                    if (markReadBtn) {
                        markReadBtn.remove();
                    }
                }
            }
        });
    }

    function markAllAsRead() {
        fetch('notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=mark_all_read'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelectorAll('.notification-row').forEach(row => {
                    row.classList.remove('unread');
                    row.classList.add('read');
                    const statusBadge = row.querySelector('.badge');
                    if (statusBadge) {
                        statusBadge.textContent = 'Read';
                        statusBadge.classList.remove('info');
                        statusBadge.classList.add('neutral');
                    }
                    const markReadBtn = row.querySelector('.mark-read-btn');
                    if (markReadBtn) {
                        markReadBtn.remove();
                    }
                });
            }
        });
    }

    function clearAllNotifications() {
        fetch('notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=clear_all'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }
});
</script>

<?php include '../includes/footer.php'; ?>
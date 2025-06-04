<?php
    $timeout_duration = 1800; // 30 minutes

    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        header("Location: ../includes/timeout.php");
        exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time();

    session_start();
    require_once '../includes/db_connect.php';

    if ($_SESSION['role'] !== 'Admin') {
        header("Location: ../index.php");
        exit;
    }

    $isDashboard = true;

    // ✅ Handle unlock action
    if (isset($_GET['action']) && $_GET['action'] === 'unlock' && isset($_GET['id'])) {
        $unlock_id = intval($_GET['id']);
        $conn->query("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = $unlock_id");

        // Log unlock action
        $admin_id = $_SESSION['user_id'];
        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, table_name, affected_id) VALUES (?, ?, ?, ?)");
        $action = "Unlocked Account";
        $table = "users";
        $stmt->bind_param("issi", $admin_id, $action, $table, $unlock_id);
        $stmt->execute();

        header("Location: manage_users.php?success=User+unlocked");
        exit;
    }

    // ✅ Fetch users including lock status
    $sql = "SELECT id, username, first_name, last_name, email, role, status, created_at, failed_attempts, locked_until 
            FROM users ORDER BY created_at DESC";
    $result = $conn->query($sql);
    $users = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    include '../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_members.php">Manage Members</a>
            <a href="manage_payments.php">Manage Payments</a>
            <a href="manage_events.php">Events</a>
            <a href="manage_users.php" class="active">User Accounts</a>
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
            <h1>User Accounts</h1>
            <p>Manage system users and their roles</p>
        </header>

        <div style="margin-bottom: 20px;">
            <a href="add_user.php" class="badge approved">+ Add New User</a>
        </div>

        <div class="table-card">
            <table>
                <tr>
                    <th>Full Name</th>
                    <th>Username</th>                    
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
                <?php if ($users): ?>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td><?= $u['role'] ?></td>
                            <td><span class="badge <?= strtolower($u['status']) ?>"><?= $u['status'] ?></span></td>
                            <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <a href="reset_user_password.php?id=<?= $u['id'] ?>" class="badge partial">Reset Password</a>
                                <a href="change_role.php?id=<?= $u['id'] ?>" class="badge approved">Change Role</a>
                                <?php if ($u['status'] === 'Active'): ?>
                                    <a href="deactivate_user.php?id=<?= $u['id'] ?>" class="badge rejected">Deactivate</a>
                                <?php else: ?>
                                    <a href="activate_user.php?id=<?= $u['id'] ?>" class="badge approved">Activate</a>
                                <?php endif; ?>

                                <?php if (!empty($u['locked_until']) && strtotime($u['locked_until']) > time()): ?>
                                    <a href="manage_users.php?action=unlock&id=<?= $u['id'] ?>" class="badge rejected" title="Locked until <?= date('M d, Y H:i', strtotime($u['locked_until'])) ?>">
                                        🔓 Unlock
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7">No users found.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

<?php
    $timeout_duration = 1800; // 30 minutes

    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        header("Location: ../includes/timeout.php");
        exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time();

    session_start();
    require_once '../includes/db_connect.php';

    if (!in_array($_SESSION['role'], ['Admin', 'Secretariat'])) {
        header("Location: ../index.php");
        exit;
    }

    $isDashboard = true;
    $success = $error = '';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $target_role = $_POST['target_role'] ?? '';
        $message = trim($_POST['message']);

        if (!$target_role || !$message) {
            $error = "All fields are required.";
        } else {
            $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, status, type)
                SELECT id, ?, 'Unread', 'Broadcast' FROM users WHERE role = ?");
            $stmt->bind_param("ss", $message, $target_role);
            $stmt->execute();
            $success = "Notification sent to all $target_role users.";
        }
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
                <a href="manage_payments.php">Manage Payments</a>
                <a href="manage_events.php">Events</a>
                <a href="manage_users.php">User Accounts</a>
                <a href="generate_reports.php">Generate Reports</a>
                <a href="view_logs.php" >Audit Logs</a>
                <a href="settings.php">Settings</a>
                <a href="send_notifications.php" class="active">Send Notifications</a>
            <?php elseif ($_SESSION['role'] === 'Secretariat'): ?>
                <a href="secretariat_dashboard.php">Dashboard</a>
                <a href="approve_members.php">Approve Members</a>
                <a href="verify_payments.php">Verify Payments</a>
                <a href="issue_letters.php">Membership Letters</a>
                <a href="generate_reports.php">Generate Reports</a>
                <a href="view_logs.php" >Audit Logs</a>
                <a href="send_notifications.php" class="active">Send Notifications</a>
            <?php endif; ?>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>Send Notifications</h1>
        </header>
        <div class="table-card" style="max-width: 600px; margin: auto;">
            <?php if ($error): ?><p style="color:red"><?= $error ?></p><?php endif; ?>
            <?php if ($success): ?><p style="color:green"><?= $success ?></p><?php endif; ?>

            <form method="POST">
                <label>Send To Role</label>
                <select name="target_role" required>
                    <option value="">-- Select Role --</option>
                    <option value="Member">Member</option>
                    <option value="Branch Leader">Branch Leader</option>
                    <option value="Secretariat">Secretariat</option>
                </select>

                <label>Message</label>
                <textarea name="message" rows="5" required style="width:100%;padding:10px;border-radius:6px;margin-bottom:15px;border:1px solid #ccc;"></textarea>

                <button type="submit" style="width:100%;padding:12px;background:#3498db;color:#fff;border:none;border-radius:8px;">Send Notification</button>
            </form>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

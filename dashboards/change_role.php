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

    $user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $success = $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_role = $_POST['role'];
        if ($new_role) {
            $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->bind_param("si", $new_role, $user_id);
            $stmt->execute();
            $success = "Role updated successfully.";
        } else {
            $error = "Please select a role.";
        }
    }

    $isDashboard = true;
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
            <a href="#">Generate Reports</a>
            <a href="view_logs.php">Audit Logs</a>
            <a href="#">Settings</a>
            <a href="#">Send Notifications</a>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>Change User Role</h1>
        </header>

        <div class="table-card" style="max-width: 500px; margin: auto;">
            <?php if ($error): ?><p style="color:red"><?= $error ?></p><?php endif; ?>
            <?php if ($success): ?><p style="color:green"><?= $success ?></p><?php endif; ?>

            <form method="POST">
                <label>Select New Role</label>
                <select name="role" required style="padding:10px;width:100%;margin-bottom:20px;border-radius:6px;border:1px solid #ccc">
                    <option value="">-- Choose Role --</option>
                    <option value="Admin">Admin</option>
                    <option value="Secretariat">Secretariat</option>
                    <option value="Branch Leader">Branch Leader</option>
                    <option value="Member">Member</option>
                </select>

                <button type="submit" style="width:100%;padding:12px;background:#2ecc71;color:#fff;border:none;border-radius:8px;">Update Role</button>
            </form>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

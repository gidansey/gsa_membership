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
                <a href="generate_reports.php" class="active">Generate Reports</a>
                <a href="view_logs.php">Audit Logs</a>
                <a href="settings.php">Settings</a>
                <a href="send_notifications.php">Send Notifications</a>

            <?php elseif ($user_role === 'Secretariat'): ?>
                <a href="secretariat_dashboard.php">Dashboard</a>
                <a href="approve_members.php">Approve Members</a>
                <a href="verify_payments.php">Verify Payments</a>
                <a href="issue_letters.php">Membership Letters</a>
                <a href="generate_reports.php" class="active">Generate Reports</a>
                <a href="view_logs.php">Audit Logs</a>
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
            <h1>Generate Reports</h1>
        </header>

        <div class="table-card">
            <h3>Downloadable Reports</h3>
            <ul style="line-height: 2;">
                <li><a href="export_event_participation_csv.php" class="button">📄 Export Participation CSV</a></li>
                <li><a href="export_payments_csv.php" class="button">📄 Export Payments CSV</a></li>
                <li><a href="export_event_participation_csv.php" class="button">📄 Export Participation CSV</a></li>
            </ul>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

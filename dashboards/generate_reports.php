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

$user_role = $_SESSION['role'];
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
                <a href="manage_payments.php">Manage Payments</a>
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
            <p>Download various CSV reports for auditing and analysis.</p>
        </header>

        <div class="table-card" style="max-width: 700px; margin: auto;">
            <h3 style="margin-bottom: 20px;">📂 Available Report Exports</h3>
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <a href="export_event_participation_csv.php" class="btn btn-primary">📄 Export Event Participation CSV</a>
                <a href="export_payments_csv.php" class="btn btn-primary">💵 Export Payments CSV</a>
                <a href="export_members_csv.php" class="btn btn-primary">👥 Export Members CSV</a>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

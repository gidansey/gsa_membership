<?php
session_start();
$timeout_duration = 1800;
date_default_timezone_set('Africa/Accra');

// Timeout
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Check Secretariat
require_once '../includes/db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Secretariat') {
    header("Location: ../index.php");
    exit;
}

// Get user
$full_name = '';
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($user = $result->fetch_assoc()) {
    $full_name = $user['first_name'] . ' ' . $user['last_name'];
}
$stmt->close();

// Stats
$pending_registrations = $conn->query("SELECT COUNT(*) AS count FROM members WHERE status = 'Pending'")
    ->fetch_assoc()['count'] ?? 0;

$dues_collected = $conn->query("SELECT SUM(amount_paid) AS total FROM payments")
    ->fetch_assoc()['total'] ?? 0;

$verified_members = $conn->query("SELECT COUNT(*) AS count FROM members WHERE status = 'Approved'")
    ->fetch_assoc()['count'] ?? 0;

$reports_generated = 4; // placeholder

$isDashboard = true;
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secretariat Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="secretariat_dashboard.php" class="active">Dashboard</a>
            <a href="approve_members.php">Approve Members</a>
            <a href="verify_payments.php">Verify Payments</a>
            <a href="issue_letters.php">Membership Letters</a>
            <a href="generate_reports.php">Generate Reports</a>
            <a href="view_logs.php">Audit Logs</a>
            <a href="send_notifications.php">Send Notifications</a>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="toggleSidebar()">☰</div>
            <h1>Secretariat Dashboard</h1>
            <p>Welcome, <?= htmlspecialchars($full_name) ?></p>
        </header>

        <section class="cards">
            <div class="card"><p>Pending Registrations</p><h2><?= $pending_registrations ?></h2></div>
            <div class="card"><p>Dues Collected</p><h2>GHS <?= number_format($dues_collected, 2) ?></h2></div>
            <div class="card"><p>Reports Generated</p><h2><?= $reports_generated ?></h2></div>
            <div class="card"><p>Verified Members</p><h2><?= $verified_members ?></h2></div>
        </section>
    </main>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}
</script>
</body>
</html>

<?php include '../includes/footer.php'; ?>

<?php
    // Start session and output buffering
    ob_start();
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Connect to DB
    require_once __DIR__ . '/../db_connect.php';

    // Redirect to login if not authenticated
    if (!isset($_SESSION['user_id'])) {
        header("Location: ../index.php");
        exit;
    }

    // Security headers
    header("X-Frame-Options: DENY");
    header("X-Content-Type-Options: nosniff");
    header("Referrer-Policy: no-referrer");

    // User info
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'] ?? 'Guest';
    $header_name = $_SESSION['username'] ?? 'Guest';

    // Dashboard links
    $role_dashboards = [
        'Admin' => 'admin_dashboard.php',
        'Secretariat' => 'secretariat_dashboard.php',
        'Branch Leader' => 'branch_leader_dashboard.php',
        'Member' => 'member_dashboard.php'
    ];
    $dashboard_link = $role_dashboards[$user_role] ?? 'dashboard.php';

    // Notification count (optional)
    $unreadCount = 0;
    // Add query here if needed to fetch unread notifications

    // header.php or init.php
    $timeout_minutes = 30;

    if (isset($_SESSION['last_active']) && (time() - $_SESSION['last_active']) > ($timeout_minutes * 60)) {
        $user_id = $_SESSION['user_id'];

        // ⏱️ Audit log
        $action = "Session Timeout";
        $table = "users";
        $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, table_name, affected_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("issi", $user_id, $action, $table, $user_id);
        $stmt->execute();

        session_unset();
        session_destroy();
        header("Location: index.php?timeout=1");
        exit;
    }
    $_SESSION['last_active'] = time();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GSA Membership System</title>
    <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>

<?php if (!isset($isDashboard) || $isDashboard !== true): ?>
    <div class="navbar">
        <div class="nav-left">
            <a href="<?= $dashboard_link ?>">Dashboard</a>

            <?php if ($user_role === 'Admin'): ?>
                <a href="../manage_members.php">Manage Members</a>
                <a href="../manage_payments.php">Payments</a>
                <a href="../manage_events.php">Events</a>
                <a href="../manage_users.php">User Accounts</a>
                <a href="../reports.php">Reports</a>

            <?php elseif ($user_role === 'Secretariat'): ?>
                <a href="../verify_members.php">Approve Members</a>
                <a href="../process_dues.php">Process Dues</a>
                <a href="../reports.php">Reports</a>

            <?php elseif ($user_role === 'Branch Leader'): ?>
                <a href="../branch_members.php">My Branch Members</a>
                <a href="../branch_events.php">Branch Events</a>

            <?php elseif ($user_role === 'Member'): ?>
                <a href="../my_profile.php">My Profile</a>
                <a href="../pay_dues.php">Pay Dues</a>
                <a href="../my_events.php">My Events</a>
            <?php endif; ?>
        </div>

        <div class="nav-right">
            <span>Welcome, <?= htmlspecialchars($header_name) ?> (<?= $user_role ?>)</span>
            <a href="../logout.php" class="logout">Logout</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($isDashboard)): ?>
    <div class="container">
<?php endif; ?>

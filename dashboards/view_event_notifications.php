<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

$event_id = intval($_GET['id'] ?? 0);
if (!$event_id) {
    die("Invalid event ID.");
}

// Get event details
$event_stmt = $conn->prepare("SELECT name, event_date, location FROM events WHERE id = ?");
$event_stmt->bind_param("i", $event_id);
$event_stmt->execute();
$event = $event_stmt->get_result()->fetch_assoc();

if (!$event) {
    die("Event not found.");
}

// Get recipients
$recipient_stmt = $conn->prepare("
    SELECT u.first_name, u.last_name, u.email, ens.sent_at
    FROM event_notifications_sent ens
    JOIN users u ON ens.user_id = u.id
    WHERE ens.event_id = ?
    ORDER BY ens.sent_at DESC
");
$recipient_stmt->bind_param("i", $event_id);
$recipient_stmt->execute();
$recipients = $recipient_stmt->get_result();

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
            <a href="manage_events.php" class="active">Events</a>
            <a href="generate_reports.php">Generate Reports</a>
            <a href="view_logs.php">Audit Logs</a>
            <a href="settings.php">Settings</a>
        </nav>
        <form action="../logout.php" method="post">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>Notification Recipients</h1>
            <p>Below is a list of users who were sent notifications for the event.</p>
        </header>

        <div class="table-card">
            <h3><?= htmlspecialchars($event['name']) ?> <small style="font-weight: normal;">— <?= htmlspecialchars($event['location']) ?> on <?= date('M d, Y - h:i A', strtotime($event['event_date'])) ?></small></h3>

            <a href="manage_events.php" class="badge neutral" style="margin-bottom: 15px;">← Back to Events</a>

            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Sent At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recipients->num_rows > 0): ?>
                        <?php while ($row = $recipients->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= date('M d, Y h:i A', strtotime($row['sent_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3">No notifications have been sent for this event.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

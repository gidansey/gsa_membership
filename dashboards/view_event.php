<?php
session_start();
require_once '../includes/db_connect.php';

$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

$event_id = intval($_GET['id'] ?? 0);
if (!$event_id) {
    die("Invalid event ID.");
}

$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$event) {
    die("Event not found.");
}

$participants_stmt = $conn->prepare("
    SELECT m.member_id, m.first_name, m.last_name, ep.participation_date, ep.event_role
    FROM event_participation ep
    JOIN members m ON ep.member_id = m.id
    WHERE ep.event_id = ?
    ORDER BY ep.participation_date DESC
");
$participants_stmt->bind_param("i", $event_id);
$participants_stmt->execute();
$participants = $participants_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$participants_stmt->close();

$feedback_stmt = $conn->prepare("
    SELECT f.comment, f.date_submitted, m.first_name, m.last_name
    FROM feedback f
    JOIN members m ON f.member_id = m.id
    WHERE f.event_id = ?
    ORDER BY f.date_submitted DESC
");
$feedback_stmt->bind_param("i", $event_id);
$feedback_stmt->execute();
$feedback = $feedback_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$feedback_stmt->close();

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
            <a href="manage_users.php">User Accounts</a>
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
            <h1>View Event Details</h1>
        </header>

        <div class="card" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2><?= htmlspecialchars($event['name'] ?? 'Unnamed Event') ?></h2>
                <div>
                    <a href="edit_event.php?id=<?= $event['id'] ?>" class="badge approved">✏️ Edit Event</a>
                    <a href="manage_events.php" class="badge info">← Back to Events</a>
                </div>
            </div>

            <div class="member-details-grid">
                <div class="member-section">
                    <h3>Event Information</h3>
                    <p><strong>Date:</strong> 
                        <?= !empty($event['event_date']) ? date('F j, Y', strtotime($event['event_date'])) : 'N/A' ?>
                    </p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($event['location'] ?? 'N/A') ?></p>
                </div>

                <div class="member-section">
                    <h3>Description</h3>
                    <p><?= nl2br(htmlspecialchars($event['description'] ?? 'No description provided.')) ?></p>

                    <?php if (!empty($event['image_path']) && file_exists('../uploads/events/' . $event['image_path'])): ?>
                        <div style="margin-top: 10px;">
                            <strong>Event Image:</strong><br>
                            <a href="../uploads/events/<?= htmlspecialchars($event['image_path']) ?>" target="_blank">
                                <img src="../uploads/events/<?= htmlspecialchars($event['image_path']) ?>" alt="Event Image" style="max-width: 100%; max-height: 300px; border: 1px solid #ccc; border-radius: 8px;">
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <hr>

            <div class="member-section">
                <h3>Participants</h3>
                <?php if ($participants): ?>
                    <ul>
                        <?php foreach ($participants as $p): ?>
                            <li>
                                <?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?>
                                (<?= htmlspecialchars($p['event_role'] ?: 'Attendee') ?>) –
                                <?= date('F j, Y', strtotime($p['participation_date'])) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No participants recorded for this event.</p>
                <?php endif; ?>
            </div>

            <hr>

            <div class="member-section">
                <h3>Feedback</h3>
                <?php if ($feedback): ?>
                    <?php foreach ($feedback as $f): ?>
                        <div style="border: 1px solid #ccc; border-radius: 6px; padding: 10px; margin-bottom: 10px;">
                            <p><strong><?= htmlspecialchars($f['first_name'] . ' ' . $f['last_name']) ?></strong></p>
                            <p><?= nl2br(htmlspecialchars($f['comment'])) ?></p>
                            <p style="font-size: small; color: gray;">Submitted on <?= date('F j, Y - g:i A', strtotime($f['date_submitted'])) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No feedback submitted for this event.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

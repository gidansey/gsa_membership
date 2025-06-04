<?php
session_start();
// Session timeout: 30 minutes
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

require_once '../includes/db_connect.php';

// Only Admins can access
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
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    die("Event not found.");
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
            <h1>Event Details</h1>
        </header>

        <div class="table-card" style="max-width: 700px; margin: auto; padding: 20px;">
            <h2><?= htmlspecialchars($event['name']) ?></h2>
            <p><strong>Date & Time:</strong> <?= date('F j, Y - g:i A', strtotime($event['event_date'])) ?></p>
            <p><strong>Location:</strong> <?= htmlspecialchars($event['location']) ?></p>

            <?php if (!empty($event['description'])): ?>
                <div style="margin: 15px 0;">
                    <p><strong>Description / Notes:</strong></p>
                    <div style="background: #f9f9f9; padding: 12px; border-radius: 6px; border: 1px solid #ddd;">
                        <?= nl2br(htmlspecialchars($event['description'])) ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($event['image_path'])): ?>
                <div style="margin-top: 15px;">
                    <div class="thumbnail-view" onclick="openModal('../uploads/events/<?= htmlspecialchars($event['image_path']) ?>')">
                        <img src="../uploads/events/<?= htmlspecialchars($event['image_path']) ?>" alt="Event Image">
                    </div>
                </div>
            <?php else: ?>
                <p><em>No event image uploaded.</em></p>
            <?php endif; ?>

            <a href="manage_events.php" class="badge approved" style="margin-top: 25px; display: inline-block;">← Back to Events</a>
        </div>
    </main>
</div>

<!-- Image Modal -->
<div id="imageModal" style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); justify-content:center; align-items:center;">
    <img id="modalImage" src="" style="max-width:90%; max-height:90%; border:5px solid white; border-radius:8px;">
    <span onclick="closeModal()" style="position:absolute; top:20px; right:30px; font-size:36px; color:white; cursor:pointer;">&times;</span>
</div>

<script>
function openModal(imageSrc) {
    document.getElementById('modalImage').src = imageSrc;
    document.getElementById('imageModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('imageModal').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>

<?php
session_start();
require_once '../includes/db_connect.php';

$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

$isDashboard = true;
$success = isset($_GET['success']) && $_GET['success'] == 1;
$filter = $_GET['filter'] ?? 'all';
$where = match($filter) {
    'upcoming' => "WHERE event_date > NOW()",
    'past' => "WHERE event_date < NOW()",
    default => ''
};

$events = [];
$sql = "SELECT * FROM events $where ORDER BY event_date DESC";
$result = $conn->query($sql);
if ($result) {
    $events = $result->fetch_all(MYSQLI_ASSOC);
}

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
            <h1>Manage Events</h1>
        </header>

        <?php if ($success): ?>
            <div class="alert success">✅ Event added successfully.</div>
        <?php endif; ?>

        <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
            <div class="alert info">✏️ Event updated successfully.</div>
        <?php endif; ?>

        <?php if (isset($_GET['notified']) && $_GET['notified'] == 1): ?>
            <div class="alert success">📧 Event was sent to all members and branch leaders.</div>
        <?php endif; ?>

        <form method="GET" class="search-form">
            <label>Filter:</label>
            <select name="filter" onchange="this.form.submit()">
                <option value="all" <?= $filter === 'all' ? 'selected' : '' ?>>All</option>
                <option value="upcoming" <?= $filter === 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                <option value="past" <?= $filter === 'past' ? 'selected' : '' ?>>Past</option>
            </select>
        </form>

        <div style="margin-bottom: 20px;">
            <a href="add_event.php" class="badge approved">+ Add New Event</a>
        </div>

        <div class="table-card">
            <h3><?= ucfirst($filter) ?> Events</h3>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($events): ?>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td>
                                    <?php if (!empty($event['image_path'])): ?>
                                        <div class="thumbnail" onclick="openModal('../uploads/events/<?= htmlspecialchars($event['image_path']) ?>')">
                                            <img src="../uploads/events/<?= htmlspecialchars($event['image_path']) ?>" alt="<?= htmlspecialchars($event['name']) ?>">
                                        </div>
                                    <?php else: ?>
                                        <div class="no-thumbnail">No Image</div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($event['name']) ?></td>
                                <td><?= date('M d, Y - h:i A', strtotime($event['event_date'])) ?></td>
                                <td><?= htmlspecialchars($event['location']) ?></td>
                                <td>
                                    <a href="view_event.php?id=<?= $event['id'] ?>" class="badge info">View</a>
                                    <form method="POST" action="send_event_notification.php" style="display:inline;">
                                        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                        <?php if (!empty($event['attachment_path'])): ?>
                                            <label style="font-size: 12px;">
                                                <input type="checkbox" name="include_attachment" checked> Include Attachment
                                            </label>
                                        <?php endif; ?>
                                        <button type="submit" class="badge success" onclick="return confirm('Send this event to all members and branch leaders?')">Send</button>
                                    </form>
                                    <a href="view_event_notifications.php?id=<?= $event['id'] ?>" class="badge viewer">Recipients</a>
                                    <a href="edit_event.php?id=<?= $event['id'] ?>" class="badge partial">Edit</a>
                                    <a href="delete_event.php?id=<?= $event['id'] ?>" onclick="return confirm('Delete this event?')" class="badge pending">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5">No events found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<!-- Modal for full image preview -->
<div id="imageModal" style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); justify-content:center; align-items:center;">
    <img id="modalImage" src="" style="max-width:90%; max-height:90%; border:5px solid white; border-radius:8px;">
    <span onclick="closeModal()" style="position:absolute; top:20px; right:30px; font-size:36px; color:white; cursor:pointer;">&times;</span>
</div>

<script>
function openModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('imageModal').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>

<?php
session_start();

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

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    die("Invalid event ID.");
}

// Fetch existing event
$stmt = $conn->prepare("SELECT * FROM events WHERE id = ?");
$stmt->bind_param("i", $id);
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
            <h1>Edit Event</h1>
            <p>Modify the event details below and save changes</p>
        </header>

        <div class="table-card" style="max-width: 600px; margin: auto;">
            <form action="process_edit_event.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?= $event['id'] ?>">
                <input type="hidden" name="current_image" value="<?= htmlspecialchars($event['image_path']) ?>">

                <label>Event Name:</label>
                <input type="text" name="name" value="<?= htmlspecialchars($event['name']) ?>" required
                       style="padding: 10px; margin-bottom: 15px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <label>Date & Time:</label>
                <input type="datetime-local" name="event_date"
                       value="<?= date('Y-m-d\TH:i', strtotime($event['event_date'])) ?>" required
                       style="padding: 10px; margin-bottom: 15px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <label>Location:</label>
                <input type="text" name="location" value="<?= htmlspecialchars($event['location']) ?>" required
                       style="padding: 10px; margin-bottom: 20px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <label>Description / Notes:</label>
                <textarea name="description" rows="5" style="padding: 10px; margin-bottom: 20px; width: 100%; border-radius: 6px; border: 1px solid #ccc;"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>

                <label>Current Image:</label><br>
                <?php if ($event['image_path']): ?>
                    <div class="thumbnail" onclick="openModal('../uploads/events/<?= htmlspecialchars($event['image_path']) ?>')">
                        <img src="../uploads/events/<?= htmlspecialchars($event['image_path']) ?>" alt="Event Image">
                    </div>
                    <label><input type="checkbox" name="delete_image"> Delete current image</label><br><br>
                <?php else: ?>
                    <em>No image uploaded.</em><br><br>
                <?php endif; ?>

                <label>Replace Image:</label>
                <input type="file" name="event_image" id="event_image" accept="image/*" onchange="previewThumbnail(event)">

                <div id="imagePreviewContainer" style="display:none; margin-top: 15px;">
                    <label>New Preview:</label>
                    <div class="thumbnail" onclick="openModalFromPreview()" title="Click to view full image">
                        <img id="previewImage" src="" alt="New Preview">
                    </div>
                </div>

                <button type="submit" class="submit">Update Event</button>
            </form>
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
function previewThumbnail(event) {
    const input = event.target;
    const preview = document.getElementById('previewImage');
    const container = document.getElementById('imagePreviewContainer');
    const modalImage = document.getElementById('modalImage');

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            modalImage.src = e.target.result;
            container.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
function openModalFromPreview() {
    const previewImage = document.getElementById('previewImage');
    if (previewImage.src) {
        openModal(previewImage.src);
    }
}
</script>

<?php include '../includes/footer.php'; ?>

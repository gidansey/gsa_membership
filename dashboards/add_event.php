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

$isDashboard = true;
$error = '';

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
            <h1>Add New Event</h1>
            <p>Fill in the form to create an event</p>
        </header>

        <div class="table-card" style="max-width: 600px; margin: auto;">
            <?php if (!empty($error)): ?>
                <div style="color: red; margin-bottom: 15px;"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" action="process_event.php" enctype="multipart/form-data">
                <label>Event Name</label>
                <input type="text" name="name" required
                       style="padding: 10px; margin-bottom: 15px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <label>Date & Time</label>
                <input type="datetime-local" name="event_date" required
                       style="padding: 10px; margin-bottom: 15px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <label>Location</label>
                <input type="text" name="location" required
                       style="padding: 10px; margin-bottom: 20px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <label>Description / Notes:</label>
                <textarea name="description" rows="5" style="padding: 10px; margin-bottom: 20px; width: 100%; border-radius: 6px; border: 1px solid #ccc;"><?= htmlspecialchars($event['description'] ?? '') ?></textarea>


                <div class="form-group">
                    <label for="event_image">Event Image:</label>
                    <input type="file" name="event_image" id="event_image" accept="image/*" onchange="previewThumbnail(event)">
                </div>

                <div id="imagePreviewContainer" style="display:none; margin-top: 15px;">
                    <label>Preview:</label>
                    <div class="thumbnail-view" onclick="openModalFromPreview()" title="Click to view full image">
                        <img id="previewImage" src="" alt="Preview">
                    </div>
                </div>

                <button type="submit" class="submit">Add Event</button>
            </form>
        </div>
    </main>
</div>

<!-- Modal for image preview -->
<div id="imageModal" style="display:none; position:fixed; z-index:9999; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.75); justify-content:center; align-items:center;">
    <img id="modalImage" src="" style="max-width:90%; max-height:90%; border:5px solid white; border-radius:8px;">
    <span onclick="closeModal()" style="position:absolute; top:20px; right:30px; font-size:36px; color:white; cursor:pointer;">&times;</span>
</div>

<script>
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
    document.getElementById('imageModal').style.display = 'flex';
}
function closeModal() {
    document.getElementById('imageModal').style.display = 'none';
}
</script>

<?php include '../includes/footer.php'; ?>

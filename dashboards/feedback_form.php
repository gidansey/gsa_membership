<?php
session_start();
require_once '../includes/db_connect.php';

// Validate and fetch event_id
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    echo "<p style='color:red;'>Invalid access. No event selected.</p>";
    echo "<p><a href='member_dashboard.php'>Back to Dashboard</a></p>";
    exit;
}

$event_id = intval($_GET['event_id']);

// Optional: Fetch event name for display
$event_name = '';
$stmt = $conn->prepare("SELECT name FROM events WHERE id = ?");
$stmt->bind_param("i", $event_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $event_name = htmlspecialchars($row['name']);
} else {
    echo "<p style='color:red;'>Event not found.</p>";
    exit;
}
$stmt->close();
$isDashboard = true;
include '../includes/header.php';
?>
<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="member_dashboard.php">Dashboard</a>
            <a href="edit_profile.php">Edit Profile</a>
            <a href="pay_dues.php">Pay Dues</a>
            <a href="event_history.php" class="active">Event History & Feedback</a>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="toggleSidebar()">☰</div>
            <h3>Submit Feedback for Event: <?= $event_name ?></h3>
        </header>

    <div class="form-container">
        <form action="submit_feedback.php" method="POST">
            <input type="hidden" name="event_id" value="<?= $event_id ?>">

            <label for="rating">Rating (1 to 5):</label>
            <select name="rating" id="rating" required>
                <option value="">--Select--</option>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?></option>
                <?php endfor; ?>
            </select><br><br>

            <label for="comments">Comments:</label><br>
            <textarea name="comments" id="comments" rows="4" cols="50" placeholder="Write your feedback here..." required></textarea><br><br>

            <button type="submit">Submit Feedback</button>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>

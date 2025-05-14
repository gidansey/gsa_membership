<?php
    $timeout_duration = 1800; // 30 minutes

    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        header("Location: ../includes/timeout.php");
        exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time();

    session_start();
    require_once '../includes/db_connect.php';

    if ($_SESSION['role'] !== 'Admin') {
        header("Location: ../index.php");
        exit;
    }

    $isDashboard = true;

    $id = intval($_GET['id']);
    $event = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name']);
        $date = $_POST['event_date'];
        $location = trim($_POST['location']);

        if ($name && $date && $location) {
            $stmt = $conn->prepare("UPDATE events SET name=?, event_date=?, location=? WHERE id=?");
            $stmt->bind_param("sssi", $name, $date, $location, $id);
            $stmt->execute();
            header("Location: manage_events.php");
            exit;
        } else {
            $error = "All fields are required.";
        }
    } else {
        $stmt = $conn->prepare("SELECT * FROM events WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $event = $stmt->get_result()->fetch_assoc();

        if (!$event) {
            die("Event not found.");
        }
    }

    include '../includes/header.php';
?>

<div class="container">
    <h2>Edit Event</h2>
    <?php if (!empty($error)): ?>
        <p style="color: red"><?= $error ?></p>
    <?php endif; ?>
    <form method="POST">
        <label>Event Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($event['name']) ?>" required>

        <label>Date & Time</label>
        <input type="datetime-local" name="event_date" value="<?= date('Y-m-d\TH:i', strtotime($event['event_date'])) ?>" required>

        <label>Location</label>
        <input type="text" name="location" value="<?= htmlspecialchars($event['location']) ?>" required>

        <button type="submit">Update Event</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>

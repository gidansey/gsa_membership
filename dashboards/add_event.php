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
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name     = trim($_POST['name']);
        $date     = $_POST['event_date'];
        $location = trim($_POST['location']);

        if ($name && $date && $location) {
            $stmt = $conn->prepare("INSERT INTO events (name, event_date, location) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $date, $location);
            $stmt->execute();
            header("Location: manage_events.php");
            exit;
        } else {
            $error = "All fields are required.";
        }
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
            <h1>Add New Event</h1>
            <p>Fill in the form to create an event</p>
        </header>

        <div class="table-card" style="max-width: 600px; margin: auto;">
            <?php if (!empty($error)): ?>
                <div style="color: red; margin-bottom: 15px;"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST">
                <label>Event Name</label>
                <input type="text" name="name" required style="padding: 10px; margin-bottom: 15px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <label>Date & Time</label>
                <input type="datetime-local" name="event_date" required style="padding: 10px; margin-bottom: 15px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <label>Location</label>
                <input type="text" name="location" required style="padding: 10px; margin-bottom: 20px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <button type="submit" style="
                    width: 100%;
                    padding: 12px;
                    background-color: #3498db;
                    color: #fff;
                    border: none;
                    border-radius: 8px;
                    font-size: 16px;
                    cursor: pointer;
                ">Add Event</button>
            </form>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

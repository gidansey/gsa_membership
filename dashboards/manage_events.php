<?php
    $timeout_duration = 1800; // 30 minutes

    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        header("Location: ../includes/timeout.php");
        exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time();

    session_start();
    require_once '../includes/db_connect.php';

    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
        header("Location: ../index.php");
        exit;
    }

    $isDashboard = true;

    // Fetch events
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

        <form method="GET" style="margin-bottom: 15px;">
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
                <tr>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Actions</th>
                </tr>
                <?php if ($events): ?>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?= htmlspecialchars($event['name']) ?></td>
                            <td><?= date('M d, Y - h:i A', strtotime($event['event_date'])) ?></td>
                            <td><?= htmlspecialchars($event['location']) ?></td>
                            <td>
                                <a href="edit_event.php?id=<?= $event['id'] ?>" class="badge partial">Edit</a>
                                <a href="delete_event.php?id=<?= $event['id'] ?>" onclick="return confirm('Delete this event?')" class="badge pending">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="4">No events found.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

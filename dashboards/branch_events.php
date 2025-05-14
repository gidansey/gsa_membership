<?php
session_start();
require_once '../includes/db_connect.php';

// Session timeout
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Only branch leaders allowed
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Branch Leader') {
    header("Location: ../index.php");
    exit;
}

$branch_id = $_SESSION['branch_id'] ?? null;
if (!$branch_id) {
    echo "<p style='color:red;'>Branch not assigned. Contact Admin.</p>";
    exit;
}

// Get events where location matches branch name
$events = [];
$sql = "SELECT e.id, e.name, e.event_date, e.location
        FROM events e
        JOIN branches b ON e.location LIKE CONCAT('%', b.branch_name, '%')
        WHERE b.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}

$isDashboard = true;
include '../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="branch_dashboard.php">Dashboard</a>
            <a href="my_members.php">My Members</a>
            <a href="branch_events.php" class="active">Branch Events</a>
            <a href="branch_announcement.php">Announcements</a>
            <a href="branch_report.php">Reports</a>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>Branch Events</h1>
            <p>View events associated with your branch</p>
        </header>
        <div class="table-card">
            <h3>Upcoming & Past Events</h3>
            <table>
                <thead>
                    <tr>
                        <th>Event Name</th>
                        <th>Date</th>
                        <th>Location</th>
                        <th>Participants</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?= htmlspecialchars($event['name']) ?></td>
                                <td><?= date('M d, Y', strtotime($event['event_date'])) ?></td>
                                <td><?= htmlspecialchars($event['location']) ?></td>
                                <td>
                                    <a href="view_event_participants.php?event_id=<?= $event['id'] ?>" class="btn">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No events found for your branch.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="create-event-btn">
            <a href="create_branch_event.php" class="btn">Create Branch Event</a>
        </div>
    </main>
</div>
<style>
.create-event-btn {
    margin-bottom: 20px;
    text-align: center;
    padding: 10px;
    background-color: #e9f5ff;
    border-radius: 6px;
}

.create-event-btn .btn {
    display: inline-block;
    padding: 10px 20px;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-weight: bold;
    transition: background-color 0.3s ease;
}

.create-event-btn .btn:hover {
    background-color: #0056b3;
}
</style>
<?php include '../includes/footer.php'; ?>

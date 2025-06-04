<?php
session_start();
require_once '../includes/db_connect.php';

// Timeout logic
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Access control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Branch Leader') {
    header("Location: ../index.php");
    exit;
}

// Get event ID
$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
if (!$event_id) {
    echo "<p style='color:red;'>No event selected.</p>";
    exit;
}

// Confirm event belongs to this branch
$branch_id = $_SESSION['branch_id'];
$check = $conn->prepare("
    SELECT e.name, e.event_date, e.location
    FROM events e
    JOIN branches b ON e.location LIKE CONCAT('%', b.branch_name, '%')
    WHERE e.id = ? AND b.id = ?
");
$check->bind_param("ii", $event_id, $branch_id);
$check->execute();
$event = $check->get_result()->fetch_assoc();

if (!$event) {
    echo "<p style='color:red;'>Unauthorized or invalid event.</p>";
    exit;
}

// Get participants
$participants = [];
$sql = "
    SELECT m.first_name, m.last_name, m.email, ep.participation_date, ep.event_role
    FROM event_participation ep
    JOIN members m ON ep.member_id = m.id
    JOIN affiliations a ON m.id = a.member_id
    WHERE ep.event_id = ? AND a.branch_id = ?
    ORDER BY ep.participation_date DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $event_id, $branch_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $participants[] = $row;
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
            <a href="branch_events.php">Branch Events</a>
            <a href="branch_report.php">Reports</a>
        </nav>
        <form action="../logout.php" method="post" style="margin-top:auto; display:flex; justify-content:center;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>Participants - <?= htmlspecialchars($event['name']) ?></h1>
            <p><?= date('M d, Y', strtotime($event['event_date'])) ?> | <?= htmlspecialchars($event['location']) ?></p>
        </header>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role in Event</th>
                        <th>Date Participated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($participants): ?>
                        <?php foreach ($participants as $p): ?>
                            <tr>
                                <td><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></td>
                                <td><?= htmlspecialchars($p['email']) ?></td>
                                <td><?= htmlspecialchars($p['event_role']) ?></td>
                                <td><?= date('M d, Y H:i', strtotime($p['participation_date'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4">No participants found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <a href="export_event_participants_csv.php?event_id=<?= $event_id ?>" class="btn">Download CSV</a>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

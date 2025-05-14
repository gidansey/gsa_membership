<?php
session_start();
$timeout_duration = 1800; // 30 minutes
date_default_timezone_set('Africa/Accra');

// Timeout check
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Check admin session
require_once '../includes/db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Fetch admin name
$full_name = '';
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($user = $result->fetch_assoc()) {
    $full_name = $user['first_name'] . ' ' . $user['last_name'];
}
$stmt->close();

// Dashboard stats
$total_members = $conn->query("SELECT COUNT(*) AS total FROM members")->fetch_assoc()['total'] ?? 0;

$pending_payments = $conn->query("
    SELECT COUNT(*) AS pending FROM payments p
    JOIN membership_types mt ON p.membership_type_id = mt.id
    WHERE p.amount_paid < mt.annual_dues
")->fetch_assoc()['pending'] ?? 0;

$upcoming_events = $conn->query("SELECT COUNT(*) AS events FROM events WHERE event_date > NOW()")
    ->fetch_assoc()['events'] ?? 0;

$feedback_responses = $conn->query("SELECT COUNT(*) AS total FROM feedback")
    ->fetch_assoc()['total'] ?? 0;

// Recent Members
$recent_members = [];
$res = $conn->query("SELECT first_name, last_name, email, date_registered, status FROM members ORDER BY date_registered DESC LIMIT 5");
while ($row = $res->fetch_assoc()) $recent_members[] = $row;

// Selected year
$selected_year = $_GET['year'] ?? date('Y');

// Get distinct payment years
$payment_years = [];
$yrs = $conn->query("SELECT DISTINCT YEAR(payment_date) AS year FROM payments ORDER BY year DESC");
while ($row = $yrs->fetch_assoc()) $payment_years[] = $row['year'];

// Recent Payments
$recent_payments = [];
$sql = "
    SELECT m.first_name, m.last_name, p.amount_paid, p.payment_date, mt.annual_dues
    FROM payments p
    JOIN members m ON p.member_id = m.id
    JOIN membership_types mt ON p.membership_type_id = mt.id
    WHERE YEAR(p.payment_date) = ?
    ORDER BY p.payment_date DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $selected_year);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['status'] = $row['amount_paid'] >= $row['annual_dues'] ? 'Paid' : ($row['amount_paid'] > 0 ? 'Partial' : 'Pending');
    $recent_payments[] = $row;
}
$stmt->close();

// Flag for sidebar
$isDashboard = true;
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="admin_dashboard.php" class="active">Dashboard</a>
            <a href="manage_members.php">Manage Members</a>
            <a href="manage_payments.php">Manage Payments</a>
            <a href="manage_events.php">Events</a>
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
            <div class="hamburger" onclick="toggleSidebar()">☰</div>
            <h1>Admin Dashboard</h1>
            <p>Welcome, <?= htmlspecialchars($full_name) ?></p>
        </header>

        <section class="cards">
            <div class="card"><p>Total Members</p><h2><?= $total_members ?></h2></div>
            <div class="card"><p>Pending Payments</p><h2><?= $pending_payments ?></h2></div>
            <div class="card"><p>Upcoming Events</p><h2><?= $upcoming_events ?></h2></div>
            <div class="card"><p>Feedback Responses</p><h2><?= $feedback_responses ?></h2></div>
        </section>

        <section class="grid">
            <div class="table-card">
                <h3>Recent Members</h3>
                <table>
                    <tr><th>Name</th><th>Email</th><th>Date Joined</th><th>Status</th></tr>
                    <?php foreach ($recent_members as $m): ?>
                        <tr>
                            <td><?= htmlspecialchars($m['first_name'] . ' ' . $m['last_name']) ?></td>
                            <td><?= htmlspecialchars($m['email']) ?></td>
                            <td><?= date('M d, Y', strtotime($m['date_registered'])) ?></td>
                            <td><span class="badge <?= strtolower($m['status']) ?>"><?= $m['status'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="table-card">
                <h3>Recent Payments</h3>
                <form method="GET">
                    <label for="year">Filter by Year:</label>
                    <select name="year" id="year" onchange="this.form.submit()">
                        <?php foreach ($payment_years as $y): ?>
                            <option value="<?= $y ?>" <?= $y == $selected_year ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endforeach; ?>
                    </select>
                </form>
                <table>
                    <tr><th>Member</th><th>Amount</th><th>Date</th><th>Status</th></tr>
                    <?php foreach ($recent_payments as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></td>
                            <td>GHS <?= number_format($p['amount_paid'], 2) ?></td>
                            <td><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                            <td><span class="badge <?= strtolower($p['status']) ?>"><?= $p['status'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </section>

        <section class="bottom">
            <div class="chart-card"><h3>Event Participation</h3><canvas id="eventChart"></canvas></div>
            <div class="chart-card"><h3>Feedback Overview</h3><canvas id="feedbackChart"></canvas></div>
        </section>
    </main>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}
const ctx1 = document.getElementById('eventChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
            label: 'Participants',
            data: [12, 19, 13, 15, 20, 25],
            borderColor: '#4A90E2',
            fill: false,
            tension: 0.3
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

const ctx2 = document.getElementById('feedbackChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: ['Positive', 'Neutral', 'Negative'],
        datasets: [{
            data: [60, 25, 15],
            backgroundColor: ['#2ecc71', '#f1c40f', '#e74c3c']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%'
    }
});
</script>
</body>
</html>

<?php include '../includes/footer.php'; ?>

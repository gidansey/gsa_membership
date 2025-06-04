<?php
session_start();
$timeout_duration = 1800;
date_default_timezone_set('Africa/Accra');

// Timeout check
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Session & DB
require_once '../includes/db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

// Initialize
$errors = [];
$full_name = '';

// Admin Name
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($user = $res->fetch_assoc()) {
        $full_name = $user['first_name'] . ' ' . $user['last_name'];
    }
    $stmt->close();
} else {
    $errors[] = "Failed to prepare user query: " . $conn->error;
}

// Dashboard Stats
$total_members = $conn->query("SELECT COUNT(*) AS total FROM members");
$total_members = $total_members ? $total_members->fetch_assoc()['total'] : 0;

$pending_payments = $conn->query("
    SELECT COUNT(*) AS pending FROM payments p
    JOIN membership_types mt ON p.membership_type_id = mt.id
    WHERE p.amount_paid < mt.annual_dues
");
$pending_payments = $pending_payments ? $pending_payments->fetch_assoc()['pending'] : 0;

$upcoming_events = $conn->query("SELECT COUNT(*) AS events FROM events WHERE event_date > NOW()");
$upcoming_events = $upcoming_events ? $upcoming_events->fetch_assoc()['events'] : 0;

$feedback_responses = $conn->query("SELECT COUNT(*) AS total FROM feedback");
$feedback_responses = $feedback_responses ? $feedback_responses->fetch_assoc()['total'] : 0;

// Recent Members
$recent_members = [];
$res = $conn->query("SELECT first_name, last_name, email, date_registered, status FROM members ORDER BY date_registered DESC LIMIT 5");
if ($res) while ($row = $res->fetch_assoc()) $recent_members[] = $row;

// Year
$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Payment Years
$payment_years = [];
$res = $conn->query("SELECT DISTINCT YEAR(payment_date) AS year FROM payments ORDER BY year DESC");
if ($res) while ($row = $res->fetch_assoc()) $payment_years[] = $row['year'];

// Recent Payments
$recent_payments = [];
$stmt = $conn->prepare("
    SELECT m.first_name, m.last_name, p.amount_paid, p.payment_date, mt.annual_dues
    FROM payments p
    JOIN members m ON p.member_id = m.id
    JOIN membership_types mt ON p.membership_type_id = mt.id
    WHERE YEAR(p.payment_date) = ?
    ORDER BY p.payment_date DESC LIMIT 5
");
if ($stmt) {
    $stmt->bind_param("i", $selected_year);
    if ($stmt->execute()) {
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $row['status'] = $row['amount_paid'] >= $row['annual_dues']
                ? 'Paid' : ($row['amount_paid'] > 0 ? 'Partial' : 'Pending');
            $recent_payments[] = $row;
        }
    } else {
        $errors[] = "Recent payments execute failed: " . $stmt->error;
    }
    $stmt->close();
}

// Event Participation Chart
$event_labels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$monthly_participation = array_fill(1, 12, 0);
$res = $conn->query("
    SELECT MONTH(e.event_date) AS month, COUNT(ep.id) AS participants
    FROM event_participation ep
    JOIN events e ON ep.event_id = e.id
    WHERE YEAR(e.event_date) = $selected_year
    GROUP BY MONTH(e.event_date)
    ORDER BY MONTH(e.event_date)
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $monthly_participation[(int)$row['month']] = (int)$row['participants'];
    }
}
$event_data = array_values($monthly_participation);

// Feedback Overview Chart
$feedback_data = ['Positive' => 0, 'Neutral' => 0, 'Negative' => 0];
$res = $conn->query("SELECT feedback_type, COUNT(*) AS count FROM feedback GROUP BY feedback_type");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $type = ucfirst(strtolower($row['feedback_type']));
        if (isset($feedback_data[$type])) {
            $feedback_data[$type] = (int)$row['count'];
        }
    }
}

$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

$status_counts = ['Approved' => 0, 'Inactive' => 0, 'Pending' => 0];
$res = $conn->query("SELECT status, COUNT(*) AS count FROM members GROUP BY status");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $status = ucfirst(strtolower($row['status']));
        if (isset($status_counts[$status])) $status_counts[$status] = $row['count'];
    }
} else {
    $errors[] = "Status breakdown query failed: " . $conn->error;
}

$good_standing = 0;
$stmt = $conn->prepare("SELECT COUNT(DISTINCT p.member_id) AS count FROM payments p JOIN membership_types mt ON p.membership_type_id = mt.id WHERE YEAR(p.payment_date) = ? AND p.amount_paid >= mt.annual_dues");
if ($stmt) {
    $stmt->bind_param("i", $selected_year);
    $stmt->execute();
    $res = $stmt->get_result();
    $good_standing = $res->fetch_assoc()['count'] ?? 0;
    $stmt->close();
} else {
    $errors[] = "Good standing query failed: " . $conn->error;
}

$annual_dues = 0;
$stmt = $conn->prepare("SELECT SUM(amount_paid) AS total FROM payments WHERE YEAR(payment_date) = ?");
if ($stmt) {
    $stmt->bind_param("i", $selected_year);
    $stmt->execute();
    $res = $stmt->get_result();
    $annual_dues = $res->fetch_assoc()['total'] ?? 0;
    $stmt->close();
} else {
    $errors[] = "Annual dues query failed: " . $conn->error;
}

$membership_types = [];
$res = $conn->query("SELECT mt.type_name AS type, COUNT(*) AS count FROM member_category mc JOIN membership_types mt ON mc.membership_type_id = mt.id GROUP BY mt.type_name");
if ($res) {
    while ($row = $res->fetch_assoc()) $membership_types[] = $row;
} else {
    $errors[] = "Membership type breakdown query failed: " . $conn->error;
}

$renewal_months = array_fill(1, 12, 0);
$stmt = $conn->prepare("SELECT MONTH(renewal_date) AS month, COUNT(*) AS count FROM renewals WHERE YEAR(renewal_date) = ? GROUP BY MONTH(renewal_date)");
if ($stmt) {
    $stmt->bind_param("i", $selected_year);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $renewal_months[(int)$row['month']] = $row['count'];
    }
    $stmt->close();
} else {
    $errors[] = "Renewal analysis query failed: " . $conn->error;
}

// Notification System - Initialize $unread_count
$unread_count = 0;
$user_id = $_SESSION['user_id'];

// Get unread notifications count
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND is_read = 0");
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $unread_count = $row['count'];
    }
    $stmt->close();
}

// Get latest notifications
$notif_sql = "SELECT message, link, created_at, is_read 
              FROM notifications 
              WHERE user_id = ? 
              ORDER BY created_at DESC 
              LIMIT 5";

$stmt = $conn->prepare($notif_sql);
if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $notifications = $stmt->get_result();
} else {
    $errors[] = "Failed to prepare notifications query: " . $conn->error;
}

// Sidebar flag
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
        <div class="logo-container">
            <div class="logo"></div>
        </div>
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
            <div class="header-content">
                <div>
                    <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
                    <h1>Admin Dashboard</h1>
                </div>
                <div class="welcome-section">
                    <p>Welcome, <?= htmlspecialchars($full_name) ?></p>
                    <div class="notification-container" style="position: relative;">
                        <a href="notifications.php" class="notification-badge">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            <?php if ($unread_count > 0): ?>
                                <span class="badge-count"><?= $unread_count ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="notification-dropdown">
                            <h3 style="padding: 15px; margin: 0; border-bottom: 1px solid #eee;">Notifications</h3>
                            <?php if ($notifications->num_rows > 0): ?>
                                <?php while ($notif = $notifications->fetch_assoc()): ?>
                                    <div class="notification-item <?= $notif['is_read'] ? '' : 'unread' ?>">
                                        <div><?= htmlspecialchars($notif['message']) ?></div>
                                        <div class="notification-time">
                                            <?= date('M d, Y h:i A', strtotime($notif['created_at'])) ?>
                                            <?php if (!$notif['is_read']): ?>
                                                <span style="color: #3498db; margin-left: 10px;">• New</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="notification-item">
                                    <div>No notifications</div>
                                </div>
                            <?php endif; ?>
                            <a href="notifications.php" class="view-all">View All Notifications</a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <?php if (!empty($errors)): ?>
            <div class="error-box">
                <h4>Some queries failed:</h4>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <h3>Membership Type Breakdown</h3>
        <section class="grid">
            <?php foreach ($membership_types as $type): ?>
                <div class="card">
                    <p><?= htmlspecialchars($type['type']) ?></p>
                    <h2><?= $type['count'] ?></h2>
                </div>
            <?php endforeach; ?>
        </section>

        <section class="cards">
            <div class="card"><p>Total Members</p><h2><?= $total_members ?></h2></div>
            <div class="card"><p>Active Members</p><h2><?= $status_counts['Approved'] ?></h2></div>
            <div class="card"><p>Members in Good Standing</p><h2><?= $good_standing ?></h2></div>
            <div class="card"><p>Inactive Members</p><h2><?= $status_counts['Inactive'] ?></h2></div>
            <div class="card"><p>Pending Members</p><h2><?= $status_counts['Pending'] ?></h2></div>
        </section>

        <section class="grid">
            <div class="card"><p>Total Dues Collected (<?= $selected_year ?>)</p><h2>GH¢<?= number_format($annual_dues, 2) ?></h2></div>
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
            <div class="chart-card"><h3>Status Distribution</h3><canvas id="statusChart"></canvas></div>
            <div class="chart-card"><h3>Renewal Analysis (<?= $selected_year ?>)</h3><canvas id="renewalChart"></canvas></div>
            <div class="chart-card"><h3>Event Participation</h3><canvas id="eventChart"></canvas></div>
            <div class="chart-card"><h3>Feedback Overview</h3><canvas id="feedbackChart"></canvas></div>
        </section>
    </main>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}

// Event Chart
const ctx1 = document.getElementById('eventChart').getContext('2d');
new Chart(ctx1, {
    type: 'line',
    data: {
        labels: <?= json_encode($event_labels) ?>,
        datasets: [{
            label: 'Participants',
            data: <?= json_encode($event_data) ?>,
            borderColor: '#4A90E2',
            backgroundColor: 'rgba(74,144,226,0.2)',
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

// Feedback Chart
const ctx2 = document.getElementById('feedbackChart').getContext('2d');
new Chart(ctx2, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($feedback_data)) ?>,
        datasets: [{
            data: <?= json_encode(array_values($feedback_data)) ?>,
            backgroundColor: ['#2ecc71', '#f1c40f', '#e74c3c']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%'
    }
});

const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'pie',
    data: {
        labels: ['Approved', 'Pending', 'Inactive'],
        datasets: [{
            data: [<?= $status_counts['Approved'] ?>, <?= $status_counts['Pending'] ?>, <?= $status_counts['Inactive'] ?>],
            backgroundColor: ['#27ae60', '#f1c40f', '#e74c3c']
        }]
    },
    options: { responsive: true }
});

const renewalCtx = document.getElementById('renewalChart').getContext('2d');
new Chart(renewalCtx, {
    type: 'bar',
    data: {
        labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
        datasets: [{
            label: 'Renewals',
            data: <?= json_encode(array_values($renewal_months)) ?>,
            backgroundColor: '#3498db'
        }]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>

<?php include '../includes/footer.php'; ?>

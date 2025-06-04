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

require_once '../includes/db_connect.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Secretariat') {
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = '';
$errors = [];

$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($user = $res->fetch_assoc()) {
    $full_name = $user['first_name'] . ' ' . $user['last_name'];
}
$stmt->close();

$selected_year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Status Counts
$status_counts = ['Approved' => 0, 'Inactive' => 0, 'Pending' => 0];
$res = $conn->query("SELECT status, COUNT(*) AS count FROM members GROUP BY status");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $status = ucfirst(strtolower($row['status']));
        if (isset($status_counts[$status])) {
            $status_counts[$status] = $row['count'];
        }
    }
} else {
    $errors[] = "Status query failed: " . $conn->error;
}

// Good Standing
$good_standing = 0;
$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT p.member_id) AS count
    FROM payments p
    JOIN membership_types mt ON p.membership_type_id = mt.id
    WHERE YEAR(p.payment_date) = ? AND p.amount_paid >= mt.annual_dues
");
if ($stmt) {
    $stmt->bind_param("i", $selected_year);
    $stmt->execute();
    $res = $stmt->get_result();
    $good_standing = $res->fetch_assoc()['count'] ?? 0;
    $stmt->close();
} else {
    $errors[] = "Good standing query failed: " . $conn->error;
}

// Dues Collected
$dues_collected = 0;
$stmt = $conn->prepare("SELECT SUM(amount_paid) AS total FROM payments WHERE YEAR(payment_date) = ?");
if ($stmt) {
    $stmt->bind_param("i", $selected_year);
    $stmt->execute();
    $res = $stmt->get_result();
    $dues_collected = $res->fetch_assoc()['total'] ?? 0;
    $stmt->close();
} else {
    $errors[] = "Dues collection query failed: " . $conn->error;
}

// Membership Types
$membership_types = [];
$res = $conn->query("SELECT mt.type_name AS type, COUNT(*) AS count FROM member_category mc JOIN membership_types mt ON mc.membership_type_id = mt.id GROUP BY mt.type_name");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $membership_types[] = $row;
    }
} else {
    $errors[] = "Membership type breakdown query failed: " . $conn->error;
}

// Renewals
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
    $errors[] = "Renewal query failed: " . $conn->error;
}

// Feedback
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

// Event Participation
$event_labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$monthly_participation = array_fill(1, 12, 0);
$res = $conn->query("
    SELECT MONTH(e.event_date) AS month, COUNT(ep.id) AS participants
    FROM event_participation ep
    JOIN events e ON ep.event_id = e.id
    WHERE YEAR(e.event_date) = $selected_year
    GROUP BY MONTH(e.event_date)
");
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $monthly_participation[(int)$row['month']] = (int)$row['participants'];
    }
}
$event_data = array_values($monthly_participation);

// Notifications - Get unread count and latest notifications
$unread_count = 0;
$notif_stmt = $conn->prepare("SELECT COUNT(*) AS count FROM notifications WHERE user_id = ? AND is_read = 0");
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$unread_result = $notif_stmt->get_result();
if ($unread_row = $unread_result->fetch_assoc()) {
    $unread_count = $unread_row['count'];
}
$notif_stmt->close();

// Get latest notifications
$notif_stmt = $conn->prepare("SELECT id, message, link, created_at, is_read FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$notif_stmt->bind_param("i", $user_id);
$notif_stmt->execute();
$notifications = $notif_stmt->get_result();
$notif_stmt->close();

// Header
$isDashboard = true;
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secretariat Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="secretariat_dashboard.php" class="active">Dashboard</a>
            <a href="manage_payments.php">Manage Payments</a>
            <a href="approve_members.php">Approve Members</a>
            <a href="verify_payments.php">Verify Payments</a>
            <a href="issue_letters.php">Membership Letters</a>
            <a href="generate_reports.php">Generate Reports</a>
            <a href="view_logs.php">Audit Logs</a>
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
                    <h1>Secretariat Dashboard</h1>
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

        <!-- Stats -->
        <section class="cards">
            <div class="card"><p>Total Members</p><h2><?= array_sum($status_counts) ?></h2></div>
            <div class="card"><p>Active</p><h2><?= $status_counts['Approved'] ?></h2></div>
            <div class="card"><p>Inactive</p><h2><?= $status_counts['Inactive'] ?></h2></div>
            <div class="card"><p>Pending</p><h2><?= $status_counts['Pending'] ?></h2></div>
        </section>

        <section class="grid">
            <div class="card"><p>Good Standing</p><h2><?= $good_standing ?></h2></div>
            <div class="card"><p>Dues Collected (<?= $selected_year ?>)</p><h2>GHS <?= number_format($dues_collected, 2) ?></h2></div>
        </section>

        <h3>Membership Breakdown</h3>
        <section class="grid">
            <?php foreach ($membership_types as $type): ?>
                <div class="card"><p><?= htmlspecialchars($type['type']) ?></p><h2><?= $type['count'] ?></h2></div>
            <?php endforeach; ?>
        </section>

        <section class="bottom">
            <div class="chart-card"><h3>Status Distribution</h3><canvas id="statusChart"></canvas></div>
            <div class="chart-card"><h3>Renewal Analysis</h3><canvas id="renewalChart"></canvas></div>
            <div class="chart-card"><h3>Event Participation</h3><canvas id="eventChart"></canvas></div>
            <div class="chart-card"><h3>Feedback Overview</h3><canvas id="feedbackChart"></canvas></div>
        </section>
    </main>
</div>

<script>
new Chart(document.getElementById('statusChart'), {
    type: 'pie',
    data: {
        labels: ['Approved', 'Pending', 'Inactive'],
        datasets: [{
            data: [<?= $status_counts['Approved'] ?>, <?= $status_counts['Pending'] ?>, <?= $status_counts['Inactive'] ?>],
            backgroundColor: ['#2ecc71', '#f1c40f', '#e74c3c']
        }]
    }
});
new Chart(document.getElementById('renewalChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($event_labels) ?>,
        datasets: [{
            label: 'Renewals',
            data: <?= json_encode(array_values($renewal_months)) ?>,
            backgroundColor: '#3498db'
        }]
    }
});
new Chart(document.getElementById('eventChart'), {
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
    }
});
new Chart(document.getElementById('feedbackChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_keys($feedback_data)) ?>,
        datasets: [{
            data: <?= json_encode(array_values($feedback_data)) ?>,
            backgroundColor: ['#2ecc71', '#f1c40f', '#e74c3c']
        }]
    },
    options: { cutout: '70%' }
});

// Mark notifications as read when clicked
document.querySelectorAll('.notification-item').forEach(item => {
    item.addEventListener('click', function() {
        const notificationId = this.dataset.id;
        if (notificationId) {
            fetch('mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${notificationId}`
            });
        }
    });
});
</script>
</body>
</html>

<?php include '../includes/footer.php'; ?>
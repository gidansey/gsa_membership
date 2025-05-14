<?php
session_start();

$timeout_duration = 1800; // 30 minutes
date_default_timezone_set('Africa/Accra');
require_once '../includes/db_connect.php';

// Session timeout handling
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Authorization check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Member') {
    header("Location: ../index.php");
    exit();
}

// Fetch member information
$full_name = '';
$member_id = $_SESSION['member_id'] ?? 0;
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($user = $result->fetch_assoc()) {
    $full_name = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
}
$stmt->close();

// Dues Status Calculation
$dues_status = 'Pending';
$due_query = "SELECT p.amount_paid, mt.annual_dues 
              FROM payments p
              JOIN membership_types mt ON p.membership_type_id = mt.id
              WHERE p.member_id = ?
              ORDER BY p.payment_date DESC 
              LIMIT 1";
$stmt = $conn->prepare($due_query);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$res = $stmt->get_result();
if ($pay = $res->fetch_assoc()) {
    $dues_status = ($pay['amount_paid'] >= $pay['annual_dues']) ? 'Paid' :
                  (($pay['amount_paid'] > 0) ? 'Partial' : 'Pending');
}
$stmt->close();

// Event Statistics
$event_count = 0;
$res = $conn->query("SELECT COUNT(*) AS count FROM events WHERE event_date > NOW()");
if ($row = $res->fetch_assoc()) {
    $event_count = $row['count'];
}

// Feedback Statistics
$feedback_count = 0;
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM feedback WHERE member_id = ?");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$res = $stmt->get_result();
if ($row = $res->fetch_assoc()) {
    $feedback_count = $row['count'];
}
$stmt->close();

$isDashboard = true;
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="member_dashboard.php" class="active">Dashboard</a>
            <a href="edit_profile.php">Edit Profile</a>
            <a href="pay_dues.php">Pay Dues</a>
            <a href="event_history.php">Event History & Feedback</a>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="toggleSidebar()">☰</div>
            <h1>Member Dashboard</h1>
            <p>Welcome, <?= $full_name ?></p>
        </header>

        <section class="cards">
            <div class="card">
                <p>Dues Status</p>
                <h2><?= $dues_status ?></h2>
            </div>
            <div class="card">
                <p>Upcoming Events</p>
                <h2><?= $event_count ?></h2>
            </div>
            <div class="card">
                <p>Feedback Submitted</p>
                <h2><?= $feedback_count ?></h2>
            </div>
        </section>

        <section class="grid">
            <!-- Recent Payments Table -->
            <div class="table-card">
                <h3>Recent Payments</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Amount (GH¢)</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("
                            SELECT p.amount_paid, p.payment_date, mt.annual_dues
                            FROM payments p
                            JOIN membership_types mt ON p.membership_type_id = mt.id
                            WHERE p.member_id = ?
                            ORDER BY p.payment_date DESC
                            LIMIT 5
                        ");
                        $stmt->bind_param("i", $member_id);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        
                        if ($res->num_rows > 0):
                            while ($pay = $res->fetch_assoc()):
                                $status = ($pay['amount_paid'] >= $pay['annual_dues']) ? 'Paid' :
                                          (($pay['amount_paid'] > 0) ? 'Partial' : 'Pending');
                        ?>
                        <tr>
                            <td>GHS <?= number_format($pay['amount_paid'], 2) ?></td>
                            <td><?= date('M d, Y', strtotime($pay['payment_date'])) ?></td>
                            <td><span class="badge <?= strtolower($status) ?>"><?= $status ?></span></td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="3">No payments found</td>
                        </tr>
                        <?php endif;
                        $stmt->close();
                        ?>
                    </tbody>
                </table>
            </div>

            <!-- Event Participation Table -->
            <div class="table-card">
                <h3>Event Participation</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Event</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $conn->prepare("
                            SELECT e.id AS event_id, e.name AS event_name, e.event_date
                            FROM event_participation ep
                            JOIN events e ON ep.event_id = e.id
                            WHERE ep.member_id = ?
                            ORDER BY e.event_date DESC
                            LIMIT 5
                        ");
                        if (!$stmt) {
                            die("Prepare failed: " . $conn->error);
                        }
                        $stmt->bind_param("i", $member_id);
                        $stmt->execute();
                        $res = $stmt->get_result();

                        if ($res->num_rows > 0):
                            while ($row = $res->fetch_assoc()):
                                $event_id = $row['event_id'];
                                $event_name = htmlspecialchars($row['event_name']);
                                $event_date = date('M d, Y', strtotime($row['event_date']));
                        ?>
                        <tr>
                            <td><?= $event_name ?></td>
                            <td><?= $event_date ?></td>
                            <td>
                                <a class="btn small primary" href="feedback_form.php?event_id=<?= $event_id ?>">Give Feedback</a>
                                <a class="btn small secondary" href="view_feedback.php?event_id=<?= $event_id ?>">View Feedback</a>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="3">No participation found.</td>
                        </tr>
                        <?php endif;
                        $stmt->close();
                        ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}
</script>

</body>
</html>

<?php include '../includes/footer.php'; ?>
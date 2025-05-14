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

// Access control
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Branch Leader') {
    header("Location: ../index.php");
    exit;
}

$branch_id = $_SESSION['branch_id'] ?? null;
if (!$branch_id) {
    echo "<p style='color:red;'>Branch not assigned. Contact Admin.</p>";
    exit;
}

$selected_year = $_GET['year'] ?? date('Y');
$year_filter = "AND YEAR(payment_date) = " . intval($selected_year);

// TOTAL MEMBERS IN BRANCH
$res = $conn->query("SELECT COUNT(*) AS count FROM affiliations WHERE branch_id = $branch_id");
$total_members = $res->fetch_assoc()['count'] ?? 0;

// STATUS BREAKDOWN
$active_members = $inactive_members = $pending_members = 0;
$statusQuery = "
    SELECT status, COUNT(*) AS count
    FROM members m
    JOIN affiliations a ON m.id = a.member_id
    WHERE a.branch_id = $branch_id
    GROUP BY status
";
$res = $conn->query($statusQuery);
while ($row = $res->fetch_assoc()) {
    switch ($row['status']) {
        case 'Approved': $active_members = $row['count']; break;
        case 'Inactive': $inactive_members = $row['count']; break;
        case 'Pending': $pending_members = $row['count']; break;
    }
}

// TOTAL DUES (ANNUAL)
$duesQuery = "
    SELECT SUM(p.amount_paid) AS total
    FROM payments p
    JOIN members m ON p.member_id = m.id
    JOIN affiliations a ON a.member_id = m.id
    WHERE a.branch_id = $branch_id $year_filter
";
$total_dues = $conn->query($duesQuery)->fetch_assoc()['total'] ?? 0;
$branch_imprest = 0.40 * $total_dues;

// EVENT PARTICIPATION
$eventQuery = "
    SELECT COUNT(*) AS total
    FROM event_participation ep
    JOIN members m ON ep.member_id = m.id
    JOIN affiliations a ON a.member_id = m.id
    WHERE a.branch_id = $branch_id
";
$event_participants = $conn->query($eventQuery)->fetch_assoc()['total'] ?? 0;

// FEEDBACK
$feedbackQuery = "
    SELECT COUNT(*) AS total
    FROM feedback f
    JOIN members m ON f.member_id = m.id
    JOIN affiliations a ON a.member_id = m.id
    WHERE a.branch_id = $branch_id
";
$feedback_submissions = $conn->query($feedbackQuery)->fetch_assoc()['total'] ?? 0;

// MONTHLY DUES FOR CHART
$monthly_dues = array_fill(1, 12, 0);
$dues_sql = "
    SELECT MONTH(payment_date) AS month, SUM(amount_paid) AS total
    FROM payments p
    JOIN members m ON p.member_id = m.id
    JOIN affiliations a ON a.member_id = m.id
    WHERE a.branch_id = $branch_id AND YEAR(payment_date) = $selected_year
    GROUP BY MONTH(payment_date)
";
$res = $conn->query($dues_sql);
while ($row = $res->fetch_assoc()) {
    $monthly_dues[(int)$row['month']] = (float)$row['total'];
}
$monthly_dues_json = json_encode(array_values($monthly_dues));
$status_data = json_encode([$active_members, $pending_members, $inactive_members]);

// MEMBERSHIP TYPE BREAKDOWN
$typeBreakdown = [];
$typeQuery = "
    SELECT mt.type_name AS type, COUNT(*) AS count
    FROM members m
    JOIN affiliations a ON a.member_id = m.id
    JOIN member_category mc ON mc.member_id = m.id
    JOIN membership_types mt ON mc.membership_type_id = mt.id
    WHERE a.branch_id = $branch_id
    GROUP BY mt.type_name
    ORDER BY mt.type_name
";
$stmt = $conn->prepare("SELECT mt.type_name AS type, COUNT(*) AS count
                       FROM members m
                       JOIN affiliations a ON a.member_id = m.id
                       JOIN member_category mc ON mc.member_id = m.id
                       JOIN membership_types mt ON mc.membership_type_id = mt.id
                       WHERE a.branch_id = ?
                       GROUP BY mt.type_name
                       ORDER BY mt.type_name");
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $typeBreakdown[] = $row;
}


$isDashboard = true;
?>

<?php include '../includes/header.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const duesCtx = document.getElementById('duesChart').getContext('2d');

        new Chart(statusCtx, {
            type: 'pie',
            data: {
                labels: ['Approved', 'Pending', 'Inactive'],
                datasets: [{
                    data: <?= $status_data ?>,
                    backgroundColor: ['#27ae60', '#f1c40f', '#e74c3c'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true
            }
        });

        new Chart(duesCtx, {
            type: 'line',
            data: {
                labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
                datasets: [{
                    label: 'Dues Collected (GHS)',
                    data: <?= $monthly_dues_json ?>,
                    backgroundColor: 'rgba(52,152,219,0.2)',
                    borderColor: '#3498db',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    });
</script>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="branch_dashboard.php">Dashboard</a>
            <a href="my_members.php">My Members</a>
            <a href="branch_events.php">Branch Events</a>
            <a href="branch_announcement.php">Announcements</a>
            <a href="branch_report.php" class="active">Reports</a>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>Branch Report (<?= $selected_year ?>)</h1>
        </header>

        <form method="GET" style="margin-bottom: 20px;">
            <label>Select Year:</label>
            <select name="year" onchange="this.form.submit()" style="padding:5px;border-radius:4px;">
                <?php
                $now = date('Y');
                for ($y = $now; $y >= 2020; $y--) {
                    echo "<option value='$y' " . ($selected_year == $y ? 'selected' : '') . ">$y</option>";
                }
                ?>
            </select>
        </form>

        <!-- Summary Cards -->
        <div class="cards">
            <div class="card"><p>Total Members</p><h2><?= $total_members ?></h2></div>
            <div class="card"><p>Approved</p><h2><?= $active_members ?></h2></div>
            <div class="card"><p>Pending</p><h2><?= $pending_members ?></h2></div>
            <div class="card"><p>Inactive</p><h2><?= $inactive_members ?></h2></div>
        </div>

        <div class="cards">
            <div class="card"><p>Annual Dues (<?= $selected_year ?>)</p><h2>GH¢ <?= number_format($total_dues, 2) ?></h2></div>
            <div class="card"><p>40% Branch Imprest</p><h2>GH¢ <?= number_format($branch_imprest, 2) ?></h2></div>
            <div class="card"><p>Event Participants</p><h2><?= $event_participants ?></h2></div>
            <div class="card"><p>Feedback Submitted</p><h2><?= $feedback_submissions ?></h2></div>
        </div>

        <!-- Membership Type Breakdown -->
        <h3>Membership Type Breakdown</h3>
        <div class="cards">
            <?php foreach ($typeBreakdown as $type): ?>
                <div class="card">
                    <p><?= htmlspecialchars($type['type']) ?></p>
                    <h2><?= $type['count'] ?></h2>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Charts -->
        <section class="bottom">
            <div class="chart-card">
                <h3>Status Distribution</h3>
                <canvas id="statusChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>Monthly Dues</h3>
                <canvas id="duesChart"></canvas>
            </div>
        </section>

        <button onclick="window.print()" class="btn">🖨️ Print Report</button>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
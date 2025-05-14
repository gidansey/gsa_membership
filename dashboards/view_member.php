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

// Get member ID
$member_id = intval($_GET['id'] ?? 0);
if (!$member_id) {
    die("Invalid member ID.");
}

$branch_id = $_SESSION['branch_id'];

// Main member info + institution from affiliation + membership type from member_category
$sql = "
  SELECT
    m.*,
    a.branch_id,
    a.institution_name   AS institution,
    mc.membership_type_id,
    mt.type_name         AS membership_type
  FROM members m
  JOIN affiliations a 
    ON m.id = a.member_id
  LEFT JOIN member_category mc 
    ON m.id = mc.member_id
  LEFT JOIN membership_types mt 
    ON mc.membership_type_id = mt.id
  WHERE m.id = ? 
    AND a.branch_id = ?
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("ii", $member_id, $branch_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$member) {
    die("Member not found or not in your branch.");
}

// Academic background
$academic_stmt = $conn->prepare("SELECT * FROM academic_background WHERE member_id = ?");
if (!$academic_stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$academic_stmt->bind_param("i", $member_id);
$academic_stmt->execute();
$academic_result = $academic_stmt->get_result();
$academics = $academic_result->fetch_all(MYSQLI_ASSOC);
$academic_stmt->close();

// Last paid dues and next renewal
$dues_stmt = $conn->prepare("SELECT amount_paid, payment_date FROM payments WHERE member_id = ? ORDER BY payment_date DESC LIMIT 1");
if (!$dues_stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$dues_stmt->bind_param("i", $member_id);
$dues_stmt->execute();
$dues_result = $dues_stmt->get_result();
$dues = $dues_result->fetch_assoc();
$dues_stmt->close();

// Branch events
$branch_events_stmt = $conn->prepare("
    SELECT e.name AS title, ep.participation_date AS date_participated 
    FROM event_participation ep
    JOIN events e ON ep.event_id = e.id
    JOIN branches b ON e.location LIKE CONCAT('%', b.branch_name, '%') AND b.id = ?
    WHERE ep.member_id = ?
");
if (!$branch_events_stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$branch_events_stmt->bind_param("ii", $branch_id, $member_id);
$branch_events_stmt->execute();
$branch_events = $branch_events_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$branch_events_stmt->close();

// National events
$national_events_stmt = $conn->prepare("
    SELECT e.name AS title, ep.participation_date AS date_participated
    FROM event_participation ep
    JOIN events e ON ep.event_id = e.id
    WHERE ep.member_id = ? AND e.location LIKE '%National%'
");
if (!$national_events_stmt) {
    die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
}
$national_events_stmt->bind_param("i", $member_id);
$national_events_stmt->execute();
$national_events = $national_events_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$national_events_stmt->close();

// Page display
$isDashboard = true;
include '../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="branch_dashboard.php">Dashboard</a>
            <a href="my_members.php" class="active">My Members</a>
            <a href="branch_events.php">Branch Events</a>
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
            <h1>View Member Details</h1>
        </header>

        <div class="card" style="padding: 20px;">
            <h2><?= htmlspecialchars($member['first_name'] . ' ' . $member['last_name']) ?></h2>
            <p><strong>Member ID:</strong> <?= htmlspecialchars($member['member_id']) ?></p>
            <p><strong>Email:</strong> <?= htmlspecialchars($member['email']) ?></p>
            <p><strong>Phone:</strong> <?= htmlspecialchars($member['phone'] ?? 'N/A') ?></p>
            <p><strong>Gender:</strong> <?= htmlspecialchars($member['gender'] ?? 'N/A') ?></p>
            <p><strong>Date of Birth:</strong> <?= !empty($member['dob']) ? htmlspecialchars($member['dob']) : 'N/A' ?></p>
            <p><strong>Institution:</strong> <?= htmlspecialchars($member['institution'] ?? 'N/A') ?></p>
            <p><strong>Membership Type:</strong> <?= htmlspecialchars($member['membership_type'] ?? 'N/A') ?></p>
            <p><strong>Status:</strong> 
                <span class="badge <?= strtolower($member['status'] ?? '') ?>">
                    <?= htmlspecialchars($member['status'] ?? 'Unknown') ?>
                </span>
            </p>
            <p><strong>Date Registered:</strong> 
                <?= !empty($member['date_registered']) ? htmlspecialchars(date('F j, Y g:i a', strtotime($member['date_registered']))) : 'Unknown' ?>
            </p>
            <p><strong>Notes:</strong><br>
                <?= nl2br(htmlspecialchars($member['notes'] ?? 'No notes')) ?>
            </p>

            <?php if ($dues): ?>
                <p><strong>Last Paid Dues:</strong> GH₵<?= htmlspecialchars($dues['amount_paid']) ?> on <?= date('F j, Y', strtotime($dues['payment_date'])) ?></p>
            <?php else: ?>
                <p><strong>Dues:</strong> No payment history available.</p>
            <?php endif; ?>

            <hr>
            <h3>Academic Background</h3>
            <?php if ($academics): ?>
                <ul>
                    <?php foreach ($academics as $edu): ?>
                        <li>
                            <?= htmlspecialchars($edu['highest_qualification'] . ' in ' . $edu['discipline'] . ' from ' . $edu['institution_attended'] . ' (' . $edu['graduation_year'] . ')') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No academic background on file.</p>
            <?php endif; ?>

            <hr>
            <h3>Branch Event Participation</h3>
            <?php if ($branch_events): ?>
                <ul>
                    <?php foreach ($branch_events as $event): ?>
                        <li><?= htmlspecialchars($event['title']) ?> – <?= date('F j, Y', strtotime($event['date_participated'])) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No branch events attended.</p>
            <?php endif; ?>

            <hr>
            <h3>National Event Participation</h3>
            <?php if ($national_events): ?>
                <ul>
                    <?php foreach ($national_events as $event): ?>
                        <li><?= htmlspecialchars($event['title']) ?> – <?= date('F j, Y', strtotime($event['date_participated'])) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>No national events attended.</p>
            <?php endif; ?>

            <br>
            <a href="my_members.php" class="badge info">← Back to Members</a>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
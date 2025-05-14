<?php
session_start(); // Start session before using $_SESSION

$timeout_duration = 1800; // 30 minutes

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

// Handle issuing letters securely
if (isset($_GET['issue_id'])) {
    $member_id = intval($_GET['issue_id']);

    // Use prepared statement to update
    $stmt = $conn->prepare("UPDATE members SET letter_issued = 1 WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $member_id);
        $stmt->execute();
        $stmt->close();

        // Optional: Audit log
        $user_id = $_SESSION['user_id'];
        $action = "Issued membership letter to Member ID #$member_id";
        $log_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
        if ($log_stmt) {
            $log_stmt->bind_param("is", $user_id, $action);
            $log_stmt->execute();
            $log_stmt->close();
        }
    }
    header("Location: issue_letters.php");
    exit;
}

$isDashboard = true;
include '../includes/header.php';
?>

<div class="dashboard">
        <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="secretariat_dashboard.php">Dashboard</a>
            <a href="approve_members.php">Approve Members</a>
            <a href="verify_payments.php">Verify Payments</a>
            <a href="issue_letters.php" class="active">Membership Letters</a>
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
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>Issue Membership Letters</h1>
        </header>

        <div class="table-card">
            <table>
                <thead>
                    <tr>
                        <th>Member Name</th>
                        <th>Membership ID</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "SELECT id, member_id, CONCAT(first_name, ' ', other_names, ' ', last_name) AS full_name, letter_issued 
                              FROM members 
                              WHERE status = 'Approved'
                              ORDER BY date_registered DESC";

                    $result = $conn->query($query);
                    if (!$result) {
                        die("Query failed: " . $conn->error);
                    }

                    while ($row = $result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['full_name']) ?></td>
                        <td><?= htmlspecialchars($row['member_id']) ?></td>
                        <td>
                            <?php if (!$row['letter_issued']): ?>
                                <a href="?issue_id=<?= $row['id'] ?>" class="btn" onclick="return confirm('Are you sure you want to issue this letter?');">Issue</a>
                            <?php else: ?>
                                <span class="badge">Issued</span>
                                <a href="generate_letter.php?member_id=<?= $row['id'] ?>" class="btn" target="_blank" style="margin-left: 10px;">Download Letter</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

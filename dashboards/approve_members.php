<?php
    $timeout_duration = 1800; // 30 minutes

    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        header("Location: ../includes/timeout.php");
        exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time();

    session_start();
    require_once '../includes/db_connect.php';

    // Only Secretariat users allowed
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Secretariat') {
        header("Location: ../index.php");
        exit;
    }

    $isDashboard = true;
    include '../includes/header.php';

    // Handle approval or rejection actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $member_id = intval($_POST['member_id']);
        $action = $_POST['action'];

        if ($action === 'approve') {
            $stmt = $conn->prepare("UPDATE members SET status = 'Approved' WHERE id = ?");
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE members SET status = 'Rejected' WHERE id = ?");
        }

        if (isset($stmt)) {
            $stmt->bind_param("i", $member_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    // Fetch pending members
    $pending_members = [];
    $result = $conn->query("SELECT * FROM members WHERE status = 'Pending'");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $pending_members[] = $row;
        }
    }
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="secretariat_dashboard.php">Dashboard</a>
            <a href="approve_members.php" class="active">Approve Members</a>
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
            <div class="hamburger" onclick="toggleSidebar()">☰</div>
            <h1>Approve Member Registrations</h1>
        </header>

        <section class="content">
            <?php if (count($pending_members) === 0): ?>
                <p>No pending registrations at the moment.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Institution</th>
                            <th>Branch</th>
                            <th>Date Registered</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_members as $member): ?>
                        <tr>
                            <td><?= htmlspecialchars($member['full_name']) ?></td>
                            <td><?= htmlspecialchars($member['email']) ?></td>
                            <td><?= htmlspecialchars($member['institution']) ?></td>
                            <td><?= htmlspecialchars($member['branch']) ?></td>
                            <td><?= date('d M Y', strtotime($member['date_registered'])) ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="member_id" value="<?= $member['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn-approve">Approve</button>
                                    <button type="submit" name="action" value="reject" class="btn-reject">Reject</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </section>
    </main>
</div>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}
</script>

<?php include '../includes/footer.php'; ?>

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

    // Access control: Admin only
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
        header("Location: ../index.php");
        exit;
    }

    // Get member ID
    $member_id = intval($_GET['id'] ?? 0);
    if (!$member_id) {
        die("Invalid member ID.");
    }

    // Main member info query (includes branch name)
    $sql = "
        SELECT
            m.*,
            a.institution_name AS institution,
            b.branch_name,
            mc.membership_type_id,
            mt.type_name AS membership_type
        FROM members m
        LEFT JOIN affiliations a ON m.id = a.member_id
        LEFT JOIN branches b ON a.branch_id = b.id
        LEFT JOIN member_category mc ON m.id = mc.member_id
        LEFT JOIN membership_types mt ON mc.membership_type_id = mt.id
        WHERE m.id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $member_id);
    $stmt->execute();
    $member = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$member) {
        die("Member not found.");
    }

    // Academic background
    $academic_stmt = $conn->prepare("SELECT * FROM academic_background WHERE member_id = ?");
    $academic_stmt->bind_param("i", $member_id);
    $academic_stmt->execute();
    $academics = $academic_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $academic_stmt->close();

    // Employment information
    $employment_stmt = $conn->prepare("SELECT * FROM employment WHERE member_id = ?");
    $employment_stmt->bind_param("i", $member_id);
    $employment_stmt->execute();
    $employment = $employment_stmt->get_result()->fetch_assoc();
    $employment_stmt->close();

    // Last paid dues
    $dues_stmt = $conn->prepare("SELECT amount_paid, payment_date FROM payments WHERE member_id = ? ORDER BY payment_date DESC LIMIT 1");
    $dues_stmt->bind_param("i", $member_id);
    $dues_stmt->execute();
    $dues = $dues_stmt->get_result()->fetch_assoc();
    $dues_stmt->close();

    // National events only
    $national_events_stmt = $conn->prepare("
        SELECT e.name AS title, ep.participation_date AS date_participated
        FROM event_participation ep
        JOIN events e ON ep.event_id = e.id
        WHERE ep.member_id = ? AND e.location LIKE '%National%'
    ");
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
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_members.php" class="active">Manage Members</a>
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
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>View Member Details</h1>
        </header>

        <div class="card" style="padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h2><?= htmlspecialchars($member['first_name'] . ' ' . $member['other_names'] . ' ' . $member['last_name']) ?></h2>
                <div>
                    <a href="edit_member.php?id=<?= $member['id'] ?>" class="badge approved">✏️ Edit Member</a>
                    <a href="manage_members.php" class="badge info">← Back to Members</a>
                </div>
            </div>
            
            <div class="member-details-grid">
                <div class="member-section">
                    <h3>Personal Information</h3>
                    <p><strong>Member ID:</strong> <?= htmlspecialchars($member['member_id']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($member['email']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($member['phone'] ?? 'N/A') ?></p>
                    <p><strong>Gender:</strong> <?= htmlspecialchars($member['gender'] ?? 'N/A') ?></p>
                    <p><strong>Date of Birth:</strong> <?= !empty($member['dob']) ? htmlspecialchars($member['dob']) : 'N/A' ?></p>
                    <p><strong>Branch:</strong> <?= htmlspecialchars($member['branch_name'] ?? 'N/A') ?></p>
                    <p><strong>Membership Type:</strong> <?= htmlspecialchars($member['membership_type'] ?? 'N/A') ?></p>
                    <p><strong>Status:</strong> 
                        <span class="badge <?= strtolower($member['status'] ?? '') ?>">
                            <?= htmlspecialchars($member['status'] ?? 'Unknown') ?>
                        </span>
                    </p>
                    <p><strong>Date Registered:</strong> 
                        <?= !empty($member['date_registered']) ? htmlspecialchars(date('F j, Y g:i a', strtotime($member['date_registered']))) : 'Unknown' ?>
                    </p>
                </div>

                <div class="member-section">
                    <h3>Membership Details</h3>
                    <?php if ($dues): ?>
                        <p><strong>Last Paid Dues:</strong> GH₵<?= htmlspecialchars($dues['amount_paid']) ?> on <?= date('F j, Y', strtotime($dues['payment_date'])) ?></p>
                    <?php else: ?>
                        <p><strong>Dues:</strong> No payment history available.</p>
                    <?php endif; ?>
                    
                    <p><strong>Notes:</strong><br>
                        <?= nl2br(htmlspecialchars($member['notes'] ?? 'No notes')) ?>
                    </p>
                </div>
            </div>

            <hr>
            
            <div class="member-section">
                <h3>Employment Information</h3>
                <?php if ($employment): ?>
                    <p><strong>Employment Status:</strong> <?= htmlspecialchars($employment['employment_status'] ?? 'Not specified') ?></p>
                    <p><strong>Current Position:</strong> <?= htmlspecialchars($employment['current_position'] ?? 'N/A') ?></p>
                    <p><strong>Organization:</strong> <?= htmlspecialchars($employment['organization'] ?? 'N/A') ?></p>
                    <p><strong>Sector:</strong> <?= htmlspecialchars($employment['sector'] ?? 'N/A') ?></p>
                    <p><strong>Location:</strong> <?= htmlspecialchars($employment['location'] ?? 'N/A') ?></p>
                <?php else: ?>
                    <p>No employment information on file.</p>
                <?php endif; ?>
            </div>

            <hr>
            
            <div class="member-section">
                <h3>Academic Background</h3>
                <?php if ($academics): ?>
                    <ul>
                        <?php foreach ($academics as $edu): ?>
                            <li>
                                <?php 
                                    $qualification = !empty($edu['highest_qualification']) ? htmlspecialchars($edu['highest_qualification']) : 'Qualification not specified';
                                    $discipline = !empty($edu['discipline']) ? ' in ' . htmlspecialchars($edu['discipline']) : '';
                                    $institution = !empty($edu['institution_attended']) ? ' from ' . htmlspecialchars($edu['institution_attended']) : '';
                                    $year = ($edu['graduation_year'] != '0000') ? ' (' . htmlspecialchars($edu['graduation_year']) . ')' : '';
                                    
                                    echo $qualification . $discipline . $institution . $year;
                                ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No academic background on file.</p>
                <?php endif; ?>
            </div>

            <hr>
            
            <div class="member-section">
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
            </div>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
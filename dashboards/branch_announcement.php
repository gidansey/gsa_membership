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
    die("Branch not assigned. Contact Admin.");
}

$success = $error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $send_email = isset($_POST['send_email']) ? 1 : 0;
    
    if (empty($subject) || empty($message)) {
        $error = "Subject and message are required";
    } else {
        // Save to database
        $stmt = $conn->prepare("INSERT INTO announcements 
                              (branch_id, subject, message, created_by, created_at) 
                              VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("issi", $branch_id, $subject, $message, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $announcement_id = $conn->insert_id;
            
            // If email option selected
            if ($send_email) {
                // Get member emails
                $email_stmt = $conn->prepare("
                    SELECT m.email, m.first_name, m.last_name
                    FROM members m
                    JOIN affiliations a ON a.member_id = m.id
                    WHERE a.branch_id = ? AND m.status = 'Approved'
                ");
                $email_stmt->bind_param("i", $branch_id);
                $email_stmt->execute();
                $result = $email_stmt->get_result();
                
                // Send emails (implementation depends on your email system)
                while ($row = $result->fetch_assoc()) {
                    // Example: send_email($row['email'], $subject, $message);
                    // You would implement your actual email sending function
                }
            }
            
            $success = "Announcement sent successfully!";
        } else {
            $error = "Error saving announcement: " . $conn->error;
        }
    }
}

// Get past announcements
$announcements = [];
$stmt = $conn->prepare("
    SELECT a.*, u.first_name, u.last_name 
    FROM announcements a
    JOIN users u ON a.created_by = u.id
    WHERE a.branch_id = ?
    ORDER BY a.created_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();
$announcements = $result->fetch_all(MYSQLI_ASSOC);

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
            <a href="branch_announcement.php" class="active">Announcements</a>
            <a href="branch_report.php">Reports</a>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>Branch Announcements</h1>
        </header>

        <?php if ($error): ?>
            <div class="alert error"><?= $error ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert success"><?= $success ?></div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" class="announcement-form">
                <div class="form-group">
                    <label for="subject">Subject</label>
                    <input type="text" id="subject" name="subject" required maxlength="100" placeholder="Enter announcement subject">
                </div>
                
                <div class="form-group">
                    <label for="message">Message</label>
                    <textarea id="message" name="message" rows="6" required placeholder="Type your announcement message here..."></textarea>
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="send_email" name="send_email">
                    <label for="send_email">Also send as email to all active members</label>
                </div>
                
                <button type="submit" class="submit-btn">Send Announcement</button>
            </form>
        </div>

        <section class="announcement-history">
            <h2>Recent Announcements</h2>
            
            <?php if (empty($announcements)): ?>
                <p class="no-announcements">No announcements have been sent yet.</p>
            <?php else: ?>
                <?php foreach ($announcements as $announcement): ?>
                    <div class="announcement-card">
                        <div class="announcement-header">
                            <h3><?= htmlspecialchars($announcement['subject']) ?></h3>
                            <span class="announcement-date">
                                <?= date('M j, Y g:i a', strtotime($announcement['created_at'])) ?>
                            </span>
                        </div>
                        <div class="announcement-body">
                            <p><?= nl2br(htmlspecialchars($announcement['message'])) ?></p>
                        </div>
                        <div class="announcement-footer">
                            Sent by <?= htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</div>

<?php include '../includes/footer.php'; ?>
<?php
session_start();
require_once '../includes/db_connect.php';

// Timeout
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Admin and Secretariat
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Secretariat'])) {
    header("Location: ../index.php");
    exit();
}

$payment_id = intval($_GET['id'] ?? 0);
if (!$payment_id) {
    die("Invalid payment ID.");
}

// Fetch existing payment
$stmt = $conn->prepare("
    SELECT p.*, m.member_id, m.first_name, m.last_name, m.id AS member_db_id
    FROM payments p
    JOIN members m ON p.member_id = m.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) die("Payment not found.");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount_paid = floatval($_POST['amount_paid']);
    $payment_date = $_POST['payment_date'];
    $membership_type_id = intval($_POST['membership_type_id']);
    $member_db_id = intval($payment['member_db_id']);

    // Update payment
    $update = $conn->prepare("UPDATE payments SET amount_paid = ?, payment_date = ?, membership_type_id = ? WHERE id = ?");
    $update->bind_param("dsii", $amount_paid, $payment_date, $membership_type_id, $payment_id);
    $update->execute();
    $update->close();

    // Recalculate dates
    $latest = $conn->prepare("SELECT payment_date FROM payments WHERE member_id = ? ORDER BY payment_date DESC LIMIT 1");
    $latest->bind_param("i", $member_db_id);
    $latest->execute();
    $latest_payment = $latest->get_result()->fetch_assoc();
    $latest->close();

    if ($latest_payment) {
        $start = $latest_payment['payment_date'];
        $next_renewal = date('Y-m-d', strtotime("$start +1 year"));
        $expiry = date('Y-m-d', strtotime("$start +1 year -1 day"));

        $update_member = $conn->prepare("UPDATE members SET next_renewal_date = ?, membership_expiry_date = ? WHERE id = ?");
        $update_member->bind_param("ssi", $next_renewal, $expiry, $member_db_id);
        $update_member->execute();
        $update_member->close();
    }

    // Audit log
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $action = "Updated payment for member {$payment['member_id']} - {$payment['first_name']} {$payment['last_name']} (Payment ID: $payment_id)";
    $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, table_name, affected_id, ip_address, user_agent) VALUES (?, ?, 'payments', ?, ?, ?)");
    $log->bind_param("isiss", $user_id, $action, $payment_id, $ip_address, $user_agent);
    $log->execute();
    $log->close();

    // Redirect with success message
    header("Location: manage_payments.php?message=" . urlencode("Payment updated successfully."));
    exit;
}

// Fetch membership types
$types = $conn->query("SELECT id, type_name FROM membership_types")->fetch_all(MYSQLI_ASSOC);

$isDashboard = true;
include '../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_members.php">Manage Members</a>
            <a href="manage_payments.php" class="active">Manage Payments</a>
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
        <header><h1>Edit Payment</h1></header>

        <div class="form-container">
            <form method="POST">
                <div class="form-group">
                    <label>Member:</label>
                    <input type="text" value="<?= htmlspecialchars($payment['member_id'] . ' - ' . $payment['first_name'] . ' ' . $payment['last_name']) ?>" disabled>
                </div>

                <div class="form-group">
                    <label>Membership Type:</label>
                    <select name="membership_type_id" required>
                        <option value="">-- Select Type --</option>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= $type['id'] ?>" <?= $type['id'] == $payment['membership_type_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['type_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Amount Paid (GH¢):</label>
                    <input type="number" name="amount_paid" value="<?= htmlspecialchars($payment['amount_paid']) ?>" step="0.01" required>
                </div>

                <div class="form-group">
                    <label>Payment Date:</label>
                    <input type="date" name="payment_date" value="<?= htmlspecialchars($payment['payment_date']) ?>" required>
                </div>

                <button type="submit">💾 Save Changes</button>
                <a href="manage_payments.php" class="badge info" style="margin-left:10px;">← Cancel</a>
            </form>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

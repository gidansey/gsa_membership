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

// Admin only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

$payment_id = intval($_GET['id'] ?? 0);
if (!$payment_id) {
    die("Invalid payment ID.");
}

// Fetch payment info
$stmt = $conn->prepare("
    SELECT p.id, p.member_id AS member_fk, m.member_id, m.first_name, m.last_name, p.amount_paid, p.payment_date
    FROM payments p
    JOIN members m ON p.member_id = m.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $payment_id);
$stmt->execute();
$payment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$payment) {
    die("Payment not found.");
}

// Count payments
$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM payments WHERE member_id = ?");
$count_stmt->bind_param("i", $payment['member_fk']);
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$count_stmt->close();

$can_delete = $count_result['total'] > 1;

// All other members for transfer
$members = $conn->query("SELECT id, member_id, first_name, last_name FROM members WHERE id != {$payment['member_fk']} ORDER BY first_name")->fetch_all(MYSQLI_ASSOC);

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    if (isset($_POST['transfer_to'])) {
        // Transfer payment to another member
        $new_member_id = intval($_POST['transfer_to']);
        $update = $conn->prepare("UPDATE payments SET member_id = ? WHERE id = ?");
        $update->bind_param("ii", $new_member_id, $payment_id);
        $update->execute();
        $update->close();

        // Log transfer
        $action = "Transferred payment ID $payment_id from member {$payment['member_id']} to member ID $new_member_id";
        $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, table_name, affected_id, ip_address, user_agent) VALUES (?, ?, 'payments', ?, ?, ?)");
        $log->bind_param("isiss", $user_id, $action, $payment_id, $ip_address, $user_agent);
        $log->execute();
        $log->close();

        header("Location: manage_payments.php?message=" . urlencode("Payment transferred successfully."));
        exit;
    } else {
        // Delete payment
        $del_stmt = $conn->prepare("DELETE FROM payments WHERE id = ?");
        $del_stmt->bind_param("i", $payment_id);
        $del_stmt->execute();
        $del_stmt->close();

        // Log deletion
        $action = "Deleted payment for member {$payment['member_id']} - {$payment['first_name']} {$payment['last_name']} (Payment ID: $payment_id)";
        $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, table_name, affected_id, ip_address, user_agent) VALUES (?, ?, 'payments', ?, ?, ?)");
        $log->bind_param("isiss", $user_id, $action, $payment_id, $ip_address, $user_agent);
        $log->execute();
        $log->close();

        header("Location: manage_payments.php?message=" . urlencode("Payment deleted successfully."));
        exit;
    }
}

$isDashboard = true;
include '../includes/header.php';
?>

<div class="dashboard">
    <main class="main">
        <header><h1>Delete or Transfer Payment</h1></header>

        <div class="form-container">
            <ul>
                <li><strong>Member:</strong> <?= htmlspecialchars($payment['member_id'] . ' - ' . $payment['first_name'] . ' ' . $payment['last_name']) ?></li>
                <li><strong>Amount Paid:</strong> GH₵<?= htmlspecialchars(number_format($payment['amount_paid'], 2)) ?></li>
                <li><strong>Date:</strong> <?= htmlspecialchars(date('F j, Y', strtotime($payment['payment_date']))) ?></li>
            </ul>

            <?php if ($can_delete): ?>
                <form method="POST" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-danger">🗑 Yes, Delete</button>
                    <a href="manage_payments.php" class="badge info" style="margin-left: 15px;">← Cancel</a>
                </form>
            <?php else: ?>
                <div class="alert warning" style="margin-top: 20px; background: #fff8e1; padding: 10px; border: 1px solid #ffecb3; color: #a67c00;">
                    ⚠ This is the member’s only payment. You cannot delete it directly. You can transfer it to another member.
                </div>

                <form method="POST" style="margin-top: 20px;">
                    <label>Select another member to transfer this payment to:</label>
                    <select name="transfer_to" required>
                        <option value="">-- Select Member --</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['member_id'] . ' - ' . $m['first_name'] . ' ' . $m['last_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary" style="margin-top: 10px;">🔄 Transfer Payment</button>
                    <a href="manage_payments.php" class="badge info" style="margin-left: 15px;">← Cancel</a>
                </form>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

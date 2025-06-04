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

// Admin only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}

$member_id = intval($_GET['id'] ?? 0);
if (!$member_id) {
    die("Invalid member ID.");
}

// Reactivate member
$stmt = $conn->prepare("UPDATE members SET status = 'Approved' WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $member_id);
$stmt->execute();
$stmt->close();

// Insert audit log
$admin_id = $_SESSION['user_id'];
$log_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, table_name, affected_id, timestamp) VALUES (?, ?, ?, ?, NOW())");
if (!$log_stmt) {
    die("Log prepare failed: " . $conn->error);
}
$action = "Reactivated member";
$table = "members";
$log_stmt->bind_param("sssi", $admin_id, $action, $table, $member_id);
$log_stmt->execute();
$log_stmt->close();

// Redirect back
header("Location: manage_members.php?status=Approved&message=Member+Reactivated");
exit;

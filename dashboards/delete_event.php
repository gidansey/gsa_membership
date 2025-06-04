<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// 1. Ensure ID is valid
if ($id <= 0) {
    header("Location: manage_events.php?error=Invalid+Event+ID");
    exit;
}

// 2. Check if event is linked to participation records
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM event_participation WHERE event_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
if ($result['total'] > 0) {
    header("Location: manage_events.php?error=Cannot+delete+event+with+linked+participation+records");
    exit;
}

// 3. Log audit action BEFORE deletion
$audit = $conn->prepare("INSERT INTO audit_logs (user_id, action, table_name, affected_id) VALUES (?, ?, ?, ?)");
$action = "Deleted Event";
$table = "events";
$audit->bind_param("issi", $user_id, $action, $table, $id);
$audit->execute();

// 4. Delete event
$stmt = $conn->prepare("DELETE FROM events WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

// 5. Redirect
header("Location: manage_events.php?success=Event+deleted+successfully");
exit;

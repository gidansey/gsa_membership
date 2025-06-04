<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false]);
    exit;
}

require_once '../includes/db_connect.php';

$notif_id = intval($_POST['id']);
$current_read = intval($_POST['is_read']);
$new_status = $current_read ? 0 : 1; // Toggle

$stmt = $conn->prepare("UPDATE notifications SET is_read = ? WHERE id = ? AND user_id = ?");
$stmt->bind_param("iii", $new_status, $notif_id, $_SESSION['user_id']);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'new_status' => $new_status]);
} else {
    echo json_encode(['success' => false]);
}
$stmt->close();
$conn->close();
?>

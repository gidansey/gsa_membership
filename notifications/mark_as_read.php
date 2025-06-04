<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    http_response_code(403);
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND (recipient_id = ? OR recipient_role = 'Branch Leader')");
$stmt->bind_param("ii", $id, $_SESSION['user_id']);
$stmt->execute();
$stmt->close();

http_response_code(200);
?>

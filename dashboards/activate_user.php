<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($user_id > 0) {
    $stmt = $conn->prepare("UPDATE users SET status = 'Active' WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
}

header("Location: manage_users.php");
exit;

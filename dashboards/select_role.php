<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['selected_role'])) {
    header("Location: ../index.php");
    exit;
}

$selected_role = $_POST['selected_role'];
$_SESSION['role'] = $selected_role;

$user_id = $_SESSION['user_id'];

if ($selected_role === 'Branch Leader') {
    // Get branch_id from users table (or branch_leaders table if separate)
    $stmt = $conn->prepare("SELECT branch_id FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $_SESSION['branch_id'] = $row['branch_id'];
    }
    $stmt->close();

    header("Location: dashboards/branch_dashboard.php");
    exit;
} elseif ($selected_role === 'Member') {
    header("Location: dashboards/member_dashboard.php");
    exit;
} else {
    // Unknown role
    session_destroy();
    header("Location: ../index.php");
    exit;
}

<?php
session_start();
require_once 'includes/db_connect.php';

// Capture user info before destroying session
$user_id = $_SESSION['user_id'] ?? null;

// 1. Log audit trail if user is logged in
if ($user_id) {
    $action = "User Logout";
    $table_name = "users";
    $affected_id = $user_id;

    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, table_name, affected_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issi", $user_id, $action, $table_name, $affected_id);
    $stmt->execute();
}

// 2. Clear session
$_SESSION = [];
session_destroy();

// 3. Clear cookie if needed
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Redirect to login
header("Location: logged_out.php");
exit;

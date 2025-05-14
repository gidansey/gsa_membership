<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Branch Leader') {
    die("Unauthorized access.");
}

$member_ids = $_POST['member_ids'] ?? [];

if (empty($member_ids) || !is_array($member_ids)) {
    die("No members selected.");
}

$email_subject = "Bulk Message from GSA Branch";
$email_body = "This is a message from your branch leader.";

foreach ($member_ids as $id) {
    $stmt = $conn->prepare("SELECT email FROM members WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        mail($row['email'], $email_subject, $email_body);
    }
}

echo "✅ Emails sent successfully!";
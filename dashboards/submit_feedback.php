<?php
session_start();
require 'db_connect.php'; // your DB connection file

$member_id = $_SESSION['member_id']; // from session
$event_id = $_POST['event_id'];
$rating = $_POST['rating'];
$comments = $_POST['comments'];

$stmt = $conn->prepare("INSERT INTO feedback (member_id, event_id, rating, comments) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $member_id, $event_id, $rating, $comments);

if ($stmt->execute()) {
    echo "Feedback submitted successfully!";
} else {
    echo "Error: " . $conn->error;
}

$stmt->close();
$conn->close();
?>

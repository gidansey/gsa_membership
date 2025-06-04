<?php
require 'db_connect.php';

$event_id = $_GET['event_id'];

$query = "SELECT f.rating, f.comments, f.submitted_at, m.first_name, m.last_name
          FROM feedback f
          JOIN members m ON f.member_id = m.id
          WHERE f.event_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

echo "<h3>Feedback for Event #$event_id</h3>";
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div style='border:1px solid #ccc; margin:10px; padding:10px;'>
            <strong>{$row['first_name']} {$row['last_name']}</strong> (Rated: {$row['rating']}/5)<br>
            <em>Submitted on: {$row['submitted_at']}</em><br>
            <p>{$row['comments']}</p>
        </div>";
    }
} else {
    echo "No feedback available yet.";
}
$stmt->close();
$conn->close();
?>

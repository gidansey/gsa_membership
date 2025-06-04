<?php
session_start();
require_once '../includes/db_connect.php';

// Only allow Admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

// Set headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=event_participation.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Column headers
fputcsv($output, ['Event Name', 'Member Name', 'Email', 'Participation Date', 'Role']);

// Fetch participation records
$sql = "
    SELECT 
        e.name AS event_name,
        CONCAT(m.first_name, ' ', m.last_name) AS member_name,
        m.email,
        ep.participation_date,
        ep.event_role
    FROM event_participation ep
    JOIN members m ON ep.member_id = m.id
    JOIN events e ON ep.event_id = e.id
    ORDER BY ep.participation_date DESC
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['event_name'],
            $row['member_name'],
            $row['email'],
            date('Y-m-d H:i', strtotime($row['participation_date'])),
            $row['event_role']
        ]);
    }
} else {
    fputcsv($output, ['No data found.']);
}

fclose($output);
exit;

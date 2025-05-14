<?php
session_start();
require_once '../includes/db_connect.php';

// Security checks
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Branch Leader') {
    header("Location: ../index.php");
    exit;
}

// Event ID validation
if (!isset($_GET['event_id']) || !is_numeric($_GET['event_id'])) {
    die("Invalid event ID.");
}

$event_id = intval($_GET['event_id']);
$branch_id = $_SESSION['branch_id'] ?? null;

if (!$branch_id) {
    die("Branch not assigned.");
}

// Verify event belongs to branch
$event_check = $conn->prepare("
    SELECT e.name FROM events e
    JOIN branches b ON e.location LIKE CONCAT('%', b.branch_name, '%')
    WHERE e.id = ? AND b.id = ?
");
$event_check->bind_param("ii", $event_id, $branch_id);
$event_check->execute();
$event = $event_check->get_result()->fetch_assoc();

if (!$event) {
    die("Unauthorized or event not found.");
}

// Fetch participants
$sql = "
    SELECT CONCAT(m.first_name, ' ', m.last_name) AS full_name, m.email, ep.event_role, ep.participation_date
    FROM event_participation ep
    JOIN members m ON ep.member_id = m.id
    JOIN affiliations a ON m.id = a.member_id
    WHERE ep.event_id = ? AND a.branch_id = ?
    ORDER BY ep.participation_date DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $event_id, $branch_id);
$stmt->execute();
$result = $stmt->get_result();

// Output CSV headers
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=event_participants_branch_{$branch_id}_event_{$event_id}.csv");

$output = fopen("php://output", "w");
fputcsv($output, ['Full Name', 'Email', 'Role in Event', 'Participation Date']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['full_name'],
        $row['email'],
        $row['event_role'],
        $row['participation_date']
    ]);
}

fclose($output);
exit;

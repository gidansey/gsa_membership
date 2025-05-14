<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Branch Leader') {
    header("Location: ../index.php");
    exit;
}

$branch_id = $_SESSION['branch_id'];
$search = $_GET['search'] ?? '';

$where = "a.branch_id = ?";
$params = [$branch_id];
$types = 'i';

if (!empty($search)) {
    $where .= " AND (m.first_name LIKE ? OR m.last_name LIKE ? OR m.email LIKE ?)";
    $searchTerm = "%$search%";
    array_push($params, $searchTerm, $searchTerm, $searchTerm);
    $types .= 'sss';
}

$query = "
    SELECT m.id, m.member_id, m.first_name, m.last_name, m.email, m.status
    FROM members m
    JOIN affiliations a ON m.id = a.member_id
    WHERE $where
";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="branch_members.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Member ID', 'Full Name', 'Email', 'Status']);

while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['member_id'],
        $row['first_name'] . ' ' . $row['last_name'],
        $row['email'],
        $row['status']
    ]);
}
fclose($output);
exit;
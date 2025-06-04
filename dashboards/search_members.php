<?php
require_once '../includes/db_connect.php';

$q = trim($_GET['q'] ?? '');
$q = "%$q%";

$stmt = $conn->prepare("SELECT id, member_id, first_name, last_name FROM members WHERE first_name LIKE ? OR last_name LIKE ? OR member_id LIKE ? ORDER BY first_name, last_name LIMIT 20");
$stmt->bind_param("sss", $q, $q, $q);
$stmt->execute();
$result = $stmt->get_result();

$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}

header('Content-Type: application/json');
echo json_encode($members);

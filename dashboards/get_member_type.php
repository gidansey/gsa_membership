<?php
require_once '../includes/db_connect.php';

$member_id = intval($_GET['member_id'] ?? 0);

$sql = "
    SELECT 
        m.id, 
        m.member_id, 
        m.first_name, 
        m.last_name,
        m.photo_path,
        mt.id AS membership_type_id, 
        mt.type_name, 
        mt.annual_dues
    FROM members m
    JOIN member_category mc ON m.id = mc.member_id
    JOIN membership_types mt ON mc.membership_type_id = mt.id
    WHERE m.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

header('Content-Type: application/json');
echo json_encode($data);

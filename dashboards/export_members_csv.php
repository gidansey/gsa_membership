<?php
session_start();
require_once '../includes/db_connect.php';

// Admin-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

// Set headers to trigger CSV file download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=registered_members_export.csv');

// Open the output stream
$output = fopen('php://output', 'w');

// Output CSV column headers
fputcsv($output, [
    'Member ID',
    'First Name',
    'Last Name',
    'Email',
    'Phone',
    'Branch',
    'Institution',
    'Membership Type',
    'Registration Date'
]);

// Query member data
$sql = "
    SELECT 
        m.id,
        m.first_name,
        m.last_name,
        m.email,
        m.phone,
        b.name AS branch,
        i.name AS institution,
        mt.name AS membership_type,
        m.created_at
    FROM members m
    LEFT JOIN branches b ON m.branch_id = b.id
    LEFT JOIN institutions i ON m.institution_id = i.id
    LEFT JOIN membership_types mt ON m.membership_type_id = mt.id
    ORDER BY m.created_at DESC
";

$result = $conn->query($sql);

// Output rows
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['first_name'],
            $row['last_name'],
            $row['email'],
            $row['phone'],
            $row['branch'],
            $row['institution'],
            $row['membership_type'],
            date('Y-m-d', strtotime($row['created_at']))
        ]);
    }
} else {
    fputcsv($output, ['No members found.']);
}

fclose($output);
exit;
?>

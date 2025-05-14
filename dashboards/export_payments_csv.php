<?php
session_start();
require_once '../includes/db_connect.php';

// Only allow Admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

// Force download as CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=payments_export.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Output column headers
fputcsv($output, [
    'Member Name',
    'Email',
    'Membership Type',
    'Amount Paid (GHS)',
    'Payment Date',
    'Payment Mode',
    'Reference No'
]);

// Fetch payment records
$sql = "
    SELECT 
        CONCAT(m.first_name, ' ', m.last_name) AS member_name,
        m.email,
        mt.type_name AS membership_type,
        p.amount_paid,
        p.payment_date,
        p.payment_mode,
        p.reference_no
    FROM payments p
    JOIN members m ON p.member_id = m.id
    JOIN membership_types mt ON p.membership_type_id = mt.id
    ORDER BY p.payment_date DESC
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['member_name'],
            $row['email'],
            $row['membership_type'],
            number_format($row['amount_paid'], 2),
            $row['payment_date'],
            $row['payment_mode'],
            $row['reference_no']
        ]);
    }
} else {
    fputcsv($output, ['No payment records found.']);
}

fclose($output);
exit;

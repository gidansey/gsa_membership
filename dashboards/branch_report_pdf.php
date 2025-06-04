<?php
require_once('tcpdf/tcpdf.php');
require_once('config.php');
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'branch_leader') {
    header("Location: login.php");
    exit();
}

$branch_id = $_SESSION['branch_id'];
$start_date = $_GET['start_date'] ?? '2000-01-01';
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch branch name
$stmt = $conn->prepare("SELECT name FROM branches WHERE id = ?");
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$stmt->bind_result($branch_name);
$stmt->fetch();
$stmt->close();

// Count members
$member_result = $conn->query("SELECT COUNT(*) AS total FROM members WHERE branch_id = $branch_id");
$member_total = $member_result->fetch_assoc()['total'];

// Membership types
$types_result = $conn->query("
    SELECT mt.name, COUNT(*) as count
    FROM members m
    JOIN membership_types mt ON m.membership_type_id = mt.id
    WHERE m.branch_id = $branch_id
    GROUP BY mt.id
");
$types_data = [];
while ($row = $types_result->fetch_assoc()) {
    $types_data[] = $row;
}

// Prepare data for chart
$labels = [];
$data = [];
foreach ($types_data as $type) {
    $labels[] = $type['name'];
    $data[] = $type['count'];
}

// Generate chart using QuickChart
$chart_url = 'https://quickchart.io/chart?c=' . urlencode(json_encode([
    'type' => 'pie',
    'data' => [
        'labels' => $labels,
        'datasets' => [[
            'data' => $data,
            'backgroundColor' => ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b']
        ]]
    ],
    'options' => [
        'title' => ['display' => true, 'text' => 'Membership Type Distribution']
    ]
]));

// Dues summary (within date range)
$dues_query = $conn->query("
    SELECT 
        SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) AS paid,
        SUM(CASE WHEN status = 'Unpaid' THEN 1 ELSE 0 END) AS unpaid
    FROM membership_dues
    WHERE member_id IN (SELECT id FROM members WHERE branch_id = $branch_id)
    AND date BETWEEN '$start_date' AND '$end_date'
");
$dues = $dues_query->fetch_assoc();

// Event Participation (within date range)
$events_query = $conn->query("
    SELECT e.name, COUNT(p.id) AS participants
    FROM events e
    LEFT JOIN event_participation p ON e.id = p.event_id
    WHERE e.branch_id = $branch_id
    AND e.date BETWEEN '$start_date' AND '$end_date'
    GROUP BY e.id
");

// Generate PDF
$pdf = new TCPDF();
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);

// GSA logo (assuming it's in assets folder)
$pdf->Image('assets/gsa_logo.png', 15, 10, 30); // x, y, width
$pdf->Ln(20); // spacing

$pdf->WriteHTML("
    <h1>Ghana Science Association</h1>
    <h2>Branch Report: $branch_name</h2>
    <p><strong>Period:</strong> $start_date to $end_date</p>
    <hr>
    <p><strong>Total Members:</strong> $member_total</p>
    <p><strong>Dues Paid:</strong> {$dues['paid']} | <strong>Unpaid:</strong> {$dues['unpaid']}</p>
");

$pdf->WriteHTML("<h3>Event Participation</h3><table border='1' cellpadding='4'><tr><th>Event</th><th>Participants</th></tr>");
while ($row = $events_query->fetch_assoc()) {
    $pdf->WriteHTML("<tr><td>{$row['name']}</td><td>{$row['participants']}</td></tr>");
}
$pdf->WriteHTML("</table>");

// Add chart image (pie chart)
$pdf->Ln(10);
$pdf->WriteHTML("<h3>Membership Breakdown by Type</h3>");
$pdf->Image($chart_url, '', '', 100, 100, 'PNG'); // Chart size

$pdf->Output("branch_report_$branch_name.pdf", 'I');
?>

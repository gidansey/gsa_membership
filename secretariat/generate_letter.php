<?php
require_once '../includes/db_connect.php';
require_once '../vendor/autoload.php'; // Adjust if your autoload path differs

use TCPDF;

// Start session and check role
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Secretariat') {
    header("Location: ../index.php");
    exit;
}

// Validate member_id input
if (!isset($_GET['member_id']) || !is_numeric($_GET['member_id'])) {
    die("Invalid or missing member ID.");
}

$member_id = intval($_GET['member_id']);

// Fetch member details
$stmt = $conn->prepare("SELECT member_id, first_name, other_names, last_name, institution, date_registered FROM members WHERE id = ? AND letter_issued = 1");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("No issued letter found for this member.");
}

$member = $result->fetch_assoc();
$fullName = htmlspecialchars($member['first_name'] . ' ' . $member['other_names'] . ' ' . $member['last_name']);
$memberID = htmlspecialchars($member['member_id']);
$institution = htmlspecialchars($member['institution']);
$registrationDate = date('F j, Y', strtotime($member['date_registered']));
$today = date('F j, Y');

// Create PDF
$pdf = new TCPDF();
$pdf->SetCreator('GSA Membership System');
$pdf->SetAuthor('Ghana Science Association');
$pdf->SetTitle('Membership Confirmation Letter');
$pdf->SetMargins(20, 30, 20);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);

$html = <<<EOD
<h2 style="text-align: center;">Ghana Science Association</h2>
<p>Date: $today</p>
<p>To Whom It May Concern,</p>
<p>This letter is to certify that <strong>$fullName</strong>, identified by Member ID <strong>$memberID</strong>, is a registered member of the Ghana Science Association.</p>
<p><strong>Institution:</strong> $institution<br>
<strong>Date of Registration:</strong> $registrationDate</p>
<p>We welcome them as a valuable part of our scientific community and encourage their continued participation in our national and branch-level activities.</p>
<br><br>
<p>Yours sincerely,</p>
<p><strong>Scientific Coordinator</strong><br>Ghana Science Association</p>
EOD;

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output("GSA_Letter_$memberID.pdf", 'D'); // 'D' forces download

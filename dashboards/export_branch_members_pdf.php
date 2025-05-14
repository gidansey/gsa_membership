<?php
require_once '../vendor/autoload.php';
use Dompdf\Dompdf;

ob_start();
include 'my_members_data.php'; // Extract member table HTML here
$html = ob_get_clean();

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream("branch_members.pdf", ["Attachment" => false]);
exit(0);
<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SESSION['role'] !== 'Admin') exit("Unauthorized");

$id = intval($_GET['id']);
$action = $_GET['action'];

$status = match ($action) {
    'approve' => 'Approved',
    'reject' => 'Rejected',
    default => 'Pending'
};

$conn->query("UPDATE members SET status = '$status' WHERE id = $id");
header("Location: manage_members.php");

<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SESSION['role'] !== 'Admin') exit("Unauthorized");

$id = intval($_GET['id']);
$conn->query("DELETE FROM members WHERE id = $id");
header("Location: manage_members.php");

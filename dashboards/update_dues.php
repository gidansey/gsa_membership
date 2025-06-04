<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

if (isset($_POST['dues']) && is_array($_POST['dues'])) {
    foreach ($_POST['dues'] as $id => $amount) {
        $stmt = $conn->prepare("UPDATE membership_types SET annual_dues = ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $id);
        $stmt->execute();
    }
}

header("Location: settings.php?success=Dues+updated");
exit;

<?php
session_start();
require_once '../includes/db_connect.php';

if ($_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

function updateSetting($conn, $key, $value) {
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $stmt->bind_param("ss", $key, $value);
    $stmt->execute();
}

// Save posted values
$org_name = $_POST['org_name'] ?? '';
$slogan = $_POST['slogan'] ?? '';
$contact_email = $_POST['contact_email'] ?? '';

updateSetting($conn, 'org_name', $org_name);
updateSetting($conn, 'slogan', $slogan);
updateSetting($conn, 'contact_email', $contact_email);

// Redirect back
header("Location: settings.php?success=Settings+updated");
exit;

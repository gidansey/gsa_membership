<?php
session_start();
require_once '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id          = intval($_POST['id']);
    $name        = trim($_POST['name']);
    $date        = $_POST['event_date'];
    $location    = trim($_POST['location']);
    $description = trim($_POST['description']);
    $oldImage    = $_POST['current_image'] ?? null;
    $deleteImage = isset($_POST['delete_image']);

    $upload_dir = '../uploads/events/';
    $newImage = $oldImage;

    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Handle new image upload
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['event_image']['tmp_name'];
        $original_name = basename($_FILES['event_image']['name']);
        $sanitized_name = preg_replace("/[^a-zA-Z0-9.\-_]/", "", $original_name);
        $unique_name = uniqid() . '_' . $sanitized_name;
        $target_path = $upload_dir . $unique_name;

        if (move_uploaded_file($tmp_name, $target_path)) {
            if ($oldImage && file_exists($upload_dir . $oldImage)) {
                unlink($upload_dir . $oldImage);
            }
            $newImage = $unique_name;
        }
    } elseif ($deleteImage && $oldImage) {
        if (file_exists($upload_dir . $oldImage)) {
            unlink($upload_dir . $oldImage);
        }
        $newImage = null;
    }

    // Update event in DB
    $stmt = $conn->prepare("UPDATE events SET name = ?, event_date = ?, location = ?, description = ?, image_path = ? WHERE id = ?");
    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("sssssi", $name, $date, $location, $description, $newImage, $id);
    $stmt->execute();

    header("Location: manage_events.php?updated=1");
    exit;
}
?>
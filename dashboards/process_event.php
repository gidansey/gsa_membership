<?php
session_start();
require_once '../includes/db_connect.php';

// Only Admins can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $date     = $_POST['event_date'];
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    $imagePath = null;

    $upload_dir = '../uploads/events/';

    // Ensure the upload directory exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Process the uploaded image if provided
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
        $tmp_name = $_FILES['event_image']['tmp_name'];
        $original_name = basename($_FILES['event_image']['name']);
        $sanitized_name = preg_replace("/[^a-zA-Z0-9.\-_]/", "", $original_name);
        $unique_name = uniqid() . '_' . $sanitized_name;
        $target_path = $upload_dir . $unique_name;

        if (move_uploaded_file($tmp_name, $target_path)) {
            $imagePath = $unique_name; // ✅ Save only the filename
        } else {
            die("Failed to upload image.");
        }
    }

    // Insert into database
    if ($name && $date && $location) {
        $stmt = $conn->prepare("INSERT INTO events (name, event_date, location, image_path) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            die("Database error: " . $conn->error);
        }
        $stmt->bind_param("ssss", $name, $date, $location, $imagePath);
        $stmt->execute();

        // Redirect with success message
        header("Location: manage_events.php?success=1");
        exit;
    } else {
        die("All fields are required.");
    }
}
?>

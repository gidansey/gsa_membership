<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/mailer.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);

    // Fetch event
    $stmt = $conn->prepare("SELECT name, event_date, location, description, image_path, attachment_path FROM events WHERE id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $event = $stmt->get_result()->fetch_assoc();

    if (!$event) {
        die("Event not found.");
    }

    $event_name = $event['name'];
    $event_date = date('M d, Y - h:i A', strtotime($event['event_date']));
    $location   = $event['location'];
    $description = nl2br($event['description']);
    $image_path = !empty($event['image_path']) ? "../uploads/events/" . $event['image_path'] : null;
    $attachment_path = !empty($event['attachment_path']) ? "../uploads/event_attachments/" . $event['attachment_path'] : null;
    $event_url = "https://yourdomain.com/dashboards/view_event.php?id=$event_id";

    $subject = "📢 Upcoming Event: $event_name";
    $body = "
        <p>Dear Member,</p>
        <p>You are invited to the following event:</p>
        <p><strong>Event:</strong> $event_name<br>
        <strong>Date:</strong> $event_date<br>
        <strong>Location:</strong> $location</p>
        [EVENT_IMAGE]
        <p><strong>Description:</strong><br>$description</p>
        <p><a href='$event_url'>View Full Event Details</a></p>
        <p>Thank you,<br>GSA Secretariat</p>
    ";

    $now = date('Y-m-d H:i:s');
    $admin_id = $_SESSION['user_id'];

    // Fetch all users across roles
    $users = $conn->query("SELECT id, email, first_name FROM users");

    if ($users && $users->num_rows > 0) {
        while ($user = $users->fetch_assoc()) {
            $user_id = $user['id'];
            $email   = $user['email'];

            // Prevent duplicate notification
            $check = $conn->prepare("SELECT id FROM event_notifications_sent WHERE event_id = ? AND user_id = ?");
            $check->bind_param("ii", $event_id, $user_id);
            $check->execute();
            $check_result = $check->get_result();

            if ($check_result->num_rows > 0) {
                continue; // Already sent
            }

            // Prepare attachments array
            $attachments = [];
            if (!empty($_POST['include_attachment']) && $attachment_path && file_exists($attachment_path)) {
                $attachments[] = $attachment_path;
            }

            // Send email (with BCC to Admin)
            send_email($email, $subject, $body, $image_path, $attachments, ['bcc' => $_SESSION['email']]);

            // Internal notification
            $message = "📢 You are invited to <strong>$event_name</strong> on $event_date at $location.";
            $link = "view_event.php?id=" . $event_id;

            $notif_stmt = $conn->prepare("
                INSERT INTO notifications (user_id, message, link, created_at, is_read)
                VALUES (?, ?, ?, ?, 0)
            ");
            $notif_stmt->bind_param("isss", $user_id, $message, $link, $now);
            $notif_stmt->execute();

            // Mark as sent
            $mark = $conn->prepare("INSERT INTO event_notifications_sent (event_id, user_id, sent_at) VALUES (?, ?, ?)");
            $mark->bind_param("iis", $event_id, $user_id, $now);
            $mark->execute();
        }
    }

    // Log action in audit_logs
    $action = "Sent event notification";
    $detail = "Sent event '$event_name' to all users.";
    $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, detail, created_at) VALUES (?, ?, ?, ?)");
    $log->bind_param("isss", $admin_id, $action, $detail, $now);
    $log->execute();

    header("Location: manage_events.php?notified=1");
    exit;
}

header("Location: manage_events.php");
exit;

<?php
session_start();
require_once '../includes/db_connect.php';

$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Branch Leader') {
    header("Location: ../index.php");
    exit;
}

$branch_id = $_SESSION['branch_id'] ?? null;
if (!$branch_id) {
    die("Branch not assigned. Contact Admin.");
}

// Get branch name
$branch_name = '';
$branch_stmt = $conn->prepare("SELECT branch_name FROM branches WHERE id = ?");
$branch_stmt->bind_param("i", $branch_id);
$branch_stmt->execute();
$branch_stmt->bind_result($branch_name);
$branch_stmt->fetch();
$branch_stmt->close();

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_name = trim($_POST['event_name']);
    $event_date = $_POST['event_date'];

    if (!$event_name || !$event_date) {
        $error = "All fields are required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO events (name, event_date, location) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $event_name, $event_date, $branch_name);
        if ($stmt->execute()) {
            $success = "Event created successfully!";
        } else {
            $error = "Error creating event.";
        }
        $stmt->close();
    }
}

$isDashboard = true;
include '../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="branch_dashboard.php">Dashboard</a>
            <a href="my_members.php">My Members</a>
            <a href="branch_events.php" class="active">Branch Events</a>
            <a href="branch_announcement.php">Announcements</a>
            <a href="branch_report.php">Reports</a>
        </nav>
        <form action="../logout.php" method="post" style="margin-top:auto; display:flex; justify-content:center;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <h1>Create Branch Event</h1>
            <p>Location will be auto-set to your branch: <strong><?= htmlspecialchars($branch_name) ?></strong></p>
        </header>

        <div class="form-container">
            <?php if ($error): ?>
                <div class="alert error"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="alert success"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST" class="event-form">
                <div class="form-group">
                    <label for="event_name">Event Name</label>
                    <input type="text" id="event_name" name="event_name" placeholder="Enter event name" required>
                </div>

                <div class="form-group">
                    <label for="event_date">Event Date</label>
                    <input type="date" id="event_date" name="event_date" required>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" id="location" value="<?= htmlspecialchars($branch_name) ?>" disabled>
                    <small class="help-text">Automatically set to your branch</small>
                </div>

                <button type="submit" class="submit-btn">Create Event</button>
            </form>
        </div>
    </main>
</div>

<style>
    /* Form Container */
    .form-container {
        max-width: 500px;
        margin: 20px auto;
        padding: 25px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
    }

    /* Alert Messages */
    .alert {
        padding: 12px 15px;
        margin-bottom: 20px;
        border-radius: 4px;
        font-weight: 500;
    }

    .error {
        background-color: #ffebee;
        color: #c62828;
        border-left: 4px solid #c62828;
    }

    .success {
        background-color: #e8f5e9;
        color: #2e7d32;
        border-left: 4px solid #2e7d32;
    }

    /* Form Group Styling */
    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 500;
        color: #2f3640;
        font-size: 14px;
    }

    /* Input Fields */
    .form-group input {
        width: 100%;
        padding: 12px 15px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-size: 14px;
        transition: all 0.3s ease;
    }

    .form-group input:not([disabled]):focus {
        border-color: #3498db;
        outline: none;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    .form-group input[disabled] {
        background-color: #f5f5f5;
        color: #666;
        cursor: not-allowed;
    }

    /* Date Input Customization */
    input[type="date"] {
        position: relative;
    }

    input[type="date"]::-webkit-calendar-picker-indicator {
        background: transparent;
        bottom: 0;
        color: transparent;
        cursor: pointer;
        height: auto;
        left: 0;
        position: absolute;
        right: 0;
        top: 0;
        width: auto;
    }

    /* Help Text */
    .help-text {
        display: block;
        margin-top: 6px;
        font-size: 12px;
        color: #666;
    }

    /* Submit Button */
    .submit-btn {
        width: 100%;
        padding: 14px;
        background-color: #2f3640;
        color: white;
        border: none;
        border-radius: 6px;
        font-size: 15px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.3s;
        margin-top: 10px;
    }

    .submit-btn:hover {
        background-color: #1e272e;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
        .form-container {
            padding: 20px;
            margin: 20px 15px;
        }
    }
</style>

<?php include '../includes/footer.php'; ?>
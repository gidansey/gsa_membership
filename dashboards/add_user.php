<?php
    $timeout_duration = 1800; // 30 minutes

    if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
        header("Location: ../includes/timeout.php");
        exit;
    }
    $_SESSION['LAST_ACTIVITY'] = time();

    session_start();
    require_once '../includes/db_connect.php';

    if ($_SESSION['role'] !== 'Admin') {
        header("Location: ../index.php");
        exit;
    }

    $isDashboard = true;
    $success = $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $first_name = trim($_POST['first_name']);
        $last_name  = trim($_POST['last_name']);
        $username   = trim($_POST['username']);
        $email      = trim($_POST['email']);
        $phone      = trim($_POST['phone']);
        $role       = $_POST['role'];
        $password   = $_POST['password'];
        $confirm_pw = $_POST['confirm_password'];

        if (!$first_name || !$last_name || !$username || !$email || !$phone || !$password || !$confirm_pw || !$role) {
            $error = "All fields are required.";
        } elseif ($password !== $confirm_pw) {
            $error = "Passwords do not match.";
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $error = "Username or email already exists.";
            } else {
                $hashed_pw = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("
                    INSERT INTO users (username, password, first_name, last_name, email, phone, role, status)
                    VALUES (?, ?, ?, ?, ?, ?, 'Active')
                ");
                $stmt->bind_param("ssssss", $username, $hashed_pw, $first_name, $last_name, $phone, $email, $role);
                $stmt->execute();
                $success = "User account created successfully.";
            }
        }
    }

    include '../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_members.php">Manage Members</a>
            <a href="manage_payments.php">Manage Payments</a>
            <a href="manage_events.php">Events</a>
            <a href="manage_users.php" class="active">User Accounts</a>
            <a href="generate_reports.php">Generate Reports</a>
            <a href="view_logs.php">Audit Logs</a>
            <a href="settings.php">Settings</a>
            <a href="send_notifications.php">Send Notifications</a>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>Add New User</h1>
            <p>Fill in the form to create a new user account</p>
        </header>

        <div class="table-card" style="max-width: 600px; margin: auto;">
            <?php if ($error): ?>
                <div style="color: red; margin-bottom: 15px;"><?= $error ?></div>
            <?php elseif ($success): ?>
                <div style="color: green; margin-bottom: 15px;"><?= $success ?></div>
            <?php endif; ?>

            <form method="POST">
                <label for="first_name">First Name</label>
                <input type="text" name="first_name" required style="padding: 10px; margin-bottom: 15px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <label for="last_name">Last Name</label>
                <input type="text" name="last_name" required style="padding: 10px; margin-bottom: 15px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <label for="username">Username</label>
                <input type="text" name="username" required style="padding: 10px; margin-bottom: 15px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <label for="email">Email</label>
                <input type="email" name="email" required style="padding: 10px; margin-bottom: 15px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <label for="phone">Phone</label>
                <input type="phone" name="phonr" required style="padding: 10px; margin-bottom: 15px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <label for="role">Role</label>
                <select name="role" required style="padding: 10px; margin-bottom: 15px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">
                    <option value="">-- Select Role --</option>
                    <option value="Admin">Admin</option>
                    <option value="Secretariat">Secretariat</option>
                    <option value="Branch Leader">Branch Leader</option>
                    <option value="Member">Member</option>
                </select>

                <label for="password">Password</label>
                <input type="password" name="password" required style="padding: 10px; margin-bottom: 15px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <label for="confirm_password">Confirm Password</label>
                <input type="password" name="confirm_password" required style="padding: 10px; margin-bottom: 20px; width: 100%; border-radius: 6px; border: 1px solid #ccc;">

                <button type="submit" style="
                    width: 100%;
                    padding: 12px;
                    background-color: #3498db;
                    color: #fff;
                    border: none;
                    border-radius: 8px;
                    font-size: 16px;
                    cursor: pointer;
                ">Create User</button>
            </form>
        </div>
    </main>
</div>

<?php include '../includes/footer.php'; ?>

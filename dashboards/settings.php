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

    // Fetch current system info (if any)
    $info = [
        'org_name' => 'Ghana Science Association',
        'slogan' => 'Empowering Science for National Development',
        'contact_email' => 'info@gsa.org.gh',
    ];

    // Fetch membership types
    $membership_types = [];
    $res = $conn->query("SELECT * FROM membership_types ORDER BY id");
    while ($row = $res->fetch_assoc()) {
        $membership_types[] = $row;
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
            <a href="manage_users.php">User Accounts</a>
            <a href="generate_reports.php">Generate Reports</a>
            <a href="view_logs.php">Audit Logs</a>
            <a href="settings.php" class="active">Settings</a>
            <a href="send_notifications.php">Send Notifications</a>
        </nav>
        <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
            <button type="submit" class="logout">Logout</button>
        </form>
    </aside>

    <main class="main">
        <header>
            <div class="hamburger" onclick="document.querySelector('.sidebar').classList.toggle('active')">☰</div>
            <h1>System Settings</h1>
        </header>

        <div class="table-card" style="max-width: 700px; margin: auto;">
            <h3>System Information</h3>
            <form method="POST" action="save_settings.php">
                <label>Organization Name</label>
                <input type="text" name="org_name" value="<?= htmlspecialchars($info['org_name']) ?>" required style="padding:10px;width:100%;margin-bottom:15px;border-radius:6px;border:1px solid #ccc">

                <label>Slogan</label>
                <input type="text" name="slogan" value="<?= htmlspecialchars($info['slogan']) ?>" required style="padding:10px;width:100%;margin-bottom:15px;border-radius:6px;border:1px solid #ccc">

                <label>Contact Email</label>
                <input type="email" name="contact_email" value="<?= htmlspecialchars($info['contact_email']) ?>" required style="padding:10px;width:100%;margin-bottom:20px;border-radius:6px;border:1px solid #ccc">

                <button type="submit" style="width:100%;padding:12px;background:#3498db;color:#fff;border:none;border-radius:8px;">Save Settings</button>
            </form>
        </div>
        <div class="table-card" style="max-width: 700px; margin: 40px auto;">
            <h3>Membership Dues Configuration</h3>
            <form method="POST" action="update_dues.php">
                <?php foreach ($membership_types as $type): ?>
                    <label><?= htmlspecialchars($type['type_name']) ?></label>
                    <input type="number" step="0.01" name="dues[<?= $type['id'] ?>]"
                           value="<?= htmlspecialchars($type['annual_dues']) ?>"
                           style="padding:10px;width:100%;margin-bottom:15px;border-radius:6px;border:1px solid #ccc">
                <?php endforeach; ?>
                <button type="submit" style="width:100%;padding:12px;background:#27ae60;color:#fff;border:none;border-radius:8px;">
                    Update Dues
                </button>
            </form>
        </div>
    </main>

</div>

<?php include '../includes/footer.php'; ?>

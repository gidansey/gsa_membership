<?php
session_start();
require_once '../includes/db_connect.php';

// Timeout
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Admin or Secretariat only
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Secretariat'])) {
    header("Location: ../index.php");
    exit();
}

// Initialize variables
$members = [];
$types = [];
$error = '';
$member_data = null;
$search_value = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch membership types
$types_result = $conn->query("SELECT id, type_name, annual_dues FROM membership_types ORDER BY type_name");
if ($types_result) {
    $types = $types_result->fetch_all(MYSQLI_ASSOC);
} else {
    $error = "Error fetching membership types: " . $conn->error;
}

// Fetch members based on search or all members
if (!empty($search_value)) {
    $search_param = "%$search_value%";
    $stmt = $conn->prepare("SELECT m.id, m.member_id, m.first_name, m.last_name 
                          FROM members m
                          WHERE CONCAT(m.first_name, ' ', m.last_name) LIKE ? OR m.member_id LIKE ?
                          ORDER BY m.first_name, m.last_name");
    if ($stmt) {
        $stmt->bind_param("ss", $search_param, $search_param);
        $stmt->execute();
        $result = $stmt->get_result();
        $members = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $error = "Error preparing search: " . $conn->error;
    }
} else {
    $members_result = $conn->query("SELECT id, member_id, first_name, last_name FROM members ORDER BY first_name, last_name");
    if ($members_result) {
        $members = $members_result->fetch_all(MYSQLI_ASSOC);
    } else {
        $error = "Error fetching members: " . $conn->error;
    }
}

// Get membership type and amount for each member from member_category
foreach ($members as &$member) {
    $type_query = $conn->prepare("SELECT mc.membership_type_id, mt.type_name, mt.annual_dues 
                                FROM member_category mc
                                JOIN membership_types mt ON mc.membership_type_id = mt.id
                                WHERE mc.member_id = ?
                                ORDER BY mc.year_joined DESC
                                LIMIT 1");
    $type_query->bind_param("i", $member['id']);
    $type_query->execute();
    $type_result = $type_query->get_result()->fetch_assoc();
    $member['membership_type_id'] = $type_result['membership_type_id'] ?? null;
    $member['type_name'] = $type_result['type_name'] ?? 'Not specified';
    $member['annual_dues'] = $type_result['annual_dues'] ?? 0;
    $type_query->close();
}
unset($member);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $member_id = intval($_POST['member_id']);
    $membership_type_id = intval($_POST['membership_type_id']);
    $amount_paid = floatval($_POST['amount_paid']);
    $payment_date = $_POST['payment_date'];
    $payment_mode = trim($_POST['payment_mode']);
    $reference_no = ($payment_mode !== 'Cash' && isset($_POST['reference_no'])) ? trim($_POST['reference_no']) : null;
    $admin_id = $_SESSION['user_id'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    // Calculate dates
    $exp_stmt = $conn->prepare("SELECT expiry_date FROM payments WHERE member_id = ? ORDER BY expiry_date DESC LIMIT 1");
    $exp_stmt->bind_param("i", $member_id);
    $exp_stmt->execute();
    $exp_result = $exp_stmt->get_result()->fetch_assoc();
    $exp_stmt->close();

    $base_date = $exp_result ? new DateTime($exp_result['expiry_date']) : new DateTime($payment_date);
    $renewal_date = clone $base_date;
    $expiry_date = clone $base_date;

    $renewal_date->modify('+1 day');
    $expiry_date->modify('+1 year');

    $next = $renewal_date->format('Y-m-d');
    $exp = $expiry_date->format('Y-m-d');

    // Insert payment
    $insert = $conn->prepare("INSERT INTO payments (member_id, membership_type_id, amount_paid, payment_date, payment_mode, reference_no, next_renewal_date, expiry_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param("iidsssss", $member_id, $membership_type_id, $amount_paid, $payment_date, $payment_mode, $reference_no, $next, $exp);

    if ($insert->execute()) {
        $payment_id = $insert->insert_id;

        // Update member record
        $update_member = $conn->prepare("UPDATE members SET next_renewal_date = ?, membership_expiry_date = ? WHERE id = ?");
        $update_member->bind_param("ssi", $next, $exp, $member_id);
        $update_member->execute();
        $update_member->close();

        // Log to audit table
        $member_name = "";
        foreach ($members as $m) {
            if ($m['id'] == $member_id) {
                $member_name = "{$m['member_id']} - {$m['first_name']} {$m['last_name']}";
                break;
            }
        }
        
        $action = "Recorded new payment for $member_name (Payment ID: $payment_id)";
        $log = $conn->prepare("INSERT INTO audit_logs (user_id, action, table_name, affected_id, ip_address, user_agent) VALUES (?, ?, 'payments', ?, ?, ?)");
        $log->bind_param("isiss", $admin_id, $action, $payment_id, $ip_address, $user_agent);
        $log->execute();
        $log->close();

        header("Location: manage_payments.php?message=" . urlencode("Payment recorded successfully."));
        exit;
    } else {
        $error = "Failed to record payment: " . $insert->error;
    }
    $insert->close();
}

$isDashboard = true;
include '../includes/header.php';
?>

<div class="dashboard-container">
    <aside class="sidebar">
        <div class="logo-container">
            <div class="logo"></div>
            <h2>GSA Membership</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="manage_members.php"><i class="fas fa-users"></i> Manage Members</a>
            <a href="manage_payments.php" class="active"><i class="fas fa-money-bill-wave"></i> Manage Payments</a>
            <a href="manage_events.php"><i class="fas fa-calendar-alt"></i> Events</a>
            <a href="manage_users.php"><i class="fas fa-user-cog"></i> User Accounts</a>
            <a href="generate_reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a href="view_logs.php"><i class="fas fa-clipboard-list"></i> Audit Logs</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a href="send_notifications.php"><i class="fas fa-bell"></i> Notifications</a>
        </nav>
        <form action="../logout.php" method="post" class="logout-form">
            <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </form>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <h1><i class="fas fa-money-check-alt"></i> Record New Payment</h1>
        </header>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="card payment-card">
            <div class="card-header">
                <h2>Payment Information</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="record_payment.php" class="search-form mb-4">
                    <div class="form-group">
                        <label class="form-label">Search Member</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Enter member name or ID" value="<?= htmlspecialchars($search_value) ?>">
                            <button type="submit" class="btn btn-search">
                                <i class="fas fa-search"></i> Search
                            </button>
                            <a href="record_payment.php" class="btn btn-clear">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </form>

                <form method="POST" id="paymentForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Member</label>
                                <select name="member_id" id="memberSelect" class="form-select" required onchange="updateMemberDetails(this.value)">
                                    <option value="">-- Select Member --</option>
                                    <?php foreach ($members as $m): ?>
                                        <option value="<?= $m['id'] ?>" 
                                            data-type-id="<?= $m['membership_type_id'] ?? '' ?>"
                                            data-type-name="<?= htmlspecialchars($m['type_name']) ?>"
                                            data-amount="<?= $m['annual_dues'] ?? 0 ?>"
                                            <?= isset($_GET['member_id']) && $_GET['member_id'] == $m['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars("{$m['member_id']} - {$m['first_name']} {$m['last_name']}") ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Membership Type</label>
                                <div class="input-group">
                                    <input type="text" id="membershipTypeDisplay" class="form-control" readonly>
                                    <input type="hidden" name="membership_type_id" id="membershipTypeId">
                                    <span class="input-group-text">
                                        <i class="fas fa-id-card"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Amount (GH¢)</label>
                                <div class="input-group">
                                    <span class="input-group-text">GH¢</span>
                                    <input type="number" name="amount_paid" id="amountPaid" class="form-control" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Payment Date</label>
                                <div class="input-group">
                                    <input type="date" name="payment_date" class="form-control" required value="<?= date('Y-m-d') ?>">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-day"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Payment Mode</label>
                                <select name="payment_mode" id="paymentMode" class="form-select" required onchange="toggleReferenceField()">
                                    <option value="">-- Select Mode --</option>
                                    <option value="Mobile Money">Mobile Money</option>
                                    <option value="Bank Deposit">Bank Deposit</option>
                                    <option value="Cash">Cash</option>
                                    <option value="Online">Online</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group" id="referenceField" style="display: none;">
                                <label class="form-label">Reference Number</label>
                                <div class="input-group">
                                    <input type="text" name="reference_no" id="referenceNo" class="form-control" placeholder="Enter reference number">
                                    <span class="input-group-text">
                                        <i class="fas fa-hashtag"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Record Payment
                        </button>
                        <a href="manage_payments.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
</div>

<script>
function updateMemberDetails(memberId) {
    if (!memberId) return;
    
    const memberSelect = document.getElementById('memberSelect');
    const selectedOption = memberSelect.options[memberSelect.selectedIndex];
    
    if (selectedOption) {
        document.getElementById('membershipTypeDisplay').value = selectedOption.getAttribute('data-type-name');
        document.getElementById('membershipTypeId').value = selectedOption.getAttribute('data-type-id');
        document.getElementById('amountPaid').value = selectedOption.getAttribute('data-amount');
    }
}

function toggleReferenceField() {
    const paymentMode = document.getElementById('paymentMode').value;
    const referenceField = document.getElementById('referenceField');
    
    if (paymentMode === 'Cash') {
        referenceField.style.display = 'none';
        document.getElementById('referenceNo').required = false;
    } else {
        referenceField.style.display = 'block';
        document.getElementById('referenceNo').required = true;
    }
}

// Initialize form if coming with member_id parameter
<?php if (isset($_GET['member_id'])): ?>
document.addEventListener('DOMContentLoaded', function() {
    const memberId = <?= intval($_GET['member_id'] ?? 0) ?>;
    if (memberId) {
        const memberSelect = document.getElementById('memberSelect');
        memberSelect.value = memberId;
        updateMemberDetails(memberId);
    }
});
<?php endif; ?>

document.querySelector('.hamburger')?.addEventListener('click', () => {
        document.querySelector('.sidebar').classList.toggle('active');
    });
</script>

<style>
.alert.error {
    background: #fdecea;
    padding: 10px;
    border: 1px solid #f5c2c7;
    color: #a94442;
    margin-bottom: 1rem;
}
.search-form .search-box {
    display: flex;
    align-items: center;
    gap: 10px;
}
.form-actions {
    display: flex;
    gap: 10px;
    align-items: center;
}
.btn-save, .btn-cancel {
    padding: 8px 16px;
    border-radius: 4px;
    text-decoration: none;
}
.btn-save {
    background: #4CAF50;
    color: white;
    border: none;
}
.btn-cancel {
    background: #f0f0f0;
    color: #333;
    border: 1px solid #ccc;
}

/* Base Styles */
:root {
    --primary-color: #3498db;
    --secondary-color: #2c3e50;
    --success-color: #2ecc71;
    --danger-color: #e74c3c;
    --light-color: #ecf0f1;
    --dark-color: #34495e;
}

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f5f7fa;
    color: #333;
}

.dashboard-container {
    display: flex;
    min-height: 100vh;
}

/* Sidebar Styles */
.sidebar {
    width: 250px;
    background: var(--secondary-color);
    color: white;
    padding: 20px 0;
    display: flex;
    flex-direction: column;
    position: fixed;
    height: 100vh;
    z-index: 1000;
}

.logo-container {
    padding: 0 20px 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.logo {
    width: 80px;
    height: 80px;
    margin: 0 auto 10px;
    background-color: var(--primary-color);
    border-radius: 50%;
}

.sidebar-nav {
    flex: 1;
    padding: 20px 0;
}

.sidebar-nav a {
    display: block;
    padding: 12px 20px;
    color: var(--light-color);
    text-decoration: none;
    transition: all 0.3s;
    border-left: 3px solid transparent;
}

.sidebar-nav a:hover {
    background: rgba(255,255,255,0.1);
    border-left-color: var(--primary-color);
}

.sidebar-nav a.active {
    background: rgba(255,255,255,0.1);
    border-left-color: var(--primary-color);
    font-weight: 600;
}

.sidebar-nav a i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}

.logout-form {
    padding: 20px;
}

.logout-btn {
    width: 100%;
    padding: 10px;
    background: var(--danger-color);
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    transition: background 0.3s;
}

.logout-btn:hover {
    background: #c0392b;
}

/* Main Content Styles */
.main-content {
    flex: 1;
    padding: 30px;
    position: relative;
    margin-left: 250px;
    width: calc(100% - 250px);
    min-height: 100vh;
}

.page-header {
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 1px solid #ddd;
}

.page-header h1 {
    font-size: 24px;
    font-weight: 600;
    color: var(--dark-color);
}

.page-header h1 i {
    color: var(--primary-color);
    margin-right: 10px;
}

/* Card Styles */
.card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 30px;
}

.card-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
}

.card-header h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: var(--dark-color);
}

.card-body {
    padding: 20px;
}

/* Form Styles */
.form-label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: var(--dark-color);
}

.form-control, .form-select {
    width: 100%;
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    transition: border 0.3s;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    outline: none;
    box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
}

.input-group {
    display: flex;
}

.input-group .form-control {
    flex: 1;
}

.input-group-text {
    padding: 10px 15px;
    background: #f8f9fa;
    border: 1px solid #ddd;
    border-left: none;
    color: #555;
}

.input-group .form-control:first-child {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.input-group-text {
    border-top-left-radius: 0;
    border-bottom-left-radius: 0;
}

/* Button Styles */
.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn i {
    margin-right: 8px;
}

.btn-primary {
    background: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: #2980b9;
}

.btn-secondary {
    background: #95a5a6;
    color: white;
}

.btn-secondary:hover {
    background: #7f8c8d;
}

.btn-search {
    background: var(--success-color);
    color: white;
}

.btn-search:hover {
    background: #27ae60;
}

.btn-clear {
    background: #e74c3c;
    color: white;
}

.btn-clear:hover {
    background: #c0392b;
}

/* Alert Styles */
.alert {
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.alert-danger {
    background: #fdecea;
    border: 1px solid #f5c2c7;
    color: #a94442;
}

.alert-danger i {
    margin-right: 10px;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 15px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

/* Responsive Grid */
.row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -15px;
}

.col-md-6 {
    flex: 0 0 50%;
    max-width: 50%;
    padding: 0 15px;
}

@media (max-width: 992px) {
    .sidebar {
        width: 220px;
    }
    .main-content {
        margin-left: 220px;
        width: calc(100% - 220px);
    }
}

@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }
    .main-content {
        margin-left: 0;
        width: 100%;
    }
}

/* Animation */
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.card {
    animation: fadeIn 0.3s ease-out;
}
</style>

<!-- Font Awesome for icons -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<?php include '../includes/footer.php'; ?>
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

// Admin-only access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: ../index.php");
    exit;
}

$member_id = intval($_GET['id'] ?? 0);
if (!$member_id) die("Invalid member ID");

// Fetch dropdowns
$branches = $conn->query("SELECT id, branch_name FROM branches ORDER BY branch_name")->fetch_all(MYSQLI_ASSOC);
$types = $conn->query("SELECT id, type_name FROM membership_types ORDER BY type_name")->fetch_all(MYSQLI_ASSOC);

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $first_name = trim($_POST['first_name']);
    $other_names = trim($_POST['other_names']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $gender = $_POST['gender'];
    $dob = $_POST['dob'];
    $status = $_POST['status'];
    $notes = trim($_POST['notes']);
    $branch_id = intval($_POST['branch_id']);
    $institution = trim($_POST['institution']);
    $membership_type_id = intval($_POST['membership_type_id']);
    $admin_id = $_SESSION['user_id'];

    // Update members
    $stmt = $conn->prepare("
        UPDATE members 
        SET title=?, first_name=?, other_names=?, last_name=?, email=?, phone=?, gender=?, dob=?, status=?, notes=? 
        WHERE id=?
    ");
    $stmt->bind_param("ssssssssssi", $title, $first_name, $other_names, $last_name, $email, $phone, $gender, $dob, $status, $notes, $member_id);
    $stmt->execute();
    $stmt->close();

    // Update affiliation
    $aff_stmt = $conn->prepare("UPDATE affiliations SET branch_id=?, institution_name=? WHERE member_id=?");
    $aff_stmt->bind_param("isi", $branch_id, $institution, $member_id);
    $aff_stmt->execute();
    $aff_stmt->close();

    // Update membership type
    $cat_stmt = $conn->prepare("UPDATE member_category SET membership_type_id=? WHERE member_id=?");
    $cat_stmt->bind_param("ii", $membership_type_id, $member_id);
    $cat_stmt->execute();
    $cat_stmt->close();

    // Update academic background (only one)
    $qualification = trim($_POST['academic']['qualification']);
    $discipline = trim($_POST['academic']['discipline']);
    $school = trim($_POST['academic']['institution']);
    $grad_year = trim($_POST['academic']['graduation_year']);

    $conn->query("DELETE FROM academic_background WHERE member_id = $member_id");
    if ($qualification && $discipline && $school && $grad_year) {
        $acad_stmt = $conn->prepare("INSERT INTO academic_background (member_id, highest_qualification, discipline, institution_attended, graduation_year) VALUES (?, ?, ?, ?, ?)");
        $acad_stmt->bind_param("issss", $member_id, $qualification, $discipline, $school, $grad_year);
        $acad_stmt->execute();
        $acad_stmt->close();
    }

    // Update employment (only one)
    $org = trim($_POST['employment']['organization']);
    $pos = trim($_POST['employment']['position']);
    $start = $_POST['employment']['start_date'];
    $end = $_POST['employment']['end_date'];

    $conn->query("DELETE FROM employment WHERE member_id = $member_id");
    if ($org && $pos && $start) {
        $emp_stmt = $conn->prepare("INSERT INTO employment (member_id, organization, position, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
        $emp_stmt->bind_param("issss", $member_id, $org, $pos, $start, $end);
        $emp_stmt->execute();
        $emp_stmt->close();
    }

    // Audit log
    $log_stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, target_id, timestamp) VALUES (?, 'Updated member details', ?, NOW())");
    $log_stmt->bind_param("ii", $admin_id, $member_id);
    $log_stmt->execute();
    $log_stmt->close();

    header("Location: view_member_details.php?id=$member_id&updated=1");
    exit;
}

// Fetch member info
$sql = "
    SELECT m.*, a.branch_id, a.institution_name, mc.membership_type_id
    FROM members m
    LEFT JOIN affiliations a ON m.id = a.member_id
    LEFT JOIN member_category mc ON m.id = mc.member_id
    WHERE m.id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $member_id);
$stmt->execute();
$member = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$member) die("Member not found.");

// Fetch academic background
$academic = $conn->query("SELECT * FROM academic_background WHERE member_id = $member_id LIMIT 1")->fetch_assoc();

// Fetch employment history
$employment = $conn->query("SELECT * FROM employment WHERE member_id = $member_id LIMIT 1")->fetch_assoc();

$isDashboard = true;
include '../includes/header.php';
?>

<div class="dashboard">
    <aside class="sidebar">
        <div class="logo"></div>
        <nav>
            <a href="admin_dashboard.php">Dashboard</a>
            <a href="manage_members.php" class="active">Manage Members</a>
            <a href="manage_payments.php">Manage Payments</a>
            <a href="manage_events.php">Events</a>
            <a href="manage_users.php">User Accounts</a>
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
        <header><h1>Edit Member</h1></header>

        <div class="form-container">
            <form method="POST" onsubmit="return validateForm();">
                <!-- Basic Info -->
                <div class="form-group"><label>Title:</label><input type="text" name="title" value="<?= htmlspecialchars($member['title']) ?>"></div>
                <div class="form-group"><label>First Name:</label><input type="text" name="first_name" value="<?= htmlspecialchars($member['first_name']) ?>" required></div>
                <div class="form-group"><label>Other Names:</label><input type="text" name="other_names" value="<?= htmlspecialchars($member['other_names']) ?>"></div>
                <div class="form-group"><label>Last Name:</label><input type="text" name="last_name" value="<?= htmlspecialchars($member['last_name']) ?>" required></div>
                <div class="form-group"><label>Email:</label><input type="email" name="email" value="<?= htmlspecialchars($member['email']) ?>" required></div>
                <div class="form-group"><label>Phone:</label><input type="text" name="phone" value="<?= htmlspecialchars($member['phone']) ?>"></div>
                <div class="form-group">
                    <label>Gender:</label>
                    <select name="gender" required>
                        <option value="">-- Select --</option>
                        <option value="Male" <?= $member['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                        <option value="Female" <?= $member['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                        <option value="Other" <?= $member['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="form-group"><label>Date of Birth:</label><input type="date" name="dob" value="<?= htmlspecialchars($member['dob']) ?>"></div>
                <div class="form-group">
                    <label>Status:</label>
                    <select name="status" required>
                        <option value="Pending" <?= $member['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Approved" <?= $member['status'] === 'Approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="Rejected" <?= $member['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="Inactive" <?= $member['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="form-group"><label>Branch:</label>
                    <select name="branch_id" required>
                        <?php foreach ($branches as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= $b['id'] == $member['branch_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['branch_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Institution:</label><input type="text" name="institution" value="<?= htmlspecialchars($member['institution_name']) ?>" required></div>
                <div class="form-group">
                    <label>Membership Type:</label>
                    <select name="membership_type_id" required>
                        <?php foreach ($types as $type): ?>
                            <option value="<?= $type['id'] ?>" <?= $type['id'] == $member['membership_type_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type['type_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Notes:</label><textarea name="notes"><?= htmlspecialchars($member['notes']) ?></textarea></div>

                <hr>
                <h3>Academic Background</h3>
                <div class="form-group"><label>Qualification:</label><input type="text" name="academic[qualification]" value="<?= htmlspecialchars($academic['highest_qualification'] ?? '') ?>"></div>
                <div class="form-group"><label>Discipline:</label><input type="text" name="academic[discipline]" value="<?= htmlspecialchars($academic['discipline'] ?? '') ?>"></div>
                <div class="form-group"><label>Institution Attended:</label><input type="text" name="academic[institution]" value="<?= htmlspecialchars($academic['institution_attended'] ?? '') ?>"></div>
                <div class="form-group"><label>Graduation Year:</label><input type="text" name="academic[graduation_year]" value="<?= htmlspecialchars($academic['graduation_year'] ?? '') ?>"></div>

                <hr>
                <h3>Employment History</h3>
                <div class="form-group"><label>Organization:</label><input type="text" name="employment[organization]" value="<?= htmlspecialchars($employment['organization'] ?? '') ?>"></div>
                <div class="form-group"><label>Position:</label><input type="text" name="employment[position]" value="<?= htmlspecialchars($employment['position'] ?? '') ?>"></div>
                <div class="form-group"><label>Start Date:</label><input type="date" name="employment[start_date]" value="<?= htmlspecialchars($employment['start_date'] ?? '') ?>"></div>
                <div class="form-group"><label>End Date:</label><input type="date" name="employment[end_date]" value="<?= htmlspecialchars($employment['end_date'] ?? '') ?>"></div>

                <button type="submit">💾 Save Changes</button>
                <a href="view_member_details.php?id=<?= $member['id'] ?>" class="badge info" style="margin-left: 10px;">← Cancel</a>
            </form>
        </div>
    </main>
</div>

<script>
function validateForm() {
    const fields = ['first_name', 'last_name', 'email', 'gender', 'status', 'branch_id', 'institution', 'membership_type_id'];
    for (let field of fields) {
        if (!document.forms[0][field].value.trim()) {
            alert('Please fill out all required fields.');
            return false;
        }
    }
    return true;
}
</script>

<?php include '../includes/footer.php'; ?>

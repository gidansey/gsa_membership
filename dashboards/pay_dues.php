<?php
session_start();
require_once '../includes/db_connect.php';
date_default_timezone_set('Africa/Accra');

// 1) Session timeout
$timeout = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && time() - $_SESSION['LAST_ACTIVITY'] > $timeout) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// 2) Authorization: only Member or Branch Leader-as-Member
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Member','Branch Leader'])) {
    header("Location: ../index.php");
    exit;
}

// 3) Link to member record
$member_id = $_SESSION['member_id'] ?? null;
if (!$member_id) {
    die("Your account is not linked to a member record.");
}

// 4) Handle form submission
$error = $success = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type_id = (int)$_POST['membership_type_id'];
    $amount  = floatval($_POST['amount_paid']);
    if ($type_id <= 0 || $amount <= 0) {
        $error = "Please select a membership type and enter a valid amount.";
    } else {
        $stmt = $conn->prepare("
            INSERT INTO payments (member_id, membership_type_id, amount_paid, payment_date)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->bind_param("iid", $member_id, $type_id, $amount);
        if ($stmt->execute()) {
            $success = "Payment recorded successfully.";
        } else {
            $error = "Failed to record payment: " . $stmt->error;
        }
        $stmt->close();
    }
}

// 5) Fetch membership types for dropdown
$types = [];
$res = $conn->query("SELECT id, type_name, annual_dues FROM membership_types ORDER BY type_name");
while ($row = $res->fetch_assoc()) {
    $types[] = $row;
}

// 6) Fetch current dues status
$dues_status = 'No payments yet';
$stmt = $conn->prepare("
    SELECT p.amount_paid, mt.annual_dues
    FROM payments p
    JOIN membership_types mt ON p.membership_type_id = mt.id
    WHERE p.member_id = ?
    ORDER BY p.payment_date DESC
    LIMIT 1
");
$stmt->bind_param("i", $member_id);
$stmt->execute();
if ($r = $stmt->get_result()->fetch_assoc()) {
    $dues_status = $r['amount_paid'] >= $r['annual_dues']
                  ? 'Paid'
                  : ($r['amount_paid'] > 0 ? 'Partial' : 'Pending');
}
$stmt->close();

// 7) Fetch recent payments
$recent = [];
$stmt = $conn->prepare("
    SELECT amount_paid, payment_date
    FROM payments
    WHERE member_id = ?
    ORDER BY payment_date DESC
    LIMIT 5
");
$stmt->bind_param("i", $member_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $recent[] = $row;
}
$stmt->close();

// 8) Page header
$isDashboard = true;
include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pay Dues</title>
  <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
<div class="dashboard">
  <aside class="sidebar">
    <div class="logo"></div>
    <nav>
      <a href="member_dashboard.php">Dashboard</a>
      <a href="edit_profile.php">Edit Profile</a>
      <a href="pay_dues.php" class="active">Pay Dues</a>
      <a href="event_history.php">Event History & Feedback</a>
    </nav>
    <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
        <button type="submit" class="logout">Logout</button>
    </form>
  </aside>

  <main class="main">
    <header>
      <div class="hamburger" onclick="toggleSidebar()">☰</div>
      <h1>Pay Annual Dues</h1>
      <p>Your current status: <span class="badge <?= strtolower($dues_status) ?>">
        <?= htmlspecialchars($dues_status) ?>
      </span></p>
    </header>

    <div class="form-container">
      <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
      <?php elseif ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <form method="POST">
        <div class="form-group">
          <label for="membership_type_id">Membership Type</label>
          <select id="membership_type_id" name="membership_type_id" required>
            <option value="">-- Select Type --</option>
            <?php foreach ($types as $t): ?>
              <option value="<?= $t['id'] ?>">
                <?= htmlspecialchars($t['type_name']) ?> (<?= number_format($t['annual_dues'],2) ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="amount_paid">Amount Paid (GH¢)</label>
          <input type="number" step="0.01" id="amount_paid" name="amount_paid" required>
        </div>
        <button type="submit" class="submit-btn">Submit Payment</button>
      </form>
    </div>

    <?php if (!empty($recent)): ?>
    <div class="table-card">
      <h3>Recent Payments</h3>
      <table>
        <thead>
          <tr>
            <th>Amount (GH¢)</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($recent as $p): ?>
          <tr>
            <td><?= number_format($p['amount_paid'],2) ?></td>
            <td><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </main>
</div>

<script>
function toggleSidebar(){
  document.querySelector('.sidebar').classList.toggle('active');
}
</script>
</body>
</html>
<?php include '../includes/footer.php'; ?>

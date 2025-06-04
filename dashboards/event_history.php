<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
date_default_timezone_set('Africa/Accra');

// 1) Session timeout
$timeout = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && time() - $_SESSION['LAST_ACTIVITY'] > $timeout) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// 2) Authorization
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Member','Branch Leader'])) {
    header("Location: ../index.php");
    exit;
}

// 3) Member ID
$member_id = $_SESSION['member_id'] ?? null;
if (!$member_id) {
    die("Your account is not linked to a member record.");
}

// 4) Handle feedback submission
$error = $success = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id'], $_POST['feedback'])) {
    $event_id = (int)$_POST['event_id'];
    $comment  = trim($_POST['feedback']);
    if ($comment === '') {
        $error = "Feedback cannot be empty.";
    } else {
        // Check if an entry exists
        $chk = $conn->prepare("SELECT id FROM feedback WHERE member_id = ? AND event_id = ?");
        $chk->bind_param("ii", $member_id, $event_id);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows) {
            // update
            $up = $conn->prepare("UPDATE feedback SET comment = ?, submitted_at = NOW() WHERE member_id = ? AND event_id = ?");
            $up->bind_param("sii", $comment, $member_id, $event_id);
            if ($up->execute()) {
                $success = "Feedback updated.";
            } else {
                $error = "Failed to update feedback.";
            }
            $up->close();
        } else {
            // insert
            $in = $conn->prepare("INSERT INTO feedback (member_id, event_id, comment, submitted_at) VALUES (?, ?, ?, NOW())");
            $in->bind_param("iis", $member_id, $event_id, $comment);
            if ($in->execute()) {
                $success = "Feedback saved.";
            } else {
                $error = "Failed to save feedback.";
            }
            $in->close();
        }
        $chk->close();
    }
}

// 5) Fetch participations + any existing feedback
$sql = "
  SELECT 
    ep.event_id,
    e.name         AS event_name,
    ep.participation_date,
    COALESCE(f.comment, '') AS comment
  FROM event_participation ep
  JOIN events e ON ep.event_id = e.id
  LEFT JOIN feedback f 
    ON f.member_id = ep.member_id 
   AND f.event_id  = ep.event_id
  WHERE ep.member_id = ?
  ORDER BY ep.participation_date DESC
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $member_id);
$stmt->execute();
$participations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 6) Include header
$isDashboard = true;
include __DIR__ . '/../includes/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Event History & Feedback</title>
  <link rel="stylesheet" href="../assets/styles.css">
</head>
<body>
<div class="dashboard">
  <aside class="sidebar">
    <div class="logo"></div>
    <nav>
      <a href="member_dashboard.php">Dashboard</a>
      <a href="edit_profile.php">Edit Profile</a>
      <a href="pay_dues.php">Pay Dues</a>
      <a href="event_history.php" class="active">Event History & Feedback</a>
    </nav>
    <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
        <button type="submit" class="logout">Logout</button>
    </form>
  </aside>

  <main class="main">
    <header>
      <div class="hamburger" onclick="toggleSidebar()">☰</div>
      <h1>My Event History</h1>
    </header>

    <div class="table-card">
      <h3>Participated Events</h3>
      <?php if ($error): ?>
        <div class="message error"><?= htmlspecialchars($error) ?></div>
      <?php elseif ($success): ?>
        <div class="message success"><?= htmlspecialchars($success) ?></div>
      <?php endif; ?>

      <table>
        <thead>
          <tr>
            <th>Event</th>
            <th>Date</th>
            <th>Your Feedback</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($participations)): ?>
          <tr><td colspan="4">You have not participated in any events yet.</td></tr>
        <?php else: ?>
          <?php foreach ($participations as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['event_name']) ?></td>
            <td><?= date('M d, Y', strtotime($p['participation_date'])) ?></td>
            <td><?= nl2br(htmlspecialchars($p['comment'] ?: '—')) ?></td>
            <td>
              <button class="btn btn-primary" onclick="showForm(<?= $p['event_id'] ?>)">
                <?= $p['comment'] ? 'Edit' : 'Add' ?> Feedback
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Feedback forms, one per event -->
    <?php foreach ($participations as $p): ?>
    <div id="form-<?= $p['event_id'] ?>" class="form-container" style="display:none;">
      <h3>Feedback for “<?= htmlspecialchars($p['event_name']) ?>”</h3>
      <form method="POST">
        <input type="hidden" name="event_id" value="<?= $p['event_id'] ?>">
        <div class="form-group">
          <textarea name="feedback" rows="4" required><?= htmlspecialchars($p['comment']) ?></textarea>
        </div>
        <button type="submit" class="submit-btn">Save Feedback</button>
        <button type="button" class="btn btn-danger" onclick="hideForm(<?= $p['event_id'] ?>)">
          Cancel
        </button>
      </form>
    </div>
    <?php endforeach; ?>

  </main>
</div>

<script>
function toggleSidebar(){
  document.querySelector('.sidebar').classList.toggle('active');
}
function showForm(id){
  document.querySelectorAll('.form-container').forEach(el=>el.style.display='none');
  document.getElementById('form-'+id).style.display = 'block';
  window.scrollTo(0, document.getElementById('form-'+id).offsetTop - 20);
}
function hideForm(id){
  document.getElementById('form-'+id).style.display = 'none';
}
</script>
</body>
</html>
<?php include __DIR__ . '/../includes/footer.php'; ?>

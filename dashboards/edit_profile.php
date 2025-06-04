<?php
session_start();
require_once '../includes/db_connect.php';
date_default_timezone_set('Africa/Accra');

// Session timeout
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    header("Location: ../includes/timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();

// Only Member or Branch Leader (as member) may edit
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Member','Branch Leader'])) {
    header("Location: ../index.php");
    exit;
}

// Link to members table
$member_id = $_SESSION['member_id'] ?? null;
if (!$member_id) {
    die("No member record linked to your account.");
}

$error = $success = "";

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1) Basic profile fields
    $fn = trim($_POST['first_name']);
    $ln = trim($_POST['last_name']);
    $on = trim($_POST['other_names']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $dob = trim($_POST['dob']);
    $gender = trim($_POST['gender']);
    $nid = trim($_POST['national_id']);
    $region = trim($_POST['region']);
    $res_addr = trim($_POST['residential_address']);
    $post_addr = trim($_POST['postal_address']);
    $notes = trim($_POST['notes']);
    // 2) Affiliations
    $branch_id = (int)$_POST['branch_id'];
    $institution_name = trim($_POST['institution_name']);
    // 3) Academic up to 3
    $academics = $_POST['academic'] ?? [];
    // 4) Employment up to 3
    $employments = $_POST['employment'] ?? [];

    // basic validation
    if ($fn === '' || $ln === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "First name, last name and valid email are required.";
    }

    if (!$error) {
        $conn->begin_transaction();
        try {
            // Update users table
            $u = $conn->prepare("UPDATE users
                SET first_name=?, last_name=?, email=?, phone=?
                WHERE id=?");
            $u->bind_param("ssssi", $fn, $ln, $email, $phone, $_SESSION['user_id']);
            $u->execute(); $u->close();

            // Update members table
            $m = $conn->prepare("UPDATE members
                SET first_name=?, last_name=?, other_names=?, dob=?, gender=?, national_id=?, region=?, residential_address=?, postal_address=?, notes=?
                WHERE id=?");
            $m->bind_param("ssssssssssi",
                $fn, $ln, $on, $dob, $gender, $nid, $region, $res_addr, $post_addr, $notes, $member_id
            );
            $m->execute(); $m->close();

            // Update affiliation
            $a = $conn->prepare("UPDATE affiliations
                SET branch_id=?, institution_name=?
                WHERE member_id=?");
            $a->bind_param("isi", $branch_id, $institution_name, $member_id);
            // if none existed, insert instead
            if (!$a->execute()) {
                $ins = $conn->prepare("INSERT INTO affiliations (member_id, branch_id, institution_name) VALUES (?,?,?)");
                $ins->bind_param("iis", $member_id, $branch_id, $institution_name);
                $ins->execute();
                $ins->close();
            }
            $a->close();

            // Rebuild academic_background: delete all, then insert non-empty
            $conn->query("DELETE FROM academic_background WHERE member_id=$member_id");
            $ai = $conn->prepare("INSERT INTO academic_background (member_id, highest_qualification, discipline, institution_attended, graduation_year)
                VALUES (?,?,?,?,?)");
            foreach ($academics as $ac) {
                if (trim($ac['highest_qualification'])==='') continue;
                $ai->bind_param("isssi",
                    $member_id,
                    $ac['highest_qualification'],
                    $ac['discipline'],
                    $ac['institution_attended'],
                    $ac['graduation_year']
                );
                $ai->execute();
            }
            $ai->close();

            // Rebuild employment: same pattern
            $conn->query("DELETE FROM employment WHERE member_id=$member_id");
            $ei = $conn->prepare("INSERT INTO employment (member_id, employment_status, current_position, organization, sector, location)
                VALUES (?,?,?,?,?,?)");
            foreach ($employments as $em) {
                if (trim($em['organization'])==='') continue;
                $ei->bind_param("isssss",
                    $member_id,
                    $em['employment_status'],
                    $em['current_position'],
                    $em['organization'],
                    $em['sector'],
                    $em['location']
                );
                $ei->execute();
            }
            $ei->close();

            $conn->commit();
            $success = "Profile saved successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Save failed: " . $e->getMessage();
        }
    }
}

// Fetch for display
// 1) core user + member already loaded above
$stmt = $conn->prepare("
  SELECT u.first_name,u.last_name,u.email,u.phone,
         m.other_names,m.dob,m.gender,m.national_id,m.region,
         m.residential_address,m.postal_address,m.notes
    FROM users u
    JOIN members m ON m.id=?
   WHERE u.id=?
");
$stmt->bind_param("ii", $member_id, $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($fn,$ln,$email,$phone,$on,$dob,$gender,$nid,$region,$res_addr,$post_addr,$notes);
$stmt->fetch();
$stmt->close();

// 2) affiliation
$aff = $conn->prepare("SELECT branch_id,institution_name FROM affiliations WHERE member_id=?");
$aff->bind_param("i",$member_id);
$aff->execute();
$aff->bind_result($db_branch_id,$db_institution);
$aff->fetch();
$aff->close();

// branches list for dropdown
$branches = $conn->query("SELECT id,branch_name FROM branches ORDER BY branch_name")->fetch_all(MYSQLI_ASSOC);

// 3) academics
$ac_rows = $conn->prepare("SELECT highest_qualification,discipline,institution_attended,graduation_year FROM academic_background WHERE member_id=?");
$ac_rows->bind_param("i",$member_id);
$ac_rows->execute();
$ac_rs = $ac_rows->get_result()->fetch_all(MYSQLI_ASSOC);
$ac_rows->close();
// pad to 3
while(count($ac_rs)<3) $ac_rs[]=['highest_qualification'=>'','discipline'=>'','institution_attended'=>'','graduation_year'=>''];

// 4) employment
$em_rows = $conn->prepare("SELECT employment_status,current_position,organization,sector,location FROM employment WHERE member_id=?");
$em_rows->bind_param("i",$member_id);
$em_rows->execute();
$em_rs = $em_rows->get_result()->fetch_all(MYSQLI_ASSOC);
$em_rows->close();
// pad to 3
while(count($em_rs)<3) $em_rs[]=['employment_status'=>'','current_position'=>'','organization'=>'','sector'=>'','location'=>''];

$isDashboard = true;
include '../includes/header.php';
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Edit Profile</title>
  <link rel="stylesheet" href="../css/styles.css">
</head>
<body>
<div class="dashboard">
  <aside class="sidebar">
    <div class="logo"></div>
    <nav>
      <a href="member_dashboard.php">Dashboard</a>
      <a href="edit_profile.php" class="active">Edit Profile</a>
      <a href="pay_dues.php">Pay Dues</a>
      <a href="event_history.php">Event History</a>
    </nav>
    <form action="../logout.php" method="post" style="width: 100%; display: flex; justify-content: center; margin-top: auto;">
        <button type="submit" class="logout">Logout</button>
    </form>
  </aside>

  <main class="main">
    <header>
      <div class="hamburger" onclick="toggleSidebar()">☰</div>
      <h1>Edit Profile</h1>
    </header>

    <div class="form-container">
      <?php if($error): ?>
        <div class="message error"><?=htmlspecialchars($error)?></div>
      <?php elseif($success): ?>
        <div class="message success"><?=htmlspecialchars($success)?></div>
      <?php endif; ?>

      <form method="POST">
        <!-- Core Fields -->
        <div class="form-group"><label>First Name *</label><input name="first_name" value="<?=$fn?>" required></div>
        <div class="form-group"><label>Last Name *</label><input name="last_name" value="<?=$ln?>" required></div>
        <div class="form-group"><label>Other Names</label><input name="other_names" value="<?=$on?>"></div>
        <div class="form-group"><label>Email *</label><input name="email" type="email" value="<?=$email?>" required></div>
        <div class="form-group"><label>Phone</label><input name="phone" value="<?=$phone?>"></div>
        <div class="small-group">
          <div class="form-group"><label>Date of Birth</label><input name="dob" type="date" value="<?=$dob?>"></div>
          <div class="form-group"><label>Gender</label>
            <select name="gender">
              <option value="">--</option>
              <option <?= $gender==='Male'?'selected':''?>>Male</option>
              <option <?= $gender==='Female'?'selected':''?>>Female</option>
            </select>
          </div>
        </div>
        <div class="form-group"><label>National ID</label><input name="national_id" value="<?=$nid?>"></div>
        <div class="form-group"><label>Region</label><input name="region" value="<?=$region?>"></div>
        <div class="form-group"><label>Residential Address</label><textarea name="residential_address"><?=$res_addr?></textarea></div>
        <div class="form-group"><label>Postal Address</label><textarea name="postal_address"><?=$post_addr?></textarea></div>
        <div class="form-group"><label>Notes</label><textarea name="notes"><?=$notes?></textarea></div>

        <!-- Affiliation -->
        <h3>Affiliation</h3>
        <div class="form-group">
          <label>Branch</label>
          <select name="branch_id" required>
            <?php foreach($branches as $b): ?>
              <option value="<?=$b['id']?>" <?=($b['id']==$db_branch_id)?'selected':''?>>
                <?=htmlspecialchars($b['branch_name'])?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Institution Name</label>
          <input name="institution_name" value="<?=htmlspecialchars($db_institution)?>">
        </div>

        <!-- Academic (up to 3) -->
        <h3>Academic Background</h3>
        <?php foreach($ac_rs as $i=>$ac): ?>
          <div class="repeat-block">
            <h4>Entry <?= $i+1 ?></h4>
            <div class="small-group">
              <div class="form-group"><label>Qualification</label>
                <input name="academic[<?= $i ?>][highest_qualification]" 
                       value="<?=htmlspecialchars($ac['highest_qualification'])?>">
              </div>
              <div class="form-group"><label>Graduation Year</label>
                <input name="academic[<?= $i ?>][graduation_year]" 
                       value="<?=htmlspecialchars($ac['graduation_year'])?>" type="number">
              </div>
            </div>
            <div class="form-group"><label>Discipline</label>
              <input name="academic[<?= $i ?>][discipline]" 
                     value="<?=htmlspecialchars($ac['discipline'])?>">
            </div>
            <div class="form-group"><label>Institution Attended</label>
              <input name="academic[<?= $i ?>][institution_attended]" 
                     value="<?=htmlspecialchars($ac['institution_attended'])?>">
            </div>
          </div>
        <?php endforeach; ?>

        <!-- Employment (up to 3) -->
        <h3>Employment History</h3>
        <?php foreach($em_rs as $i=>$em): ?>
          <div class="repeat-block">
            <h4>Job <?= $i+1 ?></h4>
            <div class="small-group">
              <div class="form-group"><label>Status</label>
                <input name="employment[<?= $i ?>][employment_status]" 
                       value="<?=htmlspecialchars($em['employment_status'])?>">
              </div>
              <div class="form-group"><label>Position</label>
                <input name="employment[<?= $i ?>][current_position]" 
                       value="<?=htmlspecialchars($em['current_position'])?>">
              </div>
            </div>
            <div class="form-group"><label>Organization</label>
              <input name="employment[<?= $i ?>][organization]" 
                     value="<?=htmlspecialchars($em['organization'])?>">
            </div>
            <div class="small-group">
              <div class="form-group"><label>Sector</label>
                <input name="employment[<?= $i ?>][sector]" 
                       value="<?=htmlspecialchars($em['sector'])?>">
              </div>
              <div class="form-group"><label>Location</label>
                <input name="employment[<?= $i ?>][location]" 
                       value="<?=htmlspecialchars($em['location'])?>">
              </div>
            </div>
          </div>
        <?php endforeach; ?>

        <button type="submit">Save Changes</button>
        &nbsp;<a href="member_dashboard.php">← Back to Dashboard</a>
      </form>
    </div>
  </main>
</div>

<script>
function toggleSidebar() {
  document.querySelector('.sidebar').classList.toggle('active');
}
</script>
</body>
</html>

<?php include '../includes/footer.php'; ?>

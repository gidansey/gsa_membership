<?php
session_start();
require_once 'db_connect.php';

$error = '';
$maxAttempts    = 5;
$lockoutMinutes = 15;

/**
 * Audit logger
 */
function log_audit($conn, $uid, $action) {
    $stmt = $conn->prepare("
      INSERT INTO audit_logs (user_id, action, table_name, affected_id)
      VALUES (?, ?, 'users', ?)
    ");
    $stmt->bind_param("isi", $uid, $action, $uid);
    $stmt->execute();
}

// 1) Role‐picker POST? finalize and redirect
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['choose_role'])) {
    if (!empty($_SESSION['choose_roles']) && in_array($_POST['role_name'], $_SESSION['choose_roles'])) {
        $_SESSION['role'] = $_POST['role_name'];
        unset($_SESSION['choose_roles']);
        if ($_SESSION['role'] === 'Branch Leader') {
            header("Location: dashboards/branch_dashboard.php");
        } else {
            header("Location: dashboards/member_dashboard.php");
        }
        exit;
    } else {
        $error = 'Invalid role selection.';
    }
}

// 2) Login POST? authenticate
if ($_SERVER['REQUEST_METHOD']==='POST' && !isset($_POST['choose_role'])) {
    $u = trim($_POST['username']);
    $p = $_POST['password'];
    $remember = isset($_POST['remember']);

    $stmt = $conn->prepare("
      SELECT id,password,role,status,failed_attempts,locked_until,email
        FROM users
       WHERE username=? OR email=?
    ");
    $stmt->bind_param("ss",$u,$u);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $error = 'User not found.';
    }
    elseif (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
        $error = 'Account locked until ' . $user['locked_until'];
        log_audit($conn, $user['id'], 'Login Blocked');
    }
    elseif ($user['status'] !== 'Active') {
        $error = 'Account inactive.';
        log_audit($conn, $user['id'], 'Login Blocked');
    }
    elseif (!password_verify($p, $user['password'])) {
        // failed attempt
        $fa = $user['failed_attempts'] + 1;
        $lk = $fa >= $maxAttempts
            ? date('Y-m-d H:i:s', strtotime("+$lockoutMinutes minutes"))
            : null;
        $upd = $conn->prepare("
          UPDATE users
             SET failed_attempts=?, locked_until=?
           WHERE id=?
        ");
        $upd->bind_param("isi",$fa,$lk,$user['id']);
        $upd->execute();
        $upd->close();

        $error = $fa >= $maxAttempts
               ? "Too many attempts; locked for {$lockoutMinutes} minutes."
               : "Invalid password ({".($maxAttempts-$fa)." tries left})";
        log_audit($conn, $user['id'], 'Login Failed');
    }
    else {
        // successful login
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $u;

        // clear failures
        $conn->query("UPDATE users SET failed_attempts=0, locked_until=NULL WHERE id={$user['id']}");
        log_audit($conn, $user['id'], 'Login Success');

        // optionally: set remember_token
        if ($remember) {
            $token = bin2hex(random_bytes(32));
            setcookie("remember_token",$token, time()+86400*30, '/');
            $stmt = $conn->prepare("UPDATE users SET remember_token=? WHERE id=?");
            $stmt->bind_param("si",$token,$user['id']);
            $stmt->execute();
            $stmt->close();
        }

        // capture member_id & branch_id
        $mem = $conn->prepare("SELECT id FROM members WHERE email=? LIMIT 1");
        $mem->bind_param("s",$user['email']);
        $mem->execute();
        $mid = $mem->get_result()->fetch_assoc()['id'] ?? null;
        $mem->close();
        $_SESSION['member_id'] = $mid;

        if ($mid) {
            $aff = $conn->prepare("SELECT branch_id FROM affiliations WHERE member_id=?");
            $aff->bind_param("i",$mid);
            $aff->execute();
            $bid = $aff->get_result()->fetch_assoc()['branch_id'] ?? null;
            $aff->close();
            $_SESSION['branch_id'] = $bid;
        }

        // Admin & Secretariat go straight
        if ($user['role']==='Admin') {
            $_SESSION['role']='Admin';
            header("Location: dashboards/admin_dashboard.php"); exit;
        }
        if ($user['role']==='Secretariat') {
            $_SESSION['role']='Secretariat';
            header("Location: dashboards/secretariat_dashboard.php"); exit;
        }

        // multi‐role Member/Branch Leader
        $choices = ['Member'];
        if ($user['role']==='Branch Leader') {
            $choices[]='Branch Leader';
        }

        if (count($choices)===1) {
            $_SESSION['role']='Member';
            header("Location: dashboards/member_dashboard.php");
            exit;
        }

        // two choices → ask
        $_SESSION['choose_roles'] = $choices;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>GSA Login</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: Arial;
            background: #f4f6f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        form {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 350px;
            text-align: center;
        }
        img.logo {
            width: 80px;
            margin-bottom: 20px;
        }
        h2 {
            margin-bottom: 20px;
            color: #2f3640;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        .remember-me {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            font-size: 14px;
            margin-top: 10px;
        }
        .remember-me input {
            margin-right: 5px;
        }
        .forgot-link {
            text-align: right;
            margin-top: 5px;
        }
        .forgot-link a {
            font-size: 13px;
            color: #007BFF;
            text-decoration: none;
        }
        .forgot-link a:hover {
            text-decoration: underline;
        }
        button {
            background: #2f3640;
            color: white;
            padding: 12px;
            margin-top: 15px;
            border: none;
            border-radius: 6px;
            width: 100%;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
        }
        .error {
            color: crimson;
            margin-top: 10px;
            font-size: 14px;
        }
    </style>
</head>
<body>
  <?php if ($error): ?>
    <p class="error"><?=htmlspecialchars($error)?></p>
  <?php endif; ?>

  <?php if (!empty($_SESSION['choose_roles'])): ?>
    <form method="POST">
      <h2>Select Role</h2>
      <select name="role_name" required>
        <option value="">-- choose --</option>
        <?php foreach($_SESSION['choose_roles'] as $r): ?>
          <option value="<?=htmlspecialchars($r)?>"><?=htmlspecialchars($r)?></option>
        <?php endforeach; ?>
      </select><br><br>
      <button name="choose_role">Continue</button>
    </form>
  <?php else: ?>
    <form method="POST">
      <img src="assets/gsa_logo.svg" class="logo"><br>
      <h2>GSA Login</h2>
      <input type="text" name="username" placeholder="Username or Email" required><br>
      <input type="password" name="password" placeholder="Password" required><br>
      <div class="remember-me">
        <input type="checkbox" name="remember" id="remember">
        <label for="remember">Remember Me</label>
      </div>
      <button>Login</button>
      <div class="forgot-link">
        <a href="forgot_password.php">Forgot Password?</a>
      </div>
    </form>
  <?php endif; ?>
</body>
</html>
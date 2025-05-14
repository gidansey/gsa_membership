<?php
require_once 'db_connect.php';
$message = "";
$showForm = true;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($email, $expires_at);
    $stmt->fetch();

    if (!$email || strtotime($expires_at) < time()) {
        $message = "Invalid or expired token.";
        $showForm = false;
    }
} else {
    $message = "No token provided.";
    $showForm = false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["password"])) {
    $password = $_POST["password"];
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
    $stmt->bind_param("ss", $hashed, $email);
    $stmt->execute();

    $conn->query("DELETE FROM password_resets WHERE email = '$email'");

    $message = "Password reset successful. You may now <a href='index.php'>login</a>.";
    $showForm = false;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
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
        input {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 6px;
            border: 1px solid #ccc;
            font-size: 14px;
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
        p {
            font-size: 14px;
            color: #333;
            margin-top: 10px;
        }
        a {
            color: #2980b9;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <form method="POST">
        <img src="assets/gsa_logo.svg" alt="GSA Logo" class="logo">
        <h2>Reset Password</h2>
        <?php if ($showForm): ?>
            <input type="password" name="password" placeholder="Enter new password" required>
            <button type="submit">Reset Password</button>
        <?php endif; ?>
        <p><?= $message ?></p>
        <?php if (!$showForm): ?>
            <p><a href="index.php">Back to Login</a></p>
        <?php endif; ?>
    </form>
</body>
</html>

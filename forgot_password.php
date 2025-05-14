<?php
require_once 'db_connect.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $conn->query("DELETE FROM password_resets WHERE email = '$email'"); // clear previous

        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $token, $expires);
        $stmt->execute();

        $resetLink = "http://yourdomain.com/reset_password.php?token=$token";
        // Replace with your mailer
        mail($email, "GSA Password Reset", "Click to reset password: $resetLink");

        $message = "A password reset link has been sent to your email.";
    } else {
        $message = "Email not found.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
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
        <h2>Forgot Password</h2>
        <input type="email" name="email" placeholder="Enter your email" required>
        <button type="submit">Send Reset Link</button>
        <p><?= $message ?></p>
        <p><a href="index.php">Back to Login</a></p>
    </form>
</body>
</html>
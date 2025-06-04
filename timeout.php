<?php
session_start();
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Session Timeout</title>
    <meta http-equiv="refresh" content="5;url=../index.php"> <!-- Redirect after 5 seconds -->
    <link rel="stylesheet" href="../assets/styles.css"> <!-- Optional styling -->
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding-top: 100px;
            background-color: #f2f2f2;
        }
        .timeout-box {
            display: inline-block;
            padding: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .timeout-box h1 {
            color: #d9534f;
        }
    </style>
</head>
<body>
    <div class="timeout-box">
        <h1>⏳ Session Timed Out</h1>
        <p>Your session has expired due to inactivity.</p>
        <p>You will be redirected to the login page in 5 seconds.</p>
        <a href="../index.php" class="button">Go to Login Now</a>
    </div>
</body>
</html>

<?php
// No session required — user is already logged out
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Logged Out</title>
  <meta http-equiv="refresh" content="3;url=index.php">
  <style>
    body {
      background: #f4f6f9;
      font-family: Arial, sans-serif;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .message-box {
      background: white;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
      text-align: center;
      width: 320px;
    }
    .message-box img.logo {
      width: 70px;
      margin-bottom: 20px;
    }
    .message-box h2 {
      color: #2f3640;
      margin-bottom: 10px;
    }
    .message-box p {
      font-size: 14px;
      color: #555;
    }
    .message-box a.button {
      display: inline-block;
      margin-top: 20px;
      background: #2f3640;
      color: white;
      padding: 10px 20px;
      text-decoration: none;
      border-radius: 6px;
      font-size: 14px;
    }
    .message-box a.button:hover {
      background: #1d2027;
    }
  </style>
</head>
<body>
  <div class="message-box">
    <img src="assets/gsa_logo.svg" alt="GSA Logo" class="logo">
    <h2>You have been logged out</h2>
    <p>You will be redirected to the login page shortly.</p>
    <a class="button" href="index.php">Login Again</a>
  </div>
</body>
</html>
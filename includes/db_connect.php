<?php
// Database credentials
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "gsa_membership";

// Create connection
$conn = new mysqli($host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

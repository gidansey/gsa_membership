<?php
session_start();

$timeout_duration = 1800;

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: timeout.php");
    exit;
}
$_SESSION['LAST_ACTIVITY'] = time();
?>

<?php
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$role = $_SESSION['role'];

// Redirect to dashboard (Dashboard handles permissions/visibility)
header("Location: dashboard");
exit();
exit();
?>
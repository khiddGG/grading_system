<?php
session_start();

// If already logged in, redirect to respective dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    header("Location: /grading_systemv2/pages/$role/dashboard.php");
    exit;
}

// Otherwise, go to login
header('Location: /grading_systemv2/pages/login.php');
exit;

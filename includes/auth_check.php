<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /chemical_inventory/auth/login.php');
    exit;
}

// Optional role check
if (isset($required_role) && $_SESSION['role'] !== $required_role) {
    die("Access denied: insufficient privileges.");
}
?>

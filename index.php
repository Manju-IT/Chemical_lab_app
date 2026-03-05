<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: /chemical_inventory/dashboard/dashboard.php');
} else {
    header('Location: /chemical_inventory/auth/login.php');
}
exit;
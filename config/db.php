<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'lab_inventory';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log errors to database
function logError($message, $file, $line) {
    global $conn;
    $user_id = $_SESSION['user_id'] ?? null;
    $stmt = $conn->prepare("INSERT INTO error_logs (error_message, file, line, user_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $message, $file, $line, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Log audit actions
function logAudit($action, $chemical_id = null, $details = '') {
    global $conn;
    $user_id = $_SESSION['user_id'] ?? null;
    if (!$user_id) return;
    $stmt = $conn->prepare("INSERT INTO audit_logs (user_id, action, chemical_id, details) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isis", $user_id, $action, $chemical_id, $details);
    $stmt->execute();
    $stmt->close();
}
?>
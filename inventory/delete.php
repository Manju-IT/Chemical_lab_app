<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') die("Access denied");

$id = intval($_GET['id'] ?? 0);
if ($id) {
    // Get chemical_id for log
    $res = $conn->query("SELECT chemical_id FROM chemicals WHERE id = $id");
    if ($res->num_rows) {
        $chem = $res->fetch_assoc();
        $chem_id = $chem['chemical_id'];
        $stmt = $conn->prepare("DELETE FROM chemicals WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            logAudit("delete chemical", $id, "Chemical ID: $chem_id");
        }
        $stmt->close();
    }
}
header('Location: list.php');
exit;
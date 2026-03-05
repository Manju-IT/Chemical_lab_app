<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') die("Access denied");

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="audit_logs_' . date('Ymd') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Time', 'User', 'Action', 'Chemical ID', 'Details']);

$logs = $conn->query("SELECT a.created_at, u.username, a.action, a.chemical_id, a.details FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");
while ($row = $logs->fetch_assoc()) {
    fputcsv($output, [
        $row['created_at'],
        $row['username'] ?? 'System',
        $row['action'],
        $row['chemical_id'],
        $row['details']
    ]);
}
fclose($output);
exit;
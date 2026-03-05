<?php
// File: simulation/export.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$simulation_days = 30;
$report = [];

// Run simulation (same logic as report.php)
$chems = $conn->query("SELECT id, chemical_id, current_stock, usage_rate, expiry_date FROM chemicals");
while ($chem = $chems->fetch_assoc()) {
    $stock = $chem['current_stock'];
    $usage = $chem['usage_rate'];
    $expiry_ts = strtotime($chem['expiry_date']);
    $now = time();

    for ($day = 1; $day <= $simulation_days; $day++) {
        $current_day_ts = $now + ($day * 86400);
        $alert = '';

        if ($expiry_ts < $current_day_ts) {
            $alert = 'EXPIRED';
            $stock = 0;
            $report[] = [
                'day' => $day,
                'chemical' => $chem['chemical_id'],
                'stock' => 0,
                'alert' => $alert
            ];
            break;
        }

        $stock -= $usage;
        if ($stock < 0) $stock = 0;

        if ($stock <= $usage * 7) {
            $alert = 'LOW STOCK';
        }
        if ($stock <= 0) {
            $alert = 'STOCK OUT';
            $report[] = [
                'day' => $day,
                'chemical' => $chem['chemical_id'],
                'stock' => 0,
                'alert' => $alert
            ];
            break;
        }

        $report[] = [
            'day' => $day,
            'chemical' => $chem['chemical_id'],
            'stock' => round($stock, 2),
            'alert' => $alert
        ];
    }
}

// Output CSV
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="simulation_report_' . date('Ymd') . '.csv"');

$output = fopen('php://output', 'w');
fputcsv($output, ['Day', 'Chemical', 'Predicted Stock', 'Alert']);

foreach ($report as $r) {
    fputcsv($output, [$r['day'], $r['chemical'], $r['stock'], $r['alert']]);
}

fclose($output);
exit;
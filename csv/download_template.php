<?php
// File: csv/download_template.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="chemical_template.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add header row
fputcsv($output, ['ChemicalID', 'ChemicalName', 'BatchID', 'Quantity', 'ExpiryDate', 'StorageCategory', 'UsageRate']);

// Optionally add one example row (commented out if you want empty template)
// fputcsv($output, ['C001', 'Sodium Chloride', 'B2024-01', '500', '2025-12-31', 'A', '2.5']);

fclose($output);
exit;
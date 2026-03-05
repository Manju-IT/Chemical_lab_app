<?php
// File: audit/export_pdf.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

// Include FPDF library (adjust path as needed)
require_once '../lib/fpdf.php';

// Fetch logs
$logs = $conn->query("SELECT a.created_at, u.username, a.action, a.chemical_id, a.details FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC");

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Audit Logs',0,1,'C');
$pdf->Ln(5);

$pdf->SetFont('Arial','B',10);
$pdf->Cell(20,7,'Time',1);
$pdf->Cell(25,7,'User',1);
$pdf->Cell(40,7,'Action',1);
$pdf->Cell(20,7,'Chem ID',1);
$pdf->Cell(85,7,'Details',1);
$pdf->Ln();

$pdf->SetFont('Arial','',9);
while ($row = $logs->fetch_assoc()) {
    $pdf->Cell(20,6,substr($row['created_at'],0,10),1);
    $pdf->Cell(25,6,substr($row['username']??'System',0,12),1);
    $pdf->Cell(40,6,substr($row['action'],0,20),1);
    $pdf->Cell(20,6,$row['chemical_id']??'',1);
    $pdf->Cell(85,6,substr($row['details'],0,50),1);
    $pdf->Ln();
}

$pdf->Output('D', 'audit_logs_'.date('Ymd').'.pdf');
exit;
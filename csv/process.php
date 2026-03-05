<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') die("Access denied");

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['csv_file'])) {
    header('Location: upload.php');
    exit;
}

$file = $_FILES['csv_file']['tmp_name'];
if (!is_uploaded_file($file)) {
    die("No file uploaded.");
}

$filename = basename($_FILES['csv_file']['name']);
$handle = fopen($file, 'r');
if (!$handle) {
    die("Cannot open file.");
}

$total = 0;
$inserted = 0;
$rejected = 0;

// Expected columns: ChemicalID, ChemicalName, BatchID, Quantity, ExpiryDate, StorageCategory, UsageRate
$header = fgetcsv($handle); // optionally validate header

while (($row = fgetcsv($handle)) !== FALSE) {
    $total++;
    if (count($row) < 7) {
        $rejected++;
        logRejected($filename, implode(',', $row), "Insufficient columns");
        continue;
    }

    list($chem_id, $name, $batch, $qty, $expiry, $category, $usage) = $row;
    $qty = floatval($qty);
    $usage = floatval($usage);

    $errors = [];

    // Validate
    if (empty($chem_id) || empty($name) || empty($batch) || empty($expiry) || empty($category)) {
        $errors[] = "Missing required field";
    }
    if ($qty < 0) $errors[] = "Negative stock";
    if ($usage < 0) $errors[] = "Negative usage rate";
    $date = DateTime::createFromFormat('Y-m-d', $expiry);
    if (!$date || $date->format('Y-m-d') !== $expiry) {
        $errors[] = "Invalid date format";
    }

    // Check duplicate chemical_id
    $check = $conn->prepare("SELECT id FROM chemicals WHERE chemical_id = ?");
    $check->bind_param("s", $chem_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $errors[] = "Duplicate Chemical ID";
    }
    $check->close();

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO chemicals (chemical_id, name, batch_id, current_stock, expiry_date, storage_category, usage_rate) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdssd", $chem_id, $name, $batch, $qty, $expiry, $category, $usage);
        if ($stmt->execute()) {
            $inserted++;
            logAudit("csv import", $stmt->insert_id, "Chemical ID: $chem_id");
        } else {
            $rejected++;
            logRejected($filename, implode(',', $row), "DB error: " . $conn->error);
        }
        $stmt->close();
    } else {
        $rejected++;
        logRejected($filename, implode(',', $row), implode('; ', $errors));
    }
}
fclose($handle);

function logRejected($file, $rowdata, $reason) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO rejected_records (csv_filename, row_data, rejection_reason) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $file, $rowdata, $reason);
    $stmt->execute();
    $stmt->close();
}
?>
<?php include '../includes/header.php'; ?>
<h2>Upload Summary</h2>
<ul class="list-group">
    <li class="list-group-item">Total rows: <?= $total ?></li>
    <li class="list-group-item list-group-item-success">Inserted: <?= $inserted ?></li>
    <li class="list-group-item list-group-item-danger">Rejected: <?= $rejected ?></li>
</ul>
<a href="upload.php" class="btn btn-primary mt-3">Upload another</a>
<?php include '../includes/footer.php'; ?>
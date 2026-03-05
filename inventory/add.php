<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') die("Access denied");

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chem_id = trim($_POST['chemical_id']);
    $name = trim($_POST['name']);
    $batch = trim($_POST['batch_id']);
    $stock = floatval($_POST['current_stock']);
    $expiry = $_POST['expiry_date'];
    $category = trim($_POST['storage_category']);
    $usage = floatval($_POST['usage_rate']);

    // Validation
    if (empty($chem_id) || empty($name) || empty($batch) || empty($expiry) || empty($category)) {
        $errors[] = "All fields are required.";
    }
    if ($stock < 0) $errors[] = "Stock cannot be negative.";
    if ($usage < 0) $errors[] = "Usage rate cannot be negative.";
    $date = DateTime::createFromFormat('Y-m-d', $expiry);
    if (!$date || $date->format('Y-m-d') !== $expiry) {
        $errors[] = "Invalid expiry date format (YYYY-MM-DD).";
    }

    // Check duplicate chemical_id
    $check = $conn->prepare("SELECT id FROM chemicals WHERE chemical_id = ?");
    $check->bind_param("s", $chem_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $errors[] = "Chemical ID already exists.";
    }
    $check->close();

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO chemicals (chemical_id, name, batch_id, current_stock, expiry_date, storage_category, usage_rate) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssdssd", $chem_id, $name, $batch, $stock, $expiry, $category, $usage);
        if ($stmt->execute()) {
            logAudit("add chemical", $stmt->insert_id, "Chemical ID: $chem_id");
            header('Location: list.php');
            exit;
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}
?>
<?php include '../includes/header.php'; ?>
<h2>Add Chemical</h2>
<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <?= implode('<br>', $errors) ?>
    </div>
<?php endif; ?>
<form method="post">
    <div class="mb-3">
        <label>Chemical ID (unique)</label>
        <input type="text" name="chemical_id" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Name</label>
        <input type="text" name="name" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Batch ID</label>
        <input type="text" name="batch_id" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Current Stock</label>
        <input type="number" step="0.01" name="current_stock" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Expiry Date (YYYY-MM-DD)</label>
        <input type="date" name="expiry_date" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Storage Category</label>
        <input type="text" name="storage_category" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Usage Rate (daily)</label>
        <input type="number" step="0.01" name="usage_rate" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Add</button>
    <a href="list.php" class="btn btn-secondary">Cancel</a>
</form>
<?php include '../includes/footer.php'; ?>
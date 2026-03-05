<?php
// File: inventory/edit.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Only admin allowed
if ($_SESSION['role'] !== 'admin') {
    die("Access denied: insufficient privileges.");
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: list.php');
    exit;
}

// Fetch current data
$stmt = $conn->prepare("SELECT * FROM chemicals WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header('Location: list.php');
    exit;
}
$chemical = $result->fetch_assoc();
$stmt->close();

$errors = [];
$success = false;

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

    // Check duplicate chemical_id only if changed
    if ($chem_id !== $chemical['chemical_id']) {
        $check = $conn->prepare("SELECT id FROM chemicals WHERE chemical_id = ? AND id != ?");
        $check->bind_param("si", $chem_id, $id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors[] = "Chemical ID already exists.";
        }
        $check->close();
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE chemicals SET chemical_id=?, name=?, batch_id=?, current_stock=?, expiry_date=?, storage_category=?, usage_rate=? WHERE id=?");
        $stmt->bind_param("sssdssdi", $chem_id, $name, $batch, $stock, $expiry, $category, $usage, $id);
        if ($stmt->execute()) {
            $success = true;
            // Assuming logAudit exists (if not, you can comment or define)
            if (function_exists('logAudit')) {
                logAudit("edit chemical", $id, "Chemical ID: $chem_id");
            }
            // Refresh data
            $chemical = array_merge($chemical, $_POST); // simple update
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}

include '../includes/header.php';
?>

<style>
    /* ==================== MODERN EDIT FORM STYLES ==================== */
    :root {
        --primary: #4776E6;
        --primary-dark: #8E54E9;
        --success: #28a745;
        --danger: #dc3545;
        --warning: #ffc107;
    }

    .edit-container {
        padding: 1rem 0;
    }

    /* Header */
    .edit-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .edit-header h2 {
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .edit-header h2 i {
        background: linear-gradient(45deg, var(--primary), var(--primary-dark));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-size: 2rem;
    }

    /* Back button */
    .btn-back {
        background: rgba(255,255,255,0.8);
        backdrop-filter: blur(5px);
        border: 1px solid rgba(255,255,255,0.5);
        border-radius: 50px;
        padding: 0.6rem 1.5rem;
        font-weight: 500;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: #333;
        text-decoration: none;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .btn-back:hover {
        transform: translateY(-2px);
        background: white;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    /* Form Card */
    .form-card {
        background: rgba(255,255,255,0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 30px;
        border: 1px solid rgba(255,255,255,0.5);
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        padding: 2rem;
        margin-bottom: 2rem;
    }

    .form-card:hover {
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }

    /* Form groups */
    .form-group {
        margin-bottom: 1.8rem;
        position: relative;
    }

    .form-group label {
        font-weight: 500;
        color: #555;
        margin-bottom: 0.5rem;
        display: block;
        font-size: 0.95rem;
        letter-spacing: 0.3px;
    }

    .input-icon {
        position: relative;
    }

    .input-icon i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #aaa;
        font-size: 1.2rem;
        transition: color 0.3s;
        z-index: 10;
    }

    .input-icon input,
    .input-icon select {
        width: 100%;
        padding: 12px 15px 12px 45px;
        border: 2px solid #eaeef2;
        border-radius: 50px;
        font-size: 1rem;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s;
        background: white;
        appearance: none;
        -webkit-appearance: none;
    }

    .input-icon input:focus,
    .input-icon select:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 0 4px rgba(71, 118, 230, 0.1);
    }

    .input-icon input:focus + i,
    .input-icon select:focus + i {
        color: var(--primary);
    }

    /* Alerts */
    .alert-modern {
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(5px);
        border-left: 5px solid;
        border-radius: 20px;
        padding: 1rem 1.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: flex-start;
        gap: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.03);
    }

    .alert-modern i {
        font-size: 1.5rem;
    }

    .alert-success {
        border-color: var(--success);
        color: #155724;
        background: #d4edda;
    }

    .alert-danger {
        border-color: var(--danger);
        color: #721c24;
        background: #f8d7da;
    }

    /* Buttons */
    .btn-group {
        display: flex;
        gap: 15px;
        margin-top: 2rem;
        flex-wrap: wrap;
    }

    .btn-primary-modern {
        background: linear-gradient(45deg, var(--primary), var(--primary-dark));
        border: none;
        border-radius: 50px;
        padding: 12px 30px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: white;
        text-decoration: none;
        box-shadow: 0 8px 15px -5px rgba(71, 118, 230, 0.4);
        flex: 1;
    }

    .btn-primary-modern:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 20px -5px rgba(71, 118, 230, 0.6);
    }

    .btn-secondary-modern {
        background: #f1f3f5;
        border: none;
        border-radius: 50px;
        padding: 12px 30px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        color: #555;
        text-decoration: none;
        box-shadow: 0 5px 10px rgba(0,0,0,0.05);
        flex: 1;
    }

    .btn-secondary-modern:hover {
        background: #e9ecef;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px rgba(0,0,0,0.1);
    }

    /* Responsive */
    @media (max-width: 576px) {
        .form-card {
            padding: 1.5rem;
        }
        .btn-group {
            flex-direction: column;
        }
    }
</style>

<div class="edit-container">
    <!-- Header with back button -->
    <div class="edit-header">
        <h2>
            <i class="fas fa-edit"></i> Edit Chemical
        </h2>
        <a href="list.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success): ?>
        <div class="alert-modern alert-success">
            <i class="fas fa-check-circle"></i>
            <span>Chemical updated successfully.</span>
        </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="alert-modern alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <span><?= implode('<br>', $errors) ?></span>
        </div>
    <?php endif; ?>

    <!-- Edit Form Card -->
    <div class="form-card">
        <form method="post">
            <div class="form-group">
                <label for="chemical_id">Chemical ID (unique)</label>
                <div class="input-icon">
                    <i class="fas fa-barcode"></i>
                    <input type="text" id="chemical_id" name="chemical_id" value="<?= htmlspecialchars($chemical['chemical_id']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="name">Name</label>
                <div class="input-icon">
                    <i class="fas fa-tag"></i>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($chemical['name']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="batch_id">Batch ID</label>
                <div class="input-icon">
                    <i class="fas fa-layer-group"></i>
                    <input type="text" id="batch_id" name="batch_id" value="<?= htmlspecialchars($chemical['batch_id']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="current_stock">Current Stock</label>
                <div class="input-icon">
                    <i class="fas fa-weight-hanging"></i>
                    <input type="number" step="0.01" id="current_stock" name="current_stock" value="<?= $chemical['current_stock'] ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="expiry_date">Expiry Date (YYYY-MM-DD)</label>
                <div class="input-icon">
                    <i class="fas fa-calendar-alt"></i>
                    <input type="date" id="expiry_date" name="expiry_date" value="<?= $chemical['expiry_date'] ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="storage_category">Storage Category</label>
                <div class="input-icon">
                    <i class="fas fa-archive"></i>
                    <input type="text" id="storage_category" name="storage_category" value="<?= htmlspecialchars($chemical['storage_category']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="usage_rate">Usage Rate (daily)</label>
                <div class="input-icon">
                    <i class="fas fa-chart-line"></i>
                    <input type="number" step="0.01" id="usage_rate" name="usage_rate" value="<?= $chemical['usage_rate'] ?>" required>
                </div>
            </div>

            <div class="btn-group">
                <button type="submit" class="btn-primary-modern">
                    <i class="fas fa-save"></i> Update Chemical
                </button>
                <a href="list.php" class="btn-secondary-modern">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
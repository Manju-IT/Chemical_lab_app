<?php
// File: inventory/view.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: list.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM chemicals WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header('Location: list.php');
    exit;
}
$chem = $result->fetch_assoc();
$stmt->close();

// Determine alerts
$alerts = [];
$usage = $chem['usage_rate'];
$stock = $chem['current_stock'];
$expiry = strtotime($chem['expiry_date']);
$now = time();
$days_to_expiry = ($expiry - $now) / 86400;

if ($stock <= $usage * 7) {
    $alerts[] = ['type' => 'Low Stock', 'message' => "Stock ($stock) is below 7-day consumption (" . round($usage*7,2) . ")"];
}
if ($stock - $usage * 10 <= 0) {
    $alerts[] = ['type' => 'Reorder', 'message' => "Will run out within 10 days (reorder now)"];
}
if ($days_to_expiry <= 30 && $days_to_expiry >= 0) {
    $alerts[] = ['type' => 'Expiring Soon', 'message' => "Expires in " . round($days_to_expiry) . " days"];
}
if ($days_to_expiry < 0) {
    $alerts[] = ['type' => 'Expired', 'message' => "Expired on {$chem['expiry_date']} – UNUSABLE"];
}

include '../includes/header.php';
?>

<style>
    /* ==================== MODERN DETAILS PAGE STYLES ==================== */
    :root {
        --primary: #4776E6;
        --primary-dark: #8E54E9;
        --success: #28a745;
        --warning: #ffc107;
        --danger: #dc3545;
        --info: #17a2b8;
    }

    .detail-container {
        padding: 1rem 0;
    }

    /* Header */
    .detail-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .detail-header h2 {
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .detail-header h2 i {
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

    /* Main card */
    .detail-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.5);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    .detail-card-header {
        background: linear-gradient(45deg, #1e1e2f, #2a2a40);
        color: white;
        padding: 1.5rem 2rem;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .detail-card-header i {
        font-size: 2.5rem;
        color: #a0a0ff;
    }

    .detail-card-header h3 {
        margin: 0;
        font-weight: 600;
        font-size: 1.8rem;
    }

    .detail-card-body {
        padding: 2rem;
    }

    /* Info grid */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
    }

    .info-item {
        background: rgba(255,255,255,0.5);
        border-radius: 20px;
        padding: 1.2rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 15px;
        transition: all 0.3s;
        border: 1px solid rgba(255,255,255,0.5);
    }

    .info-item:hover {
        background: white;
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }

    .info-icon {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(45deg, var(--primary), var(--primary-dark));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }

    .info-content {
        flex: 1;
    }

    .info-label {
        font-size: 0.85rem;
        color: #777;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-value {
        font-size: 1.4rem;
        font-weight: 600;
        color: #333;
        line-height: 1.2;
    }

    .info-value small {
        font-size: 0.9rem;
        font-weight: 400;
        color: #888;
    }

    /* Status badges */
    .status-badge {
        padding: 0.35rem 1rem;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .status-ok {
        background: #d4edda;
        color: #155724;
    }
    .status-warning {
        background: #fff3cd;
        color: #856404;
    }
    .status-danger {
        background: #f8d7da;
        color: #721c24;
    }
    .status-info {
        background: #d1ecf1;
        color: #0c5460;
    }

    /* Alerts section */
    .alerts-section {
        margin-top: 2rem;
    }

    .alerts-section h4 {
        font-weight: 600;
        color: #333;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-modern {
        background: rgba(255,255,255,0.7);
        backdrop-filter: blur(5px);
        border-left: 5px solid;
        border-radius: 20px;
        padding: 1rem 1.5rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: flex-start;
        gap: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.03);
        transition: transform 0.2s;
    }

    .alert-modern:hover {
        transform: translateX(5px);
    }

    .alert-icon {
        font-size: 1.8rem;
        color: inherit;
    }

    .alert-content {
        flex: 1;
    }

    .alert-title {
        font-weight: 600;
        font-size: 1.1rem;
        margin-bottom: 0.2rem;
    }

    .alert-message {
        color: #555;
    }

    /* Alert type colors */
    .alert-lowstock { border-color: #ffc107; }
    .alert-reorder { border-color: #fd7e14; }
    .alert-expiring { border-color: #17a2b8; }
    .alert-expired { border-color: #dc3545; }

    /* Responsive */
    @media (max-width: 768px) {
        .detail-card-header h3 {
            font-size: 1.4rem;
        }
        .info-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="detail-container">
    <!-- Header with back button -->
    <div class="detail-header">
        <h2>
            <i class="fas fa-flask"></i> Chemical Details
        </h2>
        <a href="list.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>

    <!-- Main information card -->
    <div class="detail-card">
        <div class="detail-card-header">
            <i class="fas fa-cube"></i>
            <h3><?= htmlspecialchars($chem['chemical_id']) ?>: <?= htmlspecialchars($chem['name']) ?></h3>
        </div>
        <div class="detail-card-body">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-icon"><i class="fas fa-barcode"></i></div>
                    <div class="info-content">
                        <div class="info-label">Batch ID</div>
                        <div class="info-value"><?= htmlspecialchars($chem['batch_id']) ?></div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="fas fa-weight-hanging"></i></div>
                    <div class="info-content">
                        <div class="info-label">Current Stock</div>
                        <div class="info-value"><?= $chem['current_stock'] ?> <small>units</small></div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div class="info-content">
                        <div class="info-label">Expiry Date</div>
                        <div class="info-value">
                            <?= date('d M Y', strtotime($chem['expiry_date'])) ?>
                            <?php
                                // Show status badge near expiry
                                if ($days_to_expiry < 0) {
                                    echo '<span class="status-badge status-danger ms-2"><i class="fas fa-skull-crossbones"></i> Expired</span>';
                                } elseif ($days_to_expiry <= 30) {
                                    echo '<span class="status-badge status-warning ms-2"><i class="fas fa-hourglass-half"></i> Expiring Soon</span>';
                                } else {
                                    echo '<span class="status-badge status-ok ms-2"><i class="fas fa-check-circle"></i> OK</span>';
                                }
                            ?>
                        </div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="fas fa-tag"></i></div>
                    <div class="info-content">
                        <div class="info-label">Storage Category</div>
                        <div class="info-value"><?= htmlspecialchars($chem['storage_category']) ?></div>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-icon"><i class="fas fa-chart-line"></i></div>
                    <div class="info-content">
                        <div class="info-label">Usage Rate (daily)</div>
                        <div class="info-value"><?= $chem['usage_rate'] ?> <small>units/day</small></div>
                    </div>
                </div>
                <?php
                    // Calculate projected days remaining
                    if ($usage > 0) {
                        $days_remaining = floor($stock / $usage);
                        $projected_runout = date('d M Y', strtotime("+$days_remaining days"));
                    } else {
                        $days_remaining = '∞';
                        $projected_runout = 'N/A';
                    }
                ?>
                <div class="info-item">
                    <div class="info-icon"><i class="fas fa-hourglass-end"></i></div>
                    <div class="info-content">
                        <div class="info-label">Est. Run-out Date</div>
                        <div class="info-value">
                            <?= $projected_runout ?>
                            <small>(<?= is_numeric($days_remaining) ? $days_remaining . ' days' : $days_remaining ?> )</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts section -->
    <?php if (!empty($alerts)): ?>
    <div class="alerts-section">
        <h4><i class="fas fa-bell" style="color: #ffc107;"></i> Alerts & Notifications</h4>
        <?php foreach ($alerts as $a): 
            $alert_class = '';
            $icon = '';
            switch ($a['type']) {
                case 'Low Stock':
                    $alert_class = 'alert-lowstock';
                    $icon = 'fa-exclamation-triangle';
                    break;
                case 'Reorder':
                    $alert_class = 'alert-reorder';
                    $icon = 'fa-cart-plus';
                    break;
                case 'Expiring Soon':
                    $alert_class = 'alert-expiring';
                    $icon = 'fa-hourglass-half';
                    break;
                case 'Expired':
                    $alert_class = 'alert-expired';
                    $icon = 'fa-skull-crossbones';
                    break;
            }
        ?>
            <div class="alert-modern <?= $alert_class ?>">
                <div class="alert-icon"><i class="fas <?= $icon ?>"></i></div>
                <div class="alert-content">
                    <div class="alert-title"><?= htmlspecialchars($a['type']) ?></div>
                    <div class="alert-message"><?= htmlspecialchars($a['message']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Additional action buttons (optional) -->
    <div class="mt-4 d-flex gap-3">
        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="edit.php?id=<?= $id ?>" class="btn-modern" style="background: linear-gradient(45deg, var(--primary), var(--primary-dark)); color: white; padding: 0.6rem 1.8rem; border-radius: 50px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fas fa-edit"></i> Edit Chemical
            </a>
            <a href="delete.php?id=<?= $id ?>" class="btn-modern" style="background: linear-gradient(45deg, #dc3545, #c82333); color: white; padding: 0.6rem 1.8rem; border-radius: 50px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px;" onclick="return confirm('Are you sure you want to delete this item?')">
                <i class="fas fa-trash"></i> Delete
            </a>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
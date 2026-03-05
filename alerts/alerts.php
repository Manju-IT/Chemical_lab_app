<?php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Fetch all chemicals and generate alerts
$chems = $conn->query("SELECT * FROM chemicals");
$alerts = [];

while ($row = $chems->fetch_assoc()) {
    $id = $row['id'];
    $chem = $row['chemical_id'];
    $stock = $row['current_stock'];
    $usage = $row['usage_rate'];
    $expiry = $row['expiry_date'];

    // Low stock: stock <= usage * 7
    if ($stock <= $usage * 7) {
        $alerts[] = ['type' => 'Low Stock', 'chemical' => $chem, 'message' => "Stock ($stock) is below 7-day consumption (" . ($usage*7) . ")"];
    }

    // Reorder: predicted stock zero within 10 days: stock - usage*10 <= 0
    if ($stock - $usage * 10 <= 0) {
        $days = ceil($stock / $usage);
        $alerts[] = ['type' => 'Reorder', 'chemical' => $chem, 'message' => "Stock will be exhausted in $days days (reorder now)"];
    }

    // Expiry: within 30 days
    $days_to_expiry = (strtotime($expiry) - time()) / (60 * 60 * 24);
    if ($days_to_expiry <= 30 && $days_to_expiry >= 0) {
        $alerts[] = ['type' => 'Expiry', 'chemical' => $chem, 'message' => "Expires on $expiry (" . round($days_to_expiry) . " days left)"];
    }
    if ($days_to_expiry < 0) {
        $alerts[] = ['type' => 'Expired', 'chemical' => $chem, 'message' => "Expired on $expiry – UNUSABLE"];
    }
}

// Count alerts by type for summary
$alert_counts = [
    'Low Stock' => 0,
    'Reorder' => 0,
    'Expiry' => 0,
    'Expired' => 0
];
foreach ($alerts as $a) {
    if (isset($alert_counts[$a['type']])) $alert_counts[$a['type']]++;
}
?>
<?php include '../includes/header.php'; ?>

<!-- Modern Alerts Page Styles -->
<style>
    /* ==================== MODERN ALERTS STYLES ==================== */
    :root {
        --primary: #4776E6;
        --primary-dark: #8E54E9;
        --success: #28a745;
        --warning: #ffc107;
        --danger: #dc3545;
        --info: #17a2b8;
    }

    .alerts-container {
        padding: 1rem 0;
    }

    /* Header */
    .alerts-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .alerts-header h2 {
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alerts-header h2 i {
        background: linear-gradient(45deg, var(--primary), var(--primary-dark));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-size: 2rem;
    }

    /* Summary Cards */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .summary-card {
        background: rgba(255,255,255,0.7);
        backdrop-filter: blur(10px);
        border-radius: 20px;
        padding: 1.2rem;
        border: 1px solid rgba(255,255,255,0.5);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .summary-card:hover {
        transform: translateY(-3px);
        background: white;
    }

    .summary-icon {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        background: linear-gradient(45deg, var(--primary), var(--primary-dark));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.4rem;
    }

    .summary-content {
        flex: 1;
    }

    .summary-label {
        font-size: 0.8rem;
        color: #777;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .summary-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: #333;
        line-height: 1.2;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: rgba(255,255,255,0.7);
        backdrop-filter: blur(10px);
        border-radius: 30px;
        border: 1px solid rgba(255,255,255,0.5);
    }

    .empty-state i {
        font-size: 4rem;
        color: #28a745;
        margin-bottom: 1rem;
    }

    .empty-state p {
        font-size: 1.2rem;
        color: #333;
    }

    /* Table Card */
    .table-card {
        background: rgba(255,255,255,0.7);
        backdrop-filter: blur(10px);
        border-radius: 30px;
        border: 1px solid rgba(255,255,255,0.5);
        overflow: hidden;
        padding: 1.5rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
    }

    .modern-table thead tr {
        background: linear-gradient(45deg, #1e1e2f, #2a2a40);
        color: white;
    }

    .modern-table th {
        padding: 1rem 1.5rem;
        font-weight: 600;
        text-align: left;
    }

    .modern-table td {
        padding: 1rem 1.5rem;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        color: #212529;
        vertical-align: middle;
    }

    .modern-table tbody tr {
        transition: background 0.2s;
    }

    .modern-table tbody tr:hover {
        background: rgba(71, 118, 230, 0.1);
    }

    /* Alert badges */
    .alert-badge {
        padding: 0.4rem 1rem;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .badge-lowstock { background: #fff3cd; color: #856404; }
    .badge-reorder { background: #ffe0b3; color: #b45f06; }
    .badge-expiry { background: #d1ecf1; color: #0c5460; }
    .badge-expired { background: #f8d7da; color: #721c24; }

    /* Responsive */
    @media (max-width: 768px) {
        .modern-table th, .modern-table td {
            padding: 0.75rem;
        }
        .summary-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<div class="alerts-container">
    <!-- Header -->
    <div class="alerts-header">
        <h2>
            <i class="fas fa-bell"></i> Active Alerts
        </h2>
        <span class="badge bg-secondary" style="font-size: 1rem; padding: 0.5rem 1.2rem;">
            Total: <?= count($alerts) ?>
        </span>
    </div>

    <!-- Summary Cards -->
    <?php if (!empty($alerts)): ?>
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-icon"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="summary-content">
                <div class="summary-label">Low Stock</div>
                <div class="summary-value"><?= $alert_counts['Low Stock'] ?></div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon"><i class="fas fa-cart-plus"></i></div>
            <div class="summary-content">
                <div class="summary-label">Reorder</div>
                <div class="summary-value"><?= $alert_counts['Reorder'] ?></div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon"><i class="fas fa-hourglass-half"></i></div>
            <div class="summary-content">
                <div class="summary-label">Expiry</div>
                <div class="summary-value"><?= $alert_counts['Expiry'] ?></div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon"><i class="fas fa-skull-crossbones"></i></div>
            <div class="summary-content">
                <div class="summary-label">Expired</div>
                <div class="summary-value"><?= $alert_counts['Expired'] ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alerts Table or Empty State -->
    <?php if (empty($alerts)): ?>
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <p>No alerts at this time. All chemicals are within safe limits.</p>
        </div>
    <?php else: ?>
        <div class="table-card">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Chemical</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($alerts as $a): 
                    $badge_class = '';
                    $icon = '';
                    switch ($a['type']) {
                        case 'Low Stock':
                            $badge_class = 'badge-lowstock';
                            $icon = 'fa-exclamation-triangle';
                            break;
                        case 'Reorder':
                            $badge_class = 'badge-reorder';
                            $icon = 'fa-cart-plus';
                            break;
                        case 'Expiry':
                            $badge_class = 'badge-expiry';
                            $icon = 'fa-hourglass-half';
                            break;
                        case 'Expired':
                            $badge_class = 'badge-expired';
                            $icon = 'fa-skull-crossbones';
                            break;
                    }
                ?>
                    <tr>
                        <td>
                            <span class="alert-badge <?= $badge_class ?>">
                                <i class="fas <?= $icon ?>"></i> <?= $a['type'] ?>
                            </span>
                        </td>
                        <td><strong><?= htmlspecialchars($a['chemical']) ?></strong></td>
                        <td><?= htmlspecialchars($a['message']) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
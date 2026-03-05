<?php
// File: simulation/report.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$simulation_days = 30;
$report = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chems = $conn->query("SELECT id, chemical_id, current_stock, usage_rate, expiry_date FROM chemicals");
    while ($chem = $chems->fetch_assoc()) {
        $stock = $chem['current_stock'];
        $usage = $chem['usage_rate'];
        $expiry_ts = strtotime($chem['expiry_date']);
        $now = time();

        for ($day = 1; $day <= $simulation_days; $day++) {
            $current_day_ts = $now + ($day * 86400);
            $alert = '';

            // Stop if expired
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

            // Consume stock
            $stock -= $usage;
            if ($stock < 0) $stock = 0;

            // Check alerts
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
}

include '../includes/header.php';
?>

<!-- Modern Simulation Report Styles -->
<style>
    /* ==================== MODERN SIMULATION REPORT STYLES ==================== */
    :root {
        --primary: #4776E6;
        --primary-dark: #8E54E9;
        --success: #28a745;
        --warning: #ffc107;
        --danger: #dc3545;
        --info: #17a2b8;
    }

    .simulation-container {
        padding: 1rem 0;
    }

    /* Header */
    .simulation-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .simulation-header h2 {
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .simulation-header h2 i {
        background: linear-gradient(45deg, var(--primary), var(--primary-dark));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-size: 2rem;
    }

    /* Action buttons */
    .btn-run {
        background: linear-gradient(45deg, var(--warning), #fd7e14);
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
        box-shadow: 0 8px 15px -5px rgba(255, 193, 7, 0.4);
    }

    .btn-run:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 20px -5px rgba(255, 193, 7, 0.6);
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

    .summary-icon.warning { background: linear-gradient(45deg, #ffc107, #fd7e14); }
    .summary-icon.danger { background: linear-gradient(45deg, #dc3545, #c82333); }
    .summary-icon.info { background: linear-gradient(45deg, #17a2b8, #138496); }

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

    /* Search bar */
    .search-wrapper {
        background: rgba(255,255,255,0.8);
        backdrop-filter: blur(10px);
        border-radius: 50px;
        padding: 0.3rem 0.3rem 0.3rem 1.5rem;
        display: flex;
        align-items: center;
        border: 1px solid rgba(255,255,255,0.5);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        max-width: 400px;
        width: 100%;
        margin-bottom: 1.5rem;
    }

    .search-wrapper i {
        color: #888;
        font-size: 1.2rem;
    }

    .search-wrapper input {
        border: none;
        background: transparent;
        padding: 0.8rem 1rem;
        font-size: 1rem;
        width: 100%;
        outline: none;
        font-family: 'Poppins', sans-serif;
    }

    .search-wrapper input::placeholder {
        color: #aaa;
    }

    /* Table Card */
    .table-card {
        background: rgba(255,255,255,0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 30px;
        border: 1px solid rgba(255,255,255,0.5);
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        overflow: hidden;
        padding: 1.5rem;
    }

    .modern-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .modern-table thead tr {
        background: linear-gradient(45deg, #1e1e2f, #2a2a40);
        color: white;
    }

    .modern-table th {
        padding: 1rem 1.2rem;
        font-weight: 600;
        text-align: left;
    }

    .modern-table td {
        padding: 1rem 1.2rem;
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
        padding: 0.3rem 1rem;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.8rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .badge-lowstock { background: #fff3cd; color: #856404; }
    .badge-expired { background: #f8d7da; color: #721c24; }
    .badge-stockout { background: #f8d7da; color: #721c24; }
    .badge-ok { background: #d4edda; color: #155724; }

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
        color: #aaa;
        margin-bottom: 1rem;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .modern-table th, .modern-table td {
            padding: 0.75rem 0.5rem;
            font-size: 0.8rem;
        }
        .simulation-header {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<div class="simulation-container">
    <!-- Header with title and run button -->
    <div class="simulation-header">
        <h2>
            <i class="fas fa-chart-line"></i> Simulation Report (<?= $simulation_days ?> days)
        </h2>
        <form method="post" style="margin: 0;">
            <button type="submit" class="btn-run">
                <i class="fas fa-play"></i> Run Simulation
            </button>
        </form>
    </div>

    <?php if (!empty($report)): 
        // Calculate summary stats
        $total_entries = count($report);
        $low_stock_count = count(array_filter($report, fn($r) => $r['alert'] === 'LOW STOCK'));
        $expired_count = count(array_filter($report, fn($r) => $r['alert'] === 'EXPIRED'));
        $stockout_count = count(array_filter($report, fn($r) => $r['alert'] === 'STOCK OUT'));
    ?>

    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-icon"><i class="fas fa-list"></i></div>
            <div class="summary-content">
                <div class="summary-label">Total Entries</div>
                <div class="summary-value"><?= $total_entries ?></div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon warning"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="summary-content">
                <div class="summary-label">Low Stock Alerts</div>
                <div class="summary-value"><?= $low_stock_count ?></div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon danger"><i class="fas fa-skull-crossbones"></i></div>
            <div class="summary-content">
                <div class="summary-label">Expired</div>
                <div class="summary-value"><?= $expired_count ?></div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon danger"><i class="fas fa-times-circle"></i></div>
            <div class="summary-content">
                <div class="summary-label">Stock Outs</div>
                <div class="summary-value"><?= $stockout_count ?></div>
            </div>
        </div>
    </div>

    <!-- Search bar -->
    <div class="search-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Search by chemical or alert..." autocomplete="off">
    </div>

    <!-- Report Table Card -->
    <div class="table-card">
        <table class="modern-table" id="reportTable">
            <thead>
                <tr>
                    <th>Day</th>
                    <th>Chemical</th>
                    <th>Predicted Stock</th>
                    <th>Alert</th>
                </tr>
            </thead>
            <tbody id="reportBody">
                <?php foreach ($report as $r): 
                    $badge_class = '';
                    $icon = '';
                    if ($r['alert'] === 'LOW STOCK') {
                        $badge_class = 'badge-lowstock';
                        $icon = 'fa-exclamation-triangle';
                    } elseif ($r['alert'] === 'EXPIRED') {
                        $badge_class = 'badge-expired';
                        $icon = 'fa-skull-crossbones';
                    } elseif ($r['alert'] === 'STOCK OUT') {
                        $badge_class = 'badge-stockout';
                        $icon = 'fa-times-circle';
                    } else {
                        $badge_class = 'badge-ok';
                        $icon = 'fa-check-circle';
                    }
                ?>
                <tr>
                    <td><?= $r['day'] ?></td>
                    <td><strong><?= htmlspecialchars($r['chemical']) ?></strong></td>
                    <td><?= $r['stock'] ?></td>
                    <td>
                        <?php if ($r['alert']): ?>
                            <span class="alert-badge <?= $badge_class ?>">
                                <i class="fas <?= $icon ?>"></i> <?= $r['alert'] ?>
                            </span>
                        <?php else: ?>
                            <span class="alert-badge badge-ok"><i class="fas fa-check"></i> OK</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>
        <!-- Empty state when no simulation run -->
        <div class="empty-state">
            <i class="fas fa-chart-line"></i>
            <p>No simulation report yet. Click "Run Simulation" to generate data.</p>
        </div>
    <?php endif; ?>
</div>

<script>
    // Client-side search filter
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#reportBody tr');
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
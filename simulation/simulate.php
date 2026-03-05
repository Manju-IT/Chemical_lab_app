<?php
// File: simulation/simulate.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') die("Access denied");

$results = [];
$simulation_time = 30;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $chems = $conn->query("SELECT id, chemical_id, current_stock, usage_rate, expiry_date FROM chemicals");
    while ($chem = $chems->fetch_assoc()) {
        $stock = $chem['current_stock'];
        $usage = $chem['usage_rate'];
        $expiry = strtotime($chem['expiry_date']);
        $alerts = [];
        $daily = [];

        for ($day = 1; $day <= $simulation_time; $day++) {
            if ($stock <= 0) break;

            $current_day_time = time() + ($day * 86400);
            if ($expiry < $current_day_time) {
                $alerts[] = "Day $day: Expired – UNUSABLE";
                break;
            }

            $stock -= $usage;
            if ($stock < 0) $stock = 0;

            if ($stock <= $usage * 7) {
                $alerts[] = "Day $day: Low stock ($stock) – reorder advised";
            }

            $daily[] = ['day' => $day, 'stock' => max(0, $stock)];
        }

        $results[] = [
            'chemical' => $chem['chemical_id'],
            'daily' => $daily,
            'alerts' => $alerts
        ];
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="simulation-header">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
        <h2><i class="fas fa-chart-line me-2"></i>Simulation (30 days)</h2>
        <div>
            <a href="report.php" class="btn-sim" data-bs-toggle="tooltip" title="View detailed report">
                <i class="fas fa-file-alt"></i> View Report
            </a>
            <a href="export.php" class="btn-sim btn-success" data-bs-toggle="tooltip" title="Export as CSV">
                <i class="fas fa-file-csv"></i> Export Report
            </a>
        </div>
    </div>
    <form method="post" class="mt-3">
        <button type="submit" class="btn-run"><i class="fas fa-play"></i> Run Simulation</button>
    </form>
</div>

<?php if (!empty($results)): ?>
    <div class="results-grid">
        <?php foreach ($results as $res): ?>
            <div class="result-card">
                <div class="result-card-header">
                    <i class="fas fa-flask"></i>
                    <span><?= htmlspecialchars($res['chemical']) ?></span>
                </div>
                <div class="result-card-body">
                    <table class="table-sim">
                        <thead>
                            <tr><th>Day</th><th>Predicted Stock</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($res['daily'] as $d): ?>
                                <tr>
                                    <td><?= $d['day'] ?></td>
                                    <td><?= $d['stock'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php if (!empty($res['alerts'])): ?>
                        <div class="alert-sim">
                            <i class="fas fa-exclamation-circle"></i>
                            <div><?= implode('<br>', $res['alerts']) ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="empty-state">
        <i class="fas fa-chart-line"></i>
        <p>No simulation run yet. Click "Run Simulation" to start.</p>
    </div>
<?php endif; ?>

<style>
    .simulation-header {
        background: rgba(255,255,255,0.7);
        backdrop-filter: blur(10px);
        border-radius: 30px;
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border: 1px solid rgba(255,255,255,0.5);
    }

    .simulation-header h2 {
        font-weight: 600;
        color: #333;
    }

    .btn-sim {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(45deg, #4776E6, #8E54E9);
        color: white;
        padding: 10px 20px;
        border-radius: 50px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s;
        box-shadow: 0 5px 15px rgba(71, 118, 230, 0.3);
        border: none;
        margin: 0 5px;
    }

    .btn-sim.btn-success {
        background: linear-gradient(45deg, #28a745, #20c997);
        box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
    }

    .btn-sim:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(71, 118, 230, 0.4);
        color: white;
    }

    .btn-run {
        background: linear-gradient(45deg, #ffc107, #fd7e14);
        color: white;
        border: none;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s;
        box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-run:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(255, 193, 7, 0.4);
    }

    .results-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 1.5rem;
    }

    .result-card {
        background: rgba(255,255,255,0.8);
        backdrop-filter: blur(10px);
        border-radius: 25px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border: 1px solid rgba(255,255,255,0.5);
        transition: transform 0.3s;
    }

    .result-card:hover {
        transform: translateY(-5px);
    }

    .result-card-header {
        background: linear-gradient(45deg, #1e1e2f, #2a2a40);
        color: white;
        padding: 1rem 1.5rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .result-card-header i {
        font-size: 1.3rem;
        color: #a0a0ff;
    }

    .result-card-body {
        padding: 1.5rem;
    }

    .table-sim {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 1rem;
    }

    .table-sim th {
        background: #f0f4ff;
        font-weight: 600;
        color: #333;
        padding: 8px 10px;
        text-align: left;
    }

    .table-sim td {
        padding: 8px 10px;
        border-bottom: 1px solid #eee;
    }

    .table-sim tr:last-child td {
        border-bottom: none;
    }

    .alert-sim {
        background: rgba(255, 193, 7, 0.1);
        border-left: 5px solid #ffc107;
        border-radius: 15px;
        padding: 1rem;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        color: #856404;
        margin-top: 1rem;
    }

    .alert-sim i {
        font-size: 1.5rem;
        color: #ffc107;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: rgba(255,255,255,0.5);
        border-radius: 30px;
    }

    .empty-state i {
        font-size: 4rem;
        color: #c0c0c0;
        margin-bottom: 1rem;
    }

    .empty-state p {
        color: #666;
        font-size: 1.2rem;
    }
</style>

<?php include '../includes/footer.php'; ?>
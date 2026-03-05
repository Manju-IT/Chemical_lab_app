<?php
// File: dashboard/dashboard.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

$role = $_SESSION['role'];

// ==================== AGGREGATE COUNTS (Original) ====================
$total = $conn->query("SELECT COUNT(*) AS c FROM chemicals")->fetch_assoc()['c'];
$low = $conn->query("SELECT COUNT(*) AS c FROM chemicals WHERE current_stock <= usage_rate * 7")->fetch_assoc()['c'];
$expiring = $conn->query("SELECT COUNT(*) AS c FROM chemicals WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE()")->fetch_assoc()['c'];
$expired = $conn->query("SELECT COUNT(*) AS c FROM chemicals WHERE expiry_date < CURDATE()")->fetch_assoc()['c'];
$alerts_count = $low + $expiring + $expired;

// ==================== ADDITIONAL DATA FOR ENHANCED DASHBOARD ====================

// 1. Stock by Category (Original - for bar chart)
$cat_data = $conn->query("SELECT storage_category, SUM(current_stock) AS total FROM chemicals GROUP BY storage_category");
$categories = [];
$stocks = [];
while ($row = $cat_data->fetch_assoc()) {
    $categories[] = $row['storage_category'] ?: 'Uncategorized';
    $stocks[] = $row['total'];
}

// 2. Stock Status Distribution (for doughnut chart)
$normal = $total - ($low + $expiring + $expired); // approximate
$status_labels = ['Normal', 'Low Stock', 'Expiring Soon', 'Expired'];
$status_data = [$normal, $low, $expiring, $expired];
$status_colors = ['#28a745', '#ffc107', '#17a2b8', '#dc3545'];

// 3. Low Stock Details (for table)
$low_stock_items = $conn->query("
    SELECT chemical_id, current_stock, usage_rate, expiry_date 
    FROM chemicals 
    WHERE current_stock <= usage_rate * 7 
    ORDER BY current_stock ASC 
    LIMIT 10
");

// 4. Expiring Soon Details (for table)
$expiring_items = $conn->query("
    SELECT chemical_id, expiry_date, current_stock 
    FROM chemicals 
    WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE() 
    ORDER BY expiry_date ASC 
    LIMIT 10
");

// 5. Recent Audit Logs (if table exists)
$audit_logs = false;
$audit_check = $conn->query("SHOW TABLES LIKE 'audit_logs'");
if ($audit_check->num_rows > 0) {
    $audit_logs = $conn->query("
        SELECT user_id, action, timestamp 
        FROM audit_logs 
        ORDER BY timestamp DESC 
        LIMIT 5
    ");
}

// 6. Usage Trend Data (simulated from chemicals with usage_rate)
$usage_trend_labels = [];
$usage_trend_data = [];
$usage_result = $conn->query("SELECT chemical_id, usage_rate FROM chemicals LIMIT 7");
while ($row = $usage_result->fetch_assoc()) {
    $usage_trend_labels[] = $row['chemical_id'];
    $usage_trend_data[] = $row['usage_rate'];
}

// 7. Forecast Data (simple projection for next 7 days based on avg usage)
$forecast_labels = ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5', 'Day 6', 'Day 7'];
$forecast_data = [];
$avg_usage = $conn->query("SELECT AVG(usage_rate) AS avg FROM chemicals")->fetch_assoc()['avg'] ?: 0;
$current_total_stock = $total; // rough
for ($i = 1; $i <= 7; $i++) {
    $current_total_stock -= $avg_usage * 10; // arbitrary factor
    $forecast_data[] = max(0, round($current_total_stock, 2));
}

// 8. Alerts Timeline (combine low stock, expiring, expired into one list)
$alerts_timeline = [];
$low_res = $conn->query("SELECT chemical_id, 'Low Stock' as type, current_stock as value, NULL as expiry FROM chemicals WHERE current_stock <= usage_rate * 7 LIMIT 5");
while ($row = $low_res->fetch_assoc()) {
    $alerts_timeline[] = $row;
}
$expiring_res = $conn->query("SELECT chemical_id, 'Expiring Soon' as type, NULL as value, expiry_date as expiry FROM chemicals WHERE expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND expiry_date >= CURDATE() LIMIT 5");
while ($row = $expiring_res->fetch_assoc()) {
    $alerts_timeline[] = $row;
}
$expired_res = $conn->query("SELECT chemical_id, 'Expired' as type, NULL as value, expiry_date as expiry FROM chemicals WHERE expiry_date < CURDATE() LIMIT 5");
while ($row = $expired_res->fetch_assoc()) {
    $alerts_timeline[] = $row;
}
shuffle($alerts_timeline); // mix them up
$alerts_timeline = array_slice($alerts_timeline, 0, 8); // show 8 latest-ish

// 9. Quick Stats for admin
$total_users = 0;
if ($role === 'admin') {
    $user_res = $conn->query("SELECT COUNT(*) AS c FROM users");
    if ($user_res) $total_users = $user_res->fetch_assoc()['c'];
}

?>
<?php include '../includes/header.php'; ?>

<!-- Include ApexCharts for advanced charts (optional, but we'll use Chart.js for simplicity and add ApexCharts for gauge) -->
<!-- For a truly pro dashboard, we include both Chart.js and ApexCharts -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts/dist/apexcharts.css">
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Moment.js for date handling -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

<style>
    /* ==================== GLOBAL STYLES (consistent with previous redesigns) ==================== */
    :root {
        --primary-gradient: linear-gradient(45deg, #4776E6, #8E54E9);
        --success-gradient: linear-gradient(45deg, #28a745, #20c997);
        --warning-gradient: linear-gradient(45deg, #ffc107, #fd7e14);
        --danger-gradient: linear-gradient(45deg, #dc3545, #c82333);
        --info-gradient: linear-gradient(45deg, #17a2b8, #138496);
        --dark-gradient: linear-gradient(45deg, #1e1e2f, #2a2a40);
    }

    body {
        /* background: #f8fafc; */
        background: linear-gradient(135deg, #eef2f7, #e6ecf5);
        font-family: 'Poppins', sans-serif;
    }



    .kpi-container {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
    margin-bottom: 30px;
}

.kpi-card {
    padding: 28px;
    border-radius: 14px;
    color: white;
    position: relative;
    min-height: 140px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.kpi-value {
    font-size: 3rem;
    font-weight: 700;
}

.kpi-label {
    font-size: 14px;
    opacity: 0.9;
    margin-bottom: 8px;
}

.kpi-icon {
    position: absolute;
    right: 20px;
    bottom: 20px;
    font-size: 40px;
    opacity: 0.25;
}


@media (max-width: 1200px) {
    .kpi-container {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 600px) {
    .kpi-container {
        grid-template-columns: 1fr;
    }
}


    /* Dashboard Cards */
    .dashboard-card {
        /* background: rgba(255, 255, 255, 0.9);
         */
        background: #ffffff;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 18px;
        /* box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
         */
        box-shadow: 0 8px 20px rgba(0,0,0,0.08);
        border: 1px solid rgba(255, 255, 255, 0.5);
        transition: transform 0.3s, box-shadow 0.3s;
        overflow: visible;
    }

    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }

    .card-header-custom {
        padding: 1.2rem 1.5rem;
        /* background: rgba(255, 255, 255, 0.6); */
        background: #f7f9fc;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-header-custom i {
        font-size: 1.4rem;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* KPI Cards */
    .kpi-card {
        border-radius: 10px;
        padding: 2.5rem;
        width 150px;
        
        color: white;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        transition: all 0.3s;
         min-height: 150px;
        
    }

    .card-footer {
    background: transparent;
    padding: 12px 16px;
}

    .kpi-card::before {
        content: '';
        position: absolute;
        top: -20px;
        right: -20px;
        width: 120px;
        height: 120px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
        transition: all 0.5s;
    }

    .kpi-card:hover::before {
        transform: scale(1.5);
    }

    .kpi-card .kpi-icon {
        font-size: 2.5rem;
        opacity: 0.5;
        position: absolute;
        bottom: 10px;
        right: 15px;
    }

    .kpi-card .kpi-value {
        font-size: 2.5rem;
        font-weight: 700;
        line-height: 1.2;
    }

    .kpi-card .kpi-label {
        font-size: 1rem;
        opacity: 0.8;
        margin-bottom: 0.5rem;
    }

    .bg-primary-gradient { background: linear-gradient(45deg, #4776E6, #8E54E9); }
    .bg-success-gradient { background: linear-gradient(45deg, #28a745, #20c997); }
    .bg-warning-gradient { background: linear-gradient(45deg, #ffc107, #fd7e14); }
    .bg-info-gradient { background: linear-gradient(45deg, #17a2b8, #138496); }
    .bg-danger-gradient { background: linear-gradient(45deg, #dc3545, #c82333); }
    .bg-dark-gradient { background: linear-gradient(45deg, #1e1e2f, #2a2a40); }

    /* Tables */
    .modern-table {
        width: 100%;
        border-collapse: collapse;
    }

    .modern-table th {
        text-align: left;
        padding: 0.75rem 1rem;
        /* background: #f8f9fa; */
        background: #f3f6fb;
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
    }

    .modern-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e9ecef;
        color: #212529;
    }

    .modern-table tbody tr:hover {
        background: rgba(71, 118, 230, 0.05);
    }

    /* Badges */
    .badge-modern {
        padding: 0.4rem 0.8rem;
        border-radius: 10px;
        font-weight: 500;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }
/* 
    .badge-low { background: #fff3cd; color: #856404; }
    .badge-expiring { background: #d1ecf1; color: #0c5460; }
    .badge-expired { background: #f8d7da; color: #721c24; }
    .badge-normal { background: #d4edda; color: #155724; } */

    .badge-low { background: #fff4e5; color: #ff8c00; }
.badge-expiring { background: #e6f4ff; color: #1e88e5; }
.badge-expired { background: #ffe6e6; color: #e53935; }
.badge-normal { background: #e8f8f0; color: #2e7d32; }

    /* Alert timeline */
    .timeline-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 0.8rem 0;
        border-bottom: 1px solid #eee;
    }

    .timeline-item:last-child {
        border-bottom: none;
    }

    .timeline-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: white;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        
    }

    .timeline-content {
        flex: 1;
    }

    .timeline-content h6 {
        margin: 0;
        font-weight: 600;
    }

    
    .timeline-content small {
        color: #888;
    }

    /* Action buttons */
    .action-btn {
        border: none;
        background: rgba(255,255,255,0.2);
        backdrop-filter: blur(5px);
        border-radius: 50px;
        padding: 0.5rem 1.2rem;
        font-weight: 500;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        color: white;
        text-decoration: none;
    }

    .action-btn:hover {
        background: rgba(255,255,255,0.3);
        color: white;
        transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .kpi-card .kpi-value {
            font-size: 2rem;
        }
    }

    .row.g-4 > div {
    display: flex;
}

.dashboard-card {
    width: 100%;
    margin-bottom: 0;

}
.card-body {
    padding: 1rem 1.2rem;
}

.system-health div {
    font-size: 14px;
}

.system-health .progress {
    margin-top: 8px;
    margin-bottom: 6px;
    border-radius: 10px;
}

.system-health .progress-bar {
    border-radius: 10px;
}
.quick-actions .btn {
    border-radius: 10px;
    font-weight: 500;
}
</style>

<!-- ==================== DASHBOARD HEADER ==================== -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
    <h2 class="fw-bold"><i class="fas fa-tachometer-alt me-2" style="color: #4776E6;"></i>Dashboard</h2>
    <?php if ($role === 'admin'): ?>
        <div class="mt-2 mt-sm-0">
            <a href="analytics.php" class="btn btn-outline-primary me-2" data-bs-toggle="tooltip" title="View detailed analytics"><i class="fas fa-chart-bar"></i> Analytics</a>
            <a href="forecast.php" class="btn btn-outline-info" data-bs-toggle="tooltip" title="View depletion forecast"><i class="fas fa-calendar-alt"></i> Forecast</a>
        </div>
    <?php endif; ?>
</div>

<!-- ==================== KPI CARDS (Enhanced) ==================== -->
<!-- <div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="kpi-card bg-primary-gradient">
            <div class="kpi-label">Total Chemicals</div>
            <div class="kpi-value"><?= $total ?></div>
            <i class="fas fa-flask kpi-icon"></i>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="kpi-card bg-warning-gradient">
            <div class="kpi-label">Low Stock</div>
            <div class="kpi-value"><?= $low ?></div>
            <i class="fas fa-exclamation-triangle kpi-icon"></i>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="kpi-card bg-info-gradient">
            <div class="kpi-label">Expiring Soon</div>
            <div class="kpi-value"><?= $expiring ?></div>
            <i class="fas fa-hourglass-half kpi-icon"></i>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="kpi-card bg-danger-gradient">
            <div class="kpi-label">Expired</div>
            <div class="kpi-value"><?= $expired ?></div>
            <i class="fas fa-skull-crossbones kpi-icon"></i>
        </div>
    </div>
</div> -->

<div class="kpi-container">

    <div class="kpi-card bg-primary-gradient">
        <div class="kpi-label">Total Chemicals</div>
        <div class="kpi-value"><?= $total ?></div>
        <i class="fas fa-flask kpi-icon"></i>
    </div>

    <div class="kpi-card bg-warning-gradient">
        <div class="kpi-label">Low Stock</div>
        <div class="kpi-value"><?= $low ?></div>
        <i class="fas fa-exclamation-triangle kpi-icon"></i>
    </div>

    <div class="kpi-card bg-info-gradient">
        <div class="kpi-label">Expiring Soon</div>
        <div class="kpi-value"><?= $expiring ?></div>
        <i class="fas fa-hourglass-half kpi-icon"></i>
    </div>

    <div class="kpi-card bg-danger-gradient">
        <div class="kpi-label">Expired</div>
        <div class="kpi-value"><?= $expired ?></div>
        <i class="fas fa-skull-crossbones kpi-icon"></i>
    </div>

</div>

<!-- ==================== CHARTS ROW ==================== -->
<div class="row g-4 mb-4">
    <!-- Bar Chart: Stock by Category -->
    <div class="col-lg-6">
        <div class="dashboard-card p-3">
            <div class="card-header-custom">
                <i class="fas fa-chart-bar"></i> Stock by Category
            </div>
            <div class="card-body quick-actions">
                <canvas id="stockBarChart" height="350"></canvas>
            </div>
        </div>
    </div>
    <!-- Doughnut Chart: Stock Status Distribution -->
    <div class="col-lg-6">
        <div class="dashboard-card p-3">
            <div class="card-header-custom">
                <i class="fas fa-chart-pie"></i> Stock Status Distribution
            </div>
            <div class="card-body quick-actions">
                <canvas id="statusDoughnutChart" height="350"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ==================== SECOND ROW: FORECAST & USAGE TREND ==================== -->
<div class="row g-4 mb-4">
    <!-- Line Chart: Usage Trend (Top 7 chemicals) -->
    <div class="col-lg-6">
        <div class="dashboard-card p-3">
            <div class="card-header-custom">
                <i class="fas fa-chart-line"></i> Usage Rate (Top 7)
            </div>
            <div class="card-body quick-actions">
                <canvas id="usageLineChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <!-- Forecast Bar Chart -->
    <div class="col-lg-6">
        <div class="dashboard-card p-3">
            <div class="card-header-custom">
                <i class="fas fa-calendar-week"></i> Stock Forecast (Next 7 Days)
            </div>
            <div class="card-body quick-actions">
                <canvas id="forecastChart" height="300"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- ==================== TABLES AND ALERTS SECTION ==================== -->
<div class="row g-4 mb-4">
    <!-- Low Stock Table -->
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="card-header-custom">
                <i class="fas fa-exclamation-circle text-warning"></i> Low Stock Items
            </div>
            <div class="card-body p-0 quick-actions">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Chemical</th>
                            <th>Stock</th>
                            <th>Usage Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($low_stock_items->num_rows > 0): ?>
                            <?php while ($item = $low_stock_items->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['chemical_id']) ?></td>
                                    <td><span class="badge-modern badge-low"><?= $item['current_stock'] ?></span></td>
                                    <td><?= $item['usage_rate'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-3">No low stock items</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-transparent text-end">
                <a href="/chemical_inventory/alerts/alerts.php?type=low" class="btn btn-sm btn-outline-warning">View All</a>
            </div>
        </div>
    </div>

    <!-- Expiring Soon Table -->
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="card-header-custom">
                <i class="fas fa-hourglass-half text-info"></i> Expiring Soon
            </div>
            <div class="card-body p-0 quick-actions">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Chemical</th>
                            <th>Expiry Date</th>
                            <th>Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($expiring_items->num_rows > 0): ?>
                            <?php while ($item = $expiring_items->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['chemical_id']) ?></td>
                                    <td><span class="badge-modern badge-expiring"><?= $item['expiry_date'] ?></span></td>
                                    <td><?= $item['current_stock'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-3">No items expiring soon</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-transparent text-end">
                <a href="/chemical_inventory/alerts/alerts.php?type=expiring" class="btn btn-sm btn-outline-info">View All</a>
            </div>
        </div>
    </div>

    <!-- Alerts Timeline -->
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="card-header-custom">
                <i class="fas fa-bell text-danger"></i> Recent Alerts
            </div>
            <div class="card-body quick-actions">
                <?php if (!empty($alerts_timeline)): ?>
                    <?php foreach ($alerts_timeline as $alert): ?>
                        <div class="timeline-item">
                            <div class="timeline-icon <?= $alert['type'] == 'Low Stock' ? 'bg-warning' : ($alert['type'] == 'Expiring Soon' ? 'bg-info' : 'bg-danger') ?>">
                                <i class="fas fa-<?= $alert['type'] == 'Low Stock' ? 'exclamation' : ($alert['type'] == 'Expiring Soon' ? 'clock' : 'skull') ?>"></i>
                            </div>
                            <div class="timeline-content">
                                <h6><?= htmlspecialchars($alert['chemical_id']) ?></h6>
                                <small>
                                    <?= $alert['type'] ?>
                                    <?php if ($alert['type'] == 'Low Stock'): ?> - Stock: <?= $alert['value'] ?><?php endif; ?>
                                    <?php if (isset($alert['expiry'])): ?> - <?= $alert['expiry'] ?><?php endif; ?>
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-center text-muted py-3">No recent alerts</p>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent text-end">
                <a href="/chemical_inventory/alerts/alerts.php" class="btn btn-sm btn-outline-danger">View All Alerts</a>
            </div>
        </div>
    </div>
</div>

<!-- ==================== BOTTOM ROW: AUDIT LOGS & QUICK ACTIONS ==================== -->
<div class="row g-4">
    <!-- Recent Audit Logs -->
    <div class="col-lg-8">
        <div class="dashboard-card">
            <div class="card-header-custom">
                <i class="fas fa-history"></i> Recent Activities
            </div>
            <div class="card-body p-0 quick-actions">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($audit_logs && $audit_logs->num_rows > 0): ?>
                            <?php while ($log = $audit_logs->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['user_id']) ?></td>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                    <td><small><?= date('Y-m-d H:i', strtotime($log['timestamp'])) ?></small></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-3">No audit logs available</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($role === 'admin'): ?>
            <div class="card-footer bg-transparent text-end">
                <a href="/chemical_inventory/audit/logs.php" class="btn btn-sm btn-outline-secondary">View Full Logs</a>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Quick Actions & Stats -->
    <div class="col-lg-4">
        <div class="dashboard-card">
            <div class="card-header-custom">
                <i class="fas fa-bolt"></i> Quick Actions
            </div>
            <div class="card-body quick-actions">
                <div class="d-grid gap-3">
                    <a href="/chemical_inventory/alerts/alerts.php" class="btn btn-warning text-start">
                        <i class="fas fa-exclamation-triangle me-2"></i> View Alerts (<?= $alerts_count ?>)
                    </a>
                    <?php if ($role === 'admin'): ?>
                        <a href="/chemical_inventory/csv/upload.php" class="btn btn-success text-start">
                            <i class="fas fa-upload me-2"></i> Upload CSV
                        </a>
                        <a href="/chemical_inventory/simulation/simulate.php" class="btn btn-info text-start">
                            <i class="fas fa-play me-2"></i> Run Simulation
                        </a>
                        <a href="/chemical_inventory/audit/logs.php" class="btn btn-secondary text-start">
                            <i class="fas fa-history me-2"></i> Audit Logs
                        </a>
                        <a href="/chemical_inventory/auth/register_user.php" class="btn btn-primary text-start">
                            <i class="fas fa-user-plus me-2"></i> Register User (<?= $total_users ?> total)
                        </a>
                    <?php else: ?>
                        <a href="/chemical_inventory/inventory/list.php" class="btn btn-primary text-start">
                            <i class="fas fa-cubes me-2"></i> View Inventory
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($role === 'admin'): ?>
            <div class="card-footer bg-transparent">
                <small class="text-muted"><i class="fas fa-users me-1"></i> Total Users: <?= $total_users ?></small>
            </div>
            <?php endif; ?>
        </div>

        
    </div>
</div>

<!-- ==================== CHART INITIALIZATION SCRIPTS ==================== -->
<script>
    // 1. Bar Chart - Stock by Category
    const barCtx = document.getElementById('stockBarChart').getContext('2d');
    new Chart(barCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($categories) ?>,
            datasets: [{
                label: 'Current Stock',
                data: <?= json_encode($stocks) ?>,
                backgroundColor: 'rgba(71, 118, 230, 0.6)',
                borderColor: '#4776E6',
                borderWidth: 1,
                borderRadius: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { drawBorder: false } },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Doughnut Chart - Stock Status
    const doughnutCtx = document.getElementById('statusDoughnutChart').getContext('2d');
    new Chart(doughnutCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($status_labels) ?>,
            datasets: [{
                data: <?= json_encode($status_data) ?>,
                backgroundColor: <?= json_encode($status_colors) ?>,
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            cutout: '65%',
        }
    });

    // 3. Line Chart - Usage Trend
    const lineCtx = document.getElementById('usageLineChart').getContext('2d');
    new Chart(lineCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode($usage_trend_labels) ?>,
            datasets: [{
                label: 'Usage Rate',
                data: <?= json_encode($usage_trend_data) ?>,
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#28a745',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { drawBorder: false } },
                x: { grid: { display: false } }
            }
        }
    });

    // 4. Forecast Bar Chart
    const forecastCtx = document.getElementById('forecastChart').getContext('2d');
    new Chart(forecastCtx, {
        type: 'line', // line chart for forecast
        data: {
            labels: <?= json_encode($forecast_labels) ?>,
            datasets: [{
                label: 'Projected Stock',
                data: <?= json_encode($forecast_data) ?>,
                borderColor: '#fd7e14',
                backgroundColor: 'rgba(253, 126, 20, 0.1)',
                tension: 0.4,
                fill: true,
                pointBackgroundColor: '#fd7e14',
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, grid: { drawBorder: false } },
                x: { grid: { display: false } }
            }
        }
    });

    // Optional: Add a gauge chart using ApexCharts for a pro touch
    // (We'll add a small gauge for storage usage)
    var gaugeOptions = {
        series: [75],
        chart: {
            type: 'radialBar',
            height: 200,
            sparkline: { enabled: true },
        },
        plotOptions: {
            radialBar: {
                startAngle: -90,
                endAngle: 90,
                track: { background: "#e7e7e7", strokeWidth: '97%' },
                dataLabels: {
                    name: { show: false },
                    value: { fontSize: '16px', fontWeight: 600, offsetY: -5 }
                }
            }
        },
        fill: { colors: ['#28a745'] },
        labels: ['Storage']
    };
    if (document.getElementById("storageGauge")) {
        new ApexCharts(document.querySelector("#storageGauge"), gaugeOptions).render();
    }
</script>

<!-- Add a container for the gauge if you want, but we already have progress bar. We'll keep progress bar. -->

<?php include '../includes/footer.php'; ?>
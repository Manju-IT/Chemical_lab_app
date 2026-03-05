<?php
// File: dashboard/analytics.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// ==================== FETCH DATA FROM CHEMICALS TABLE ====================

// 1. Stock by Category (original)
$cat_data = $conn->query("SELECT storage_category, SUM(current_stock) AS total FROM chemicals GROUP BY storage_category");
$categories = [];
$stocks = [];
while ($row = $cat_data->fetch_assoc()) {
    $categories[] = $row['storage_category'] ?: 'Uncategorized';
    $stocks[] = $row['total'];
}

// 2. Top 5 Chemicals by Usage Rate (original)
$top_usage = $conn->query("SELECT chemical_id, name, usage_rate FROM chemicals ORDER BY usage_rate DESC LIMIT 5");
$top_names = [];
$top_rates = [];
while ($row = $top_usage->fetch_assoc()) {
    $top_names[] = $row['name'] ?: $row['chemical_id'];
    $top_rates[] = $row['usage_rate'];
}

// 3. Expiring Soon (next 90 days, grouped by month) (original)
$expiry_data = $conn->query("
    SELECT DATE_FORMAT(expiry_date, '%Y-%m') as month, COUNT(*) as count 
    FROM chemicals 
    WHERE expiry_date >= CURDATE() AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY) 
    GROUP BY month 
    ORDER BY month
");
$months = [];
$exp_counts = [];
while ($row = $expiry_data->fetch_assoc()) {
    $months[] = $row['month'];
    $exp_counts[] = $row['count'];
}

// 4. Additional Analytics Data
// Total chemicals
$total_chemicals = $conn->query("SELECT COUNT(*) AS c FROM chemicals")->fetch_assoc()['c'];

// Low stock count (current_stock <= usage_rate * 7)
$low_stock_count = $conn->query("SELECT COUNT(*) AS c FROM chemicals WHERE current_stock <= usage_rate * 7")->fetch_assoc()['c'];

// Expired count
$expired_count = $conn->query("SELECT COUNT(*) AS c FROM chemicals WHERE expiry_date < CURDATE()")->fetch_assoc()['c'];

// Average usage rate
$avg_usage = $conn->query("SELECT AVG(usage_rate) AS avg FROM chemicals")->fetch_assoc()['avg'];

// Stock distribution by category (for pie chart)
$cat_pie_data = $conn->query("SELECT storage_category, SUM(current_stock) AS total FROM chemicals GROUP BY storage_category");
$pie_categories = [];
$pie_stocks = [];
$pie_colors = ['#4776E6', '#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d', '#6610f2'];
$color_index = 0;
while ($row = $cat_pie_data->fetch_assoc()) {
    $pie_categories[] = $row['storage_category'] ?: 'Uncategorized';
    $pie_stocks[] = $row['total'];
}

// 5. Data from other tables (with existence checks)
// Audit logs count
$audit_logs_count = 0;
$audit_check = $conn->query("SHOW TABLES LIKE 'audit_logs'");
if ($audit_check->num_rows > 0) {
    $audit_res = $conn->query("SELECT COUNT(*) AS c FROM audit_logs");
    if ($audit_res) $audit_logs_count = $audit_res->fetch_assoc()['c'];
}

// Rejected records count
$rejected_count = 0;
$reject_check = $conn->query("SHOW TABLES LIKE 'rejected_records'");
if ($reject_check->num_rows > 0) {
    $reject_res = $conn->query("SELECT COUNT(*) AS c FROM rejected_records");
    if ($reject_res) $rejected_count = $reject_res->fetch_assoc()['c'];
}

// Error logs count
$error_logs_count = 0;
$error_check = $conn->query("SHOW TABLES LIKE 'error_logs'");
if ($error_check->num_rows > 0) {
    $error_res = $conn->query("SELECT COUNT(*) AS c FROM error_logs");
    if ($error_res) $error_logs_count = $error_res->fetch_assoc()['c'];
}

// 6. Recent audit logs (if table exists)
$recent_audits = [];
if ($audit_check->num_rows > 0) {
    $audit_recent = $conn->query("
        SELECT user_id, action, created_at 
        FROM audit_logs 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    if ($audit_recent) {
        while ($row = $audit_recent->fetch_assoc()) {
            $recent_audits[] = $row;
        }
    }
}

// 7. Low stock items for table
$low_stock_items = $conn->query("
    SELECT chemical_id, name, current_stock, usage_rate 
    FROM chemicals 
    WHERE current_stock <= usage_rate * 7 
    ORDER BY current_stock ASC 
    LIMIT 5
");

// 8. Recently expired items
$recent_expired = $conn->query("
    SELECT chemical_id, name, expiry_date 
    FROM chemicals 
    WHERE expiry_date < CURDATE() 
    ORDER BY expiry_date DESC 
    LIMIT 5
");

?>
<?php include '../includes/header.php'; ?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- Font Awesome (already in header) -->
<!-- Google Fonts Poppins (already in header) -->

<style>
    /* ==================== MODERN ANALYTICS STYLES ==================== */
    :root {
        --primary: #4776E6;
        --primary-dark: #8E54E9;
        --success: #28a745;
        --warning: #ffc107;
        --danger: #dc3545;
        --info: #17a2b8;
        --dark: #1e1e2f;
    }

    .analytics-container {
        padding: 1rem 0;
    }

    /* KPI Cards */
    .kpi-grid {
        display: grid;
        /* grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); */
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }
    .chart-card table{
    width:100%;
}

    .kpi-card-analytics {
        background: rgba(255,255,255,0.8);
        backdrop-filter: blur(10px);
        border-radius: 25px;
        padding: 1.5rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border: 1px solid rgba(255,255,255,0.5);
        transition: transform 0.3s, box-shadow 0.3s;
        position: relative;
        overflow: hidden;
    }

    .kpi-card-analytics:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }

    .kpi-card-analytics::after {
        content: '';
        position: absolute;
        top: -20px;
        right: -20px;
        width: 100px;
        height: 100px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        transition: all 0.5s;
    }

    .kpi-card-analytics:hover::after {
        transform: scale(1.5);
    }

    .kpi-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        background: linear-gradient(45deg, var(--primary), var(--primary-dark));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .kpi-value {
        font-size: 2.2rem;
        font-weight: 700;
        color: #333;
        line-height: 1.2;
    }

    .kpi-label {
        font-size: 0.9rem;
        color: #777;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Chart Cards */
    .chart-card {
        background: rgba(255,255,255,0.8);
        backdrop-filter: blur(10px);
        border-radius: 30px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border: 1px solid rgba(255,255,255,0.5);
        transition: transform 0.3s;
    }

    .chart-card:hover {
        transform: translateY(-5px);
    }

    .chart-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 1.5rem;
        font-weight: 600;
        color: #333;
        border-bottom: 1px solid #eee;
        padding-bottom: 0.8rem;
    }

    .chart-header i {
        font-size: 1.5rem;
        background: linear-gradient(45deg, var(--primary), var(--primary-dark));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    /* Tables */
    .modern-table {
        width: 100%;
        border-collapse: collapse;
    }

    .modern-table th {
        text-align: left;
        padding: 1rem;
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
    }

    .modern-table td {
        padding: 1rem;
        border-bottom: 1px solid #e9ecef;
        color: #212529;
    }

    .modern-table tbody tr:hover {
        background: rgba(71, 118, 230, 0.05);
    }

    .badge {
        padding: 0.3rem 0.8rem;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.75rem;
    }

    .badge-success { background: #d4edda; color: #155724; }
    .badge-warning { background: #fff3cd; color: #856404; }
    .badge-danger { background: #f8d7da; color: #721c24; }
    .badge-info { background: #d1ecf1; color: #0c5460; }

    /* Responsive */
    @media (max-width: 768px) {
        .kpi-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    .chart-wrapper{
    position: relative;
    height: 320px;
    width: 100%;
}

</style>

<div class="analytics-container">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h2 class="fw-bold"><i class="fas fa-chart-pie me-2" style="color: #4776E6;"></i>Analytics Dashboard</h2>
        <div class="mt-2 mt-sm-0">
            <a href="dashboard.php" class="btn btn-outline-secondary me-2"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
            <a href="forecast.php" class="btn btn-outline-info"><i class="fas fa-calendar-alt"></i> Forecast</a>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi-card-analytics">
            <div class="kpi-icon"><i class="fas fa-flask"></i></div>
            <div class="kpi-value"><?= $total_chemicals ?></div>
            <div class="kpi-label">Total Chemicals</div>
        </div>
        <div class="kpi-card-analytics">
            <div class="kpi-icon"><i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i></div>
            <div class="kpi-value"><?= $low_stock_count ?></div>
            <div class="kpi-label">Low Stock Items</div>
        </div>
        <div class="kpi-card-analytics">
            <div class="kpi-icon"><i class="fas fa-hourglass-half" style="color: #17a2b8;"></i></div>
            <div class="kpi-value"><?= array_sum($exp_counts) ?></div>
            <div class="kpi-label">Expiring in 90 Days</div>
        </div>
        <div class="kpi-card-analytics">
            <div class="kpi-icon"><i class="fas fa-skull-crossbones" style="color: #dc3545;"></i></div>
            <div class="kpi-value"><?= $expired_count ?></div>
            <div class="kpi-label">Expired</div>
        </div>
        <div class="kpi-card-analytics">
            <div class="kpi-icon"><i class="fas fa-chart-line" style="color: #28a745;"></i></div>
            <div class="kpi-value"><?= round($avg_usage, 2) ?></div>
            <div class="kpi-label">Avg Usage Rate</div>
        </div>
        <div class="kpi-card-analytics">
            <div class="kpi-icon"><i class="fas fa-history"></i></div>
            <div class="kpi-value"><?= $audit_logs_count ?></div>
            <div class="kpi-label">Audit Logs</div>
        </div>
        <div class="kpi-card-analytics">
            <div class="kpi-icon"><i class="fas fa-file-excel" style="color: #fd7e14;"></i></div>
            <div class="kpi-value"><?= $rejected_count ?></div>
            <div class="kpi-label">Rejected CSV</div>
        </div>
        <div class="kpi-card-analytics">
            <div class="kpi-icon"><i class="fas fa-bug" style="color: #6c757d;"></i></div>
            <div class="kpi-value"><?= $error_logs_count ?></div>
            <div class="kpi-label">Error Logs</div>
        </div>
    </div>

    <!-- First Row: Bar Charts -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-chart-bar"></i> Stock by Category
                </div>
                <div class="chart-wrapper">
                <canvas id="catChart" ></canvas>
</div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-chart-bar" style="color: #ffc107;"></i> Top 5 Chemicals by Usage Rate
                </div>
                <div class="chart-wrapper">
                <canvas id="usageChart" ></canvas>
</div>
            </div>
        </div>
    </div>

    <!-- Second Row: Expiry Line Chart and Pie Chart -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-chart-line" style="color: #dc3545;"></i> Expiring Soon (Next 90 Days)
                </div>
                <div class="chart-wrapper">
                <canvas id="expiryChart"></canvas>
</div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-chart-pie" style="color: #28a745;"></i> Stock Distribution by Category
                </div>
                <div class="chart-wrapper">
                <canvas id="pieChart" ></canvas>
</div>
            </div>
        </div>
    </div>

    <!-- Third Row: Tables -->
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i> Low Stock Items
                </div>
                <table class="modern-table">
                    <thead>
                        <tr><th>Chemical</th><th>Stock</th><th>Usage Rate</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($low_stock_items->num_rows > 0): ?>
                            <?php while ($item = $low_stock_items->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['name'] ?: $item['chemical_id']) ?></td>
                                    <td><span class="badge badge-warning"><?= $item['current_stock'] ?></span></td>
                                    <td><?= $item['usage_rate'] ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-3">No low stock items</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-clock" style="color: #17a2b8;"></i> Recently Expired
                </div>
                <table class="modern-table">
                    <thead>
                        <tr><th>Chemical</th><th>Expiry Date</th></tr>
                    </thead>
                    <tbody>
                        <?php if ($recent_expired->num_rows > 0): ?>
                            <?php while ($item = $recent_expired->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['name'] ?: $item['chemical_id']) ?></td>
                                    <td><span class="badge badge-danger"><?= $item['expiry_date'] ?></span></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="2" class="text-center py-3">No expired items</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-card">
                <div class="chart-header">
                    <i class="fas fa-history"></i> Recent Audit Logs
                </div>
                <table class="modern-table">
                    <thead>
                        <tr><th>User</th><th>Action</th><th>Time</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_audits)): ?>
                            <?php foreach ($recent_audits as $log): ?>
                                <tr>
                                    <td><?= htmlspecialchars($log['user_id']) ?></td>
                                    <td><?= htmlspecialchars($log['action']) ?></td>
                                    <td><small><?= date('H:i d/m', strtotime($log['created_at'])) ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center py-3">No audit logs</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Optional: Summary Card -->
    <div class="chart-card">
        <div class="chart-header">
            <i class="fas fa-clipboard-list"></i> System Summary
        </div>
        <div class="row">
            <div class="col-md-3 col-6 mb-3">
                <div class="fw-bold">Total Categories</div>
                <div class="h4"><?= count($categories) ?></div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="fw-bold">Total Batches</div>
                <div class="h4"><?= $total_chemicals ?></div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="fw-bold">Max Usage Rate</div>
                <div class="h4"><?= max($top_rates) ?? 0 ?></div>
            </div>
            <div class="col-md-3 col-6 mb-3">
                <div class="fw-bold">Database Size</div>
                <div class="h4"><?= round(($total_chemicals * 0.5 + $audit_logs_count * 0.1 + $error_logs_count * 0.05), 2) ?> KB</div>
            </div>
        </div>
    </div>
</div>

<script>
    // Chart 1: Stock by Category (Bar)
    new Chart(document.getElementById('catChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($categories) ?>,
            datasets: [{
                label: 'Total Stock',
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
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { drawBorder: false } } }
        }
    });

    // Chart 2: Top Usage Rate (Bar)
    new Chart(document.getElementById('usageChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($top_names) ?>,
            datasets: [{
                label: 'Daily Usage Rate',
                data: <?= json_encode($top_rates) ?>,
                backgroundColor: 'rgba(255, 193, 7, 0.6)',
                borderColor: '#ffc107',
                borderWidth: 1,
                borderRadius: 5,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { drawBorder: false } } }
        }
    });

    // Chart 3: Expiring Soon (Line)
    new Chart(document.getElementById('expiryChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($months) ?>,
            datasets: [{
                label: 'Number Expiring',
                data: <?= json_encode($exp_counts) ?>,
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.3,
                fill: true,
                pointBackgroundColor: '#dc3545',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { drawBorder: false } } }
        }
    });

    // Chart 4: Pie Chart - Stock Distribution by Category
    new Chart(document.getElementById('pieChart'), {
        type: 'pie',
        data: {
            labels: <?= json_encode($pie_categories) ?>,
            datasets: [{
                data: <?= json_encode($pie_stocks) ?>,
                backgroundColor: <?= json_encode(array_slice($pie_colors, 0, count($pie_categories))) ?>,
                borderWidth: 0,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
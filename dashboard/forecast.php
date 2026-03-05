<?php
// File: dashboard/forecast.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

$chems = $conn->query("SELECT chemical_id, name, current_stock, usage_rate FROM chemicals WHERE usage_rate > 0");

$forecast = [];
while ($row = $chems->fetch_assoc()) {
    $stock = $row['current_stock'];
    $rate = $row['usage_rate'];
    if ($rate <= 0) continue;
    $days = floor($stock / $rate);
    $depletion_date = date('Y-m-d', strtotime("+$days days"));
    $forecast[] = [
        'chemical' => $row['chemical_id'],
        'name' => $row['name'],
        'stock' => $stock,
        'rate' => $rate,
        'days' => $days,
        'depletion' => $depletion_date
    ];
}

// Calculate summary stats
$total_chemicals = count($forecast);
$avg_days = $total_chemicals > 0 ? round(array_sum(array_column($forecast, 'days')) / $total_chemicals, 1) : 0;
$critical_count = count(array_filter($forecast, fn($f) => $f['days'] <= 7));
$warning_count = count(array_filter($forecast, fn($f) => $f['days'] > 7 && $f['days'] <= 30));
$ok_count = $total_chemicals - $critical_count - $warning_count;

// Histogram buckets
$buckets = [
    "0-7" => 0,
    "8-30" => 0,
    "31-60" => 0,
    "61-90" => 0,
    "90+" => 0
];

foreach ($forecast as $f) {
    $d = $f['days'];

    if ($d <= 7) $buckets["0-7"]++;
    elseif ($d <= 30) $buckets["8-30"]++;
    elseif ($d <= 60) $buckets["31-60"]++;
    elseif ($d <= 90) $buckets["61-90"]++;
    else $buckets["90+"]++;
}


include '../includes/header.php';
?>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* ==================== MODERN FORECAST STYLES ==================== */
    :root {
        --primary: #4776E6;
        --primary-dark: #8E54E9;
        --success: #28a745;
        --warning: #ffc107;
        --danger: #dc3545;
        --info: #17a2b8;
    }

    .forecast-container {
        padding: 1rem 0;
    }

    /* Header */
    .forecast-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .forecast-header h2 {
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .forecast-header h2 i {
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

    /* Chart Card */
    /* .chart-card {
        background: rgba(255,255,255,0.7);
        backdrop-filter: blur(10px);
        border-radius: 30px;
        border: 1px solid rgba(255,255,255,0.5);
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    } */


.chart-card{
background:rgba(255,255,255,0.7);
backdrop-filter:blur(10px);
border-radius:30px;
border:1px solid rgba(255,255,255,0.5);
padding:1.5rem;
margin-bottom:2rem;
box-shadow:0 10px 30px rgba(0,0,0,0.05);
height:380px;
display:flex;
flex-direction:column;
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

    /* Status badges */
    .status-badge {
        padding: 0.4rem 1rem;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .badge-critical { background: #f8d7da; color: #721c24; }
    .badge-warning { background: #fff3cd; color: #856404; }
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
            padding: 0.75rem;
        }
        .summary-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    .chart-wrapper{
position:relative;
height:300px;
width:100%;
max-height:320px;
}

.row.g-4{
margin-bottom:20px;
}

.chart-card{
height:100%;
display:flex;
flex-direction:column;
}


</style>

<div class="forecast-container">
    <!-- Header -->
    <div class="forecast-header">
        <h2>
            <i class="fas fa-calendar-alt"></i> Stock Depletion Forecast
        </h2>
        <span class="badge bg-secondary" style="font-size: 1rem; padding: 0.5rem 1.2rem;">
            <?= $total_chemicals ?> Chemicals
        </span>
    </div>

    <?php if (empty($forecast)): ?>
        <div class="empty-state">
            <i class="fas fa-chart-line"></i>
            <p>No forecast data available. Ensure chemicals have usage rate > 0.</p>
        </div>
    <?php else: ?>

    <!-- Summary Cards -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="summary-icon"><i class="fas fa-clock"></i></div>
            <div class="summary-content">
                <div class="summary-label">Avg Days Remaining</div>
                <div class="summary-value"><?= $avg_days ?></div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon"><i class="fas fa-exclamation-triangle text-danger"></i></div>
            <div class="summary-content">
                <div class="summary-label">Critical (≤7 days)</div>
                <div class="summary-value"><?= $critical_count ?></div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon"><i class="fas fa-hourglass-half" style="background: linear-gradient(45deg, #ffc107, #fd7e14);"></i></div>
            <div class="summary-content">
                <div class="summary-label">Warning (8-30 days)</div>
                <div class="summary-value"><?= $warning_count ?></div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon"><i class="fas fa-check-circle" style="background: linear-gradient(45deg, #28a745, #20c997);"></i></div>
            <div class="summary-content">
                <div class="summary-label">OK (>30 days)</div>
                <div class="summary-value"><?= $ok_count ?></div>
            </div>
        </div>
    </div>

    <!-- Bar Chart: Days Remaining per Chemical -->
    <!-- <div class="chart-card">
        <div class="chart-header">
            <i class="fas fa-chart-bar"></i> Days Remaining by Chemical
        </div>
        <div class="chart-wrapper">
        <canvas id="forecastChart" ></canvas>
    </div>
    </div> -->

    <div class="row g-4">

<!-- Risk Distribution -->
<div class="col-lg-6">
<div class="chart-card">
<div class="chart-header">
<i class="fas fa-exclamation-circle"></i> Risk Distribution
</div>

<div class="chart-wrapper">
<canvas id="riskChart"></canvas>
</div>

</div>
</div>

<!-- Days Remaining Histogram -->
<div class="col-lg-6">
<div class="chart-card">
<div class="chart-header">
<i class="fas fa-chart-area"></i> Days Remaining Distribution
</div>

<div class="chart-wrapper">
<canvas id="daysHistogram"></canvas>
</div>

</div>
</div>

</div>


    <!-- Forecast Table -->
    <div class="table-card">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Chemical ID</th>
                    <th>Name</th>
                    <th>Stock</th>
                    <th>Usage Rate</th>
                    <th>Days Remaining</th>
                    <th>Depletion Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($forecast as $f): 
                    $days = $f['days'];
                    if ($days <= 7) {
                        $badge_class = 'badge-critical';
                        $icon = 'fa-exclamation-triangle';
                        $status = 'Critical';
                    } elseif ($days <= 30) {
                        $badge_class = 'badge-warning';
                        $icon = 'fa-hourglass-half';
                        $status = 'Warning';
                    } else {
                        $badge_class = 'badge-ok';
                        $icon = 'fa-check-circle';
                        $status = 'OK';
                    }
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($f['chemical']) ?></strong></td>
                    <td><?= htmlspecialchars($f['name']) ?></td>
                    <td><?= $f['stock'] ?></td>
                    <td><?= $f['rate'] ?>/day</td>
                    <td><?= $f['days'] ?></td>
                    <td><?= $f['depletion'] ?></td>
                    <td>
                        <span class="status-badge <?= $badge_class ?>">
                            <i class="fas <?= $icon ?>"></i> <?= $status ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Chart Script -->
    <script>
        

// Risk Distribution Doughnut
new Chart(document.getElementById('riskChart'),{
type:'doughnut',
data:{
labels:['Critical','Warning','OK'],
datasets:[{
data:[<?= $critical_count ?>,<?= $warning_count ?>,<?= $ok_count ?>],
backgroundColor:['#dc3545','#ffc107','#28a745']
}]
},
options:{
responsive:true,
maintainAspectRatio:false,
animation:false,
plugins:{
legend:{
position:'bottom',
labels:{
boxWidth:12,
padding:15
}
}
},
plugins:{legend:{position:'bottom'}}
}
});


// Days Remaining Histogram
new Chart(document.getElementById('daysHistogram'),{
type:'bar',
data:{
labels:<?= json_encode(array_keys($buckets)) ?>,
datasets:[{
label:'Chemicals',
data:<?= json_encode(array_values($buckets)) ?>,
backgroundColor:[
'#dc3545',
'#ffc107',
'#17a2b8',
'#28a745',
'#6c757d'
],
borderRadius:6
}]
},
options:{
responsive:true,
maintainAspectRatio:false,
animation:false,
plugins:{legend:{display:false}},
scales:{
y:{beginAtZero:true},
x:{grid:{display:false}},
},
plugins:{
legend:{
position:'bottom',
labels:{
boxWidth:12,
padding:15
}
}
}

}
});

</script>
    

    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
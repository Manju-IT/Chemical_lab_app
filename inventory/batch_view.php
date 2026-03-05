<?php
// File: inventory/batch_view.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Group by chemical_id (or by name? Use chemical_id as unique identifier)
$result = $conn->query("SELECT chemical_id, name, batch_id, current_stock, expiry_date, usage_rate FROM chemicals ORDER BY chemical_id, expiry_date");

$batches = [];
while ($row = $result->fetch_assoc()) {
    $batches[$row['chemical_id']][] = $row;
}

include '../includes/header.php';
?>

<!-- Add Font Awesome (already in header) and Poppins -->
<style>
    /* ==================== MODERN BATCH VIEW STYLES ==================== */
    :root {
        --primary: #4776E6;
        --primary-dark: #8E54E9;
        --success: #28a745;
        --warning: #ffc107;
        --danger: #dc3545;
        --info: #17a2b8;
        --dark: #1e1e2f;
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        font-family: 'Poppins', sans-serif;
    }

    .batch-container {
        padding: 1rem 0;
    }

    /* Header with search */
    .batch-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .batch-header h2 {
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .batch-header h2 i {
        background: linear-gradient(45deg, var(--primary), var(--primary-dark));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-size: 2rem;
    }

    .search-box {
        background: rgba(255,255,255,0.8);
        backdrop-filter: blur(10px);
        border-radius: 50px;
        padding: 0.3rem 0.3rem 0.3rem 1.5rem;
        display: flex;
        align-items: center;
        border: 1px solid rgba(255,255,255,0.5);
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .search-box i {
        color: #888;
        font-size: 1.2rem;
    }

    .search-box input {
        border: none;
        background: transparent;
        padding: 0.8rem 1rem;
        font-size: 1rem;
        min-width: 250px;
        outline: none;
        font-family: 'Poppins', sans-serif;
    }

    .search-box input::placeholder {
        color: #aaa;
    }

    .search-box button {
        background: linear-gradient(45deg, var(--primary), var(--primary-dark));
        border: none;
        color: white;
        padding: 0.6rem 1.5rem;
        border-radius: 50px;
        font-weight: 500;
        transition: all 0.3s;
        cursor: pointer;
    }

    .search-box button:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(71, 118, 230, 0.3);
    }

    /* Chemical Group Cards */
    .chemical-card {
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.5);
        margin-bottom: 2rem;
        overflow: hidden;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .chemical-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
    }

    .chemical-card-header {
        background: linear-gradient(45deg, #1e1e2f, #2a2a40);
        color: white;
        padding: 1.2rem 1.8rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .chemical-card-header .title {
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
    }

    .chemical-card-header .title i {
        font-size: 1.8rem;
        color: #a0a0ff;
    }

    .chemical-card-header .title span {
        font-size: 1.3rem;
    }

    .chemical-card-header .badge-group {
        display: flex;
        gap: 10px;
    }

    .stat-badge {
        background: rgba(255,255,255,0.2);
        border-radius: 50px;
        padding: 0.4rem 1.2rem;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .chemical-card-body {
        padding: 1.5rem;
    }

    /* Modern Table */
    .modern-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95rem;
    }

    .modern-table th {
        text-align: left;
        padding: 1rem 1rem;
        background: #f8f9fa;
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
    }

    .modern-table td {
        padding: 1rem 1rem;
        border-bottom: 1px solid #e9ecef;
        color: #212529;
    }

    .modern-table tbody tr:hover {
        background: rgba(71, 118, 230, 0.05);
        transition: background 0.2s;
    }

    /* Status Badges */
    .status-badge {
        padding: 0.35rem 1rem;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.8rem;
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

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: rgba(255,255,255,0.5);
        border-radius: 30px;
        margin-top: 2rem;
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

    /* Responsive */
    @media (max-width: 768px) {
        .chemical-card-header {
            flex-direction: column;
            align-items: start;
        }
        .modern-table {
            font-size: 0.8rem;
        }
        .modern-table th, .modern-table td {
            padding: 0.75rem 0.5rem;
        }
        .search-box input {
            min-width: 150px;
        }
    }
</style>

<div class="batch-container">
    <!-- Header with Search -->
    <div class="batch-header">
        <h2>
            <i class="fas fa-layer-group"></i> Multi-Batch Inventory
        </h2>
        <div class="search-box">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Search chemical ID or name..." autocomplete="off">
            <button onclick="filterChemicals()"><i class="fas fa-filter"></i> Filter</button>
        </div>
    </div>

    <?php if (empty($batches)): ?>
        <div class="empty-state">
            <i class="fas fa-flask"></i>
            <p>No chemicals found in inventory.</p>
        </div>
    <?php else: ?>
        <div id="chemicalList">
            <?php foreach ($batches as $chem_id => $batch_list): 
                // Calculate summary stats for this chemical
                $total_stock = array_sum(array_column($batch_list, 'current_stock'));
                $batch_count = count($batch_list);
                $expired_count = 0;
                $expiring_count = 0;
                foreach ($batch_list as $b) {
                    $expiry = strtotime($b['expiry_date']);
                    $now = time();
                    $days = ($expiry - $now) / 86400;
                    if ($days < 0) $expired_count++;
                    elseif ($days <= 30) $expiring_count++;
                }
            ?>
            <div class="chemical-card" data-chemical-id="<?= htmlspecialchars(strtolower($chem_id)) ?>" data-chemical-name="<?= htmlspecialchars(strtolower($batch_list[0]['name'] ?? '')) ?>">
                <div class="chemical-card-header">
                    <div class="title">
                        <i class="fas fa-flask"></i>
                        <span><?= htmlspecialchars($chem_id) ?> (<?= htmlspecialchars($batch_list[0]['name'] ?? 'Unknown') ?>)</span>
                    </div>
                    <div class="badge-group">
                        <span class="stat-badge"><i class="fas fa-cubes"></i> <?= $batch_count ?> batch<?= $batch_count > 1 ? 'es' : '' ?></span>
                        <span class="stat-badge"><i class="fas fa-weight-hanging"></i> Total: <?= $total_stock ?></span>
                        <?php if ($expired_count > 0): ?>
                            <span class="stat-badge" style="background: rgba(220,53,69,0.2);"><i class="fas fa-skull-crossbones"></i> Expired: <?= $expired_count ?></span>
                        <?php endif; ?>
                        <?php if ($expiring_count > 0): ?>
                            <span class="stat-badge" style="background: rgba(255,193,7,0.2);"><i class="fas fa-clock"></i> Expiring: <?= $expiring_count ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="chemical-card-body">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Batch ID</th>
                                <th>Stock</th>
                                <th>Expiry Date</th>
                                <th>Usage Rate</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($batch_list as $b): 
                                $expiry = strtotime($b['expiry_date']);
                                $now = time();
                                $days = ($expiry - $now) / 86400;
                                if ($days < 0) {
                                    $status = 'Expired';
                                    $badge_class = 'status-danger';
                                    $icon = 'fa-skull-crossbones';
                                } elseif ($days <= 30) {
                                    $status = 'Expiring Soon';
                                    $badge_class = 'status-warning';
                                    $icon = 'fa-hourglass-half';
                                } else {
                                    $status = 'OK';
                                    $badge_class = 'status-ok';
                                    $icon = 'fa-check-circle';
                                }
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($b['batch_id']) ?></strong></td>
                                <td><?= $b['current_stock'] ?></td>
                                <td><?= date('d M Y', strtotime($b['expiry_date'])) ?></td>
                                <td><?= $b['usage_rate'] ?> /day</td>
                                <td><span class="status-badge <?= $badge_class ?>"><i class="fas <?= $icon ?>"></i> <?= $status ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // Client-side search filter
    function filterChemicals() {
        let input = document.getElementById('searchInput').value.toLowerCase();
        let cards = document.querySelectorAll('.chemical-card');
        cards.forEach(card => {
            let chemId = card.getAttribute('data-chemical-id');
            let chemName = card.getAttribute('data-chemical-name');
            if (chemId.includes(input) || chemName.includes(input)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }

    // Optional: real-time search as you type
    document.getElementById('searchInput').addEventListener('keyup', filterChemicals);
</script>

<?php include '../includes/footer.php'; ?>
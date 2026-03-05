<?php
// File: inventory/list.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
$role = $_SESSION['role'];

$result = $conn->query("SELECT * FROM chemicals ORDER BY expiry_date");
?>
<?php include '../includes/header.php'; ?>

<!-- Custom styles for this page -->
<style>
    /* ==================== MODERN INVENTORY LIST STYLES ==================== */
    :root {
        --primary: #4776E6;
        --primary-dark: #8E54E9;
        --success: #28a745;
        --warning: #ffc107;
        --danger: #dc3545;
        --info: #17a2b8;
    }

    .inventory-container {
        padding: 1rem 0;
    }

    /* Header section */
    .inventory-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .inventory-header h2 {
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .inventory-header h2 i {
        background: linear-gradient(45deg, var(--primary), var(--primary-dark));
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        font-size: 2rem;
    }

    /* Action buttons */
    .action-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn-modern {
        border: none;
        background: rgba(255,255,255,0.8);
        backdrop-filter: blur(5px);
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
        border: 1px solid rgba(255,255,255,0.5);
    }

    .btn-modern:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        background: white;
    }

    .btn-modern.btn-success {
        background: linear-gradient(45deg, #28a745, #20c997);
        color: white;
    }

    .btn-modern.btn-info {
        background: linear-gradient(45deg, #17a2b8, #138496);
        color: white;
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

    /* Modern Card Table Container */
    .table-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        border-radius: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.5);
        overflow: hidden;
        padding: 1.5rem;
    }

    /* Modern Table */
    .modern-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95rem;
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

    /* Action Icons */
    .action-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255,255,255,0.8);
        color: #555;
        margin: 0 3px;
        transition: all 0.2s;
        text-decoration: none;
        border: 1px solid rgba(255,255,255,0.5);
    }

    .action-icon:hover {
        transform: translateY(-2px);
        background: white;
        box-shadow: 0 5px 10px rgba(0,0,0,0.1);
    }

    .action-icon.edit:hover { color: var(--primary); }
    .action-icon.delete:hover { color: var(--danger); }
    .action-icon.view:hover { color: var(--info); }

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

    /* Responsive */
    @media (max-width: 768px) {
        .modern-table {
            font-size: 0.8rem;
        }
        .modern-table th, .modern-table td {
            padding: 0.75rem 0.5rem;
        }
        .inventory-header {
            flex-direction: column;
            align-items: stretch;
        }
        .action-buttons {
            justify-content: flex-start;
        }
        .search-wrapper {
            max-width: 100%;
        }
    }
</style>

<div class="inventory-container">
    <!-- Header with title and action buttons -->
    <div class="inventory-header">
        <h2>
            <i class="fas fa-cubes"></i> Inventory List
        </h2>
        <div class="action-buttons">
            <?php if ($role === 'admin'): ?>
                <a href="add.php" class="btn-modern btn-success" data-bs-toggle="tooltip" title="Add new chemical">
                    <i class="fas fa-plus-circle"></i> Add Chemical
                </a>
            <?php endif; ?>
            <a href="batch_view.php" class="btn-modern btn-info" data-bs-toggle="tooltip" title="View by batches">
                <i class="fas fa-layer-group"></i> Batches View
            </a>
        </div>
    </div>

    <!-- Search bar -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by Chemical ID or Name...">
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="table-card">
        <?php if ($result->num_rows > 0): ?>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Chemical ID</th>
                        <th>Name</th>
                        <th>Batch</th>
                        <th>Stock</th>
                        <th>Expiry</th>
                        <th>Category</th>
                        <th>Usage Rate</th>
                        <?php if ($role === 'admin'): ?><th style="text-align: center;">Actions</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody id="chemicalTable">
                <?php while ($row = $result->fetch_assoc()): 
                    // Determine status based on expiry
                    $expiry = strtotime($row['expiry_date']);
                    $now = time();
                    $days = ($expiry - $now) / 86400;
                    if ($days < 0) {
                        $status_class = 'status-danger';
                        $status_icon = 'fa-skull-crossbones';
                        $status_text = 'Expired';
                    } elseif ($days <= 30) {
                        $status_class = 'status-warning';
                        $status_icon = 'fa-hourglass-half';
                        $status_text = 'Expiring Soon';
                    } else {
                        $status_class = 'status-ok';
                        $status_icon = 'fa-check-circle';
                        $status_text = 'OK';
                    }
                ?>
                    <tr>
                        <td><a href="view.php?id=<?= $row['id'] ?>" class="text-decoration-none fw-bold" style="color: var(--primary);"><?= htmlspecialchars($row['chemical_id']) ?></a></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($row['batch_id']) ?></span></td>
                        <td><?= $row['current_stock'] ?></td>
                        <td>
                            <span class="status-badge <?= $status_class ?>">
                                <i class="fas <?= $status_icon ?>"></i> <?= date('d M Y', strtotime($row['expiry_date'])) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($row['storage_category']) ?></td>
                        <td><?= $row['usage_rate'] ?>/day</td>
                        <?php if ($role === 'admin'): ?>
                        <td style="text-align: center;">
                            <a href="edit.php?id=<?= $row['id'] ?>" class="action-icon edit" data-bs-toggle="tooltip" title="Edit"><i class="fas fa-edit"></i></a>
                            <a href="delete.php?id=<?= $row['id'] ?>" class="action-icon delete" onclick="return confirm('Are you sure you want to delete this item?')" data-bs-toggle="tooltip" title="Delete"><i class="fas fa-trash"></i></a>
                            <a href="view.php?id=<?= $row['id'] ?>" class="action-icon view" data-bs-toggle="tooltip" title="View details"><i class="fas fa-eye"></i></a>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-flask"></i>
                <p>No chemicals found in inventory.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Client-side search filter
document.getElementById('searchInput').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#chemicalTable tr');
    rows.forEach(row => {
        // Search in chemical ID and name columns (index 0 and 1)
        let chemId = row.cells[0]?.textContent.toLowerCase() || '';
        let name = row.cells[1]?.textContent.toLowerCase() || '';
        if (chemId.includes(filter) || name.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
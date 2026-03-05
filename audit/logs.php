<?php
// File: audit/logs.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';
if ($_SESSION['role'] !== 'admin') die("Access denied");

$logs = $conn->query("SELECT a.*, u.username FROM audit_logs a LEFT JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 500");
?>
<?php include '../includes/header.php'; ?>

<!-- Modern Audit Logs Styles -->
<style>
    /* ==================== MODERN AUDIT LOGS STYLES ==================== */
    :root {
        --primary: #4776E6;
        --primary-dark: #8E54E9;
        --success: #28a745;
        --warning: #ffc107;
        --danger: #dc3545;
        --info: #17a2b8;
    }

    .audit-container {
        padding: 1rem 0;
    }

    /* Header */
    .audit-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .audit-header h2 {
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .audit-header h2 i {
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
        /* background: white; */
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .btn-warning-modern {
        background: linear-gradient(45deg, #ffc107, #fd7e14);
        color: white;
    }

    .btn-success-modern {
        background: linear-gradient(45deg, #28a745, #20c997);
        color: white;
    }

    .btn-danger-modern {
        background: linear-gradient(45deg, #dc3545, #c82333);
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

    /* Badge for action type (optional) */
    .action-badge {
        padding: 0.25rem 0.8rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 500;
        display: inline-block;
    }

    .badge-create { background: #d4edda; color: #155724; }
    .badge-edit { background: #fff3cd; color: #856404; }
    .badge-delete { background: #f8d7da; color: #721c24; }
    .badge-login { background: #d1ecf1; color: #0c5460; }

    /* Responsive */
    @media (max-width: 768px) {
        .modern-table th, .modern-table td {
            padding: 0.75rem 0.5rem;
            font-size: 0.8rem;
        }
        .audit-header {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<div class="audit-container">
    <!-- Header with title and action buttons -->
    <div class="audit-header">
        <h2>
            <i class="fas fa-clipboard-list"></i> Audit Logs
        </h2>
        <div class="action-buttons">
            <a href="error_logs.php" class="btn-modern btn-warning-modern" data-bs-toggle="tooltip" title="View system error logs">
                <i class="fas fa-bug"></i> Error Logs
            </a>
            <a href="export.php" class="btn-modern btn-success-modern" data-bs-toggle="tooltip" title="Export as CSV">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
            <a href="export_pdf.php" class="btn-modern btn-danger-modern" data-bs-toggle="tooltip" title="Export as PDF">
                <i class="fas fa-file-pdf"></i> Export PDF
            </a>
        </div>
    </div>

    <!-- Search bar -->
    <div class="search-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Search logs..." autocomplete="off">
    </div>

    <!-- Logs Table Card -->
    <div class="table-card">
        <table class="modern-table" id="logsTable">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Chemical ID</th>
                    <th>Details</th>
                </tr>
            </thead>
            <tbody id="logBody">
                <?php while ($row = $logs->fetch_assoc()): 
                    // Optional: add a badge class based on action
                    $badge_class = '';
                    if (strpos($row['action'], 'create') !== false) $badge_class = 'badge-create';
                    elseif (strpos($row['action'], 'edit') !== false) $badge_class = 'badge-edit';
                    elseif (strpos($row['action'], 'delete') !== false) $badge_class = 'badge-delete';
                    elseif (strpos($row['action'], 'login') !== false) $badge_class = 'badge-login';
                ?>
                <tr>
                    <td><small><?= date('Y-m-d H:i:s', strtotime($row['created_at'])) ?></small></td>
                    <td><?= htmlspecialchars($row['username'] ?? 'System') ?></td>
                    <td>
                        <?php if ($badge_class): ?>
                            <span class="action-badge <?= $badge_class ?>"><?= htmlspecialchars($row['action']) ?></span>
                        <?php else: ?>
                            <?= htmlspecialchars($row['action']) ?>
                        <?php endif; ?>
                    </td>
                    <td><?= $row['chemical_id'] ?: '—' ?></td>
                    <td><?= htmlspecialchars($row['details'] ?? '') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Client-side search filter
    document.getElementById('searchInput').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let rows = document.querySelectorAll('#logBody tr');
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
<?php
// File: audit/error_logs.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

if ($_SESSION['role'] !== 'admin') {
    die("Access denied");
}

$logs = $conn->query("SELECT e.*, u.username FROM error_logs e LEFT JOIN users u ON e.user_id = u.id ORDER BY e.created_at DESC LIMIT 500");

include '../includes/header.php';
?>

<!-- Modern Error Logs Styles -->
<style>
    /* ==================== MODERN ERROR LOGS STYLES ==================== */
    :root {
        --primary: #4776E6;
        --primary-dark: #8E54E9;
        --danger: #dc3545;
        --warning: #ffc107;
        --info: #17a2b8;
    }

    .error-container {
        padding: 1rem 0;
    }

    /* Header */
    .error-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .error-header h2 {
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .error-header h2 i {
        background: linear-gradient(45deg, var(--danger), #c82333);
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
        word-break: break-word;
    }

    .modern-table tbody tr {
        transition: background 0.2s;
    }

    .modern-table tbody tr:hover {
        background: rgba(220, 53, 69, 0.05);
    }

    /* Error message styling */
    .error-message {
        font-family: monospace;
        background: rgba(220,53,69,0.1);
        padding: 0.2rem 0.5rem;
        border-radius: 8px;
        color: #721c24;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .modern-table th, .modern-table td {
            padding: 0.75rem 0.5rem;
            font-size: 0.8rem;
        }
        .error-header {
            flex-direction: column;
            align-items: stretch;
        }
    }
</style>

<div class="error-container">
    <!-- Header with title and back button -->
    <div class="error-header">
        <h2>
            <i class="fas fa-bug"></i> System Error Logs
        </h2>
        <a href="logs.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Audit Logs
        </a>
    </div>

    <!-- Search bar -->
    <div class="search-wrapper">
        <i class="fas fa-search"></i>
        <input type="text" id="searchInput" placeholder="Search error logs..." autocomplete="off">
    </div>

    <!-- Logs Table Card -->
    <div class="table-card">
        <table class="modern-table" id="errorTable">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Error Message</th>
                    <th>File</th>
                    <th>Line</th>
                </tr>
            </thead>
            <tbody id="errorBody">
                <?php while ($row = $logs->fetch_assoc()): ?>
                <tr>
                    <td><small><?= date('Y-m-d H:i:s', strtotime($row['created_at'])) ?></small></td>
                    <td><?= htmlspecialchars($row['username'] ?? 'N/A') ?></td>
                    <td><span class="error-message"><?= htmlspecialchars($row['error_message']) ?></span></td>
                    <td><?= htmlspecialchars($row['file']) ?></td>
                    <td><?= $row['line'] ?></td>
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
        let rows = document.querySelectorAll('#errorBody tr');
        rows.forEach(row => {
            let text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
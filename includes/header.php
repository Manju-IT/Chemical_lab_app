<?php
// File: includes/header.php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Inventory Management</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts - Poppins for modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Modern Navbar with Glassmorphism */
        .navbar-modern {
            background: linear-gradient(45deg, #1e1e2f, #2a2a40);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .navbar-modern .navbar-brand {
            font-weight: 600;
            font-size: 1.5rem;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .navbar-modern .navbar-brand i {
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-size: 2rem;
        }

        .navbar-modern .navbar-nav .nav-link {
            color: rgba(255,255,255,0.8);
            font-weight: 500;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border-radius: 50px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar-modern .navbar-nav .nav-link i {
            font-size: 1.1rem;
            color: #a0a0ff;
            transition: color 0.3s;
        }

        .navbar-modern .navbar-nav .nav-link:hover,
        .navbar-modern .navbar-nav .nav-link:focus,
        .navbar-modern .navbar-nav .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }

        .navbar-modern .navbar-nav .nav-link:hover i {
            color: #fff;
        }

        /* Dropdown menu styling */
        .navbar-modern .dropdown-menu {
            background: rgba(30, 30, 47, 0.9);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            padding: 0.75rem 0;
            margin-top: 0.5rem;
            animation: fadeInDown 0.3s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .navbar-modern .dropdown-item {
            color: rgba(255,255,255,0.8);
            padding: 0.6rem 1.5rem;
            font-weight: 400;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
        }

        .navbar-modern .dropdown-item i {
            width: 20px;
            color: #a0a0ff;
            font-size: 1rem;
        }

        .navbar-modern .dropdown-item:hover,
        .navbar-modern .dropdown-item:focus {
            background: rgba(102, 126, 234, 0.2);
            color: #fff;
        }

        .navbar-modern .dropdown-item:hover i {
            color: #fff;
        }

        .navbar-modern .dropdown-divider {
            border-top: 1px solid rgba(255,255,255,0.1);
            margin: 0.5rem 0;
        }

        /* User profile dropdown */
        .navbar-modern .user-dropdown .nav-link {
            background: rgba(255,255,255,0.1);
            border-radius: 50px;
            padding: 0.5rem 1.2rem;
        }

        .navbar-modern .user-dropdown .nav-link i {
            color: #fff;
        }

        /* Toggler icon */
        .navbar-modern .navbar-toggler {
            border: none;
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
            padding: 0.5rem;
        }

        .navbar-modern .navbar-toggler:focus {
            box-shadow: none;
        }

        .navbar-modern .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255,255,255,0.8)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* Responsive adjustments */
        @media (max-width: 991.98px) {
            .navbar-modern .navbar-nav .nav-link {
                padding: 0.5rem 1rem;
                margin: 0.25rem 0;
            }
            .navbar-modern .dropdown-menu {
                background: transparent;
                backdrop-filter: none;
                box-shadow: none;
                border: none;
                padding-left: 1.5rem;
            }
            .navbar-modern .dropdown-item {
                color: rgba(255,255,255,0.7);
            }
        }

        /* Main content container (already present) */
        .container.mt-4 {
            flex: 1;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-modern sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="/chemical_inventory/index.php">
            <i class="fas fa-flask"></i> Lab Inventory
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <?php if (isset($_SESSION['user_id'])): ?>
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/chemical_inventory/dashboard/dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="inventoryDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-cubes"></i> Inventory
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="inventoryDropdown">
                            <li><a class="dropdown-item" href="/chemical_inventory/inventory/list.php"><i class="fas fa-list"></i> All Chemicals</a></li>
                            <li><a class="dropdown-item" href="/chemical_inventory/inventory/batch_view.php"><i class="fas fa-layer-group"></i> Batches View</a></li>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/chemical_inventory/inventory/add.php"><i class="fas fa-plus-circle"></i> Add Chemical</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/chemical_inventory/alerts/alerts.php">
                            <i class="fas fa-exclamation-triangle"></i> Alerts
                        </a>
                    </li>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="csvDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-upload"></i> CSV
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="csvDropdown">
                                <li><a class="dropdown-item" href="/chemical_inventory/csv/upload.php"><i class="fas fa-file-upload"></i> Upload CSV</a></li>
                                <li><a class="dropdown-item" href="/chemical_inventory/csv/download_template.php"><i class="fas fa-download"></i> Download Template</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="simulationDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-chart-line"></i> Simulation
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="simulationDropdown">
                                <li><a class="dropdown-item" href="/chemical_inventory/simulation/simulate.php"><i class="fas fa-play"></i> Run Simulation</a></li>
                                <li><a class="dropdown-item" href="/chemical_inventory/simulation/report.php"><i class="fas fa-file-alt"></i> View Report</a></li>
                                <li><a class="dropdown-item" href="/chemical_inventory/simulation/export.php"><i class="fas fa-file-csv"></i> Export Report</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="auditDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-history"></i> Audit
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="auditDropdown">
                                <li><a class="dropdown-item" href="/chemical_inventory/audit/logs.php"><i class="fas fa-clipboard-list"></i> Audit Logs</a></li>
                                <li><a class="dropdown-item" href="/chemical_inventory/audit/error_logs.php"><i class="fas fa-bug"></i> Error Logs</a></li>
                                <li><a class="dropdown-item" href="/chemical_inventory/audit/export.php"><i class="fas fa-file-csv"></i> Export Logs</a></li>
                                <li><a class="dropdown-item" href="/chemical_inventory/audit/export_pdf.php"><i class="fas fa-file-pdf"></i> Export PDF</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="analyticsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-chart-pie"></i> Analytics
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="analyticsDropdown">
                                <li><a class="dropdown-item" href="/chemical_inventory/dashboard/analytics.php"><i class="fas fa-chart-bar"></i> Analytics</a></li>
                                <li><a class="dropdown-item" href="/chemical_inventory/dashboard/forecast.php"><i class="fas fa-calendar-alt"></i> Forecast</a></li>
                            </ul>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="/chemical_inventory/auth/register_user.php">
                                <i class="fas fa-user-plus"></i> Register User
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown user-dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['username']) ?> (<?= $_SESSION['role'] ?>)
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="/chemical_inventory/auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/chemical_inventory/auth/login.php">Login</a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</nav>
<div class="container mt-4">
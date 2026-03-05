<?php
// File: auth/register_user.php
require_once '../config/db.php';
require_once '../includes/auth_check.php';

// Only admin allowed
if ($_SESSION['role'] !== 'admin') {
    die("Access denied: insufficient privileges.");
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Validate
    if (empty($username) || empty($password) || empty($role)) {
        $errors[] = "All fields are required.";
    } elseif (!in_array($role, ['admin', 'staff'])) {
        $errors[] = "Invalid role selected.";
    } else {
        // Check duplicate username
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = "Username already exists.";
        }
        $stmt->close();
    }

    if (empty($errors)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed, $role);
        if ($stmt->execute()) {
            $success = true;
            // Ensure logAudit function exists
            if (function_exists('logAudit')) {
                logAudit("create user", null, "Username: $username, Role: $role");
            }
        } else {
            $errors[] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }
}

include '../includes/header.php';
?>

<!-- Add Font Awesome for icons (if not already in header) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<!-- Google Fonts - Poppins for modern look -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>
    /* Override or complement existing styles */
    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        font-family: 'Poppins', sans-serif;
        min-height: 100vh;
        display: flex;
        flex-direction: column;
    }

    .main-content {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
    }

    .register-card {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-radius: 30px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1), 0 10px 20px rgba(0, 0, 0, 0.08);
        padding: 2.5rem;
        max-width: 500px;
        width: 100%;
        margin: 0 auto;
        border: 1px solid rgba(255, 255, 255, 0.5);
        transition: transform 0.3s ease;
    }

    .register-card:hover {
        transform: translateY(-5px);
    }

    .register-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .register-header i {
        font-size: 3.5rem;
        background: linear-gradient(45deg, #4776E6, #8E54E9);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        margin-bottom: 0.5rem;
    }

    .register-header h2 {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.25rem;
    }

    .register-header p {
        color: #777;
        font-weight: 300;
        border-bottom: 2px solid #e0e0e0;
        padding-bottom: 1rem;
        margin-bottom: 0;
    }

    .alert {
        border-radius: 50px;
        padding: 0.75rem 1.5rem;
        border: none;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 1.5rem;
    }

    .alert-success {
        background: linear-gradient(45deg, #a8e6cf, #d4edda);
        color: #155724;
        border-left: 5px solid #28a745;
    }

    .alert-danger {
        background: linear-gradient(45deg, #f8d7da, #f5c6cb);
        color: #721c24;
        border-left: 5px solid #dc3545;
    }

    .form-group {
        margin-bottom: 1.5rem;
        position: relative;
    }

    .form-group label {
        font-weight: 500;
        color: #555;
        margin-bottom: 0.5rem;
        display: block;
        font-size: 0.95rem;
    }

    .input-icon {
        position: relative;
    }

    .input-icon i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #aaa;
        font-size: 1.2rem;
        transition: color 0.3s;
        z-index: 10;
    }

    .input-icon input,
    .input-icon select {
        width: 100%;
        padding: 12px 15px 12px 45px;
        border: 2px solid #eaeef2;
        border-radius: 50px;
        font-size: 1rem;
        font-family: 'Poppins', sans-serif;
        transition: all 0.3s;
        background: white;
        appearance: none;
        -webkit-appearance: none;
    }

    .input-icon select {
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23555' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
        background-size: 16px;
    }

    .input-icon input:focus,
    .input-icon select:focus {
        border-color: #4776E6;
        outline: none;
        box-shadow: 0 0 0 4px rgba(71, 118, 230, 0.1);
    }

    .input-icon input:focus + i,
    .input-icon select:focus + i {
        color: #4776E6;
    }

    .btn-group-custom {
        display: flex;
        gap: 15px;
        margin-top: 2rem;
    }

    .btn {
        padding: 12px 25px;
        border: none;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        flex: 1;
        text-decoration: none;
    }

    .btn-primary {
        background: linear-gradient(45deg, #4776E6, #8E54E9);
        color: white;
        box-shadow: 0 8px 15px -5px rgba(71, 118, 230, 0.4);
    }

    .btn-primary:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 20px -5px rgba(71, 118, 230, 0.6);
    }

    .btn-secondary {
        background: #f1f3f5;
        color: #555;
        box-shadow: 0 5px 10px rgba(0,0,0,0.05);
    }

    .btn-secondary:hover {
        background: #e9ecef;
        transform: translateY(-2px);
        box-shadow: 0 10px 15px rgba(0,0,0,0.1);
    }

    .btn:active {
        transform: translateY(0);
    }

    /* Responsive */
    @media (max-width: 576px) {
        .register-card {
            padding: 1.5rem;
        }
        .btn-group-custom {
            flex-direction: column;
        }
    }
</style>

<div class="main-content">
    <div class="register-card">
        <div class="register-header">
            <i class="fas fa-user-plus"></i>
            <h2>Create New User</h2>
            <p>Add an admin or lab staff member</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> User created successfully.
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?= implode('<br>', $errors) ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>" 
                           placeholder="e.g. johndoe" required>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Enter strong password" required>
                </div>
            </div>

            <div class="form-group">
                <label for="role">Role</label>
                <div class="input-icon">
                    <i class="fas fa-user-tag"></i>
                    <select class="form-control" id="role" name="role" required>
                        <option value="" disabled <?= !isset($_POST['role']) ? 'selected' : '' ?>>-- Select role --</option>
                        <option value="admin" <?= (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                        <option value="staff" <?= (isset($_POST['role']) && $_POST['role'] === 'staff') ? 'selected' : '' ?>>Lab Staff</option>
                    </select>
                </div>
            </div>

            <div class="btn-group-custom">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create User
                </button>
                <a href="list.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
<?php
require_once '../config/db.php';
// session_start(); // Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($id, $hashed, $role);
        $stmt->fetch();
        if (password_verify($password, $hashed)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            // Assuming logAudit() function exists
            if (function_exists('logAudit')) logAudit("login");
            header('Location: /chemical_inventory/dashboard/dashboard.php');
            exit;
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "User not found.";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Chemical Inventory</title>
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Fonts - Poppins for modern typography -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(-45deg, #667eea, #764ba2, #6b8cff, #23a6d5);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        } */
        body {
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(-45deg, #667eea, #764ba2, #6b8cff, #23a6d5);
    background-size: 400% 400%;
    animation: gradientBG 15s ease infinite;

    min-height: 100vh;
    margin: 0;

    display: flex;
    justify-content: center;
    align-items: center;
}

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Optional overlay to soften background */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            z-index: 0;
        }

        .login-container {

            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1100px;
            margin: 20px auto;
            display: flex;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        /* Left side - branding/illustration */
        .brand-panel {
            flex: 1;
            background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
            padding: 60px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: white;
            border-right: 1px solid rgba(255,255,255,0.2);
        }

        .brand-panel h1 {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .brand-panel p {
            font-size: 1.1rem;
            line-height: 1.6;
            opacity: 0.9;
            margin-bottom: 30px;
        }

        .brand-panel i {
            font-size: 5rem;
            margin-bottom: 20px;
            color: rgba(255,255,255,0.9);
            text-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .feature-list {
            list-style: none;
            margin-top: 30px;
        }

        .feature-list li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            font-weight: 300;
        }

        .feature-list li i {
            font-size: 1.5rem;
            margin-right: 15px;
            width: 30px;
        }

        /* Right side - login form */
        .form-panel {
            flex: 1;
            padding: 60px 40px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form-panel h2 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .form-panel .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-weight: 300;
            border-left: 4px solid #667eea;
            padding-left: 15px;
        }

        .input-group {
            margin-bottom: 25px;
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            font-size: 1.2rem;
            transition: color 0.3s;
        }

        .input-group input {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            font-size: 1rem;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s;
            background: white;
        }

        .input-group input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }

        /* .input-group input:focus + i {
            color: #667eea;
        } */

        .error-message {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            padding: 12px 20px;
            border-radius: 50px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .error-message i {
            font-size: 1.2rem;
        }

        .btn-login {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 50px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 20px -5px rgba(102, 126, 234, 0.4);
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 25px -5px rgba(102, 126, 234, 0.6);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        .extra-links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 0.9rem;
        }

        .extra-links a {
            color: #666;
            text-decoration: none;
            transition: color 0.3s;
        }

        .extra-links a:hover {
            color: #667eea;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                margin: 10px;
            }
            .brand-panel {
                padding: 40px 20px;
                text-align: center;
            }
            .brand-panel h1 {
                font-size: 2.5rem;
            }
            .feature-list {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <div class="overlay"></div> 

    <div class="login-container">
        <!-- Left side: brand panel -->
        <div class="brand-panel">
            <i class="fas fa-flask"></i>
            <h1>Chemical Inventory</h1>
            <p>Manage your laboratory chemicals efficiently and securely. Track stock, requests, and usage all in one place.</p>
            <ul class="feature-list">
                <li><i class="fas fa-check-circle"></i> Real-time stock tracking</li>
                <li><i class="fas fa-check-circle"></i> Role-based access control</li>
                <li><i class="fas fa-check-circle"></i> Audit logs & reporting</li>
                <li><i class="fas fa-check-circle"></i> Multi-user support</li>
            </ul>
        </div>

        <!-- Right side: login form -->
        <div class="form-panel">
            <h2>Welcome Back</h2>
            <div class="subtitle">Please login to your account</div>

            <?php if (isset($error)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input type="text" name="username" placeholder="Username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>

                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>

                
            </form>
        </div>
    </div>

</body>
</html>
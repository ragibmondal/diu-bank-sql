<?php
require_once '../config/config.php';

if(is_admin_logged_in()) {
    redirect('admin/dashboard.php');
}

$error_message = '';
$success_message = '';

if(isset($_SESSION['logout_success'])) {
    $success_message = $_SESSION['logout_success'];
    unset($_SESSION['logout_success']);
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $username = sanitize_input($_POST['username']);
        $password = $_POST['password'];

        if(empty($username) || empty($password)) {
            $error_message = 'Please fill in all fields.';
        } else {
            $database = new Database();
            $db = $database->getConnection();
            $admin = new Admin($db);

            if($admin->login($username, $password)) {
                redirect('admin/dashboard.php');
            } else {
                $error_message = 'Invalid username/email or password.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Daffodil Bank</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="../assets/fonts/inter.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #FF6B6B 0%, #4ECDC4 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .bg-shapes {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
        }
        
        .shape {
            position: absolute;
            opacity: 0.1;
            animation: float 8s ease-in-out infinite;
        }
        
        .shape1 {
            top: 20%;
            left: 10%;
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            animation-delay: 0s;
        }
        
        .shape2 {
            top: 60%;
            right: 15%;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 30px;
            animation-delay: 2s;
            transform: rotate(45deg);
        }
        
        .shape3 {
            bottom: 20%;
            left: 20%;
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
            animation-delay: 4s;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); opacity: 0.1; }
            50% { transform: translateY(-20px) rotate(180deg); opacity: 0.2; }
        }
        
        /* Main container */
        .login-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            display: flex;
            min-height: 600px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Left side - Brand */
        .login-brand {
            flex: 1;
            background: linear-gradient(135deg, #FF6B6B 0%, #4ECDC4 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 40px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .login-brand::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .brand-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }
        
        .brand-icon {
            font-size: 4rem;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .brand-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .brand-subtitle {
            font-size: 1.1rem;
            opacity: 0.9;
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        .admin-badge {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            border: 2px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 1rem;
        }
        
        /* Right side - Form */
        .login-form {
            flex: 1;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .form-title {
            font-size: 2rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }
        
        .form-subtitle {
            color: #718096;
            font-size: 1rem;
        }
        
        /* Modern form styling */
        .form-group {
            position: relative;
            margin-bottom: 1.5rem;
        }
        
        .form-control-modern {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            background: #ffffff;
            transition: all 0.3s ease;
            outline: none;
        }
        
        .form-control-modern:focus {
            border-color: #FF6B6B;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
            transform: translateY(-2px);
        }
        
        .form-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 1.1rem;
            transition: color 0.3s ease;
        }
        
        .form-group:focus-within .form-icon {
            color: #FF6B6B;
        }
        
        /* Modern button */
        .btn-login-modern {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #FF6B6B 0%, #4ECDC4 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-login-modern:hover::before {
            left: 100%;
        }
        
        .btn-login-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255, 107, 107, 0.3);
        }
        
        /* Links and utilities */
        .form-links {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 1.5rem;
            font-size: 0.9rem;
        }
        
        .link-modern {
            color: #FF6B6B;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .link-modern:hover {
            color: #e55a5a;
            text-decoration: underline;
        }
        
        /* Alert styling */
        .alert-modern {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            border: none;
            font-weight: 500;
        }
        
        .alert-error {
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            color: #c53030;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
            color: #2f855a;
        }
        
        /* Back link */
        .back-link {
            position: absolute;
            top: 30px;
            left: 30px;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            z-index: 10;
            transition: all 0.3s ease;
        }
        
        .back-link:hover {
            color: white;
            transform: translateX(-5px);
        }
        
        /* Default credentials info */
        .default-info {
            background: linear-gradient(135deg, #e6fffa 0%, #b2f5ea 100%);
            color: #2c7a7b;
            padding: 16px;
            border-radius: 12px;
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .default-info strong {
            color: #234e52;
        }
        
        /* Responsive design */
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                min-height: auto;
            }
            
            .login-brand {
                padding: 40px 20px;
                min-height: 200px;
            }
            
            .brand-title {
                font-size: 2rem;
            }
            
            .login-form {
                padding: 40px 30px;
            }
            
            .form-title {
                font-size: 1.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .login-wrapper {
                padding: 10px;
            }
            
            .login-form {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <div class="bg-shapes">
        <div class="shape shape1"></div>
        <div class="shape shape2"></div>
        <div class="shape shape3"></div>
    </div>

    <a href="../index.php" class="back-link">
        <i class="fas fa-arrow-left me-2"></i>Back to Home
    </a>

    <div class="login-wrapper">
                <div class="login-container">
            <div class="login-brand">
                <div class="brand-content">
                    <div class="brand-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h1 class="brand-title">Admin Portal</h1>
                    <div class="admin-badge">
                        <i class="fas fa-crown me-2"></i>Administrative Access
                    </div>
                    <p class="brand-subtitle">Secure administrative interface for managing Daffodil Bank operations and user accounts.</p>
                    <div class="mt-4">
                        <div class="d-flex justify-content-center align-items-center gap-3 mb-3">
                            <i class="fas fa-users opacity-75"></i>
                            <span class="opacity-90">User management</span>
                        </div>
                        <div class="d-flex justify-content-center align-items-center gap-3 mb-3">
                            <i class="fas fa-chart-bar opacity-75"></i>
                            <span class="opacity-90">Financial reporting</span>
                        </div>
                        <div class="d-flex justify-content-center align-items-center gap-3">
                            <i class="fas fa-cogs opacity-75"></i>
                            <span class="opacity-90">System configuration</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="login-form">
                <div class="form-header">
                    <h2 class="form-title">Administrator Login</h2>
                    <p class="form-subtitle">Enter your admin credentials to access the dashboard</p>
                </div>

                        <?php if($error_message): ?>
                <div class="alert-modern alert-error">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error_message; ?>
                        </div>
                        <?php endif; ?>

                        <?php if($success_message): ?>
                <div class="alert-modern alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php echo $success_message; ?>
                        </div>
                        <?php endif; ?>

                <form method="POST" id="adminLoginForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                    <div class="form-group">
                        <i class="form-icon fas fa-user-shield"></i>
                        <input type="text" name="username" class="form-control-modern" placeholder="Admin Username or Email" required 
                               value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                            </div>

                    <div class="form-group">
                        <i class="form-icon fas fa-key"></i>
                        <input type="password" name="password" class="form-control-modern" placeholder="Admin Password" required>
                            </div>

                    <button type="submit" class="btn-login-modern">
                        <i class="fas fa-sign-in-alt me-2"></i>
                        Access Admin Dashboard
                                </button>
                        </form>

                <div class="default-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Default Admin Credentials:</strong><br>
                    Username: <strong>admin</strong><br>
                    Password: <strong>admin123</strong>
                        </div>

                <div class="form-links">
                    <a href="../login.php" class="link-modern">
                        <i class="fas fa-arrow-left me-1"></i>
                        Back to Customer Login
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
            const button = this.querySelector('.btn-login-modern');
            const originalText = button.innerHTML;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Authenticating...';
            button.disabled = true;
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 3000);
        });

        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('adminLoginForm').submit();
            }
        });
    </script>
</body>
</html>
<?php
require_once 'config/config.php';

if(is_logged_in()) {
    redirect('user/dashboard.php');
}

$error_message = '';
$success_message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_message = 'Invalid security token. Please try again.';
    } else {
        $username = sanitize_input($_POST['username']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $phone = sanitize_input($_POST['phone']);
        $address = sanitize_input($_POST['address']);
        $date_of_birth = $_POST['date_of_birth'];

        $errors = [];

        if(empty($username) || strlen($username) < 3) {
            $errors[] = 'Username must be at least 3 characters long.';
        }

        if(empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if(empty($password) || strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }

        if($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }

        if(empty($first_name) || empty($last_name)) {
            $errors[] = 'First name and last name are required.';
        }

        if(empty($phone) || !preg_match('/^[0-9+\-\s()]{10,15}$/', $phone)) {
            $errors[] = 'Please enter a valid phone number.';
        }

        if(empty($date_of_birth)) {
            $errors[] = 'Date of birth is required.';
        } else {
            $birth_date = new DateTime($date_of_birth);
            $today = new DateTime();
            $age = $today->diff($birth_date)->y;
            if($age < 18) {
                $errors[] = 'You must be at least 18 years old to open an account.';
            }
        }

        if(empty($errors)) {
            $database = new Database();
            $db = $database->getConnection();
            $user = new User($db);

            if($user->exists($username, $email)) {
                $error_message = 'Username or email already exists. Please choose different ones.';
            } else {
                $user->username = $username;
                $user->email = $email;
                $user->password_hash = password_hash($password, PASSWORD_DEFAULT);
                $user->first_name = $first_name;
                $user->last_name = $last_name;
                $user->phone = $phone;
                $user->address = $address;
                $user->date_of_birth = $date_of_birth;

                if($user->register()) {
                    $_SESSION['registration_success'] = 'Registration successful! Your account is pending approval. You will be notified once approved.';
                    redirect('login.php');
                } else {
                    $error_message = 'Registration failed. Please try again.';
                }
            }
        } else {
            $error_message = implode('<br>', $errors);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Daffodil Bank</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/fontawesome.min.css" rel="stylesheet">
    <link href="assets/fonts/inter.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow-x: hidden;
            padding: 20px 0;
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
        .register-wrapper {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            display: flex;
            min-height: 700px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        /* Left side - Brand */
        .register-brand {
            flex: 1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px 40px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .register-brand::before {
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
        
        /* Right side - Form */
        .register-form {
            flex: 1.2;
            padding: 60px 50px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            overflow-y: auto;
            max-height: 100vh;
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
        
        .form-row {
            display: flex;
            gap: 1rem;
        }
        
        .form-row .form-group {
            flex: 1;
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
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
            color: #667eea;
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 80px;
            padding-top: 16px;
        }
        
        /* Modern button */
        .btn-register-modern {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .btn-register-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-register-modern:hover::before {
            left: 100%;
        }
        
        .btn-register-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
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
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .link-modern:hover {
            color: #5a6fd8;
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
        
        /* Terms checkbox */
        .form-check-modern {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            gap: 0.75rem;
        }
        
        .form-check-modern input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #667eea;
            margin-top: 2px;
        }
        
        .form-check-modern label {
            font-size: 0.9rem;
            color: #4a5568;
            line-height: 1.5;
        }
        
        /* Responsive design */
        @media (max-width: 968px) {
            .register-container {
                flex-direction: column;
                min-height: auto;
            }
            
            .register-brand {
                padding: 40px 20px;
                min-height: 250px;
            }
            
            .brand-title {
                font-size: 2rem;
            }
            
            .register-form {
                padding: 40px 30px;
                max-height: none;
            }
            
            .form-title {
                font-size: 1.5rem;
            }
            
            .form-row {
                flex-direction: column;
                gap: 0;
            }
        }
        
        @media (max-width: 480px) {
            .register-wrapper {
                padding: 10px;
            }
            
            .register-form {
                padding: 30px 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Animated background shapes -->
    <div class="bg-shapes">
        <div class="shape shape1"></div>
        <div class="shape shape2"></div>
        <div class="shape shape3"></div>
    </div>

    <!-- Back to home link -->
    <a href="index.php" class="back-link">
        <i class="fas fa-arrow-left me-2"></i>Back to Home
    </a>

    <!-- Main register container -->
    <div class="register-wrapper">
                <div class="register-container">
            <!-- Left side - Brand section -->
            <div class="register-brand">
                <div class="brand-content">
                    <div class="brand-icon">
                        <i class="fas fa-university"></i>
                    </div>
                    <h1 class="brand-title">Daffodil Bank</h1>
                    <p class="brand-subtitle">Join thousands of satisfied customers and experience secure banking with cutting-edge technology.</p>
                    <div class="mt-4">
                        <div class="d-flex justify-content-center align-items-center gap-3 mb-3">
                            <i class="fas fa-user-shield opacity-75"></i>
                            <span class="opacity-90">KYC verified accounts</span>
                        </div>
                        <div class="d-flex justify-content-center align-items-center gap-3 mb-3">
                            <i class="fas fa-mobile-alt opacity-75"></i>
                            <span class="opacity-90">Mobile banking app</span>
                        </div>
                        <div class="d-flex justify-content-center align-items-center gap-3">
                            <i class="fas fa-chart-line opacity-75"></i>
                            <span class="opacity-90">Investment opportunities</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right side - Form section -->
            <div class="register-form">
                <div class="form-header">
                    <h2 class="form-title">Create Your Account</h2>
                    <p class="form-subtitle">Fill in your details to open a new banking account</p>
                </div>

                        <?php if($error_message): ?>
                <div class="alert-modern alert-error">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php echo $error_message; ?>
                        </div>
                        <?php endif; ?>

                <form method="POST" id="registerForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                    <div class="form-row">
                        <div class="form-group">
                            <i class="form-icon fas fa-user"></i>
                            <input type="text" name="first_name" class="form-control-modern" placeholder="First Name" required 
                                   value="<?php echo isset($first_name) ? htmlspecialchars($first_name) : ''; ?>">
                                </div>
                        <div class="form-group">
                            <i class="form-icon fas fa-user"></i>
                            <input type="text" name="last_name" class="form-control-modern" placeholder="Last Name" required 
                                   value="<?php echo isset($last_name) ? htmlspecialchars($last_name) : ''; ?>">
                                </div>
                            </div>

                    <div class="form-group">
                        <i class="form-icon fas fa-at"></i>
                        <input type="text" name="username" class="form-control-modern" placeholder="Username (min. 3 characters)" required 
                               value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                            </div>

                    <div class="form-group">
                        <i class="form-icon fas fa-envelope"></i>
                        <input type="email" name="email" class="form-control-modern" placeholder="Email Address" required 
                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                            </div>

                    <div class="form-row">
                        <div class="form-group">
                            <i class="form-icon fas fa-lock"></i>
                            <input type="password" name="password" class="form-control-modern" placeholder="Password (min. 6 characters)" required>
                                    </div>
                        <div class="form-group">
                            <i class="form-icon fas fa-lock"></i>
                            <input type="password" name="confirm_password" class="form-control-modern" placeholder="Confirm Password" required>
                                </div>
                            </div>

                    <div class="form-row">
                        <div class="form-group">
                            <i class="form-icon fas fa-phone"></i>
                            <input type="tel" name="phone" class="form-control-modern" placeholder="Phone Number" required 
                                   value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                                    </div>
                        <div class="form-group">
                            <i class="form-icon fas fa-calendar"></i>
                            <input type="date" name="date_of_birth" class="form-control-modern" required 
                                   value="<?php echo isset($date_of_birth) ? htmlspecialchars($date_of_birth) : ''; ?>">
                                </div>
                            </div>

                    <div class="form-group">
                        <i class="form-icon fas fa-map-marker-alt"></i>
                        <textarea name="address" class="form-control-modern form-textarea" placeholder="Complete Address" 
                                  rows="3"><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
                            </div>

                    <div class="form-check-modern">
                        <input type="checkbox" id="terms" required>
                        <label for="terms">
                            I agree to the <a href="#" target="_blank" class="link-modern">Terms and Conditions</a> and 
                            <a href="#" target="_blank" class="link-modern">Privacy Policy</a>. I understand that my account will require admin approval before activation.
                                    </label>
                            </div>

                    <button type="submit" class="btn-register-modern">
                        <i class="fas fa-user-plus me-2"></i>
                        Create My Account
                                </button>
                        </form>

                <div class="form-links">
                    <span class="text-muted me-2">Already have an account?</span>
                    <a href="login.php" class="link-modern">
                        <i class="fas fa-sign-in-alt me-1"></i>
                        Sign In Here
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const button = this.querySelector('.btn-register-modern');
            const originalText = button.innerHTML;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Creating Account...';
            button.disabled = true;
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
            }, 5000);
        });

        document.querySelector('input[name="confirm_password"]').addEventListener('input', function() {
            const password = document.querySelector('input[name="password"]').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        document.querySelector('input[name="date_of_birth"]').addEventListener('change', function() {
            const birthDate = new Date(this.value);
            const today = new Date();
            const age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            
            if (age < 18) {
                this.setCustomValidity('You must be at least 18 years old to create an account');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
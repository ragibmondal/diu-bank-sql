<?php
require_once '../config/config.php';

check_user_login();
$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$user_data = $user->getById($_SESSION['user_id']);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(!verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if($action == 'update_profile') {
            $first_name = sanitize_input($_POST['first_name']);
            $last_name = sanitize_input($_POST['last_name']);
            $phone = sanitize_input($_POST['phone']);
            $date_of_birth = $_POST['date_of_birth'];
            $address = sanitize_input($_POST['address']);
            
            $errors = [];
            
            if(empty($first_name)) {
                $errors[] = 'First name is required.';
            }
            
            if(empty($last_name)) {
                $errors[] = 'Last name is required.';
            }
            
            if(empty($phone)) {
                $errors[] = 'Phone number is required.';
            } elseif(!preg_match('/^[0-9]{10}$/', $phone)) {
                $errors[] = 'Phone number must be 10 digits.';
            }
            
            if(empty($date_of_birth)) {
                $errors[] = 'Date of birth is required.';
            }
            
            if(empty($address)) {
                $errors[] = 'Address is required.';
            }
            
            if(empty($errors)) {
                $user->first_name = $first_name;
                $user->last_name = $last_name;
                $user->phone = $phone;
                $user->date_of_birth = $date_of_birth;
                $user->address = $address;
                
                if($user->updateProfile()) {
                    $message = 'Profile updated successfully!';
                    $_SESSION['user_name'] = $first_name . ' ' . $last_name;
                    $user_data = $user->getById($_SESSION['user_id']);
                } else {
                    $error = 'Failed to update profile. Please try again.';
                }
            } else {
                $error = implode('<br>', $errors);
            }
            
        } elseif($action == 'change_password') {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            $errors = [];
            
            if(empty($current_password)) {
                $errors[] = 'Current password is required.';
            }
            
            if(empty($new_password)) {
                $errors[] = 'New password is required.';
            } elseif(strlen($new_password) < 6) {
                $errors[] = 'New password must be at least 6 characters long.';
            }
            
            if($new_password != $confirm_password) {
                $errors[] = 'New passwords do not match.';
            }
            
            if(empty($errors)) {
                if(!password_verify($current_password, $user_data['password_hash'])) {
                    $error = 'Current password is incorrect.';
                } else {
                    $user->password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    if($user->updatePassword()) {
                        $message = 'Password changed successfully!';
                    } else {
                        $error = 'Failed to change password. Please try again.';
                    }
                }
            } else {
                $error = implode('<br>', $errors);
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
    <title>Profile - Daffodil Bank</title>
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
            background: #f8fafc;
            color: #2d3748;
            line-height: 1.6;
        }
        
        /* Modern Sidebar */
        .sidebar {
            background: linear-gradient(180deg, #1a202c 0%, #2d3748 100%);
            min-height: 100vh;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            opacity: 0.1;
        }
        
        .sidebar-header {
            padding: 2rem 1.5rem;
            position: relative;
            z-index: 2;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-brand {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .user-welcome {
            font-size: 0.9rem;
            color: #a0aec0;
            font-weight: 500;
        }
        
        .sidebar-nav {
            padding: 1rem 0;
            position: relative;
            z-index: 2;
            height: calc(100vh - 200px);
            display: flex;
            flex-direction: column;
        }
        
        .sidebar-nav .nav {
            flex: 1;
            padding-bottom: 100px;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            border-radius: 12px;
            margin: 4px 1rem;
            padding: 12px 16px !important;
            transition: all 0.3s ease;
            position: relative;
            font-weight: 500;
            border: none;
            background: none;
        }
        
        .nav-link:hover {
            background: rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            transform: translateX(5px);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            color: white !important;
            transform: translateX(5px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 12px;
            text-align: center;
        }
        
        /* Main Content Area */
        .main-content {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
            padding: 0;
        }
        
        .content-header {
            background: white;
            padding: 2rem 2rem 1rem;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: #718096;
            font-size: 1rem;
            margin: 0;
        }
        
        .content-body {
            padding: 2rem;
        }
        
        /* Modern Cards */
        .card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            background: white;
            overflow: hidden;
        }
        
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }
        
        .card-header {
            background: none;
            border: none;
            padding: 1.5rem 1.5rem 0;
            font-weight: 600;
            color: #2d3748;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        /* Form Controls */
        .form-control {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        /* Modern Buttons */
        .btn-modern {
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            border: none;
            position: relative;
            overflow: hidden;
        }
        
        .btn-modern::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn-modern:hover::before {
            left: 100%;
        }
        
        .btn-primary.btn-modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-primary.btn-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        /* Profile Info */
        .profile-info {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .profile-info::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        }
        
        .profile-avatar {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        /* Status Badges */
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .status-badge.success {
            background: #c6f6d5;
            color: #2f855a;
        }
        
        .status-badge.warning {
            background: #faf089;
            color: #975a16;
        }
        
        .status-badge.danger {
            background: #fed7d7;
            color: #c53030;
        }
        
        /* Alert Styling */
        .alert {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, #faf089 0%, #f6e05e 100%);
            color: #975a16;
            border-left: 4px solid #d69e2e;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
            color: #2f855a;
            border-left: 4px solid #38a169;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fed7d7 0%, #fbb6ce 100%);
            color: #c53030;
            border-left: 4px solid #e53e3e;
        }
        
        /* Logout Button */
        .logout-btn {
            color: #e2e8f0 !important;
            border-radius: 12px;
            padding: 12px 16px !important;
            transition: all 0.3s ease;
            position: relative;
            font-weight: 500;
            border: 1px solid rgba(226, 67, 67, 0.3);
            background: rgba(226, 67, 67, 0.15);
            text-decoration: none;
            display: flex;
            align-items: center;
            margin: 0;
        }
        
        .logout-btn:hover {
            background: rgba(226, 67, 67, 0.25) !important;
            color: #fed7d7 !important;
            transform: translateX(3px);
            border-color: rgba(226, 67, 67, 0.5);
            text-decoration: none;
        }
        
        .logout-btn i {
            width: 20px;
            margin-right: 12px;
            text-align: center;
        }
        
        /* Logout Container */
        .logout-container {
            margin-top: auto;
            margin-left: 1rem;
            margin-right: 1rem;
            margin-bottom: 1.5rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1rem;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -100%;
                top: 0;
                width: 280px;
                z-index: 1000;
                transition: left 0.3s ease;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
        }

    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="sidebar-header">
                        <h4 class="sidebar-brand">
                            <i class="fas fa-university me-2"></i>Daffodil Bank
                        </h4>
                        <p class="user-welcome mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                    </div>
                    
                    <nav class="sidebar-nav">
                        <div class="nav flex-column">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-chart-pie"></i>Dashboard
                            </a>
                            <a class="nav-link" href="accounts.php">
                                <i class="fas fa-wallet"></i>My Accounts
                            </a>
                            <a class="nav-link" href="transfer.php">
                                <i class="fas fa-paper-plane"></i>Transfer Money
                            </a>
                            <a class="nav-link" href="transactions.php">
                                <i class="fas fa-history"></i>Transactions
                            </a>
                            <a class="nav-link active" href="profile.php">
                                <i class="fas fa-user-circle"></i>Profile
                            </a>
                        </div>
                        
                        <div class="logout-container">
                            <a class="logout-btn" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i>Logout
                            </a>
                        </div>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 px-0">
                <div class="main-content">
                    <div class="content-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="page-title">My Profile</h1>
                                <p class="page-subtitle">Manage your personal information and security settings</p>
                            </div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Profile</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                    
                    <div class="content-body">

                    <!-- Messages -->
                    <?php if($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Profile Overview -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="profile-avatar">
                                        <?php echo strtoupper(substr($user_data['first_name'], 0, 1) . substr($user_data['last_name'], 0, 1)); ?>
                                    </div>
                                    <h4><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></h4>
                                    <p class="text-muted"><?php echo htmlspecialchars($user_data['email']); ?></p>
                                    
                                    <div class="mt-3">
                                        <span class="badge bg-<?php echo $user_data['registration_status'] == 'approved' ? 'success' : ($user_data['registration_status'] == 'pending' ? 'warning' : 'danger'); ?> status-badge">
                                            <?php echo ucfirst($user_data['registration_status']); ?>
                                        </span>
                                        <span class="badge bg-<?php echo $user_data['is_active'] ? 'success' : 'secondary'; ?> status-badge ms-2">
                                            <?php echo $user_data['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Account Summary -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Account Summary</h6>
                                </div>
                                <div class="card-body">
                                    <div class="info-row">
                                        <strong>Member Since:</strong><br>
                                        <span class="text-muted"><?php echo date('F j, Y', strtotime($user_data['created_at'])); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <strong>Username:</strong><br>
                                        <span class="text-muted"><?php echo htmlspecialchars($user_data['username']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <strong>Last Login:</strong><br>
                                        <span class="text-muted"><?php echo (!empty($user_data['last_login']) && $user_data['last_login'] !== null) ? date('M j, Y h:i A', strtotime($user_data['last_login'])) : 'Never'; ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Forms -->
                        <div class="col-md-8">
                            <!-- Personal Information -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Personal Information</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="update_profile">
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="first_name" class="form-label">First Name *</label>
                                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                                       value="<?php echo htmlspecialchars($user_data['first_name']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="last_name" class="form-label">Last Name *</label>
                                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                                       value="<?php echo htmlspecialchars($user_data['last_name']); ?>" required>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="email" class="form-label">Email Address</label>
                                                <input type="email" class="form-control" id="email" 
                                                       value="<?php echo htmlspecialchars($user_data['email']); ?>" disabled>
                                                <div class="form-text">Email cannot be changed. Contact support if needed.</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="phone" class="form-label">Phone Number *</label>
                                                <input type="tel" class="form-control" id="phone" name="phone" 
                                                       value="<?php echo htmlspecialchars($user_data['phone']); ?>" required>
                                            </div>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="date_of_birth" class="form-label">Date of Birth *</label>
                                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                                       value="<?php echo $user_data['date_of_birth']; ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="username" class="form-label">Username</label>
                                                <input type="text" class="form-control" id="username" 
                                                       value="<?php echo htmlspecialchars($user_data['username']); ?>" disabled>
                                                <div class="form-text">Username cannot be changed.</div>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="address" class="form-label">Address *</label>
                                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($user_data['address']); ?></textarea>
                                        </div>

                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="submit" class="btn btn-primary btn-modern">
                                                <i class="fas fa-save me-2"></i>Update Profile
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Change Password -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-lock me-2"></i>Change Password</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="passwordForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        <input type="hidden" name="action" value="change_password">
                                        
                                        <div class="mb-3">
                                            <label for="current_password" class="form-label">Current Password *</label>
                                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                                        </div>

                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="new_password" class="form-label">New Password *</label>
                                                <input type="password" class="form-control" id="new_password" name="new_password" 
                                                       minlength="6" required>
                                                <div class="form-text">Minimum 6 characters required.</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                                                       minlength="6" required>
                                            </div>
                                        </div>

                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <button type="button" class="btn btn-outline-secondary me-md-2" onclick="clearPasswordForm()">
                                                <i class="fas fa-times me-2"></i>Clear
                                            </button>
                                            <button type="submit" class="btn btn-warning">
                                                <i class="fas fa-key me-2"></i>Change Password
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Security Information -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security Information</h6>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-info">
                                        <h6>Security Tips:</h6>
                                        <ul class="mb-0">
                                            <li>Use a strong password with at least 6 characters</li>
                                            <li>Never share your login credentials with anyone</li>
                                            <li>Always log out when using shared computers</li>
                                            <li>Contact support immediately if you notice suspicious activity</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Quick Actions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <a href="accounts.php" class="btn btn-primary btn-modern w-100">
                                                <i class="fas fa-credit-card me-2"></i>My Accounts
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="transfer.php" class="btn btn-outline-primary w-100">
                                                <i class="fas fa-paper-plane me-2"></i>Transfer Money
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="transactions.php" class="btn btn-outline-success w-100">
                                                <i class="fas fa-history me-2"></i>Transaction History
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="dashboard.php" class="btn btn-outline-info w-100">
                                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('confirm_password').addEventListener('input', function() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = this.value;
            
            if(newPassword !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        function clearPasswordForm() {
            document.getElementById('passwordForm').reset();
        }

        document.getElementById('phone').addEventListener('input', function() {
            const phone = this.value.replace(/\D/g, ''); 
            this.value = phone;
            
            if(phone.length !== 10) {
                this.setCustomValidity('Phone number must be exactly 10 digits');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>
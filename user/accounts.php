<?php
require_once '../config/config.php';


check_user_login();

$database = new Database();
$db = $database->getConnection();


$user = new User($db);
$user->getById($_SESSION['user_id']);

$account = new Account($db);
$user_accounts = $account->getUserAccounts($_SESSION['user_id']);

$total_balance = 0;
foreach($user_accounts as $acc) {
    if($acc['status'] == 'active') {
        $total_balance += $acc['balance'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Accounts - Daffodil Bank</title>
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
        
        /* Balance Cards */
        .balance-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .balance-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        }
        
        .balance-amount {
            font-size: 2.5rem;
            font-weight: 800;
            margin: 0;
            position: relative;
            z-index: 2;
        }
        
        .balance-label {
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 2;
        }
        
        /* Account Cards */
        .account-card {
            border-left: 4px solid transparent;
            border-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%) 1;
            position: relative;
            margin-bottom: 1rem;
        }
        
        .account-type {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }
        
        .account-number {
            font-family: 'Monaco', 'Menlo', monospace;
            color: #718096;
            font-size: 0.9rem;
        }
        
        .account-balance {
            font-size: 1.5rem;
            font-weight: 700;
            color: #48bb78;
            margin: 0;
        }
        
        .balance-display {
            font-size: 1.5rem;
            font-weight: 700;
            color: #48bb78;
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
                            <a class="nav-link active" href="accounts.php">
                                <i class="fas fa-wallet"></i>My Accounts
                            </a>
                            <a class="nav-link" href="transfer.php">
                                <i class="fas fa-paper-plane"></i>Transfer Money
                            </a>
                            <a class="nav-link" href="transactions.php">
                                <i class="fas fa-history"></i>Transactions
                            </a>
                            <a class="nav-link" href="profile.php">
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
                                <h1 class="page-title">My Accounts</h1>
                                <p class="page-subtitle">Manage your banking accounts and track balances</p>
                            </div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                                    <li class="breadcrumb-item active">My Accounts</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                    
                    <div class="content-body">

                    <!-- Account Status Alert -->
                    <?php if($user->registration_status != 'approved'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Your account registration is currently <strong><?php echo ucfirst($user->registration_status); ?></strong>. 
                        Limited features available until approval.
                    </div>
                    <?php endif; ?>

                    <!-- Total Balance Overview -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                                <div class="card-body text-center">
                                    <h5 class="card-title">Total Balance</h5>
                                    <div class="balance-display">৳<?php echo number_format($total_balance, 2); ?></div>
                                    <small>Across <?php echo count($user_accounts); ?> account(s)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body text-center">
                                    <h5 class="card-title text-success">Active Accounts</h5>
                                    <div class="balance-display text-success">
                                        <?php echo count(array_filter($user_accounts, function($acc) { return $acc['status'] == 'active'; })); ?>
                                    </div>
                                    <small>Ready for transactions</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Accounts List -->
                    <div class="row">
                        <?php if(empty($user_accounts)): ?>
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-body text-center py-5">
                                        <i class="fas fa-credit-card fa-4x text-muted mb-3"></i>
                                        <h4>No Accounts Found</h4>
                                        <p class="text-muted">You don't have any bank accounts yet.</p>
                                        <p class="text-muted">Please contact our customer service or visit a branch to open your first account.</p>
                                        <div class="mt-4">
                                            <a href="dashboard.php" class="btn btn-primary">
                                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach($user_accounts as $acc): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card account-card h-100">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <i class="fas fa-credit-card me-2"></i>
                                            <?php echo ucfirst($acc['account_type']); ?> Account
                                        </h5>
                                        <span class="badge bg-<?php echo $acc['status'] == 'active' ? 'success' : ($acc['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                            <?php echo ucfirst($acc['status']); ?>
                                        </span>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <h6 class="text-muted">Account Number</h6>
                                                <p class="account-number"><?php echo $acc['account_number']; ?></p>
                                            </div>
                                            <div class="col-sm-6">
                                                <h6 class="text-muted">Current Balance</h6>
                                                <p class="balance-display text-success">৳<?php echo number_format($acc['balance'], 2); ?></p>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <h6 class="text-muted">Opened Date</h6>
                                                <p><?php echo date('M j, Y', strtotime($acc['created_at'])); ?></p>
                                            </div>
                                            <div class="col-sm-6">
                                                <h6 class="text-muted">Last Updated</h6>
                                                <p><?php echo date('M j, Y', strtotime($acc['updated_at'])); ?></p>
                                            </div>
                                        </div>

                                        <?php if($acc['status'] == 'active'): ?>
                                        <div class="mt-3">
                                            <div class="btn-group w-100" role="group">
                                                <a href="transfer.php?from_account=<?php echo $acc['account_id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-paper-plane me-1"></i>Transfer
                                                </a>
                                                <a href="transactions.php?account_id=<?php echo $acc['account_id']; ?>" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-history me-1"></i>History
                                                </a>
                                            </div>
                                        </div>
                                        <?php elseif($acc['status'] == 'pending'): ?>
                                        <div class="mt-3">
                                            <div class="alert alert-warning mb-0 py-2">
                                                <small><i class="fas fa-clock me-1"></i>Account pending approval</small>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <div class="mt-3">
                                            <div class="alert alert-danger mb-0 py-2">
                                                <small><i class="fas fa-ban me-1"></i>Account suspended/closed</small>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <?php if(!empty($user_accounts)): ?>
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <a href="transfer.php" class="btn btn-primary w-100">
                                                <i class="fas fa-exchange-alt me-2"></i>Transfer Money
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="transactions.php" class="btn btn-outline-primary w-100">
                                                <i class="fas fa-history me-2"></i>View All Transactions
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="dashboard.php" class="btn btn-outline-success w-100">
                                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="profile.php" class="btn btn-outline-info w-100">
                                                <i class="fas fa-user me-2"></i>Profile
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth animations on page load
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
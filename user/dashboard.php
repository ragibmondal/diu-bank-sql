<?php
require_once '../config/config.php';

check_user_login();
$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$user->getById($_SESSION['user_id']);
$account = new Account($db);
$user_accounts = $account->getUserAccounts($_SESSION['user_id']);

$transaction = new Transaction($db);
$recent_transactions = $transaction->getUserTransactions($_SESSION['user_id'], 1, 10);

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
    <title>Dashboard - Daffodil Bank</title>
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
        
        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
        }
        
        .stat-icon.success {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
        }
        
        .stat-icon.info {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
            color: white;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }
        
        .stat-label {
            color: #718096;
            font-size: 0.9rem;
            margin: 0;
        }
        
        /* Account Cards */
        .account-card {
            border-left: 4px solid transparent;
            border-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%) 1;
            position: relative;
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
        
        /* Quick Action Buttons */
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
        
        .btn-outline-primary.btn-modern {
            border: 2px solid #667eea;
            color: #667eea;
            background: transparent;
        }
        
        .btn-outline-primary.btn-modern:hover {
            background: #667eea;
            color: white;
            transform: translateY(-2px);
        }
        
        /* Transaction Items */
        .transaction-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f7fafc;
        }
        
        .transaction-item:last-child {
            border-bottom: none;
        }
        
        .transaction-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2rem;
        }
        
        .transaction-icon.credit {
            background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
            color: #38a169;
        }
        
        .transaction-icon.debit {
            background: linear-gradient(135deg, #fed7d7 0%, #fbb6ce 100%);
            color: #e53e3e;
        }
        
        .transaction-details {
            flex: 1;
        }
        
        .transaction-type {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 0.25rem;
        }
        
        .transaction-date {
            color: #718096;
            font-size: 0.85rem;
        }
        
        .transaction-amount {
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        .transaction-amount.credit {
            color: #38a169;
        }
        
        .transaction-amount.debit {
            color: #e53e3e;
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
            
            .balance-amount {
                font-size: 2rem;
            }
        }
        
        /* Loading animations */
        .loading-shimmer {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
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
                            <a class="nav-link active" href="dashboard.php">
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
                                <h1 class="page-title">Dashboard</h1>
                                <p class="page-subtitle">Welcome back to your modern banking experience</p>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="text-end">
                                    <small class="text-muted d-block">Today</small>
                                    <small class="fw-semibold"><?php echo date('M j, Y'); ?></small>
                                </div>
                                <span class="status-badge success">
                                    <i class="fas fa-check-circle me-1"></i>Account Active
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="content-body">

                    <!-- Account Status Alert -->
                    <?php if($user->registration_status != 'approved'): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Your account registration is currently <strong><?php echo ucfirst($user->registration_status); ?></strong>. 
                        <?php if($user->registration_status == 'pending'): ?>
                            Please wait for admin approval to access all features.
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Balance Overview -->
                    <div class="row g-4 mb-5">
                        <div class="col-lg-6">
                            <div class="card balance-card">
                                <div class="card-body text-center">
                                    <div class="balance-label mb-2">Total Balance</div>
                                    <h2 class="balance-amount">৳<?php echo number_format($total_balance, 2); ?></h2>
                                    <small class="balance-label">Across <?php echo count($user_accounts); ?> account(s)</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-6">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-icon success">
                                            <i class="fas fa-wallet"></i>
                                        </div>
                                        <h3 class="stat-number">
                                            <?php echo count(array_filter($user_accounts, function($acc) { return $acc['status'] == 'active'; })); ?>
                                        </h3>
                                        <p class="stat-label">Active Accounts</p>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stat-card">
                                        <div class="stat-icon info">
                                            <i class="fas fa-exchange-alt"></i>
                                        </div>
                                        <h3 class="stat-number"><?php echo count($recent_transactions); ?></h3>
                                        <p class="stat-label">Recent Transactions</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mb-5">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-bolt me-2 text-primary"></i>Quick Actions
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-lg-3 col-md-6">
                                            <a href="transfer.php" class="btn btn-primary btn-modern w-100">
                                                <i class="fas fa-paper-plane me-2"></i>Transfer Money
                                            </a>
                                        </div>
                                        <div class="col-lg-3 col-md-6">
                                            <a href="transactions.php" class="btn btn-outline-primary btn-modern w-100">
                                                <i class="fas fa-history me-2"></i>View History
                                            </a>
                                        </div>
                                        <div class="col-lg-3 col-md-6">
                                            <a href="accounts.php" class="btn btn-outline-primary btn-modern w-100">
                                                <i class="fas fa-wallet me-2"></i>My Accounts
                                            </a>
                                        </div>
                                        <div class="col-lg-3 col-md-6">
                                            <a href="profile.php" class="btn btn-outline-primary btn-modern w-100">
                                                <i class="fas fa-user-circle me-2"></i>Update Profile
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- My Accounts -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-wallet me-2 text-primary"></i>My Accounts
                                    </h5>
                                    <a href="accounts.php" class="btn btn-sm btn-outline-primary btn-modern">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if(empty($user_accounts)): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-wallet fa-3x mb-3 opacity-50"></i>
                                            <h6 class="mb-2">No accounts found</h6>
                                            <small>Contact admin to create your first account</small>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach($user_accounts as $acc): ?>
                                        <div class="account-card card mb-3">
                                            <div class="card-body py-3">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <h6 class="account-type"><?php echo ucfirst($acc['account_type']); ?> Account</h6>
                                                        <p class="account-number mb-0"><?php echo $acc['account_number']; ?></p>
                                                    </div>
                                                    <div class="text-end">
                                                        <h5 class="account-balance mb-1">৳<?php echo number_format($acc['balance'], 2); ?></h5>
                                                        <span class="status-badge <?php echo $acc['status'] == 'active' ? 'success' : ($acc['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                            <?php echo ucfirst($acc['status']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Transactions -->
                        <div class="col-lg-6 mb-4">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-history me-2 text-primary"></i>Recent Transactions
                                    </h5>
                                    <a href="transactions.php" class="btn btn-sm btn-outline-primary btn-modern">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if(empty($recent_transactions)): ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-history fa-3x mb-3 opacity-50"></i>
                                            <h6 class="mb-2">No transactions found</h6>
                                            <small>Your transaction history will appear here</small>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach(array_slice($recent_transactions, 0, 5) as $txn): ?>
                                        <div class="transaction-item">
                                            <div class="transaction-icon <?php echo $txn['direction'] == 'incoming' ? 'credit' : 'debit'; ?>">
                                                <?php if($txn['direction'] == 'incoming'): ?>
                                                    <i class="fas fa-arrow-down"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-arrow-up"></i>
                                                <?php endif; ?>
                                            </div>
                                            <div class="transaction-details">
                                                <div class="transaction-type"><?php echo ucfirst($txn['transaction_type']); ?></div>
                                                <div class="transaction-date"><?php echo date('M j, Y h:i A', strtotime($txn['created_at'])); ?></div>
                                            </div>
                                            <div class="transaction-amount <?php echo $txn['direction'] == 'incoming' ? 'credit' : 'debit'; ?>">
                                                <?php echo $txn['direction'] == 'incoming' ? '+' : '-'; ?>৳<?php echo number_format($txn['amount'], 2); ?>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
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

            const buttons = document.querySelectorAll('.btn-modern');
            buttons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const originalText = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Loading...';
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                    }, 1000);
                });
            });

            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });

            const balanceAmount = document.querySelector('.balance-amount');
            if (balanceAmount) {
                const target = parseFloat(balanceAmount.textContent.replace(/[^\d.-]/g, ''));
                let current = 0;
                const increment = target / 30;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }
                    balanceAmount.textContent = '৳' + current.toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }, 50);
            }
        });

        function toggleSidebar() {
            const sidebar = document.querySelector('.sidebar');
            sidebar.classList.toggle('show');
        }

        if (window.innerWidth <= 768) {
            const header = document.querySelector('.content-header');
            const menuButton = document.createElement('button');
            menuButton.className = 'btn btn-outline-secondary d-md-none me-3';
            menuButton.innerHTML = '<i class="fas fa-bars"></i>';
            menuButton.onclick = toggleSidebar;
            header.querySelector('div').prepend(menuButton);
        }
    </script>
</body>
</html>
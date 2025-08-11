<?php
require_once '../config/config.php';
check_user_login();
$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$user->getById($_SESSION['user_id']);

$account = new Account($db);
$user_accounts = $account->getUserAccounts($_SESSION['user_id']);

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 20;
$account_filter = $_GET['account_id'] ?? '';
$type_filter = $_GET['type'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$transaction = new Transaction($db);
$transactions = $transaction->getUserTransactions($_SESSION['user_id'], $page, $records_per_page, $account_filter, $type_filter, $date_from, $date_to);
$total_transactions = $transaction->getUserTransactionCount($_SESSION['user_id'], $account_filter, $type_filter, $date_from, $date_to);
$total_pages = ceil($total_transactions / $records_per_page);
$summary = $transaction->getUserTransactionSummary($_SESSION['user_id'], $account_filter, $date_from, $date_to);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction History - Daffodil Bank</title>
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
        
        .transaction-amount-credit {
            color: #38a169;
            font-weight: 700;
        }
        
        .transaction-amount-debit {
            color: #e53e3e;
            font-weight: 700;
        }
        
        /* Filter Section */
        .filter-section {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        /* Summary Cards */
        .summary-card {
            border-left: 4px solid transparent;
            border-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%) 1;
        }
        
        /* Table Styling */
        .table th {
            border-top: none;
            background: #f7fafc;
            font-weight: 600;
            color: #2d3748;
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
                            <a class="nav-link active" href="transactions.php">
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
                                <h1 class="page-title">Transaction History</h1>
                                <p class="page-subtitle">View and track all your banking transactions</p>
                            </div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Transaction History</li>
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
                        Transaction history may be limited.
                    </div>
                    <?php endif; ?>

                    <!-- Transaction Summary -->
                    <?php if(!empty($summary)): ?>
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card summary-card">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-muted">Total Transactions</h6>
                                    <h3 class="text-primary"><?php echo $summary['total_count'] ?? 0; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card summary-card">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-muted">Money In</h6>
                                    <h3 class="text-success">৳<?php echo number_format($summary['total_credit'] ?? 0, 2); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card summary-card">
                                <div class="card-body text-center">
                                    <h6 class="card-title text-muted">Money Out</h6>
                                    <h3 class="text-danger">৳<?php echo number_format($summary['total_debit'] ?? 0, 2); ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Filters -->
                    <div class="filter-section">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="account_id" class="form-label">Account</label>
                                <select class="form-select" id="account_id" name="account_id">
                                    <option value="">All Accounts</option>
                                    <?php foreach($user_accounts as $acc): ?>
                                    <option value="<?php echo $acc['account_id']; ?>" <?php echo ($account_filter == $acc['account_id']) ? 'selected' : ''; ?>>
                                        <?php echo ucfirst($acc['account_type']); ?> - <?php echo $acc['account_number']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="type" class="form-label">Transaction Type</label>
                                <select class="form-select" id="type" name="type">
                                    <option value="">All Types</option>
                                    <option value="deposit" <?php echo ($type_filter == 'deposit') ? 'selected' : ''; ?>>Deposit</option>
                                    <option value="withdrawal" <?php echo ($type_filter == 'withdrawal') ? 'selected' : ''; ?>>Withdrawal</option>
                                    <option value="transfer" <?php echo ($type_filter == 'transfer') ? 'selected' : ''; ?>>Transfer</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="date_from" class="form-label">From Date</label>
                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-2">
                                <label for="date_to" class="form-label">To Date</label>
                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary btn-modern">
                                        <i class="fas fa-filter me-2"></i>Filter
                                    </button>
                                </div>
                            </div>
                        </form>
                        
                        <?php if($account_filter || $type_filter || $date_from || $date_to): ?>
                        <div class="mt-3">
                            <a href="transactions.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times me-2"></i>Clear Filters
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Transactions List -->
                    <?php if(empty($transactions)): ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-history fa-4x text-muted mb-3"></i>
                                <h4>No Transactions Found</h4>
                                <?php if($account_filter || $type_filter || $date_from || $date_to): ?>
                                    <p class="text-muted">No transactions match your selected filters.</p>
                                    <a href="transactions.php" class="btn btn-outline-primary">
                                        <i class="fas fa-times me-2"></i>Clear Filters
                                    </a>
                                <?php else: ?>
                                    <p class="text-muted">You haven't made any transactions yet.</p>
                                    <a href="transfer.php" class="btn btn-primary btn-modern">
                                        <i class="fas fa-paper-plane me-2"></i>Make First Transfer
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Transactions (<?php echo $total_transactions; ?> total)</h6>
                                <small class="text-muted">Page <?php echo $page; ?> of <?php echo $total_pages; ?></small>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date & Time</th>
                                                <th>Transaction</th>
                                                <th>Account</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($transactions as $txn): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo date('M j, Y', strtotime($txn['created_at'])); ?></strong><br>
                                                        <small class="text-muted"><?php echo date('h:i A', strtotime($txn['created_at'])); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="transaction-icon <?php echo ($txn['direction'] == 'incoming') ? 'credit-icon' : 'debit-icon'; ?>">
                                                            <?php if($txn['direction'] == 'incoming'): ?>
                                                                <i class="fas fa-arrow-down"></i>
                                                            <?php else: ?>
                                                                <i class="fas fa-arrow-up"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div>
                                                            <strong><?php echo ucfirst($txn['transaction_type']); ?></strong><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($txn['description']); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo $txn['account_number']; ?></strong><br>
                                                        <small class="text-muted"><?php echo ucfirst($txn['account_type']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="<?php echo ($txn['direction'] == 'incoming') ? 'transaction-amount-credit' : 'transaction-amount-debit'; ?>">
                                                        <?php echo ($txn['direction'] == 'incoming') ? '+' : '-'; ?>৳<?php echo number_format($txn['amount'], 2); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo ($txn['status'] == 'completed') ? 'success' : (($txn['status'] == 'pending') ? 'warning' : 'danger'); ?>">
                                                        <?php echo ucfirst($txn['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <?php if($total_pages > 1): ?>
                        <nav class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page-1); ?>&<?php echo http_build_query($_GET); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                                <?php endif; ?>

                                <?php
                                $start_page = max(1, $page - 2);
                                $end_page = min($total_pages, $page + 2);
                                
                                for($i = $start_page; $i <= $end_page; $i++):
                                ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                                <?php endfor; ?>

                                <?php if($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page+1); ?>&<?php echo http_build_query($_GET); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    <?php endif; ?>

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
                                            <a href="transfer.php" class="btn btn-primary btn-modern w-100">
                                                <i class="fas fa-paper-plane me-2"></i>New Transfer
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="accounts.php" class="btn btn-outline-success w-100">
                                                <i class="fas fa-credit-card me-2"></i>My Accounts
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="dashboard.php" class="btn btn-outline-primary w-100">
                                                <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <button onclick="window.print()" class="btn btn-outline-info w-100">
                                                <i class="fas fa-print me-2"></i>Print Statement
                                            </button>
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
        document.getElementById('date_from').addEventListener('change', function() {
            const dateFrom = this.value;
            const dateTo = document.getElementById('date_to');
            
            if(dateFrom && !dateTo.value) {
                dateTo.value = new Date().toISOString().split('T')[0];
            }
        });
    </script>
</body>
</html>
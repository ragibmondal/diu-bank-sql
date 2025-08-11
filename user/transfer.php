<?php
require_once '../config/config.php';

check_user_login();

$database = new Database();
$db = $database->getConnection();

$user = new User($db);
$user->getById($_SESSION['user_id']);

$account = new Account($db);
$user_accounts = $account->getUserAccounts($_SESSION['user_id']);

$active_accounts = array_filter($user_accounts, function($acc) {
    return $acc['status'] == 'active';
});

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['transfer'])) {
    if(!verify_csrf_token($_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $from_account_id = $_POST['from_account'] ?? '';
        $to_account_number = $_POST['to_account'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $description = sanitize_input($_POST['description'] ?? '');
        
        $errors = [];
        
        if(empty($from_account_id)) {
            $errors[] = 'Please select a source account.';
        }
        
        if(empty($to_account_number)) {
            $errors[] = 'Please enter destination account number.';
        }
        
        if(empty($amount) || !is_numeric($amount) || $amount <= 0) {
            $errors[] = 'Please enter a valid amount.';
        }
        
        if(empty($description)) {
            $errors[] = 'Please enter a transfer description.';
        }
        
        if(empty($errors)) {
            $dest_account = $account->getByAccountNumber($to_account_number);
            
            if(!$dest_account) {
                $error = 'Destination account not found.';
            } elseif($dest_account['status'] != 'active') {
                $error = 'Destination account is not active.';
            } else {
                if($from_account_id == $dest_account['account_id']) {
                    $error = 'Cannot transfer to the same account.';
                } else {
                    $source_balance = $account->getBalance($from_account_id);
                    
                    if($source_balance < $amount) {
                        $error = 'Insufficient balance. Available: ৳' . number_format($source_balance, 2);
                    } else {
                        $transaction = new Transaction($db);
                        $transfer_result = $transaction->transfer($from_account_id, $dest_account['account_id'], $amount, $description);
                        
                        if($transfer_result) {
                            $message = 'Transfer successful! ৳' . number_format($amount, 2) . ' transferred to account ' . $to_account_number;
                            // Clear form
                            $_POST = [];
                        } else {
                            $error = 'Transfer failed. Please try again.';
                        }
                    }
                }
            }
        } else {
            $error = implode('<br>', $errors);
        }
    }
}

$selected_account = $_GET['from_account'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transfer Money - Daffodil Bank</title>
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
        
        /* Form styling */
        .transfer-form {
            max-width: 600px;
            margin: 0 auto;
        }
        
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
        
        .balance-display {
            font-size: 1.2rem;
            font-weight: 700;
            color: #48bb78;
        }
        
        .account-info {
            background: #f7fafc;
            border-radius: 12px;
            padding: 1rem;
            margin-top: 10px;
            border: 1px solid #e2e8f0;
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
                            <a class="nav-link active" href="transfer.php">
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
                                <h1 class="page-title">Transfer Money</h1>
                                <p class="page-subtitle">Send money between your accounts quickly and securely</p>
                            </div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-0">
                                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Transfer Money</li>
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
                        Transfers may be restricted until approval.
                    </div>
                    <?php endif; ?>

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

                    <div class="transfer-form">
                        <?php if(empty($active_accounts)): ?>
                            <!-- No Active Accounts -->
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-credit-card fa-4x text-muted mb-3"></i>
                                    <h4>No Active Accounts</h4>
                                    <p class="text-muted">You need at least one active account to make transfers.</p>
                                    <p class="text-muted">Please contact customer service or visit a branch to activate your account.</p>
                                    <div class="mt-4">
                                        <a href="accounts.php" class="btn btn-primary btn-modern me-2">
                                            <i class="fas fa-credit-card me-2"></i>View Accounts
                                        </a>
                                        <a href="dashboard.php" class="btn btn-outline-primary">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Transfer Form -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Send Money</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" id="transferForm">
                                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                        
                                        <!-- Source Account -->
                                        <div class="mb-3">
                                            <label for="from_account" class="form-label">From Account *</label>
                                            <select class="form-select" id="from_account" name="from_account" required onchange="updateAccountInfo()">
                                                <option value="">Select source account</option>
                                                <?php foreach($active_accounts as $acc): ?>
                                                <option value="<?php echo $acc['account_id']; ?>" 
                                                        data-balance="<?php echo $acc['balance']; ?>"
                                                        data-account-number="<?php echo $acc['account_number']; ?>"
                                                        data-account-type="<?php echo $acc['account_type']; ?>"
                                                        <?php echo ($selected_account == $acc['account_id']) ? 'selected' : ''; ?>>
                                                    <?php echo ucfirst($acc['account_type']); ?> - <?php echo $acc['account_number']; ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div id="accountInfo" class="account-info" style="display: none;">
                                                <div class="row">
                                                    <div class="col-sm-6">
                                                        <small class="text-muted">Account Number:</small>
                                                        <div id="accountNumber" class="fw-bold"></div>
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <small class="text-muted">Available Balance:</small>
                                                        <div id="accountBalance" class="balance-display"></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Destination Account -->
                                        <div class="mb-3">
                                            <label for="to_account" class="form-label">To Account Number *</label>
                                            <input type="text" class="form-control" id="to_account" name="to_account" 
                                                   placeholder="Enter destination account number"
                                                   value="<?php echo htmlspecialchars($_POST['to_account'] ?? ''); ?>" required>
                                            <div class="form-text">Enter the complete account number of the recipient</div>
                                        </div>

                                        <!-- Amount -->
                                        <div class="mb-3">
                                            <label for="amount" class="form-label">Amount (৳) *</label>
                                            <input type="number" class="form-control" id="amount" name="amount" 
                                                   placeholder="0.00" step="0.01" min="1"
                                                   value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
                                            <div class="form-text">Minimum transfer amount: ৳1.00</div>
                                        </div>

                                        <!-- Description -->
                                        <div class="mb-3">
                                            <label for="description" class="form-label">Description *</label>
                                            <input type="text" class="form-control" id="description" name="description" 
                                                   placeholder="Enter transfer purpose/description"
                                                   value="<?php echo htmlspecialchars($_POST['description'] ?? ''); ?>" required>
                                            <div class="form-text">Brief description of the transfer purpose</div>
                                        </div>

                                        <!-- Transfer Summary -->
                                        <div id="transferSummary" class="alert alert-info" style="display: none;">
                                            <h6>Transfer Summary:</h6>
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <strong>From:</strong> <span id="summaryFrom"></span><br>
                                                    <strong>To:</strong> <span id="summaryTo"></span>
                                                </div>
                                                <div class="col-sm-6">
                                                    <strong>Amount:</strong> ৳<span id="summaryAmount"></span><br>
                                                    <strong>Description:</strong> <span id="summaryDescription"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                            <a href="accounts.php" class="btn btn-outline-secondary me-md-2">
                                                <i class="fas fa-times me-2"></i>Cancel
                                            </a>
                                            <button type="button" class="btn btn-info me-md-2" onclick="previewTransfer()">
                                                <i class="fas fa-eye me-2"></i>Preview
                                            </button>
                                            <button type="submit" name="transfer" class="btn btn-primary btn-modern">
                                                <i class="fas fa-paper-plane me-2"></i>Transfer Money
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Transfer Tips -->
                            <div class="card mt-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-lightbulb me-2"></i>Transfer Tips</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="mb-0">
                                        <li>Verify the destination account number before transferring</li>
                                        <li>Ensure you have sufficient balance in your source account</li>
                                        <li>Keep the transaction receipt for your records</li>
                                        <li>Transfers are processed immediately for active accounts</li>
                                        <li>Contact customer service for any transfer issues</li>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateAccountInfo() {
            const select = document.getElementById('from_account');
            const accountInfo = document.getElementById('accountInfo');
            const selectedOption = select.options[select.selectedIndex];
            
            if(selectedOption.value) {
                const balance = parseFloat(selectedOption.getAttribute('data-balance'));
                const accountNumber = selectedOption.getAttribute('data-account-number');
                const accountType = selectedOption.getAttribute('data-account-type');
                
                document.getElementById('accountNumber').textContent = accountNumber;
                document.getElementById('accountBalance').textContent = '৳' + balance.toFixed(2);
                accountInfo.style.display = 'block';
            } else {
                accountInfo.style.display = 'none';
            }
        }

        function previewTransfer() {
            const fromAccount = document.getElementById('from_account');
            const toAccount = document.getElementById('to_account').value;
            const amount = document.getElementById('amount').value;
            const description = document.getElementById('description').value;
            
            if(fromAccount.value && toAccount && amount && description) {
                const selectedOption = fromAccount.options[fromAccount.selectedIndex];
                const fromAccountText = selectedOption.text;
                
                document.getElementById('summaryFrom').textContent = fromAccountText;
                document.getElementById('summaryTo').textContent = toAccount;
                document.getElementById('summaryAmount').textContent = parseFloat(amount).toFixed(2);
                document.getElementById('summaryDescription').textContent = description;
                
                document.getElementById('transferSummary').style.display = 'block';
            } else {
                alert('Please fill in all required fields to preview the transfer.');
            }
        }

        // Auto-update account info if account is pre-selected
        document.addEventListener('DOMContentLoaded', function() {
            updateAccountInfo();
        });

        // Real-time balance check
        document.getElementById('amount').addEventListener('input', function() {
            const fromAccount = document.getElementById('from_account');
            const amount = parseFloat(this.value);
            
            if(fromAccount.value && amount) {
                const selectedOption = fromAccount.options[fromAccount.selectedIndex];
                const balance = parseFloat(selectedOption.getAttribute('data-balance'));
                
                if(amount > balance) {
                    this.setCustomValidity('Amount exceeds available balance (৳' + balance.toFixed(2) + ')');
                } else {
                    this.setCustomValidity('');
                }
            }
        });
    </script>
</body>
</html>
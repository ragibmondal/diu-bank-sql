<?php
require_once '../config/config.php';

check_admin_login();

$database = new Database();
$db = $database->getConnection();

$admin = new Admin($db);
$admin->getById($_SESSION['admin_id']);

$transaction = new Transaction($db);

$message = '';
$error = '';

$status_filter = $_GET['status'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';
$account_filter = $_GET['account_id'] ?? '';
$search = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;

if($search) {
    $transactions = $transaction->searchTransactions($search);
} else {
    $transactions = $transaction->getAllTransactions($page, $limit, $status_filter, $type_filter);
}

$stats = $transaction->getTransactionStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Admin Panel</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/fontawesome.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(135deg, #FF6B6B 0%, #4ECDC4 100%);
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 10px;
            margin: 5px 0;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }
        .transaction-row {
            transition: all 0.3s ease;
        }
        .transaction-row:hover {
            background-color: #f8f9fa;
        }
        .admin-role {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <h4><i class="fas fa-university me-2"></i>Daffodil Bank</h4>
                        <small>Admin Panel</small>
                        <div class="admin-role mt-2">
                            <?php echo ucfirst(str_replace('_', ' ', $_SESSION['admin_role'])); ?>
                        </div>
                        <small class="d-block mt-1"><?php echo htmlspecialchars($_SESSION['admin_name']); ?></small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-2"></i>Manage Users
                        </a>
                        <a class="nav-link" href="accounts.php">
                            <i class="fas fa-credit-card me-2"></i>Manage Accounts
                        </a>
                        <a class="nav-link active" href="transactions.php">
                            <i class="fas fa-exchange-alt me-2"></i>Transactions
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                        <a class="nav-link" href="audit.php">
                            <i class="fas fa-history me-2"></i>Audit Logs
                        </a>
                        <hr class="my-3">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-exchange-alt me-2"></i>Transaction Management</h2>
                        <div class="text-muted">
                            <i class="fas fa-calendar me-2"></i>
                            <?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>

                    <!-- Transaction Statistics -->
                    <div class="row mb-4">
                        <?php foreach($stats as $stat): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <h6 class="card-title"><?php echo ucfirst($stat['transaction_type']); ?></h6>
                                    <h4 class="mb-1"><?php echo number_format($stat['count']); ?></h4>
                                    <small>৳<?php echo number_format($stat['total_amount'], 2); ?></small>
                                    <br>
                                    <small>Avg: ৳<?php echo number_format($stat['average_amount'], 2); ?></small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Search and Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Search</label>
                                    <input type="text" name="search" class="form-control" 
                                           placeholder="Reference, Account Number..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select">
                                        <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                        <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="failed" <?php echo $status_filter == 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Type</label>
                                    <select name="type" class="form-select">
                                        <option value="all" <?php echo $type_filter == 'all' ? 'selected' : ''; ?>>All Types</option>
                                        <option value="deposit" <?php echo $type_filter == 'deposit' ? 'selected' : ''; ?>>Deposit</option>
                                        <option value="withdrawal" <?php echo $type_filter == 'withdrawal' ? 'selected' : ''; ?>>Withdrawal</option>
                                        <option value="transfer" <?php echo $type_filter == 'transfer' ? 'selected' : ''; ?>>Transfer</option>
                                        <option value="fee" <?php echo $type_filter == 'fee' ? 'selected' : ''; ?>>Fee</option>
                                    </select>
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-search"></i> Search
                                    </button>
                                    <a href="transactions.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Transactions Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Transactions List</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($transactions)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-exchange-alt fa-3x text-muted mb-3"></i>
                                    <h5>No transactions found</h5>
                                    <p class="text-muted">No transactions match the current search criteria.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Reference</th>
                                                <th>Type</th>
                                                <th>From Account</th>
                                                <th>To Account</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($transactions as $txn): ?>
                                            <tr class="transaction-row">
                                                <td>
                                                    <strong><?php echo htmlspecialchars($txn['reference_number']); ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $txn['transaction_type'] == 'deposit' ? 'success' : 
                                                            ($txn['transaction_type'] == 'withdrawal' ? 'danger' : 
                                                             ($txn['transaction_type'] == 'transfer' ? 'info' : 'warning')); 
                                                    ?>">
                                                        <i class="fas fa-<?php 
                                                            echo $txn['transaction_type'] == 'deposit' ? 'arrow-down' : 
                                                                ($txn['transaction_type'] == 'withdrawal' ? 'arrow-up' : 
                                                                 ($txn['transaction_type'] == 'transfer' ? 'exchange-alt' : 'dollar-sign')); 
                                                        ?>"></i>
                                                        <?php echo ucfirst($txn['transaction_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if($txn['from_account_number']): ?>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($txn['from_account_number']); ?></strong>
                                                            <?php if($txn['from_user_first']): ?>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars($txn['from_user_first'] . ' ' . $txn['from_user_last']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($txn['to_account_number']): ?>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($txn['to_account_number']); ?></strong>
                                                            <?php if($txn['to_user_first']): ?>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars($txn['to_user_first'] . ' ' . $txn['to_user_last']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <h6 class="mb-0">৳<?php echo number_format($txn['amount'], 2); ?></h6>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $txn['status'] == 'completed' ? 'success' : 
                                                            ($txn['status'] == 'pending' ? 'warning' : 'danger'); 
                                                    ?>">
                                                        <?php echo ucfirst($txn['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <?php echo date('M j, Y', strtotime($txn['created_at'])); ?>
                                                        <br>
                                                        <small class="text-muted"><?php echo date('H:i:s', strtotime($txn['created_at'])); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-info btn-sm" 
                                                            data-bs-toggle="modal" data-bs-target="#transactionModal"
                                                            data-transaction='<?php echo json_encode($txn); ?>'>
                                                        <i class="fas fa-eye"></i> View
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transaction Details Modal -->
    <div class="modal fade" id="transactionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-receipt me-2"></i>Transaction Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Reference Number:</strong></td>
                                    <td id="modalReference"></td>
                                </tr>
                                <tr>
                                    <td><strong>Transaction Type:</strong></td>
                                    <td id="modalType"></td>
                                </tr>
                                <tr>
                                    <td><strong>Amount:</strong></td>
                                    <td id="modalAmount"></td>
                                </tr>
                                <tr>
                                    <td><strong>Status:</strong></td>
                                    <td id="modalStatus"></td>
                                </tr>
                                <tr>
                                    <td><strong>Date & Time:</strong></td>
                                    <td id="modalDateTime"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>From Account:</strong></td>
                                    <td id="modalFromAccount"></td>
                                </tr>
                                <tr>
                                    <td><strong>From User:</strong></td>
                                    <td id="modalFromUser"></td>
                                </tr>
                                <tr>
                                    <td><strong>To Account:</strong></td>
                                    <td id="modalToAccount"></td>
                                </tr>
                                <tr>
                                    <td><strong>To User:</strong></td>
                                    <td id="modalToUser"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <strong>Description:</strong>
                            <div class="bg-light p-3 rounded mt-2" id="modalDescription"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle transaction modal data population
        document.getElementById('transactionModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var transaction = JSON.parse(button.getAttribute('data-transaction'));
            
            document.getElementById('modalReference').textContent = transaction.reference_number;
            document.getElementById('modalType').innerHTML = '<span class="badge bg-info">' + transaction.transaction_type.charAt(0).toUpperCase() + transaction.transaction_type.slice(1) + '</span>';
            document.getElementById('modalAmount').innerHTML = '<strong>৳' + parseFloat(transaction.amount).toLocaleString() + '</strong>';
            document.getElementById('modalStatus').innerHTML = '<span class="badge bg-' + (transaction.status === 'completed' ? 'success' : (transaction.status === 'pending' ? 'warning' : 'danger')) + '">' + transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1) + '</span>';
            document.getElementById('modalDateTime').textContent = new Date(transaction.created_at).toLocaleString();
            
            document.getElementById('modalFromAccount').textContent = transaction.from_account_number || '-';
            document.getElementById('modalFromUser').textContent = transaction.from_user_first ? (transaction.from_user_first + ' ' + transaction.from_user_last) : '-';
            document.getElementById('modalToAccount').textContent = transaction.to_account_number || '-';
            document.getElementById('modalToUser').textContent = transaction.to_user_first ? (transaction.to_user_first + ' ' + transaction.to_user_last) : '-';
            document.getElementById('modalDescription').textContent = transaction.description || 'No description provided';
        });
    </script>
</body>
</html>
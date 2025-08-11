<?php
require_once '../config/config.php';

check_admin_login();

$database = new Database();
$db = $database->getConnection();
$admin = new Admin($db);
$admin->getById($_SESSION['admin_id']);

$account = new Account($db);
$user = new User($db);

$message = '';
$error = '';
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';

        if($action == 'create_account') {
            $user_id = $_POST['user_id'] ?? '';
            $account_type = $_POST['account_type'] ?? '';

            if($user_id && $account_type) {
                $account_number = $account->create($user_id, $account_type, $_SESSION['admin_id']);
                if($account_number) {
                    $message = "Account created successfully. Account Number: $account_number";
                } else {
                    $error = 'Failed to create account.';
                }
            } else {
                $error = 'Please fill in all required fields.';
            }
        } elseif($action == 'update_status') {
            $account_id = $_POST['account_id'] ?? '';
            $status = $_POST['status'] ?? '';

            if($account_id && $status) {
                if($account->updateStatus($account_id, $status, $_SESSION['admin_id'])) {
                    $message = "Account status updated to: " . ucfirst($status);
                } else {
                    $error = 'Failed to update account status.';
                }
            }
        } elseif($action == 'soft_delete_account') {
            $account_id = $_POST['account_id'] ?? '';
            if($account_id) {
                $result = $account->softDelete($account_id, $_SESSION['admin_id']);
                if($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
        } elseif($action == 'hard_delete_account') {
            $account_id = $_POST['account_id'] ?? '';
            if($account_id) {
                $result = $account->hardDelete($account_id, $_SESSION['admin_id']);
                if($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
        } elseif($action == 'adjust_balance') {
            $account_id = $_POST['account_id'] ?? '';
            $new_balance = $_POST['new_balance'] ?? '';
            $reason = $_POST['reason'] ?? '';

            if($account_id && is_numeric($new_balance) && $reason) {
                if($account->updateBalance($account_id, $new_balance, $_SESSION['admin_id'], $reason)) {
                    $message = "Balance adjusted successfully.";
                } else {
                    $error = 'Failed to adjust balance.';
                }
            } else {
                $error = 'Please fill in all required fields with valid values.';
            }
        }
    }
}

$status_filter = $_GET['status'] ?? 'all';
$user_filter = $_GET['user_id'] ?? '';
$search_query = $_GET['search'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;

$all_accounts = $account->getAllAccountsWithSearch($page, $limit, $status_filter, $search_query);

$approved_users = $admin->getAllUsers(1, 100, 'approved');

try {
    $query = "SELECT * FROM account_types WHERE is_active = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $account_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $account_types = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Accounts - Admin Panel</title>
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
        .balance-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .admin-role {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        
        .search-card {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 1px solid #dee2e6;
        }
        
        .search-input-group {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .search-input-group .form-control {
            border: none;
            box-shadow: none;
            padding: 12px 16px;
        }
        
        .search-input-group .input-group-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        
        /* Search Results Highlighting */
        mark.bg-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffeaa7 100%);
            padding: 2px 4px;
            border-radius: 3px;
            font-weight: 600;
            color: #856404;
        }
        
        /* Quick Filter Buttons */
        .quick-filter-btn {
            transition: all 0.3s ease;
            border-radius: 20px;
            margin: 2px;
        }
        
        .quick-filter-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        /* Search Result Info Alert */
        .search-info-alert {
            background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
            border: 1px solid #b6d7d9;
            border-radius: 10px;
        }
        
        /* Enhanced Table Styling */
        .table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #495057;
        }
        
        .table td {
            vertical-align: middle;
            padding: 12px;
        }
        
        .table tbody tr {
            transition: all 0.3s ease;
        }
        
        .table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.02);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        /* Loading Animation */
        .search-loading {
            display: inline-block;
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        /* Status Badge Enhancements */
        .badge {
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 15px;
        }
        
        /* Search Form Enhancements */
        .search-form-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        /* Clear Search Button */
        #clearSearch {
            border-radius: 0 8px 8px 0;
            transition: all 0.3s ease;
        }
        
        #clearSearch:hover {
            background: #dc3545;
            color: white;
            border-color: #dc3545;
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
                        <a class="nav-link active" href="accounts.php">
                            <i class="fas fa-credit-card me-2"></i>Manage Accounts
                        </a>
                        <a class="nav-link" href="transactions.php">
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
                        <h2><i class="fas fa-credit-card me-2"></i>Manage Accounts</h2>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createAccountModal">
                            <i class="fas fa-plus me-2"></i>Create Account
                        </button>
                    </div>

                    <!-- Messages -->
                    <?php if($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Search and Filters -->
                    <div class="card mb-4 search-card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-search me-2"></i>Search & Filter Accounts</h6>
                        </div>
                        <div class="card-body search-form-container">
                            <form method="GET" action="accounts.php" id="searchForm">
                                <div class="row g-3 align-items-end">
                                    <div class="col-md-5">
                                        <label for="search" class="form-label fw-semibold">Search Accounts</label>
                                        <div class="input-group search-input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="search" 
                                                   name="search" 
                                                   value="<?php echo htmlspecialchars($search_query); ?>" 
                                                   placeholder="Account number, name, email, or account type..."
                                                   autocomplete="off">
                                            <?php if(!empty($search_query)): ?>
                                            <button type="button" class="btn btn-outline-secondary" id="clearSearch" title="Clear search">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted fst-italic">Search by account number, holder name, email, username, or account type</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="status" class="form-label">Filter by Status</label>
                                        <select name="status" id="status" class="form-select">
                                            <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            <option value="active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="suspended" <?php echo $status_filter == 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                            <option value="closed" <?php echo $status_filter == 'closed' ? 'selected' : ''; ?>>Closed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="d-grid gap-2">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-search me-2"></i>Search
                                            </button>
                                            <a href="accounts.php" class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-refresh me-2"></i>Clear All
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            
                            <!-- Quick Status Filters -->
                            <div class="row mt-3">
                                <div class="col-12">
                                    <small class="text-muted d-block mb-2 fw-semibold">Quick Filters:</small>
                                    <div class="d-flex flex-wrap gap-1" role="group">
                                        <a href="accounts.php?status=all<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                                           class="btn btn-sm quick-filter-btn <?php echo $status_filter == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                            <i class="fas fa-list me-1"></i>All (<?php echo count($account->getAllAccountsWithSearch(1, 1000, 'all', $search_query)); ?>)
                                        </a>
                                        <a href="accounts.php?status=pending<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                                           class="btn btn-sm quick-filter-btn <?php echo $status_filter == 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                            <i class="fas fa-clock me-1"></i>Pending (<?php echo count($account->getAllAccountsWithSearch(1, 1000, 'pending', $search_query)); ?>)
                                        </a>
                                        <a href="accounts.php?status=active<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                                           class="btn btn-sm quick-filter-btn <?php echo $status_filter == 'active' ? 'btn-success' : 'btn-outline-success'; ?>">
                                            <i class="fas fa-check-circle me-1"></i>Active (<?php echo count($account->getAllAccountsWithSearch(1, 1000, 'active', $search_query)); ?>)
                                        </a>
                                        <a href="accounts.php?status=suspended<?php echo !empty($search_query) ? '&search=' . urlencode($search_query) : ''; ?>" 
                                           class="btn btn-sm quick-filter-btn <?php echo $status_filter == 'suspended' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                            <i class="fas fa-pause-circle me-1"></i>Suspended (<?php echo count($account->getAllAccountsWithSearch(1, 1000, 'suspended', $search_query)); ?>)
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if(!empty($search_query)): ?>
                            <div class="mt-3">
                                <div class="alert search-info-alert mb-0">
                                    <i class="fas fa-search me-2"></i>
                                    Showing results for: <strong class="text-primary">"<?php echo htmlspecialchars($search_query); ?>"</strong>
                                    <span class="text-muted">| Found <strong><?php echo count($all_accounts); ?></strong> result(s)</span>
                                    <?php if($status_filter != 'all'): ?>
                                        <span class="text-muted">in <strong><?php echo ucfirst($status_filter); ?></strong> accounts</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Accounts Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Accounts List</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($all_accounts)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                                    <h5>No accounts found</h5>
                                    <p class="text-muted">No accounts match the current filter criteria.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Account Details</th>
                                                <th>Account Holder</th>
                                                <th>Balance</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($all_accounts as $acc): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($acc['account_number']); ?></strong>
                                                        <br>
                                                        <span class="badge bg-info"><?php echo ucfirst($acc['account_type']); ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($acc['first_name'] . ' ' . $acc['last_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?php echo htmlspecialchars($acc['email']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <h6 class="mb-0">৳<?php echo number_format($acc['balance'], 2); ?></h6>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $acc['status'] == 'active' ? 'success' : 
                                                            ($acc['status'] == 'pending' ? 'warning' : 'danger'); 
                                                    ?>">
                                                        <?php echo ucfirst($acc['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M j, Y', strtotime($acc['created_at'])); ?></small>
                                                    <?php if($acc['created_by_name']): ?>
                                                        <br><small class="text-muted">by <?php echo htmlspecialchars($acc['created_by_name']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm">
                                                        <?php if($acc['status'] == 'pending'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="account_id" value="<?php echo $acc['account_id']; ?>">
                                                                <input type="hidden" name="status" value="active">
                                                                <button type="submit" class="btn btn-success btn-sm">
                                                                    <i class="fas fa-check"></i> Activate
                                                                </button>
                                                            </form>
                                                        <?php elseif($acc['status'] == 'active'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="account_id" value="<?php echo $acc['account_id']; ?>">
                                                                <input type="hidden" name="status" value="suspended">
                                                                <button type="submit" class="btn btn-warning btn-sm">
                                                                    <i class="fas fa-pause"></i> Suspend
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="account_id" value="<?php echo $acc['account_id']; ?>">
                                                                <input type="hidden" name="status" value="active">
                                                                <button type="submit" class="btn btn-success btn-sm">
                                                                    <i class="fas fa-play"></i> Reactivate
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        
                                                        <button type="button" class="btn btn-info btn-sm" 
                                                                data-bs-toggle="modal" data-bs-target="#balanceModal"
                                                                data-account-id="<?php echo $acc['account_id']; ?>"
                                                                data-account-number="<?php echo $acc['account_number']; ?>"
                                                                data-current-balance="<?php echo $acc['balance']; ?>">
                                                            <i class="fas fa-wallet"></i> Adjust Balance
                                                        </button>
                                                        
                                                        <a href="transactions.php?account_id=<?php echo $acc['account_id']; ?>" 
                                                           class="btn btn-primary btn-sm">
                                                            <i class="fas fa-history"></i> Transactions
                                                        </a>

                                                        <button type="button" class="btn btn-danger btn-sm" 
                                                                data-bs-toggle="modal" data-bs-target="#deleteAccountModal"
                                                                data-account-id="<?php echo $acc['account_id']; ?>"
                                                                data-account-number="<?php echo htmlspecialchars($acc['account_number']); ?>"
                                                                data-account-type="<?php echo ucfirst($acc['account_type']); ?>"
                                                                data-account-balance="<?php echo $acc['balance']; ?>"
                                                                data-account-holder="<?php echo htmlspecialchars($acc['first_name'] . ' ' . $acc['last_name']); ?>">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </div>
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

    <!-- Create Account Modal -->
    <div class="modal fade" id="createAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Create New Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="create_account">
                        
                        <div class="mb-3">
                            <label class="form-label">Select User</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">Choose an approved user...</option>
                                <?php foreach($approved_users as $user_data): ?>
                                    <option value="<?php echo $user_data['user_id']; ?>">
                                        <?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name'] . ' (' . $user_data['email'] . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Account Type</label>
                            <select name="account_type" class="form-select" required>
                                <option value="">Choose account type...</option>
                                <?php foreach($account_types as $type): ?>
                                    <option value="<?php echo $type['type_name']; ?>">
                                        <?php echo ucfirst($type['type_name']); ?> - <?php echo $type['description']; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-plus me-2"></i>Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Balance Adjustment Modal -->
    <div class="modal fade" id="balanceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-wallet me-2"></i>Adjust Account Balance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="adjust_balance">
                        <input type="hidden" name="account_id" id="balanceAccountId">
                        
                        <div class="mb-3">
                            <label class="form-label">Account Number</label>
                            <input type="text" class="form-control" id="balanceAccountNumber" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Current Balance</label>
                            <input type="text" class="form-control" id="balanceCurrentBalance" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">New Balance</label>
                            <input type="number" step="0.01" min="0" name="new_balance" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reason for Adjustment</label>
                            <textarea name="reason" class="form-control" rows="3" placeholder="Enter reason for balance adjustment..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-wallet me-2"></i>Adjust Balance
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal fade" id="deleteAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Delete Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-warning me-2"></i>
                        <strong>Warning:</strong> This action requires careful consideration. Please choose the appropriate option.
                    </div>

                    <div class="mb-3">
                        <h6>Account Information:</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td><strong>Account Number:</strong></td>
                                <td id="deleteAccountNumber"></td>
                            </tr>
                            <tr>
                                <td><strong>Type:</strong></td>
                                <td id="deleteAccountType"></td>
                            </tr>
                            <tr>
                                <td><strong>Holder:</strong></td>
                                <td id="deleteAccountHolder"></td>
                            </tr>
                            <tr>
                                <td><strong>Balance:</strong></td>
                                <td id="deleteAccountBalance" class="fw-bold"></td>
                            </tr>
                        </table>
                    </div>

                    <div class="mb-3">
                        <h6>Deletion Options:</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="account_delete_type" id="softDeleteAccount" value="soft" checked>
                            <label class="form-check-label" for="softDeleteAccount">
                                <strong>Close Account (Recommended)</strong>
                                <br><small class="text-muted">Account will be closed but data preserved for audit purposes</small>
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="radio" name="account_delete_type" id="hardDeleteAccount" value="hard">
                            <label class="form-check-label" for="hardDeleteAccount">
                                <strong>Permanently Delete Account</strong>
                                <br><small class="text-muted">Complete removal from database (only if zero balance and no transactions)</small>
                            </label>
                        </div>
                    </div>

                    <div id="accountDeletionWarnings" class="alert alert-info" style="display: none;">
                        <h6>Important Notes:</h6>
                        <ul id="accountWarningsList" class="mb-0"></ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirmSoftDeleteAccount">
                        <i class="fas fa-ban me-2"></i>Close Account
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmHardDeleteAccount" style="display: none;">
                        <i class="fas fa-trash me-2"></i>Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enhanced search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search');
            const statusSelect = document.getElementById('status');
            const searchForm = document.getElementById('searchForm');
            const clearSearchBtn = document.getElementById('clearSearch');
            let searchTimeout;

            // Real-time search with debouncing
            if(searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(function() {
                        if(searchInput.value.length >= 3 || searchInput.value.length === 0) {
                            searchForm.submit();
                        }
                    }, 500); // Wait 500ms after user stops typing
                });

                // Search on Enter key
                searchInput.addEventListener('keypress', function(e) {
                    if(e.key === 'Enter') {
                        e.preventDefault();
                        clearTimeout(searchTimeout);
                        searchForm.submit();
                    }
                });
            }

            // Status filter change
            if(statusSelect) {
                statusSelect.addEventListener('change', function() {
                    searchForm.submit();
                });
            }

            // Clear search functionality
            if(clearSearchBtn) {
                clearSearchBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    searchForm.submit();
                });
            }

            const searchQuery = '<?php echo addslashes($search_query); ?>';
            if(searchQuery) {
                highlightSearchTerms(searchQuery);
            }

            searchForm.addEventListener('submit', function() {
                const submitBtn = searchForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Searching...';
                submitBtn.disabled = true;
                
                setTimeout(function() {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 1000);
            });
        });

        function highlightSearchTerms(searchQuery) {
            if(!searchQuery || searchQuery.length < 2) return;
            
            const tableBody = document.querySelector('table tbody');
            if(!tableBody) return;
            
            const searchRegex = new RegExp(`(${escapeRegExp(searchQuery)})`, 'gi');
            
            tableBody.querySelectorAll('td').forEach(function(cell) {
                if(cell.querySelector('.btn-group-vertical')) return;
                
                const textNodes = getTextNodes(cell);
                textNodes.forEach(function(node) {
                    if(node.nodeValue.toLowerCase().includes(searchQuery.toLowerCase())) {
                        const parent = node.parentNode;
                        const highlightedHTML = node.nodeValue.replace(searchRegex, '<mark class="bg-warning">$1</mark>');
                        const wrapper = document.createElement('span');
                        wrapper.innerHTML = highlightedHTML;
                        parent.replaceChild(wrapper, node);
                    }
                });
            });
        }

        function escapeRegExp(string) {
            return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        function getTextNodes(element) {
            const textNodes = [];
            const walker = document.createTreeWalker(
                element,
                NodeFilter.SHOW_TEXT,
                null,
                false
            );
            
            let node;
            while(node = walker.nextNode()) {
                if(node.nodeValue.trim()) {
                    textNodes.push(node);
                }
            }
            return textNodes;
        }

        function animateSearchResults() {
            const tableRows = document.querySelectorAll('table tbody tr');
            tableRows.forEach(function(row, index) {
                row.style.opacity = '0';
                row.style.transform = 'translateY(20px)';
                
                setTimeout(function() {
                    row.style.transition = 'all 0.3s ease';
                    row.style.opacity = '1';
                    row.style.transform = 'translateY(0)';
                }, index * 50);
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            animateSearchResults();
        });

        document.getElementById('balanceModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var accountId = button.getAttribute('data-account-id');
            var accountNumber = button.getAttribute('data-account-number');
            var currentBalance = button.getAttribute('data-current-balance');
            
            document.getElementById('balanceAccountId').value = accountId;
            document.getElementById('balanceAccountNumber').value = accountNumber;
            document.getElementById('balanceCurrentBalance').value = '৳' + parseFloat(currentBalance).toFixed(2);
        });

        let currentAccountId = null;
        let currentAccountBalance = 0;

        document.getElementById('deleteAccountModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            currentAccountId = button.getAttribute('data-account-id');
            var accountNumber = button.getAttribute('data-account-number');
            var accountType = button.getAttribute('data-account-type');
            var accountHolder = button.getAttribute('data-account-holder');
            currentAccountBalance = parseFloat(button.getAttribute('data-account-balance'));
            
            document.getElementById('deleteAccountNumber').textContent = accountNumber;
            document.getElementById('deleteAccountType').textContent = accountType;
            document.getElementById('deleteAccountHolder').textContent = accountHolder;
            document.getElementById('deleteAccountBalance').textContent = '৳' + currentAccountBalance.toFixed(2);

            var warnings = [];
            if(currentAccountBalance > 0) {
                warnings.push('Account has a balance of ৳' + currentAccountBalance.toFixed(2) + '. Withdraw or transfer funds first.');
                warnings.push('Accounts with balance cannot be closed or deleted.');
            }

            if(warnings.length > 0) {
                document.getElementById('accountDeletionWarnings').style.display = 'block';
                document.getElementById('accountWarningsList').innerHTML = warnings.map(w => '<li>' + w + '</li>').join('');
            } else {
                document.getElementById('accountDeletionWarnings').style.display = 'none';
            }

            document.getElementById('softDeleteAccount').checked = true;
            updateAccountDeleteButtons();
        });

        document.querySelectorAll('input[name="account_delete_type"]').forEach(function(radio) {
            radio.addEventListener('change', updateAccountDeleteButtons);
        });

        function updateAccountDeleteButtons() {
            var deleteType = document.querySelector('input[name="account_delete_type"]:checked').value;
            
            if(deleteType === 'soft') {
                document.getElementById('confirmSoftDeleteAccount').style.display = 'inline-block';
                document.getElementById('confirmHardDeleteAccount').style.display = 'none';
            } else {
                document.getElementById('confirmSoftDeleteAccount').style.display = 'none';
                document.getElementById('confirmHardDeleteAccount').style.display = 'inline-block';
            }

            if(currentAccountBalance > 0) {
                document.getElementById('confirmSoftDeleteAccount').disabled = true;
                document.getElementById('confirmHardDeleteAccount').disabled = true;
            } else {
                document.getElementById('confirmSoftDeleteAccount').disabled = false;
                document.getElementById('confirmHardDeleteAccount').disabled = false;
            }
        }

        document.getElementById('confirmSoftDeleteAccount').addEventListener('click', function() {
            performAccountDeletion('soft_delete_account');
        });

        document.getElementById('confirmHardDeleteAccount').addEventListener('click', function() {
            if(confirm('Are you absolutely sure you want to permanently delete this account? This action cannot be undone!')) {
                performAccountDeletion('hard_delete_account');
            }
        });

        function performAccountDeletion(action) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = 
                '<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">' +
                '<input type="hidden" name="action" value="' + action + '">' +
                '<input type="hidden" name="account_id" value="' + currentAccountId + '">';
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
<?php
require_once '../config/config.php';

check_admin_login();

$database = new Database();
$db = $database->getConnection();

$admin = new Admin($db);
$admin->getById($_SESSION['admin_id']);

$stats = $admin->getDashboardStats();

$recent_activities = $admin->getRecentActivities(5);

$user = new User($db);
$pending_users = $user->getPendingUsers();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Daffodil Bank</title>
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
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .stat-card.users {
            background: linear-gradient(135deg, #FF6B6B 0%, #FF8E53 100%);
        }
        .stat-card.accounts {
            background: linear-gradient(135deg, #4ECDC4 0%, #44A08D 100%);
        }
        .stat-card.transactions {
            background: linear-gradient(135deg, #FFB75E 0%, #ED8F03 100%);
        }
        .btn-custom {
            border-radius: 25px;
            padding: 8px 20px;
            font-weight: 600;
        }
        .admin-role {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
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
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link" href="users.php">
                            <i class="fas fa-users me-2"></i>Manage Users
                            <?php if($stats['pending_users'] > 0): ?>
                                <span class="badge bg-warning ms-1"><?php echo $stats['pending_users']; ?></span>
                            <?php endif; ?>
                        </a>
                        <a class="nav-link" href="accounts.php">
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
                        <h2>Admin Dashboard</h2>
                        <div class="text-muted">
                            <i class="fas fa-calendar me-2"></i>
                            <?php echo date('l, F j, Y'); ?>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card users">
                                <div class="card-body text-center">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title">Total Users</h5>
                                            <h2 class="mb-0"><?php echo number_format($stats['total_users']); ?></h2>
                                            <?php if($stats['pending_users'] > 0): ?>
                                                <small><?php echo $stats['pending_users']; ?> pending approval</small>
                                            <?php endif; ?>
                                        </div>
                                        <i class="fas fa-users fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card accounts">
                                <div class="card-body text-center">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title">Total Accounts</h5>
                                            <h2 class="mb-0"><?php echo number_format($stats['total_accounts']); ?></h2>
                                            <small><?php echo $stats['active_accounts']; ?> active</small>
                                        </div>
                                        <i class="fas fa-credit-card fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card">
                                <div class="card-body text-center">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title">Total Balance</h5>
                                            <h2 class="mb-0">৳<?php echo number_format($stats['total_balance'], 0); ?></h2>
                                            <small>In active accounts</small>
                                        </div>
                                        <i class="fas fa-wallet fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="card stat-card transactions">
                                <div class="card-body text-center">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="card-title">Today's Transactions</h5>
                                            <h2 class="mb-0"><?php echo number_format($stats['today_transactions']); ?></h2>
                                            <small>৳<?php echo number_format($stats['today_transaction_amount'], 0); ?></small>
                                        </div>
                                        <i class="fas fa-exchange-alt fa-2x opacity-50"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-2">
                                            <a href="users.php?status=pending" class="btn btn-warning btn-custom w-100">
                                                <i class="fas fa-user-check me-2"></i>Approve Users
                                                <?php if($stats['pending_users'] > 0): ?>
                                                    <span class="badge bg-light text-dark ms-1"><?php echo $stats['pending_users']; ?></span>
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="users.php" class="btn btn-success btn-custom w-100">
                                                <i class="fas fa-user-plus me-2"></i>Create User
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="accounts.php" class="btn btn-info btn-custom w-100">
                                                <i class="fas fa-credit-card me-2"></i>Create Account
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-2">
                                            <a href="reports.php" class="btn btn-primary btn-custom w-100">
                                                <i class="fas fa-chart-bar me-2"></i>Generate Report
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Pending User Approvals -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Pending User Approvals</h5>
                                    <a href="users.php?status=pending" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if(empty($pending_users)): ?>
                                        <div class="text-center text-muted py-3">
                                            <i class="fas fa-user-check fa-2x mb-2"></i>
                                            <p>No pending approvals</p>
                                            <small>All user registrations are up to date</small>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach(array_slice($pending_users, 0, 5) as $pending_user): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-3 p-2 border rounded">
                                            <div>
                                                <h6 class="mb-1"><?php echo htmlspecialchars($pending_user['first_name'] . ' ' . $pending_user['last_name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($pending_user['email']); ?></small><br>
                                                <small class="text-muted">Registered: <?php echo date('M j, Y', strtotime($pending_user['created_at'])); ?></small>
                                            </div>
                                            <div>
                                                <a href="users.php?action=approve&id=<?php echo $pending_user['user_id']; ?>" 
                                                   class="btn btn-success btn-sm me-1">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="users.php?action=reject&id=<?php echo $pending_user['user_id']; ?>" 
                                                   class="btn btn-danger btn-sm">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Activities -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">Recent System Activities</h5>
                                    <a href="audit.php" class="btn btn-sm btn-outline-primary">View All</a>
                                </div>
                                <div class="card-body">
                                    <?php if(empty($recent_activities)): ?>
                                        <div class="text-center text-muted py-3">
                                            <i class="fas fa-history fa-2x mb-2"></i>
                                            <p>No recent activities</p>
                                            <small>System activities will appear here</small>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach($recent_activities as $activity): ?>
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="me-3">
                                                <i class="fas fa-circle text-primary" style="font-size: 0.5rem;"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo htmlspecialchars($activity['action']); ?></h6>
                                                <small class="text-muted">
                                                    by <?php echo htmlspecialchars($activity['admin_name'] ?: 'System'); ?>
                                                    - <?php echo date('M j, H:i', strtotime($activity['created_at'])); ?>
                                                </small>
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
</body>
</html>
<?php
require_once '../config/config.php';

check_admin_login();

$database = new Database();
$db = $database->getConnection();

$admin = new Admin($db);
$admin->getById($_SESSION['admin_id']);

$user = new User($db);

$message = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        $user_id = $_POST['user_id'] ?? '';

        if($action == 'approve' && $user_id) {
            if($user->updateRegistrationStatus($user_id, 'approved', $_SESSION['admin_id'])) {
                $message = 'User registration approved successfully.';
            } else {
                $error = 'Failed to approve user registration.';
            }
        } elseif($action == 'reject' && $user_id) {
            if($user->updateRegistrationStatus($user_id, 'rejected', $_SESSION['admin_id'])) {
                $message = 'User registration rejected.';
            } else {
                $error = 'Failed to reject user registration.';
            }
        } elseif($action == 'toggle_active' && $user_id) {
            $is_active = $_POST['is_active'] == '1' ? 1 : 0;
            if($admin->toggleUserActivation($user_id, $is_active)) {
                $message = $is_active ? 'User activated successfully.' : 'User deactivated successfully.';
            } else {
                $error = 'Failed to update user status.';
            }
        } elseif($action == 'soft_delete_user') {
            $user_id = $_POST['user_id'] ?? '';
            if($user_id) {
                if($user->softDelete($user_id, $_SESSION['admin_id'])) {
                    $message = 'User deactivated successfully.';
                } else {
                    $error = 'Failed to deactivate user.';
                }
            }
        } elseif($action == 'hard_delete_user') {
            $user_id = $_POST['user_id'] ?? '';
            if($user_id) {
                $result = $user->hardDelete($user_id, $_SESSION['admin_id']);
                if($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
            }
        } elseif($action == 'create_user') {
            $username = sanitize_input($_POST['username']);
            $email = sanitize_input($_POST['email']);
            $password = $_POST['password'];
            $first_name = sanitize_input($_POST['first_name']);
            $last_name = sanitize_input($_POST['last_name']);
            $phone = sanitize_input($_POST['phone']);
            $address = sanitize_input($_POST['address']);
            $date_of_birth = $_POST['date_of_birth'];
            $auto_approve = isset($_POST['auto_approve']) ? true : false;

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

            if(empty($first_name) || empty($last_name)) {
                $errors[] = 'First name and last name are required.';
            }

            if(empty($phone)) {
                $errors[] = 'Phone number is required.';
            }

            if(empty($date_of_birth)) {
                $errors[] = 'Date of birth is required.';
            }

            if(empty($errors)) {
                if($user->exists($username, $email)) {
                    $error = 'Username or email already exists. Please choose different ones.';
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
                        if($auto_approve) {
                            $new_user_id = $db->lastInsertId();
                            $user->updateRegistrationStatus($new_user_id, 'approved', $_SESSION['admin_id']);
                            $message = 'User created and approved successfully!';
                        } else {
                            $message = 'User created successfully and is pending approval.';
                        }
                    } else {
                        $error = 'Failed to create user. Please try again.';
                    }
                }
            } else {
                $error = implode('<br>', $errors);
            }
        }
    }
}

if($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
    $user_id = $_GET['id'] ?? '';

    if($action == 'approve' && $user_id) {
        if($user->updateRegistrationStatus($user_id, 'approved', $_SESSION['admin_id'])) {
            $message = 'User registration approved successfully.';
        } else {
            $error = 'Failed to approve user registration.';
        }
    } elseif($action == 'reject' && $user_id) {
        if($user->updateRegistrationStatus($user_id, 'rejected', $_SESSION['admin_id'])) {
            $message = 'User registration rejected.';
        } else {
            $error = 'Failed to reject user registration.';
        }
    }
}

$status_filter = $_GET['status'] ?? 'all';
$search_term = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;

if(!empty($search_term)) {
    $all_users = $admin->searchUsers($search_term, $page, $limit, $status_filter);
    $total_users = $admin->getSearchUserCount($search_term, $status_filter);
} else {
    $all_users = $admin->getAllUsers($page, $limit, $status_filter);
    $total_users = $admin->getUserCount($status_filter);
}
$total_pages = ceil($total_users / $limit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
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
        .status-badge {
            font-size: 0.8rem;
            padding: 4px 12px;
            border-radius: 20px;
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a class="nav-link active" href="users.php">
                            <i class="fas fa-users me-2"></i>Manage Users
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

            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-users me-2"></i>Manage Users</h2>
                        <div class="d-flex align-items-center">
                            <span class="text-muted me-3">Total Users: <?php echo $total_users; ?></span>
                            <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createUserModal">
                                <i class="fas fa-user-plus me-2"></i>Create User
                            </button>
                        </div>
                    </div>

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
                    <div class="card mb-4">
                        <div class="card-body">
                            <!-- Search Form -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <form method="GET" class="d-flex gap-2">
                                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                                        <div class="input-group flex-grow-1">
                                            <span class="input-group-text">
                                                <i class="fas fa-search"></i>
                                            </span>
                                            <input type="text" 
                                                   name="search" 
                                                   class="form-control" 
                                                   placeholder="Search by username, email, name, or account number..." 
                                                   value="<?php echo htmlspecialchars($search_term); ?>">
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search me-1"></i>Search
                                        </button>
                                        <?php if(!empty($search_term)): ?>
                                        <a href="users.php?status=<?php echo $status_filter; ?>" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-1"></i>Clear
                                        </a>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                            
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <h6 class="mb-0">Filter by Status:</h6>
                                </div>
                                <div class="col-md-9">
                                    <div class="btn-group w-100" role="group">
                                        <a href="users.php?status=all<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" 
                                           class="btn <?php echo $status_filter == 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                            All Users
                                        </a>
                                        <a href="users.php?status=pending<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" 
                                           class="btn <?php echo $status_filter == 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                            Pending
                                        </a>
                                        <a href="users.php?status=approved<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" 
                                           class="btn <?php echo $status_filter == 'approved' ? 'btn-success' : 'btn-outline-success'; ?>">
                                            Approved
                                        </a>
                                        <a href="users.php?status=rejected<?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>" 
                                           class="btn <?php echo $status_filter == 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?>">
                                            Rejected
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if(!empty($search_term)): ?>
                            <div class="mt-3">
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-search me-2"></i>
                                    <strong>Search Results:</strong> Found <?php echo $total_users; ?> user(s) matching "<?php echo htmlspecialchars($search_term); ?>"
                                    <?php if($status_filter != 'all'): ?>
                                        with status: <span class="badge bg-secondary"><?php echo ucfirst($status_filter); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Users List</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($all_users)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                    <h5>No users found</h5>
                                    <?php if(!empty($search_term)): ?>
                                        <p class="text-muted">No users match your search for "<?php echo htmlspecialchars($search_term); ?>"
                                        <?php if($status_filter != 'all'): ?>
                                            with status "<?php echo ucfirst($status_filter); ?>"
                                        <?php endif; ?>
                                        .</p>
                                        <a href="users.php?status=<?php echo $status_filter; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-times me-1"></i>Clear Search
                                        </a>
                                    <?php else: ?>
                                        <p class="text-muted">No users match the current filter criteria.</p>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>User Info</th>
                                                <th>Contact</th>
                                                <th>Registration</th>
                                                <th>Accounts</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($all_users as $user_data): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?></strong>
                                                        <br>
                                                        <small class="text-muted">@<?php echo htmlspecialchars($user_data['username']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div>
                                                        <i class="fas fa-envelope me-1"></i>
                                                        <?php echo htmlspecialchars($user_data['email']); ?>
                                                        <br>
                                                        <i class="fas fa-phone me-1"></i>
                                                        <?php echo htmlspecialchars($user_data['phone']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <small><?php echo date('M j, Y', strtotime($user_data['created_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $user_data['account_count']; ?> accounts</span>
                                                    <?php if(!empty($user_data['account_numbers'])): ?>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($user_data['account_numbers']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge status-badge bg-<?php 
                                                        echo $user_data['registration_status'] == 'approved' ? 'success' : 
                                                            ($user_data['registration_status'] == 'pending' ? 'warning' : 'danger'); 
                                                    ?>">
                                                        <?php echo ucfirst($user_data['registration_status']); ?>
                                                    </span>
                                                    <br>
                                                    <span class="badge bg-<?php echo $user_data['is_active'] ? 'success' : 'secondary'; ?> mt-1">
                                                        <?php echo $user_data['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group-vertical btn-group-sm">
                                                        <?php if($user_data['registration_status'] == 'pending'): ?>
                                                            <a href="users.php?action=approve&id=<?php echo $user_data['user_id']; ?>" 
                                                               class="btn btn-success btn-sm"
                                                               onclick="return confirm('Approve this user registration?')">
                                                                <i class="fas fa-check"></i> Approve
                                                            </a>
                                                            <a href="users.php?action=reject&id=<?php echo $user_data['user_id']; ?>" 
                                                               class="btn btn-danger btn-sm"
                                                               onclick="return confirm('Reject this user registration?')">
                                                                <i class="fas fa-times"></i> Reject
                                                            </a>
                                                        <?php else: ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                                <input type="hidden" name="action" value="toggle_active">
                                                                <input type="hidden" name="user_id" value="<?php echo $user_data['user_id']; ?>">
                                                                <input type="hidden" name="is_active" value="<?php echo $user_data['is_active'] ? '0' : '1'; ?>">
                                                                <button type="submit" class="btn btn-<?php echo $user_data['is_active'] ? 'warning' : 'success'; ?> btn-sm"
                                                                        onclick="return confirm('<?php echo $user_data['is_active'] ? 'Deactivate' : 'Activate'; ?> this user?')">
                                                                    <i class="fas fa-<?php echo $user_data['is_active'] ? 'pause' : 'play'; ?>"></i>
                                                                    <?php echo $user_data['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                        <a href="accounts.php?user_id=<?php echo $user_data['user_id']; ?>" 
                                                           class="btn btn-info btn-sm">
                                                            <i class="fas fa-credit-card"></i> Accounts
                                                        </a>
                                                        
                                                        <button type="button" class="btn btn-warning btn-sm" 
                                                                data-bs-toggle="modal" data-bs-target="#deleteUserModal"
                                                                data-user-id="<?php echo $user_data['user_id']; ?>"
                                                                data-user-name="<?php echo htmlspecialchars($user_data['first_name'] . ' ' . $user_data['last_name']); ?>"
                                                                data-user-email="<?php echo htmlspecialchars($user_data['email']); ?>"
                                                                data-account-count="<?php echo $user_data['account_count']; ?>">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <?php if($total_pages > 1): ?>
                                <div class="card-footer">
                                    <nav>
                                        <ul class="pagination justify-content-center mb-0">
                                            <?php 
                                            $search_param = !empty($search_term) ? '&search=' . urlencode($search_term) : '';
                                            ?>
                                            <?php if($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="users.php?status=<?php echo $status_filter; ?><?php echo $search_param; ?>&page=<?php echo $page-1; ?>">Previous</a>
                                                </li>
                                            <?php endif; ?>

                                            <?php for($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="users.php?status=<?php echo $status_filter; ?><?php echo $search_param; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php if($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="users.php?status=<?php echo $status_filter; ?><?php echo $search_param; ?>&page=<?php echo $page+1; ?>">Next</a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i>Create New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="create_user">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name *</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name *</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Username *</label>
                                <input type="text" name="username" class="form-control" required>
                                <small class="form-text text-muted">At least 3 characters</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email Address *</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password *</label>
                                <input type="password" name="password" class="form-control" required>
                                <small class="form-text text-muted">At least 6 characters</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" name="phone" class="form-control" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth *</label>
                                <input type="date" name="date_of_birth" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Address</label>
                                <textarea name="address" class="form-control" rows="2" placeholder="Optional"></textarea>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="auto_approve" id="autoApprove" checked>
                                    <label class="form-check-label" for="autoApprove">
                                        <strong>Auto-approve this user</strong>
                                        <br><small class="text-muted">User will be immediately approved and can login</small>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Admin Note:</strong> Users created by admin can be automatically approved for immediate access.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-user-plus me-2"></i>Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Delete User</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-warning me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone. Please choose carefully.
                    </div>

                    <div class="mb-3">
                        <h6>User Information:</h6>
                        <table class="table table-borderless table-sm">
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td id="deleteUserName"></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td id="deleteUserEmail"></td>
                            </tr>
                            <tr>
                                <td><strong>Accounts:</strong></td>
                                <td id="deleteUserAccounts"></td>
                            </tr>
                        </table>
                    </div>

                    <div class="mb-3">
                        <h6>Deletion Options:</h6>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="delete_type" id="softDelete" value="soft" checked>
                            <label class="form-check-label" for="softDelete">
                                <strong>Deactivate User (Recommended)</strong>
                                <br><small class="text-muted">User will be deactivated but data preserved for audit purposes</small>
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="radio" name="delete_type" id="hardDelete" value="hard">
                            <label class="form-check-label" for="hardDelete">
                                <strong>Permanently Delete User</strong>
                                <br><small class="text-muted">Complete removal from database (only if no accounts/transactions)</small>
                            </label>
                        </div>
                    </div>

                    <div id="deletionWarnings" class="alert alert-info" style="display: none;">
                        <h6>Important Notes:</h6>
                        <ul id="warningsList" class="mb-0"></ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirmSoftDelete">
                        <i class="fas fa-user-slash me-2"></i>Deactivate User
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmHardDelete" style="display: none;">
                        <i class="fas fa-trash me-2"></i>Delete Permanently
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('createUserModal').addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
            document.getElementById('autoApprove').checked = true;
        });

        let currentUserId = null;

        document.getElementById('deleteUserModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            currentUserId = button.getAttribute('data-user-id');
            var userName = button.getAttribute('data-user-name');
            var userEmail = button.getAttribute('data-user-email');
            var accountCount = button.getAttribute('data-account-count');
            
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteUserEmail').textContent = userEmail;
            document.getElementById('deleteUserAccounts').textContent = accountCount + ' account(s)';

            if(parseInt(accountCount) > 0) {
                document.getElementById('deletionWarnings').style.display = 'block';
                document.getElementById('warningsList').innerHTML = 
                    '<li>User has ' + accountCount + ' account(s). Consider closing accounts first.</li>' +
                    '<li>Permanent deletion may not be possible if accounts have transaction history.</li>';
            } else {
                document.getElementById('deletionWarnings').style.display = 'none';
            }

            document.getElementById('softDelete').checked = true;
            updateDeleteButtons();
        });

        document.querySelectorAll('input[name="delete_type"]').forEach(function(radio) {
            radio.addEventListener('change', updateDeleteButtons);
        });

        function updateDeleteButtons() {
            var deleteType = document.querySelector('input[name="delete_type"]:checked').value;
            
            if(deleteType === 'soft') {
                document.getElementById('confirmSoftDelete').style.display = 'inline-block';
                document.getElementById('confirmHardDelete').style.display = 'none';
            } else {
                document.getElementById('confirmSoftDelete').style.display = 'none';
                document.getElementById('confirmHardDelete').style.display = 'inline-block';
            }
        }

        document.getElementById('confirmSoftDelete').addEventListener('click', function() {
            performUserDeletion('soft_delete_user');
        });

        document.getElementById('confirmHardDelete').addEventListener('click', function() {
            if(confirm('Are you absolutely sure you want to permanently delete this user? This action cannot be undone!')) {
                performUserDeletion('hard_delete_user');
            }
        });

        function performUserDeletion(action) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = 
                '<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">' +
                '<input type="hidden" name="action" value="' + action + '">' +
                '<input type="hidden" name="user_id" value="' + currentUserId + '">';
            
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>
<?php
require_once '../config/config.php';

check_admin_login();

$database = new Database();
$db = $database->getConnection();

$admin = new Admin($db);
$admin->getById($_SESSION['admin_id']);

$action_filter = $_GET['action'] ?? 'all';
$admin_filter = $_GET['admin_id'] ?? 'all';
$date_filter = $_GET['date'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;

$where_conditions = [];
$params = [];

if($action_filter != 'all') {
    $where_conditions[] = "al.action LIKE :action";
    $params[':action'] = "%$action_filter%";
}

if($admin_filter != 'all') {
    $where_conditions[] = "al.admin_id = :admin_id";
    $params[':admin_id'] = $admin_filter;
}

if($date_filter) {
    $where_conditions[] = "DATE(al.created_at) = :date";
    $params[':date'] = $date_filter;
}

$where_clause = "";
if(!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

$offset = ($page - 1) * $limit;

try {
    $query = "SELECT al.*, 
                     au.full_name as admin_name, 
                     au.role as admin_role,
                     u.first_name, 
                     u.last_name
              FROM audit_logs al
              LEFT JOIN admin_users au ON al.admin_id = au.admin_id
              LEFT JOIN users u ON al.user_id = u.user_id
              $where_clause
              ORDER BY al.created_at DESC
              LIMIT :limit OFFSET :offset";

    $stmt = $db->prepare($query);
    
    foreach($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    
    $audit_logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $count_query = "SELECT COUNT(*) as total FROM audit_logs al $where_clause";
    $count_stmt = $db->prepare($count_query);
    
    foreach($params as $key => $value) {
        $count_stmt->bindValue($key, $value);
    }
    
    $count_stmt->execute();
    $total_logs = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_logs / $limit);

} catch(Exception $e) {
    $audit_logs = [];
    $total_logs = 0;
    $total_pages = 0;
}

try {
    $admin_query = "SELECT admin_id, full_name FROM admin_users WHERE is_active = 1 ORDER BY full_name";
    $admin_stmt = $db->prepare($admin_query);
    $admin_stmt->execute();
    $admin_list = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    $admin_list = [];
}

$common_actions = [
    'LOGIN', 'UPDATE_USER_STATUS', 'CREATE_ACCOUNT', 'UPDATE_ACCOUNT_STATUS',
    'MANUAL_DEPOSIT', 'MANUAL_WITHDRAWAL', 'MANUAL_BALANCE_ADJUSTMENT'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Admin Panel</title>
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
        .admin-role {
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
        }
        .audit-log-row {
            transition: all 0.3s ease;
        }
        .audit-log-row:hover {
            background-color: #f8f9fa;
        }
        .json-data {
            background: #f8f9fa;
            border-radius: 5px;
            padding: 10px;
            font-family: 'Courier New', monospace;
            font-size: 0.85em;
            max-height: 150px;
            overflow-y: auto;
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
                        <a class="nav-link" href="transactions.php">
                            <i class="fas fa-exchange-alt me-2"></i>Transactions
                        </a>
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                        <a class="nav-link active" href="audit.php">
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
                        <h2><i class="fas fa-history me-2"></i>System Audit Logs</h2>
                        <div class="text-muted">
                            Total Logs: <?php echo number_format($total_logs); ?>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label">Action</label>
                                    <select name="action" class="form-select">
                                        <option value="all" <?php echo $action_filter == 'all' ? 'selected' : ''; ?>>All Actions</option>
                                        <?php foreach($common_actions as $action): ?>
                                            <option value="<?php echo $action; ?>" <?php echo $action_filter == $action ? 'selected' : ''; ?>>
                                                <?php echo ucwords(str_replace('_', ' ', $action)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Admin</label>
                                    <select name="admin_id" class="form-select">
                                        <option value="all" <?php echo $admin_filter == 'all' ? 'selected' : ''; ?>>All Admins</option>
                                        <?php foreach($admin_list as $admin_user): ?>
                                            <option value="<?php echo $admin_user['admin_id']; ?>" <?php echo $admin_filter == $admin_user['admin_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($admin_user['full_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Date</label>
                                    <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date_filter); ?>">
                                </div>
                                <div class="col-md-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <a href="audit.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Audit Logs Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Audit Trail</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if(empty($audit_logs)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-history fa-3x text-muted mb-3"></i>
                                    <h5>No audit logs found</h5>
                                    <p class="text-muted">No logs match the current filter criteria.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date & Time</th>
                                                <th>Admin</th>
                                                <th>Action</th>
                                                <th>Target</th>
                                                <th>IP Address</th>
                                                <th>Details</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($audit_logs as $log): ?>
                                            <tr class="audit-log-row">
                                                <td>
                                                    <div>
                                                        <?php echo date('M j, Y', strtotime($log['created_at'])); ?>
                                                        <br>
                                                        <small class="text-muted"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if($log['admin_name']): ?>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($log['admin_name']); ?></strong>
                                                            <br>
                                                            <span class="badge bg-info"><?php echo ucfirst(str_replace('_', ' ', $log['admin_role'])); ?></span>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">System</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo strpos($log['action'], 'LOGIN') !== false ? 'success' : 
                                                            (strpos($log['action'], 'CREATE') !== false ? 'info' : 
                                                             (strpos($log['action'], 'UPDATE') !== false ? 'warning' : 
                                                              (strpos($log['action'], 'DELETE') !== false ? 'danger' : 'secondary')));
                                                    ?>">
                                                        <?php echo htmlspecialchars($log['action']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if($log['table_name']): ?>
                                                        <div>
                                                            <strong><?php echo ucfirst($log['table_name']); ?></strong>
                                                            <?php if($log['record_id']): ?>
                                                                <br><small class="text-muted">ID: <?php echo $log['record_id']; ?></small>
                                                            <?php endif; ?>
                                                            <?php if($log['first_name'] && $log['last_name']): ?>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($log['ip_address']); ?></code>
                                                </td>
                                                <td>
                                                    <?php if($log['new_values'] || $log['old_values']): ?>
                                                        <button type="button" class="btn btn-info btn-sm" 
                                                                data-bs-toggle="modal" data-bs-target="#detailsModal"
                                                                data-log='<?php echo json_encode($log); ?>'>
                                                            <i class="fas fa-eye"></i> View
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <?php if($total_pages > 1): ?>
                                <div class="card-footer">
                                    <nav>
                                        <ul class="pagination justify-content-center mb-0">
                                            <?php if($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="audit.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page-1])); ?>">Previous</a>
                                                </li>
                                            <?php endif; ?>

                                            <?php 
                                            $start_page = max(1, $page - 2);
                                            $end_page = min($total_pages, $page + 2);
                                            ?>

                                            <?php for($i = $start_page; $i <= $end_page; $i++): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="audit.php?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                                </li>
                                            <?php endfor; ?>

                                            <?php if($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="audit.php?<?php echo http_build_query(array_merge($_GET, ['page' => $page+1])); ?>">Next</a>
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

    <!-- Details Modal -->
    <div class="modal fade" id="detailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-info-circle me-2"></i>Audit Log Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Action Information</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td><strong>Action:</strong></td>
                                    <td id="modalAction"></td>
                                </tr>
                                <tr>
                                    <td><strong>Date & Time:</strong></td>
                                    <td id="modalDateTime"></td>
                                </tr>
                                <tr>
                                    <td><strong>Admin:</strong></td>
                                    <td id="modalAdmin"></td>
                                </tr>
                                <tr>
                                    <td><strong>IP Address:</strong></td>
                                    <td id="modalIP"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Target Information</h6>
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td><strong>Table:</strong></td>
                                    <td id="modalTable"></td>
                                </tr>
                                <tr>
                                    <td><strong>Record ID:</strong></td>
                                    <td id="modalRecordId"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Changes Made</h6>
                            <div id="modalChanges" class="json-data"></div>
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
        document.getElementById('detailsModal').addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var log = JSON.parse(button.getAttribute('data-log'));
            
            document.getElementById('modalAction').textContent = log.action;
            document.getElementById('modalDateTime').textContent = new Date(log.created_at).toLocaleString();
            document.getElementById('modalAdmin').textContent = log.admin_name || 'System';
            document.getElementById('modalIP').textContent = log.ip_address;
            document.getElementById('modalTable').textContent = log.table_name || '-';
            document.getElementById('modalRecordId').textContent = log.record_id || '-';
            
            var changes = '';
            if (log.old_values) {
                changes += '<strong>Old Values:</strong><br>' + JSON.stringify(JSON.parse(log.old_values), null, 2) + '<br><br>';
            }
            if (log.new_values) {
                changes += '<strong>New Values:</strong><br>' + JSON.stringify(JSON.parse(log.new_values), null, 2);
            }
            
            document.getElementById('modalChanges').innerHTML = changes || 'No change details available';
        });
    </script>
</body>
</html>
<?php
require_once '../config/config.php';

check_admin_login();

$database = new Database();
$db = $database->getConnection();

$admin = new Admin($db);
$admin->getById($_SESSION['admin_id']);

$message = '';
$error = '';
$report_data = [];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $report_type = $_POST['report_type'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        $transaction_type = $_POST['transaction_type'] ?? 'all';

        if($report_type && $start_date && $end_date) {
            try {
                if($report_type == 'transaction_summary') {
                    $query = "SELECT 
                                DATE(t.created_at) as transaction_date,
                                t.transaction_type,
                                COUNT(*) as transaction_count,
                                SUM(t.amount) as total_amount,
                                AVG(t.amount) as average_amount
                              FROM transactions t
                              WHERE DATE(t.created_at) BETWEEN :start_date AND :end_date
                                AND t.status = 'completed'";
                    
                    if($transaction_type != 'all') {
                        $query .= " AND t.transaction_type = :transaction_type";
                    }
                    
                    $query .= " GROUP BY DATE(t.created_at), t.transaction_type
                              ORDER BY transaction_date DESC, t.transaction_type";

                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':start_date', $start_date);
                    $stmt->bindParam(':end_date', $end_date);
                    
                    if($transaction_type != 'all') {
                        $stmt->bindParam(':transaction_type', $transaction_type);
                    }
                    
                    $stmt->execute();
                    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                } elseif($report_type == 'account_summary') {
                    $query = "SELECT 
                                u.user_id,
                                CONCAT(u.first_name, ' ', u.last_name) as user_name,
                                u.email,
                                a.account_number,
                                a.account_type,
                                a.balance,
                                a.status,
                                a.created_at,
                                COUNT(t.transaction_id) as total_transactions,
                                SUM(CASE WHEN t.transaction_type = 'deposit' AND t.status = 'completed' THEN t.amount ELSE 0 END) as total_deposits,
                                SUM(CASE WHEN t.transaction_type = 'withdrawal' AND t.status = 'completed' THEN t.amount ELSE 0 END) as total_withdrawals
                              FROM users u
                              JOIN accounts a ON u.user_id = a.user_id
                              LEFT JOIN transactions t ON (a.account_id = t.from_account_id OR a.account_id = t.to_account_id)
                                AND DATE(t.created_at) BETWEEN :start_date AND :end_date
                              WHERE u.registration_status = 'approved'
                              GROUP BY u.user_id, a.account_id
                              ORDER BY a.balance DESC";

                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':start_date', $start_date);
                    $stmt->bindParam(':end_date', $end_date);
                    $stmt->execute();
                    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                } elseif($report_type == 'user_activity') {
                    $query = "SELECT 
                                u.user_id,
                                CONCAT(u.first_name, ' ', u.last_name) as user_name,
                                u.email,
                                u.registration_status,
                                u.created_at as registration_date,
                                COUNT(DISTINCT a.account_id) as total_accounts,
                                COUNT(t.transaction_id) as total_transactions,
                                MAX(t.created_at) as last_transaction_date,
                                SUM(CASE WHEN t.transaction_type = 'deposit' AND t.status = 'completed' THEN t.amount ELSE 0 END) as total_deposits,
                                SUM(CASE WHEN t.transaction_type = 'withdrawal' AND t.status = 'completed' THEN t.amount ELSE 0 END) as total_withdrawals
                              FROM users u
                              LEFT JOIN accounts a ON u.user_id = a.user_id
                              LEFT JOIN transactions t ON (a.account_id = t.from_account_id OR a.account_id = t.to_account_id)
                                AND DATE(t.created_at) BETWEEN :start_date AND :end_date
                              GROUP BY u.user_id
                              ORDER BY total_transactions DESC";

                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':start_date', $start_date);
                    $stmt->bindParam(':end_date', $end_date);
                    $stmt->execute();
                    $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
                }

                $message = 'Report generated successfully!';
            } catch(Exception $e) {
                $error = 'Error generating report: ' . $e->getMessage();
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Admin Panel</title>
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
        .report-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
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
                        <a class="nav-link active" href="reports.php">
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
                        <h2><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h2>
                        <div class="text-muted">
                            <i class="fas fa-calendar me-2"></i>
                            <?php echo date('l, F j, Y'); ?>
                        </div>
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

                    <!-- Report Generation Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Generate Report</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">Report Type</label>
                                        <select name="report_type" class="form-select" required>
                                            <option value="">Select Report Type</option>
                                            <option value="transaction_summary">Transaction Summary</option>
                                            <option value="account_summary">Account Summary</option>
                                            <option value="user_activity">User Activity</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">Start Date</label>
                                        <input type="date" name="start_date" class="form-control" 
                                               value="<?php echo date('Y-m-01'); ?>" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label">End Date</label>
                                        <input type="date" name="end_date" class="form-control" 
                                               value="<?php echo date('Y-m-d'); ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Transaction Type</label>
                                        <select name="transaction_type" class="form-select">
                                            <option value="all">All Types</option>
                                            <option value="deposit">Deposit</option>
                                            <option value="withdrawal">Withdrawal</option>
                                            <option value="transfer">Transfer</option>
                                            <option value="fee">Fee</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary w-100">
                                            <i class="fas fa-chart-line me-2"></i>Generate
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Quick Report Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card report-card text-center" onclick="generateQuickReport('transaction_summary')">
                                <div class="card-body">
                                    <i class="fas fa-exchange-alt fa-3x text-primary mb-3"></i>
                                    <h5>Transaction Summary</h5>
                                    <p class="text-muted">View daily transaction summaries and trends</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card report-card text-center" onclick="generateQuickReport('account_summary')">
                                <div class="card-body">
                                    <i class="fas fa-credit-card fa-3x text-success mb-3"></i>
                                    <h5>Account Summary</h5>
                                    <p class="text-muted">Comprehensive account balances and activity</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card report-card text-center" onclick="generateQuickReport('user_activity')">
                                <div class="card-body">
                                    <i class="fas fa-users fa-3x text-info mb-3"></i>
                                    <h5>User Activity</h5>
                                    <p class="text-muted">User engagement and transaction patterns</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Results -->
                    <?php if(!empty($report_data)): ?>
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Report Results</h5>
                            <button type="button" class="btn btn-success btn-sm" onclick="exportToCSV()">
                                <i class="fas fa-download me-2"></i>Export CSV
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0" id="reportTable">
                                    <thead class="table-light">
                                        <tr>
                                            <?php if(isset($report_data[0])): ?>
                                                <?php foreach(array_keys($report_data[0]) as $column): ?>
                                                    <th><?php echo ucwords(str_replace('_', ' ', $column)); ?></th>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($report_data as $row): ?>
                                        <tr>
                                            <?php foreach($row as $key => $value): ?>
                                                <td>
                                                    <?php 
                                                    if(strpos($key, 'amount') !== false || strpos($key, 'balance') !== false) {
                                                        echo '৳' . number_format($value, 2);
                                                    } elseif(strpos($key, 'date') !== false) {
                                                        echo date('M j, Y', strtotime($value));
                                                    } elseif(is_numeric($value) && strpos($key, 'count') !== false) {
                                                        echo number_format($value);
                                                    } else {
                                                        echo htmlspecialchars($value);
                                                    }
                                                    ?>
                                                </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php elseif($_SERVER['REQUEST_METHOD'] == 'POST' && empty($report_data) && !$error): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                            <h5>No Data Found</h5>
                            <p class="text-muted">No data available for the selected criteria and date range.</p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        function generateQuickReport(reportType) {
            document.querySelector('select[name="report_type"]').value = reportType;
            document.querySelector('form').submit();
        }

        function exportToCSV() {
            const table = document.getElementById('reportTable');
            if (!table) return;

            let csv = [];
            const rows = table.querySelectorAll('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = [], cols = rows[i].querySelectorAll('td, th');
                
                for (let j = 0; j < cols.length; j++) {
                    let cellData = cols[j].innerText.replace(/"/g, '""');
                    row.push('"' + cellData + '"');
                }
                
                csv.push(row.join(','));
            }

            const csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
            const downloadLink = document.createElement('a');
            
            downloadLink.download = 'daffodil_bank_report_' + new Date().toISOString().split('T')[0] + '.csv';
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        }
    </script>
</body>
</html>
<?php
class Admin {
    private $conn;
    private $table_name = "admin_users";

    public $admin_id;
    public $username;
    public $email;
    public $password_hash;
    public $full_name;
    public $role;
    public $is_active;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        $query = "SELECT admin_id, username, email, password_hash, full_name, role 
                  FROM " . $this->table_name . " 
                  WHERE (username = :username OR email = :username) 
                    AND is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row && password_verify($password, $row['password_hash'])) {
            $_SESSION['admin_id'] = $row['admin_id'];
            $_SESSION['admin_username'] = $row['username'];
            $_SESSION['admin_name'] = $row['full_name'];
            $_SESSION['admin_email'] = $row['email'];
            $_SESSION['admin_role'] = $row['role'];
            
            $this->logAdminAction($row['admin_id'], 'LOGIN', 'admin_users', $row['admin_id'], []);
            
            return true;
        }
        return false;
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE admin_id = :admin_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":admin_id", $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->admin_id = $row['admin_id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->full_name = $row['full_name'];
            $this->role = $row['role'];
            $this->is_active = $row['is_active'];
            $this->created_at = $row['created_at'];
            return true;
        }
        return false;
    }

    public function getAllUsers($page = 1, $limit = 20, $status = 'all') {
        $offset = ($page - 1) * $limit;
        
        $where_clause = "";
        if($status != 'all') {
            $where_clause = "WHERE registration_status = :status";
        }

        $query = "SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, 
                         u.phone, u.registration_status, u.is_active, u.created_at,
                         COUNT(a.account_id) as account_count
                  FROM users u
                  LEFT JOIN accounts a ON u.user_id = a.user_id
                  $where_clause
                  GROUP BY u.user_id
                  ORDER BY u.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        if($status != 'all') {
            $stmt->bindParam(":status", $status);
        }
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserCount($status = 'all') {
        $where_clause = "";
        if($status != 'all') {
            $where_clause = "WHERE registration_status = :status";
        }

        $query = "SELECT COUNT(*) as total FROM users $where_clause";
        $stmt = $this->conn->prepare($query);
        
        if($status != 'all') {
            $stmt->bindParam(":status", $status);
        }
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function searchUsers($search_term, $page = 1, $limit = 20, $status = 'all') {
        $offset = ($page - 1) * $limit;
        
        $where_conditions = [];
        $params = [];
        
        if($status != 'all') {
            $where_conditions[] = "u.registration_status = :status";
            $params[':status'] = $status;
        }
        
        $search_conditions = [
            "u.username LIKE :search_term",
            "u.email LIKE :search_term",
            "u.first_name LIKE :search_term", 
            "u.last_name LIKE :search_term",
            "CONCAT(u.first_name, ' ', u.last_name) LIKE :search_term",
            "a.account_number LIKE :search_term"
        ];
        
        $where_conditions[] = "(" . implode(" OR ", $search_conditions) . ")";
        $params[':search_term'] = "%$search_term%";
        
        $where_clause = "WHERE " . implode(" AND ", $where_conditions);

        $query = "SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, 
                         u.phone, u.registration_status, u.is_active, u.created_at,
                         COUNT(DISTINCT a.account_id) as account_count,
                         GROUP_CONCAT(DISTINCT a.account_number SEPARATOR ', ') as account_numbers
                  FROM users u
                  LEFT JOIN accounts a ON u.user_id = a.user_id
                  $where_clause
                  GROUP BY u.user_id
                  ORDER BY u.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSearchUserCount($search_term, $status = 'all') {
        $where_conditions = [];
        $params = [];
        
        if($status != 'all') {
            $where_conditions[] = "u.registration_status = :status";
            $params[':status'] = $status;
        }
        
        $search_conditions = [
            "u.username LIKE :search_term",
            "u.email LIKE :search_term", 
            "u.first_name LIKE :search_term",
            "u.last_name LIKE :search_term",
            "CONCAT(u.first_name, ' ', u.last_name) LIKE :search_term",
            "a.account_number LIKE :search_term"
        ];
        
        $where_conditions[] = "(" . implode(" OR ", $search_conditions) . ")";
        $params[':search_term'] = "%$search_term%";
        
        $where_clause = "WHERE " . implode(" AND ", $where_conditions);

        $query = "SELECT COUNT(DISTINCT u.user_id) as total 
                  FROM users u
                  LEFT JOIN accounts a ON u.user_id = a.user_id
                  $where_clause";
        
        $stmt = $this->conn->prepare($query);
        
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['total'];
    }

    public function updateUserStatus($user_id, $status) {
        $query = "UPDATE users SET registration_status=:status WHERE user_id=:user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":user_id", $user_id);

        if($stmt->execute()) {
            $this->logAdminAction($_SESSION['admin_id'], 'UPDATE_USER_STATUS', 'users', $user_id, 
                                ['registration_status' => $status]);
            return true;
        }
        return false;
    }

    public function toggleUserActivation($user_id, $is_active) {
        $query = "UPDATE users SET is_active=:is_active WHERE user_id=:user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":is_active", $is_active, PDO::PARAM_BOOL);
        $stmt->bindParam(":user_id", $user_id);

        if($stmt->execute()) {
            $action = $is_active ? 'ACTIVATE_USER' : 'DEACTIVATE_USER';
            $this->logAdminAction($_SESSION['admin_id'], $action, 'users', $user_id, 
                                ['is_active' => $is_active]);
            return true;
        }
        return false;
    }

    public function getDashboardStats() {
        $stats = [];

        $query = "SELECT COUNT(*) as total FROM users";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $query = "SELECT COUNT(*) as total FROM users WHERE registration_status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['pending_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $query = "SELECT COUNT(*) as total FROM accounts";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_accounts'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $query = "SELECT COUNT(*) as total FROM accounts WHERE status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['active_accounts'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $query = "SELECT SUM(balance) as total FROM accounts WHERE status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_balance'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;

        $query = "SELECT COUNT(*) as total FROM transactions WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['today_transactions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        $query = "SELECT SUM(amount) as total FROM transactions 
                  WHERE DATE(created_at) = CURDATE() AND status = 'completed'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['today_transaction_amount'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;

        return $stats;
    }

    public function getRecentActivities($limit = 10) {
        $query = "SELECT al.*, u.first_name, u.last_name, au.full_name as admin_name
                  FROM audit_logs al
                  LEFT JOIN users u ON al.user_id = u.user_id
                  LEFT JOIN admin_users au ON al.admin_id = au.admin_id
                  ORDER BY al.created_at DESC
                  LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTransactionReport($start_date, $end_date, $type = 'all') {
        $where_clause = "WHERE t.created_at BETWEEN :start_date AND :end_date";
        
        if($type != 'all') {
            $where_clause .= " AND t.transaction_type = :type";
        }

        $query = "SELECT t.*, 
                         fa.account_number as from_account,
                         ta.account_number as to_account,
                         fu.first_name as from_user_first,
                         fu.last_name as from_user_last,
                         tu.first_name as to_user_first,
                         tu.last_name as to_user_last
                  FROM transactions t
                  LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
                  LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
                  LEFT JOIN users fu ON fa.user_id = fu.user_id
                  LEFT JOIN users tu ON ta.user_id = tu.user_id
                  $where_clause
                  ORDER BY t.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":start_date", $start_date);
        $stmt->bindParam(":end_date", $end_date);
        
        if($type != 'all') {
            $stmt->bindParam(":type", $type);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Log admin actions
    private function logAdminAction($admin_id, $action, $table_name, $record_id, $new_values) {
        $query = "INSERT INTO audit_logs (admin_id, action, table_name, record_id, new_values, ip_address) 
                  VALUES (:admin_id, :action, :table_name, :record_id, :new_values, :ip_address)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":admin_id", $admin_id);
        $stmt->bindParam(":action", $action);
        $stmt->bindParam(":table_name", $table_name);
        $stmt->bindParam(":record_id", $record_id);
        $stmt->bindValue(":new_values", json_encode($new_values));
        $stmt->bindValue(":ip_address", $_SERVER['REMOTE_ADDR']);

        $stmt->execute();
    }

    public function hasPermission($action) {
        $role = $_SESSION['admin_role'] ?? '';
        
        $permissions = [
            'super_admin' => ['*'],
            'account_manager' => [
                'approve_users', 'create_accounts', 'view_accounts', 
                'view_transactions', 'generate_reports'
            ],
            'customer_service' => [
                'view_users', 'view_accounts', 'view_transactions'
            ]
        ];

        if($role == 'super_admin') {
            return true;
        }

        return in_array($action, $permissions[$role] ?? []);
    }

    public static function logout() {
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_name']);
        unset($_SESSION['admin_email']);
        unset($_SESSION['admin_role']);
        session_destroy();
    }
}
?>
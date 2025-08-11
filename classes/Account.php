<?php
class Account {
    private $conn;
    private $table_name = "accounts";

    public $account_id;
    public $user_id;
    public $account_number;
    public $account_type;
    public $balance;
    public $status;
    public $created_by_admin;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($user_id, $account_type, $admin_id) {
        try {
            $query = "CALL CreateAccount(:user_id, :account_type, :admin_id, @account_number)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":account_type", $account_type);
            $stmt->bindParam(":admin_id", $admin_id);
            $stmt->execute();

            $result = $this->conn->query("SELECT @account_number as account_number");
            $row = $result->fetch(PDO::FETCH_ASSOC);
            
            if($row && $row['account_number']) {
                $this->logAdminAction($admin_id, 'CREATE_ACCOUNT', 'accounts', null, [
                    'user_id' => $user_id,
                    'account_type' => $account_type,
                    'account_number' => $row['account_number']
                ]);
                
                return $row['account_number'];
            }
            return false;
        } catch(Exception $e) {
            return false;
        }
    }

    public function getById($id) {
        $query = "SELECT a.*, u.first_name, u.last_name, u.email, u.phone
                  FROM " . $this->table_name . " a
                  JOIN users u ON a.user_id = u.user_id
                  WHERE a.account_id = :account_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":account_id", $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->account_id = $row['account_id'];
            $this->user_id = $row['user_id'];
            $this->account_number = $row['account_number'];
            $this->account_type = $row['account_type'];
            $this->balance = $row['balance'];
            $this->status = $row['status'];
            $this->created_by_admin = $row['created_by_admin'];
            $this->created_at = $row['created_at'];
            return $row;
        }
        return false;
    }

    public function getByAccountNumber($account_number) {
        $query = "SELECT a.*, u.first_name, u.last_name, u.email, u.phone
                  FROM " . $this->table_name . " a
                  JOIN users u ON a.user_id = u.user_id
                  WHERE a.account_number = :account_number";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":account_number", $account_number);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getUserAccounts($user_id) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllAccounts($page = 1, $limit = 20, $status = 'all') {
        $offset = ($page - 1) * $limit;
        
        $where_clause = "";
        if($status != 'all') {
            $where_clause = "WHERE a.status = :status";
        }

        $query = "SELECT a.*, u.first_name, u.last_name, u.email, u.username,
                         au.full_name as created_by_name
                  FROM " . $this->table_name . " a
                  JOIN users u ON a.user_id = u.user_id
                  LEFT JOIN admin_users au ON a.created_by_admin = au.admin_id
                  $where_clause
                  ORDER BY a.created_at DESC
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

    public function getAllAccountsWithSearch($page = 1, $limit = 20, $status = 'all', $search = '') {
        $offset = ($page - 1) * $limit;
        
        $where_conditions = [];
        $params = [];
        
        if($status != 'all') {
            $where_conditions[] = "a.status = :status";
            $params[':status'] = $status;
        }
        
        if(!empty($search)) {
            $where_conditions[] = "(a.account_number LIKE :search 
                                   OR u.first_name LIKE :search 
                                   OR u.last_name LIKE :search 
                                   OR u.email LIKE :search 
                                   OR u.username LIKE :search
                                   OR a.account_type LIKE :search
                                   OR CONCAT(u.first_name, ' ', u.last_name) LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        $where_clause = '';
        if(!empty($where_conditions)) {
            $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        }

        $query = "SELECT a.*, u.first_name, u.last_name, u.email, u.username,
                         au.full_name as created_by_name
                  FROM " . $this->table_name . " a
                  JOIN users u ON a.user_id = u.user_id
                  LEFT JOIN admin_users au ON a.created_by_admin = au.admin_id
                  $where_clause
                  ORDER BY a.created_at DESC
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

    public function updateStatus($account_id, $status, $admin_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status 
                  WHERE account_id = :account_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":account_id", $account_id);

        if($stmt->execute()) {
            $this->logAdminAction($admin_id, 'UPDATE_ACCOUNT_STATUS', 'accounts', $account_id, [
                'status' => $status
            ]);
            return true;
        }
        return false;
    }

    public function getBalance($account_id) {
        $query = "SELECT balance FROM " . $this->table_name . " WHERE account_id = :account_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":account_id", $account_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['balance'] : 0;
    }

    public function updateBalance($account_id, $new_balance, $admin_id, $reason) {
        try {
            $this->conn->beginTransaction();

            $current_balance = $this->getBalance($account_id);
            $adjustment = $new_balance - $current_balance;

            $query = "UPDATE " . $this->table_name . " 
                      SET balance = :balance 
                      WHERE account_id = :account_id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":balance", $new_balance);
            $stmt->bindParam(":account_id", $account_id);
            $stmt->execute();

            $transaction_type = $adjustment > 0 ? 'deposit' : 'withdrawal';
            $amount = abs($adjustment);
            $reference = 'ADJ' . date('Ymd') . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);

            $query = "INSERT INTO transactions 
                      (from_account_id, to_account_id, transaction_type, amount, description, 
                       reference_number, status)
                      VALUES 
                      (:from_account, :to_account, :type, :amount, :description, :reference, 'completed')";

            $stmt = $this->conn->prepare($query);

            if($adjustment > 0) {
                $stmt->bindValue(":from_account", null);
                $stmt->bindParam(":to_account", $account_id);
            } else {
                $stmt->bindParam(":from_account", $account_id);
                $stmt->bindValue(":to_account", null);
            }

            $stmt->bindParam(":type", $transaction_type);
            $stmt->bindParam(":amount", $amount);
            $stmt->bindParam(":description", $reason);
            $stmt->bindParam(":reference", $reference);
            $stmt->execute();

            $this->logAdminAction($admin_id, 'MANUAL_BALANCE_ADJUSTMENT', 'accounts', $account_id, [
                'old_balance' => $current_balance,
                'new_balance' => $new_balance,
                'adjustment' => $adjustment,
                'reason' => $reason
            ]);

            $this->conn->commit();
            return true;

        } catch(Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function getAccountSummary($account_id) {
        $query = "SELECT 
                    a.*,
                    u.first_name,
                    u.last_name,
                    u.email,
                    COUNT(t.transaction_id) as total_transactions,
                    SUM(CASE WHEN t.transaction_type = 'deposit' AND t.status = 'completed' THEN t.amount ELSE 0 END) as total_deposits,
                    SUM(CASE WHEN t.transaction_type = 'withdrawal' AND t.status = 'completed' THEN t.amount ELSE 0 END) as total_withdrawals,
                    MAX(t.created_at) as last_transaction_date
                  FROM " . $this->table_name . " a
                  JOIN users u ON a.user_id = u.user_id
                  LEFT JOIN transactions t ON (a.account_id = t.from_account_id OR a.account_id = t.to_account_id)
                  WHERE a.account_id = :account_id
                  GROUP BY a.account_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":account_id", $account_id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function searchAccounts($search_term) {
        $query = "SELECT a.*, u.first_name, u.last_name, u.email
                  FROM " . $this->table_name . " a
                  JOIN users u ON a.user_id = u.user_id
                  WHERE a.account_number LIKE :search 
                     OR u.first_name LIKE :search 
                     OR u.last_name LIKE :search 
                     OR u.email LIKE :search
                  ORDER BY a.created_at DESC
                  LIMIT 20";

        $search_param = "%$search_term%";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":search", $search_param);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function softDelete($account_id, $admin_id) {
        $balance = $this->getBalance($account_id);
        if($balance > 0) {
            return ['success' => false, 'message' => 'Cannot close account with remaining balance. Transfer or withdraw funds first.'];
        }

        $query = "UPDATE " . $this->table_name . " SET status = 'closed' WHERE account_id = :account_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":account_id", $account_id);

        if($stmt->execute()) {
            $this->logAdminAction($admin_id, 'SOFT_DELETE_ACCOUNT', 'accounts', $account_id, 
                                ['status' => 'closed', 'action' => 'account_closed']);
            return ['success' => true, 'message' => 'Account closed successfully.'];
        }
        return ['success' => false, 'message' => 'Failed to close account.'];
    }

    public function hardDelete($account_id, $admin_id) {
        try {
            $this->conn->beginTransaction();

            $account_info = $this->getById($account_id);
            if(!$account_info) {
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Account not found.'];
            }

            if($account_info['balance'] > 0) {
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Cannot delete account with remaining balance of ৳' . number_format($account_info['balance'], 2)];
            }

            $txn_check = "SELECT COUNT(*) as count FROM transactions 
                         WHERE from_account_id = :account_id OR to_account_id = :account_id";
            $stmt = $this->conn->prepare($txn_check);
            $stmt->bindParam(":account_id", $account_id);
            $stmt->execute();
            $txn_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if($txn_count > 0) {
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Cannot delete account with transaction history (' . $txn_count . ' transactions). Use close account instead.'];
            }

            $delete_query = "DELETE FROM " . $this->table_name . " WHERE account_id = :account_id";
            $stmt = $this->conn->prepare($delete_query);
            $stmt->bindParam(":account_id", $account_id);
            $stmt->execute();

            $this->logAdminAction($admin_id, 'HARD_DELETE_ACCOUNT', 'accounts', $account_id, [
                'deleted_account' => $account_info,
                'action' => 'permanently_deleted'
            ]);

            $this->conn->commit();
            return ['success' => true, 'message' => 'Account permanently deleted successfully.'];

        } catch(Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error deleting account: ' . $e->getMessage()];
        }
    }

    public function canDelete($account_id) {
        $account_info = $this->getById($account_id);
        if(!$account_info) {
            return ['can_soft_delete' => false, 'can_hard_delete' => false, 'message' => 'Account not found'];
        }

        $balance = $account_info['balance'];

        $query = "SELECT COUNT(*) as count FROM transactions 
                 WHERE from_account_id = :account_id OR to_account_id = :account_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":account_id", $account_id);
        $stmt->execute();
        $transaction_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        return [
            'can_soft_delete' => $balance == 0, 
            'can_hard_delete' => ($balance == 0 && $transaction_count == 0), 
            'transaction_count' => $transaction_count,
            'account_status' => $account_info['status'],
            'warnings' => [
                'has_balance' => $balance > 0,
                'has_transactions' => $transaction_count > 0,
                'is_active' => $account_info['status'] == 'active'
            ]
        ];
    }

    public function getDeletionInfo($account_id) {
        $account_info = $this->getById($account_id);
        if(!$account_info) {
            return null;
        }

        $deletion_check = $this->canDelete($account_id);
        
        return [
            'account' => $account_info,
            'deletion_options' => $deletion_check,
            'recommendations' => $this->getDeletionRecommendations($deletion_check)
        ];
    }

    private function getDeletionRecommendations($deletion_check) {
        $recommendations = [];

        if($deletion_check['warnings']['has_balance']) {
            $recommendations[] = 'Transfer or withdraw the remaining balance of ৳' . number_format($deletion_check['balance'], 2) . ' before deletion.';
        }

        if($deletion_check['warnings']['has_transactions']) {
            $recommendations[] = 'Account has ' . $deletion_check['transaction_count'] . ' transaction(s). Consider closing instead of deleting to preserve audit trail.';
        }

        if($deletion_check['can_soft_delete'] && !$deletion_check['can_hard_delete']) {
            $recommendations[] = 'Recommended: Close account instead of permanent deletion to maintain data integrity.';
        }

        if($deletion_check['can_hard_delete']) {
            $recommendations[] = 'Account can be safely deleted as it has no balance or transaction history.';
        }

        return $recommendations;
    }

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
}
?>
<?php
class Transaction {
    private $conn;
    private $table_name = "transactions";

    public $transaction_id;
    public $from_account_id;
    public $to_account_id;
    public $transaction_type;
    public $amount;
    public $description;
    public $status;
    public $reference_number;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function transfer($from_account_id, $to_account_id, $amount, $description = '') {
        try {
            $query = "CALL TransferMoney(:from_account, :to_account, :amount, :description, @result, @reference)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":from_account", $from_account_id);
            $stmt->bindParam(":to_account", $to_account_id);
            $stmt->bindParam(":amount", $amount);
            $stmt->bindParam(":description", $description);
            $stmt->execute();

            $result = $this->conn->query("SELECT @result as result, @reference as reference");
            $row = $result->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => $row['result'] === 'SUCCESS',
                'message' => $row['result'],
                'reference' => $row['reference']
            ];
        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => 'SYSTEM_ERROR',
                'reference' => ''
            ];
        }
    }

    public function deposit($account_id, $amount, $description = '', $admin_id = null) {
        try {
            $reference = 'DEP' . date('Ymd') . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            
            $query = "INSERT INTO " . $this->table_name . " 
                      (to_account_id, transaction_type, amount, description, reference_number, status)
                      VALUES (:account_id, 'deposit', :amount, :description, :reference, 'completed')";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":account_id", $account_id);
            $stmt->bindParam(":amount", $amount);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":reference", $reference);

            if($stmt->execute()) {
                if($admin_id) {
                    $this->logAdminAction($admin_id, 'MANUAL_DEPOSIT', 'transactions', 
                                        $this->conn->lastInsertId(), [
                        'account_id' => $account_id,
                        'amount' => $amount,
                        'reference' => $reference
                    ]);
                }
                
                return [
                    'success' => true,
                    'message' => 'DEPOSIT_SUCCESS',
                    'reference' => $reference
                ];
            }
            return [
                'success' => false,
                'message' => 'DEPOSIT_FAILED',
                'reference' => ''
            ];
        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => 'SYSTEM_ERROR',
                'reference' => ''
            ];
        }
    }

    public function withdraw($account_id, $amount, $description = '', $admin_id = null) {
        try {
            $balance_query = "SELECT balance FROM accounts WHERE account_id = :account_id";
            $balance_stmt = $this->conn->prepare($balance_query);
            $balance_stmt->bindParam(":account_id", $account_id);
            $balance_stmt->execute();
            $balance_row = $balance_stmt->fetch(PDO::FETCH_ASSOC);

            if(!$balance_row || $balance_row['balance'] < $amount) {
                return [
                    'success' => false,
                    'message' => 'INSUFFICIENT_FUNDS',
                    'reference' => ''
                ];
            }

            $reference = 'WDR' . date('Ymd') . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            
            $query = "INSERT INTO " . $this->table_name . " 
                      (from_account_id, transaction_type, amount, description, reference_number, status)
                      VALUES (:account_id, 'withdrawal', :amount, :description, :reference, 'completed')";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":account_id", $account_id);
            $stmt->bindParam(":amount", $amount);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":reference", $reference);

            if($stmt->execute()) {
                if($admin_id) {
                    $this->logAdminAction($admin_id, 'MANUAL_WITHDRAWAL', 'transactions', 
                                        $this->conn->lastInsertId(), [
                        'account_id' => $account_id,
                        'amount' => $amount,
                        'reference' => $reference
                    ]);
                }
                
                return [
                    'success' => true,
                    'message' => 'WITHDRAWAL_SUCCESS',
                    'reference' => $reference
                ];
            }
            return [
                'success' => false,
                'message' => 'WITHDRAWAL_FAILED',
                'reference' => ''
            ];
        } catch(Exception $e) {
            return [
                'success' => false,
                'message' => 'SYSTEM_ERROR',
                'reference' => ''
            ];
        }
    }

    public function getById($id) {
        $query = "SELECT t.*, 
                         fa.account_number as from_account_number,
                         ta.account_number as to_account_number,
                         fu.first_name as from_user_first,
                         fu.last_name as from_user_last,
                         tu.first_name as to_user_first,
                         tu.last_name as to_user_last
                  FROM " . $this->table_name . " t
                  LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
                  LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
                  LEFT JOIN users fu ON fa.user_id = fu.user_id
                  LEFT JOIN users tu ON ta.user_id = tu.user_id
                  WHERE t.transaction_id = :transaction_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":transaction_id", $id);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByReference($reference) {
        $query = "SELECT t.*, 
                         fa.account_number as from_account_number,
                         ta.account_number as to_account_number,
                         fu.first_name as from_user_first,
                         fu.last_name as from_user_last,
                         tu.first_name as to_user_first,
                         tu.last_name as to_user_last
                  FROM " . $this->table_name . " t
                  LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
                  LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
                  LEFT JOIN users fu ON fa.user_id = fu.user_id
                  LEFT JOIN users tu ON ta.user_id = tu.user_id
                  WHERE t.reference_number = :reference";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":reference", $reference);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAccountTransactions($account_id, $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;

        $query = "SELECT t.*, 
                         CASE 
                           WHEN t.from_account_id = :account_id THEN 'outgoing'
                           WHEN t.to_account_id = :account_id THEN 'incoming'
                           ELSE 'unknown'
                         END as direction,
                         fa.account_number as from_account_number,
                         ta.account_number as to_account_number,
                         fu.first_name as from_user_first,
                         fu.last_name as from_user_last,
                         tu.first_name as to_user_first,
                         tu.last_name as to_user_last
                  FROM " . $this->table_name . " t
                  LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
                  LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
                  LEFT JOIN users fu ON fa.user_id = fu.user_id
                  LEFT JOIN users tu ON ta.user_id = tu.user_id
                  WHERE (t.from_account_id = :account_id OR t.to_account_id = :account_id)
                    AND t.status = 'completed'
                  ORDER BY t.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":account_id", $account_id);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserTransactions($user_id, $page = 1, $limit = 20, $account_filter = '', $type_filter = '', $date_from = '', $date_to = '') {
        $offset = ($page - 1) * $limit;

        $query = "SELECT t.*, 
                         CASE 
                           WHEN fa.user_id = :user_id THEN 'outgoing'
                           WHEN ta.user_id = :user_id THEN 'incoming'
                           ELSE 'unknown'
                         END as direction,
                         CASE 
                           WHEN fa.user_id = :user_id THEN fa.account_number
                           WHEN ta.user_id = :user_id THEN ta.account_number
                           ELSE COALESCE(fa.account_number, ta.account_number)
                         END as account_number,
                         CASE 
                           WHEN fa.user_id = :user_id THEN fa.account_type
                           WHEN ta.user_id = :user_id THEN ta.account_type
                           ELSE COALESCE(fa.account_type, ta.account_type)
                         END as account_type,
                         fa.account_number as from_account_number,
                         ta.account_number as to_account_number,
                         fu.first_name as from_user_first,
                         fu.last_name as from_user_last,
                         tu.first_name as to_user_first,
                         tu.last_name as to_user_last
                  FROM " . $this->table_name . " t
                  LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
                  LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
                  LEFT JOIN users fu ON fa.user_id = fu.user_id
                  LEFT JOIN users tu ON ta.user_id = tu.user_id
                  WHERE (fa.user_id = :user_id OR ta.user_id = :user_id)
                    AND t.status = 'completed'";

        $params = [':user_id' => $user_id];

        if($account_filter) {
            $query .= " AND (t.from_account_id = :account_id OR t.to_account_id = :account_id)";
            $params[':account_id'] = $account_filter;
        }

        if($type_filter) {
            $query .= " AND t.transaction_type = :type";
            $params[':type'] = $type_filter;
        }

        if($date_from) {
            $query .= " AND DATE(t.created_at) >= :date_from";
            $params[':date_from'] = $date_from;
        }

        if($date_to) {
            $query .= " AND DATE(t.created_at) <= :date_to";
            $params[':date_to'] = $date_to;
        }

        $query .= " ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllTransactions($page = 1, $limit = 20, $status = 'all', $type = 'all') {
        $offset = ($page - 1) * $limit;
        
        $where_conditions = [];
        if($status != 'all') {
            $where_conditions[] = "t.status = :status";
        }
        if($type != 'all') {
            $where_conditions[] = "t.transaction_type = :type";
        }

        $where_clause = "";
        if(!empty($where_conditions)) {
            $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        }

        $query = "SELECT t.*, 
                         fa.account_number as from_account_number,
                         ta.account_number as to_account_number,
                         fu.first_name as from_user_first,
                         fu.last_name as from_user_last,
                         tu.first_name as to_user_first,
                         tu.last_name as to_user_last
                  FROM " . $this->table_name . " t
                  LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
                  LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
                  LEFT JOIN users fu ON fa.user_id = fu.user_id
                  LEFT JOIN users tu ON ta.user_id = tu.user_id
                  $where_clause
                  ORDER BY t.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $this->conn->prepare($query);
        
        if($status != 'all') {
            $stmt->bindParam(":status", $status);
        }
        if($type != 'all') {
            $stmt->bindParam(":type", $type);
        }
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->bindParam(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTransactionStats($start_date = null, $end_date = null) {
        $where_clause = "WHERE t.status = 'completed'";
        if($start_date && $end_date) {
            $where_clause .= " AND DATE(t.created_at) BETWEEN :start_date AND :end_date";
        }

        $query = "SELECT 
                    t.transaction_type,
                    COUNT(*) as count,
                    SUM(t.amount) as total_amount,
                    AVG(t.amount) as average_amount
                  FROM " . $this->table_name . " t
                  $where_clause
                  GROUP BY t.transaction_type
                  ORDER BY total_amount DESC";

        $stmt = $this->conn->prepare($query);
        
        if($start_date && $end_date) {
            $stmt->bindParam(":start_date", $start_date);
            $stmt->bindParam(":end_date", $end_date);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchTransactions($search_term) {
        $query = "SELECT t.*, 
                         fa.account_number as from_account_number,
                         ta.account_number as to_account_number,
                         fu.first_name as from_user_first,
                         fu.last_name as from_user_last,
                         tu.first_name as to_user_first,
                         tu.last_name as to_user_last
                  FROM " . $this->table_name . " t
                  LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
                  LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
                  LEFT JOIN users fu ON fa.user_id = fu.user_id
                  LEFT JOIN users tu ON ta.user_id = tu.user_id
                  WHERE t.reference_number LIKE :search 
                     OR fa.account_number LIKE :search 
                     OR ta.account_number LIKE :search
                     OR t.description LIKE :search
                  ORDER BY t.created_at DESC
                  LIMIT 50";

        $search_param = "%$search_term%";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":search", $search_param);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserTransactionCount($user_id, $account_filter = '', $type_filter = '', $date_from = '', $date_to = '') {
        $query = "SELECT COUNT(DISTINCT t.transaction_id) as count
                  FROM " . $this->table_name . " t
                  LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
                  LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
                  WHERE (fa.user_id = :user_id OR ta.user_id = :user_id)";

        $params = [':user_id' => $user_id];

        if($account_filter) {
            $query .= " AND (t.from_account_id = :account_id OR t.to_account_id = :account_id)";
            $params[':account_id'] = $account_filter;
        }

        if($type_filter) {
            $query .= " AND t.transaction_type = :type";
            $params[':type'] = $type_filter;
        }

        if($date_from) {
            $query .= " AND DATE(t.created_at) >= :date_from";
            $params[':date_from'] = $date_from;
        }

        if($date_to) {
            $query .= " AND DATE(t.created_at) <= :date_to";
            $params[':date_to'] = $date_to;
        }

        $stmt = $this->conn->prepare($query);
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['count'] : 0;
    }

    public function getUserTransactionSummary($user_id, $account_filter = '', $date_from = '', $date_to = '') {
        $query = "SELECT 
                    COUNT(DISTINCT t.transaction_id) as total_count,
                    SUM(CASE WHEN ta.user_id = :user_id AND fa.user_id != :user_id THEN t.amount ELSE 0 END) as total_credit,
                    SUM(CASE WHEN fa.user_id = :user_id AND ta.user_id != :user_id THEN t.amount 
                             WHEN fa.user_id = :user_id AND t.transaction_type IN ('withdrawal') THEN t.amount 
                             ELSE 0 END) as total_debit
                  FROM " . $this->table_name . " t
                  LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
                  LEFT JOIN accounts ta ON t.to_account_id = ta.account_id
                  WHERE (fa.user_id = :user_id OR ta.user_id = :user_id)
                    AND t.status = 'completed'";

        $params = [':user_id' => $user_id];

        if($account_filter) {
            $query .= " AND (t.from_account_id = :account_id OR t.to_account_id = :account_id)";
            $params[':account_id'] = $account_filter;
        }

        if($date_from) {
            $query .= " AND DATE(t.created_at) >= :date_from";
            $params[':date_from'] = $date_from;
        }

        if($date_to) {
            $query .= " AND DATE(t.created_at) <= :date_to";
            $params[':date_to'] = $date_to;
        }

        $stmt = $this->conn->prepare($query);
        foreach($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
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
<?php
class User {
    private $conn;
    private $table_name = "users";

    public $user_id;
    public $username;
    public $email;
    public $password_hash;
    public $first_name;
    public $last_name;
    public $phone;
    public $address;
    public $date_of_birth;
    public $registration_status;
    public $is_active;
    public $created_at;
    public $updated_at;
    public $last_login;

    public function __construct($db) {
        $this->conn = $db;
    }


    public function register() {
        $query = "INSERT INTO " . $this->table_name . " 
                SET username=:username, email=:email, password_hash=:password_hash, 
                    first_name=:first_name, last_name=:last_name, phone=:phone, 
                    address=:address, date_of_birth=:date_of_birth";

        $stmt = $this->conn->prepare($query);


        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->address = htmlspecialchars(strip_tags($this->address));

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":date_of_birth", $this->date_of_birth);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function login($username, $password) {
        $query = "SELECT user_id, username, email, password_hash, first_name, last_name, 
                         registration_status, is_active 
                  FROM " . $this->table_name . " 
                  WHERE (username = :username OR email = :username) 
                    AND is_active = 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row && password_verify($password, $row['password_hash'])) {
            if($row['registration_status'] == 'approved') {
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['user_name'] = $row['first_name'] . ' ' . $row['last_name'];
                $_SESSION['user_email'] = $row['email'];
                
                $this->updateLastLogin($row['user_id']);
                
                return true;
            } else {
                $_SESSION['login_error'] = "Your account is not yet approved or has been rejected.";
                return false;
            }
        }
        return false;
    }

    public function exists($username, $email) {
        $query = "SELECT user_id FROM " . $this->table_name . " 
                  WHERE username = :username OR email = :email";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $email);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row) {
            $this->user_id = $row['user_id'];
            $this->username = $row['username'];
            $this->email = $row['email'];
            $this->first_name = $row['first_name'];
            $this->last_name = $row['last_name'];
            $this->phone = $row['phone'];
            $this->address = $row['address'];
            $this->date_of_birth = $row['date_of_birth'];
            $this->registration_status = $row['registration_status'];
            $this->is_active = $row['is_active'];
            $this->created_at = $row['created_at'];
            $this->updated_at = $row['updated_at'] ?? null;
            $this->last_login = $row['last_login'] ?? null;
            return $row; // Return the user data array
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                SET first_name=:first_name, last_name=:last_name, phone=:phone, 
                    address=:address, date_of_birth=:date_of_birth 
                WHERE user_id=:user_id";

        $stmt = $this->conn->prepare($query);

        $this->first_name = htmlspecialchars(strip_tags($this->first_name));
        $this->last_name = htmlspecialchars(strip_tags($this->last_name));
        $this->phone = htmlspecialchars(strip_tags($this->phone));
        $this->address = htmlspecialchars(strip_tags($this->address));

        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":date_of_birth", $this->date_of_birth);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    public function changePassword($current_password, $new_password) {
        $query = "SELECT password_hash FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row && password_verify($current_password, $row['password_hash'])) {
            $query = "UPDATE " . $this->table_name . " SET password_hash=:password_hash WHERE user_id=:user_id";
            $stmt = $this->conn->prepare($query);
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt->bindParam(":password_hash", $hashed_password);
            $stmt->bindParam(":user_id", $this->user_id);

            return $stmt->execute();
        }
        return false;
    }

    public function getPendingUsers() {
        $query = "SELECT user_id, username, email, first_name, last_name, phone, 
                         date_of_birth, created_at 
                  FROM " . $this->table_name . " 
                  WHERE registration_status = 'pending' 
                  ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateRegistrationStatus($user_id, $status, $admin_id) {
        $query = "UPDATE " . $this->table_name . " 
                SET registration_status=:status 
                WHERE user_id=:user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":status", $status);
        $stmt->bindParam(":user_id", $user_id);

        if($stmt->execute()) {
            $this->logAdminAction($admin_id, 'UPDATE_USER_STATUS', 'users', $user_id, 
                                ['registration_status' => $status]);
            return true;
        }
        return false;
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

    public function softDelete($user_id, $admin_id) {
        $query = "UPDATE " . $this->table_name . " SET is_active = 0 WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);

        if($stmt->execute()) {
            $this->logAdminAction($admin_id, 'SOFT_DELETE_USER', 'users', $user_id, 
                                ['is_active' => 0, 'action' => 'deactivated']);
            return true;
        }
        return false;
    }

    public function hardDelete($user_id, $admin_id) {
        try {
            $this->conn->beginTransaction();

            $user_info = $this->getById($user_id);
            if(!$user_info) {
                $this->conn->rollback();
                return false;
            }

            $account_check = "SELECT COUNT(*) as count FROM accounts WHERE user_id = :user_id AND status IN ('active', 'pending')";
            $stmt = $this->conn->prepare($account_check);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            $account_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if($account_count > 0) {
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Cannot delete user with active accounts. Close accounts first.'];
            }

            $txn_check = "SELECT COUNT(*) as count FROM transactions t 
                         JOIN accounts a ON (t.from_account_id = a.account_id OR t.to_account_id = a.account_id) 
                         WHERE a.user_id = :user_id";
            $stmt = $this->conn->prepare($txn_check);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            $txn_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

            if($txn_count > 0) {
                $this->conn->rollback();
                return ['success' => false, 'message' => 'Cannot delete user with transaction history. Use deactivate instead.'];
            }

            $delete_accounts = "DELETE FROM accounts WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($delete_accounts);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();

            $delete_user = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
            $stmt = $this->conn->prepare($delete_user);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();

            $this->logAdminAction($admin_id, 'HARD_DELETE_USER', 'users', $user_id, [
                'deleted_user' => $user_info,
                'action' => 'permanently_deleted'
            ]);

            $this->conn->commit();
            return ['success' => true, 'message' => 'User permanently deleted successfully.'];

        } catch(Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error deleting user: ' . $e->getMessage()];
        }
    }

    public function canDelete($user_id) {
        $checks = [];

        $query = "SELECT COUNT(*) as count FROM accounts WHERE user_id = :user_id AND status IN ('active', 'pending')";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $active_accounts = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $query = "SELECT SUM(balance) as total_balance FROM accounts WHERE user_id = :user_id AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $total_balance = $stmt->fetch(PDO::FETCH_ASSOC)['total_balance'] ?: 0;

        $query = "SELECT COUNT(*) as count FROM transactions t 
                 JOIN accounts a ON (t.from_account_id = a.account_id OR t.to_account_id = a.account_id) 
                 WHERE a.user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $transaction_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        return [
            'can_soft_delete' => true,
            'can_hard_delete' => ($active_accounts == 0 && $total_balance == 0 && $transaction_count == 0),
            'active_accounts' => $active_accounts,
            'total_balance' => $total_balance,
            'transaction_count' => $transaction_count,
            'warnings' => [
                'has_active_accounts' => $active_accounts > 0,
                'has_balance' => $total_balance > 0,
                'has_transactions' => $transaction_count > 0
            ]
        ];
    }

    public function updateProfile() {
        $query = "UPDATE " . $this->table_name . " SET first_name = :first_name, last_name = :last_name, 
                  phone = :phone, date_of_birth = :date_of_birth, address = :address, updated_at = NOW() 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":first_name", $this->first_name);
        $stmt->bindParam(":last_name", $this->last_name);
        $stmt->bindParam(":phone", $this->phone);
        $stmt->bindParam(":date_of_birth", $this->date_of_birth);
        $stmt->bindParam(":address", $this->address);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    public function updatePassword() {
        $query = "UPDATE " . $this->table_name . " SET password_hash = :password_hash, updated_at = NOW() 
                  WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":password_hash", $this->password_hash);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    public function updateLastLogin($user_id) {
        $query = "UPDATE " . $this->table_name . " SET last_login = NOW() WHERE user_id = :user_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);

        return $stmt->execute();
    }

    public static function logout() {
        unset($_SESSION['user_id']);
        unset($_SESSION['username']);
        unset($_SESSION['user_name']);
        unset($_SESSION['user_email']);
        session_destroy();
    }
}
?>
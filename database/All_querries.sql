INSERT INTO users 
SET username=:username, email=:email, password_hash=:password_hash, 
   first_name=:first_name, last_name=:last_name, phone=:phone, 
   address=:address, date_of_birth=:date_of_birth;

SELECT user_id, username, email, password_hash, first_name, last_name, 
      registration_status, is_active 
FROM users 
WHERE (username = :username OR email = :username) 
 AND is_active = 1;

SELECT user_id FROM users WHERE username = :username OR email = :email;

SELECT * FROM users WHERE user_id = :user_id;

UPDATE users 
SET first_name=:first_name, last_name=:last_name, phone=:phone, 
   address=:address, date_of_birth=:date_of_birth 
WHERE user_id=:user_id;

SELECT password_hash FROM users WHERE user_id = :user_id;

UPDATE users SET password_hash=:password_hash WHERE user_id=:user_id;

SELECT user_id, username, email, first_name, last_name, phone, 
      date_of_birth, created_at 
FROM users 
WHERE registration_status = 'pending' 
ORDER BY created_at DESC;

UPDATE users SET registration_status=:status WHERE user_id=:user_id;

UPDATE users SET is_active = 0 WHERE user_id = :user_id;

SELECT COUNT(*) as count FROM accounts WHERE user_id = :user_id AND status IN ('active','pending');

SELECT COUNT(*) as count 
FROM transactions t 
JOIN accounts a ON (t.from_account_id = a.account_id OR t.to_account_id = a.account_id) 
WHERE a.user_id = :user_id;

DELETE FROM accounts WHERE user_id = :user_id;

DELETE FROM users WHERE user_id = :user_id;

SELECT COUNT(*) as count FROM accounts WHERE user_id = :user_id AND status IN ('active','pending');

SELECT SUM(balance) as total_balance FROM accounts WHERE user_id = :user_id AND status='active';

SELECT COUNT(*) as count 
FROM transactions t 
JOIN accounts a ON (t.from_account_id = a.account_id OR t.to_account_id = a.account_id) 
WHERE a.user_id = :user_id;

UPDATE users 
SET first_name = :first_name, last_name = :last_name, phone = :phone, 
   date_of_birth = :date_of_birth, address = :address, updated_at = NOW() 
WHERE user_id = :user_id;

UPDATE users SET last_login = NOW() WHERE user_id = :user_id;

CALL CreateAccount(:user_id, :account_type, :admin_id, @account_number);

SELECT @account_number as account_number;

SELECT a.*, u.first_name, u.last_name, u.email, u.phone
FROM accounts a
JOIN users u ON a.user_id = u.user_id
WHERE a.account_id = :account_id;

SELECT a.*, u.first_name, u.last_name, u.email, u.phone
FROM accounts a
JOIN users u ON a.user_id = u.user_id
WHERE a.account_number = :account_number;

SELECT * FROM accounts WHERE user_id = :user_id ORDER BY created_at DESC;

SELECT a.*, u.first_name, u.last_name, u.email, u.username,
      au.full_name as created_by_name
FROM accounts a
JOIN users u ON a.user_id = u.user_id
LEFT JOIN admin_users au ON a.created_by_admin = au.admin_id
ORDER BY a.created_at DESC
LIMIT :limit OFFSET :offset;

SELECT a.*, u.first_name, u.last_name, u.email, u.username,
      au.full_name as created_by_name
FROM accounts a
JOIN users u ON a.user_id = u.user_id
LEFT JOIN admin_users au ON a.created_by_admin = au.admin_id
WHERE 1=1
 AND (
   a.account_number LIKE :search OR
   u.first_name    LIKE :search OR
   u.last_name     LIKE :search OR
   u.email         LIKE :search OR
   u.username      LIKE :search OR
   a.account_type  LIKE :search OR
   CONCAT(u.first_name,' ',u.last_name) LIKE :search
 )
ORDER BY a.created_at DESC
LIMIT :limit OFFSET :offset;

UPDATE accounts SET status = :status WHERE account_id = :account_id;

SELECT balance FROM accounts WHERE account_id = :account_id;

UPDATE accounts SET balance = :balance WHERE account_id = :account_id;

INSERT INTO transactions 
 (from_account_id, to_account_id, transaction_type, amount, description, reference_number, status)
VALUES 
 (:from_account, :to_account, :type, :amount, :description, :reference, 'completed');

SELECT 
 a.*, u.first_name, u.last_name, u.email,
 COUNT(t.transaction_id) as total_transactions,
 SUM(CASE WHEN t.transaction_type='deposit' AND t.status='completed' THEN t.amount ELSE 0 END) as total_deposits,
 SUM(CASE WHEN t.transaction_type='withdrawal' AND t.status='completed' THEN t.amount ELSE 0 END) as total_withdrawals,
 MAX(t.created_at) as last_transaction_date
FROM accounts a
JOIN users u ON a.user_id = u.user_id
LEFT JOIN transactions t ON (a.account_id = t.from_account_id OR a.account_id = t.to_account_id)
WHERE a.account_id = :account_id
GROUP BY a.account_id;

SELECT a.*, u.first_name, u.last_name, u.email
FROM accounts a
JOIN users u ON a.user_id = u.user_id
WHERE a.account_number LIKE :search 
  OR u.first_name LIKE :search 
  OR u.last_name  LIKE :search 
  OR u.email      LIKE :search
ORDER BY a.created_at DESC
LIMIT 20;

UPDATE accounts SET status='closed' WHERE account_id=:account_id;

SELECT COUNT(*) as count 
FROM transactions 
WHERE from_account_id = :account_id OR to_account_id = :account_id;

DELETE FROM accounts WHERE account_id = :account_id;

SELECT * FROM account_types WHERE is_active = 1;

CALL TransferMoney(:from_account, :to_account, :amount, :description, @result, @reference);

SELECT @result as result, @reference as reference;

INSERT INTO transactions 
 (to_account_id, transaction_type, amount, description, reference_number, status)
VALUES 
 (:account_id, 'deposit', :amount, :description, :reference, 'completed');

SELECT balance FROM accounts WHERE account_id = :account_id;

INSERT INTO transactions 
 (from_account_id, transaction_type, amount, description, reference_number, status)
VALUES 
 (:account_id, 'withdrawal', :amount, :description, :reference, 'completed');

SELECT t.*, 
      fa.account_number as from_account_number,
      ta.account_number as to_account_number,
      fu.first_name as from_user_first,
      fu.last_name  as from_user_last,
      tu.first_name as to_user_first,
      tu.last_name  as to_user_last
FROM transactions t
LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
LEFT JOIN accounts ta ON t.to_account_id   = ta.account_id
LEFT JOIN users   fu ON fa.user_id         = fu.user_id
LEFT JOIN users   tu ON ta.user_id         = tu.user_id
WHERE t.transaction_id = :transaction_id;

SELECT t.*, 
      fa.account_number as from_account_number,
      ta.account_number as to_account_number,
      fu.first_name as from_user_first,
      fu.last_name  as from_user_last,
      tu.first_name as to_user_first,
      tu.last_name  as to_user_last
FROM transactions t
LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
LEFT JOIN accounts ta ON t.to_account_id   = ta.account_id
LEFT JOIN users   fu ON fa.user_id         = fu.user_id
LEFT JOIN users   tu ON ta.user_id         = tu.user_id
WHERE t.reference_number = :reference;

SELECT t.*,
      CASE 
        WHEN t.from_account_id = :account_id THEN 'outgoing'
        WHEN t.to_account_id   = :account_id THEN 'incoming'
        ELSE 'unknown'
      END as direction,
      fa.account_number as from_account_number,
      ta.account_number as to_account_number,
      fu.first_name as from_user_first,
      fu.last_name  as from_user_last,
      tu.first_name as to_user_first,
      tu.last_name  as to_user_last
FROM transactions t
LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
LEFT JOIN accounts ta ON t.to_account_id   = ta.account_id
LEFT JOIN users   fu ON fa.user_id         = fu.user_id
LEFT JOIN users   tu ON ta.user_id         = tu.user_id
WHERE (t.from_account_id = :account_id OR t.to_account_id = :account_id)
 AND t.status = 'completed'
ORDER BY t.created_at DESC
LIMIT :limit OFFSET :offset;

SELECT t.*,
      CASE WHEN fa.user_id = :user_id THEN 'outgoing'
           WHEN ta.user_id = :user_id THEN 'incoming'
           ELSE 'unknown' END as direction,
      CASE WHEN fa.user_id = :user_id THEN fa.account_number
           WHEN ta.user_id = :user_id THEN ta.account_number
           ELSE COALESCE(fa.account_number, ta.account_number) END as account_number,
      CASE WHEN fa.user_id = :user_id THEN fa.account_type
           WHEN ta.user_id = :user_id THEN ta.account_type
           ELSE COALESCE(fa.account_type, ta.account_type) END as account_type,
      fa.account_number as from_account_number,
      ta.account_number as to_account_number,
      fu.first_name as from_user_first,
      fu.last_name  as from_user_last,
      tu.first_name as to_user_first,
      tu.last_name  as to_user_last
FROM transactions t
LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
LEFT JOIN accounts ta ON t.to_account_id   = ta.account_id
LEFT JOIN users   fu ON fa.user_id         = fu.user_id
LEFT JOIN users   tu ON ta.user_id         = tu.user_id
WHERE (fa.user_id = :user_id OR ta.user_id = :user_id)
 AND t.status = 'completed'
ORDER BY t.created_at DESC
LIMIT :limit OFFSET :offset;

SELECT t.*,
      fa.account_number as from_account_number,
      ta.account_number as to_account_number,
      fu.first_name as from_user_first,
      fu.last_name  as from_user_last,
      tu.first_name as to_user_first,
      tu.last_name  as to_user_last
FROM transactions t
LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
LEFT JOIN accounts ta ON t.to_account_id   = ta.account_id
LEFT JOIN users   fu ON fa.user_id         = fu.user_id
LEFT JOIN users   tu ON ta.user_id         = tu.user_id
ORDER BY t.created_at DESC
LIMIT :limit OFFSET :offset;

SELECT 
 t.transaction_type,
 COUNT(*)     as count,
 SUM(t.amount) as total_amount,
 AVG(t.amount) as average_amount
FROM transactions t
WHERE t.status = 'completed'
GROUP BY t.transaction_type
ORDER BY total_amount DESC;

SELECT t.*,
      fa.account_number as from_account_number,
      ta.account_number as to_account_number,
      fu.first_name as from_user_first,
      fu.last_name  as from_user_last,
      tu.first_name as to_user_first,
      tu.last_name  as to_user_last
FROM transactions t
LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
LEFT JOIN accounts ta ON t.to_account_id   = ta.account_id
LEFT JOIN users   fu ON fa.user_id         = fu.user_id
LEFT JOIN users   tu ON ta.user_id         = tu.user_id
WHERE t.reference_number LIKE :search 
  OR fa.account_number  LIKE :search 
  OR ta.account_number  LIKE :search
  OR t.description      LIKE :search
ORDER BY t.created_at DESC
LIMIT 50;

SELECT COUNT(DISTINCT t.transaction_id) as count
FROM transactions t
LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
LEFT JOIN accounts ta ON t.to_account_id   = ta.account_id
WHERE (fa.user_id = :user_id OR ta.user_id = :user_id);

SELECT 
 COUNT(DISTINCT t.transaction_id) as total_count,
 SUM(CASE WHEN ta.user_id = :user_id AND fa.user_id != :user_id THEN t.amount ELSE 0 END) as total_credit,
 SUM(CASE WHEN fa.user_id = :user_id AND ta.user_id != :user_id THEN t.amount 
          WHEN fa.user_id = :user_id AND t.transaction_type IN ('withdrawal') THEN t.amount 
          ELSE 0 END) as total_debit
FROM transactions t
LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
LEFT JOIN accounts ta ON t.to_account_id   = ta.account_id
WHERE (fa.user_id = :user_id OR ta.user_id = :user_id)
 AND t.status = 'completed';

SELECT admin_id, username, email, password_hash, full_name, role 
FROM admin_users 
WHERE (username = :username OR email = :username) 
 AND is_active = 1;

SELECT * FROM admin_users WHERE admin_id = :admin_id;

SELECT u.user_id, u.username, u.email, u.first_name, u.last_name, 
      u.phone, u.registration_status, u.is_active, u.created_at,
      COUNT(a.account_id) as account_count
FROM users u
LEFT JOIN accounts a ON u.user_id = a.user_id
GROUP BY u.user_id
ORDER BY u.created_at DESC
LIMIT :limit OFFSET :offset;

SELECT COUNT(*) as total FROM users;

UPDATE users SET registration_status=:status WHERE user_id=:user_id;

UPDATE users SET is_active=:is_active WHERE user_id=:user_id;

SELECT COUNT(*) as total FROM users;

SELECT COUNT(*) as total FROM users WHERE registration_status = 'pending';

SELECT COUNT(*) as total FROM accounts;

SELECT COUNT(*) as total FROM accounts WHERE status = 'active';

SELECT SUM(balance) as total FROM accounts WHERE status = 'active';

SELECT COUNT(*) as total FROM transactions WHERE DATE(created_at) = CURDATE();

SELECT SUM(amount) as total FROM transactions WHERE DATE(created_at) = CURDATE() AND status = 'completed';

SELECT al.*, u.first_name, u.last_name, au.full_name as admin_name
FROM audit_logs al
LEFT JOIN users u ON al.user_id = u.user_id
LEFT JOIN admin_users au ON al.admin_id = au.admin_id
ORDER BY al.created_at DESC
LIMIT :limit;

SELECT t.*, 
      fa.account_number as from_account,
      ta.account_number as to_account,
      fu.first_name as from_user_first,
      fu.last_name  as from_user_last,
      tu.first_name as to_user_first,
      tu.last_name  as to_user_last
FROM transactions t
LEFT JOIN accounts fa ON t.from_account_id = fa.account_id
LEFT JOIN accounts ta ON t.to_account_id   = ta.account_id
LEFT JOIN users   fu ON fa.user_id         = fu.user_id
LEFT JOIN users   tu ON ta.user_id         = tu.user_id
WHERE t.created_at BETWEEN :start_date AND :end_date
ORDER BY t.created_at DESC;

SELECT 
 DATE(t.created_at) as transaction_date,
 t.transaction_type,
 COUNT(*)  as transaction_count,
 SUM(t.amount) as total_amount,
 AVG(t.amount) as average_amount
FROM transactions t
WHERE DATE(t.created_at) BETWEEN :start_date AND :end_date
 AND t.status = 'completed'
GROUP BY DATE(t.created_at), t.transaction_type
ORDER BY transaction_date DESC, t.transaction_type;

SELECT 
 u.user_id,
 CONCAT(u.first_name,' ',u.last_name) as user_name,
 u.email,
 a.account_number,
 a.account_type,
 a.balance,
 a.status,
 a.created_at,
 COUNT(t.transaction_id) as total_transactions,
 SUM(CASE WHEN t.transaction_type='deposit'   AND t.status='completed' THEN t.amount ELSE 0 END) as total_deposits,
 SUM(CASE WHEN t.transaction_type='withdrawal' AND t.status='completed' THEN t.amount ELSE 0 END) as total_withdrawals
FROM users u
JOIN accounts a ON u.user_id = a.user_id
LEFT JOIN transactions t ON (a.account_id = t.from_account_id OR a.account_id = t.to_account_id)
 AND DATE(t.created_at) BETWEEN :start_date AND :end_date
WHERE u.registration_status = 'approved'
GROUP BY u.user_id, a.account_id
ORDER BY a.balance DESC;

SELECT 
 u.user_id,
 CONCAT(u.first_name,' ',u.last_name) as user_name,
 u.email,
 u.registration_status,
 u.created_at as registration_date,
 COUNT(DISTINCT a.account_id) as total_accounts,
 COUNT(t.transaction_id) as total_transactions,
 MAX(t.created_at) as last_transaction_date,
 SUM(CASE WHEN t.transaction_type='deposit'   AND t.status='completed' THEN t.amount ELSE 0 END) as total_deposits,
 SUM(CASE WHEN t.transaction_type='withdrawal' AND t.status='completed' THEN t.amount ELSE 0 END) as total_withdrawals
FROM users u
LEFT JOIN accounts a ON u.user_id = a.user_id
LEFT JOIN transactions t ON (a.account_id = t.from_account_id OR a.account_id = t.to_account_id)
 AND DATE(t.created_at) BETWEEN :start_date AND :end_date
GROUP BY u.user_id
ORDER BY total_transactions DESC;

SELECT al.*, 
      au.full_name as admin_name, 
      au.role      as admin_role,
      u.first_name, 
      u.last_name
FROM audit_logs al
LEFT JOIN admin_users au ON al.admin_id = au.admin_id
LEFT JOIN users u ON al.user_id = u.user_id
ORDER BY al.created_at DESC
LIMIT :limit OFFSET :offset;

SELECT COUNT(*) as total FROM audit_logs al;

SELECT admin_id, full_name FROM admin_users WHERE is_active = 1 ORDER BY full_name;

INSERT INTO audit_logs (admin_id, action, table_name, record_id, new_values, ip_address) 
VALUES (:admin_id, :action, :table_name, :record_id, :new_values, :ip_address);
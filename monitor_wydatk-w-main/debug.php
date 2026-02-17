<?php
require_once 'config.php';
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

$result = [
    'mysql_connected' => false,
    'users_table_exists' => false,
    'users_count' => 0,
    'users_list' => [],
    'test_login' => null,
    'error' => null
];

try {
    $result['mysql_connected'] = true;
    
    $check = $pdo->query("SHOW TABLES LIKE 'users'")->fetch();
    if ($check) {
        $result['users_table_exists'] = true;
        
        $users = $pdo->query("SELECT id, username, role FROM users ORDER BY id")->fetchAll();
        $result['users_count'] = count($users);
        $result['users_list'] = $users;
        
        $test_user = $pdo->query("SELECT id, username, password, role FROM users WHERE username = 'admin' LIMIT 1")->fetch();
        
        if ($test_user) {
            $password_match = password_verify('admin123', $test_user['password']);
            $result['test_login'] = [
                'user_found' => true,
                'username' => $test_user['username'],
                'role' => $test_user['role'],
                'password_hash' => substr($test_user['password'], 0, 20) . '...',
                'password_verify_admin123' => $password_match,
                'session_test' => [
                    'session_id' => session_id() ?: 'NOT STARTED',
                    'session_started' => session_status() === PHP_SESSION_ACTIVE
                ]
            ];
        } else {
            $result['test_login'] = ['error' => 'User "admin" not found'];
        }
    }
    
} catch (Exception $e) {
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>

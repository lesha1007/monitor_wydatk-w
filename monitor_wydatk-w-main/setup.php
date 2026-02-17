<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$result = [
    'success' => false,
    'steps' => [],
    'error' => null
];

try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};charset={$db_config['charset']}",
        $db_config['user'],
        $db_config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $result['steps'][] = '✓ Połączenie z MySQL OK';

    $db_name = $db_config['db'];
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}`");
    $pdo->exec("USE `{$db_name}`");
    
    $result['steps'][] = "✓ Baza danych '{$db_name}' OK";

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $result['steps'][] = '✓ Tabela "users" OK';

    $pdo->exec("CREATE TABLE IF NOT EXISTS wydatki (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL DEFAULT 1,
        nazwa VARCHAR(255) NOT NULL,
        kwota DECIMAL(10, 2) NOT NULL,
        kategoria VARCHAR(100) NOT NULL,
        data_wydatku DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $result['steps'][] = '✓ Tabela "wydatki" OK (z user_id FK)';

    $count = $pdo->query("SELECT COUNT(*) as cnt FROM users")->fetch()['cnt'];
    
    if ($count == 0) {
        $admin_pass = password_hash('admin123', PASSWORD_BCRYPT);
        $user_pass = password_hash('user123', PASSWORD_BCRYPT);
        
        $pdo->exec("INSERT INTO users (username, password, role) VALUES 
            ('admin', '{$admin_pass}', 'admin'),
            ('user', '{$user_pass}', 'user')");
        
        $result['steps'][] = '✓ Dodano demopользователей';
    } else {
        $result['steps'][] = "ℹ Użytkownicy już istnieją ({$count})";
    }

    $result['success'] = true;
    $result['demo_users'] = [
        'admin' => ['login' => 'admin', 'password' => 'admin123'],
        'user' => ['login' => 'user', 'password' => 'user123']
    ];
    $result['message'] = 'System jest gotowy!';

} catch (Exception $e) {
    $result['error'] = $e->getMessage();
    $result['success'] = false;
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>

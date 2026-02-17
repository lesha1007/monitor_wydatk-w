<?php
require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$result = [
    'success' => false,
    'message' => '',
    'steps' => []
];

try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['db']};charset={$db_config['charset']}",
        $db_config['user'],
        $db_config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $result['steps'][] = '✓ Соединение с БД OK';

    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    $pdo->exec("DROP TABLE IF EXISTS wydatki");
    $pdo->exec("DROP TABLE IF EXISTS users");
    $pdo->exec("SET FOREIGN_KEY_CHECKS=1");
    
    $result['steps'][] = '✓ Старые таблицы удалены';

    $pdo->exec("CREATE TABLE users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('user', 'admin') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $result['steps'][] = '✓ Таблица users создана';

    $pdo->exec("CREATE TABLE wydatki (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL DEFAULT 1,
        nazwa VARCHAR(255) NOT NULL,
        kwota DECIMAL(10, 2) NOT NULL,
        kategoria VARCHAR(100) NOT NULL,
        data_wydatku DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    $result['steps'][] = '✓ Таблица wydatki создана';

    $admin_pass = password_hash('admin123', PASSWORD_BCRYPT);
    $user_pass = password_hash('user123', PASSWORD_BCRYPT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->execute(['admin', $admin_pass, 'admin']);
    $stmt->execute(['user', $user_pass, 'user']);
    
    $result['steps'][] = '✓ Демо-пользователи добавлены с правильными хешами';

    $test = $pdo->query("SELECT id, username, password, role FROM users WHERE username = 'admin'")->fetch();
    $verify = password_verify('admin123', $test['password']);
    
    $result['verification'] = [
        'admin_verify' => $verify,
        'password_hash' => substr($test['password'], 0, 20) . '...'
    ];
    
    if ($verify) {
        $result['success'] = true;
        $result['message'] = 'Пользователи пересозданы с правильными паролями!';
    }

} catch (Exception $e) {
    $result['error'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>

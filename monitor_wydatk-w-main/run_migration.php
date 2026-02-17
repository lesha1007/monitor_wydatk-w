<?php
require_once 'config.php';

try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};charset={$db_config['charset']}",
        $db_config['user'],
        $db_config['password']
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS {$db_config['db']}");
    $pdo->exec("USE {$db_config['db']}");

    $sql = file_get_contents('migration_users.sql');
    
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($stmt) => !empty($stmt) && !preg_match('/^--/', trim($stmt))
    );

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Migration completed successfully!',
        'demo_users' => [
            'admin' => 'admin123',
            'user' => 'user123'
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>

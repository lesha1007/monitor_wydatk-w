<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'DataSourceFactory.php';

header('Content-Type: application/json; charset=utf-8');

Auth::requireLogin();

try {
    $current_user = Auth::getCurrentUser();
    $user_id = $current_user['id'];
    
    $user_settings = get_user_settings();
    $current_source = $user_settings['data_source'] ?? 'mysql';
    $ds = DataSourceFactory::create($current_source);
    $dane = $ds->getAll($user_id);

    echo json_encode($dane, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Błąd podczas pobierania danych',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

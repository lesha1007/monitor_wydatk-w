<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'DataSourceFactory.php';

Auth::requireLogin();

$current_user = Auth::getCurrentUser();
$user_id = $current_user['id'];

$format = $_GET['format'] ?? 'json';
$user_settings = get_user_settings();
$current_source = $user_settings['data_source'] ?? 'mysql';

try {
    $ds = DataSourceFactory::create($current_source);
    $data = $ds->getAll($user_id);
    
    if (empty($data)) {
        $data = [];
    }
    
    if ($format === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="wydatki_' . date('Y-m-d_His') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        
        fclose($output);
    } else {
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="wydatki_' . date('Y-m-d_His') . '.json"');
        
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
    
    exit();
    
} catch (Exception $e) {
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    echo json_encode([
        'error' => 'Błąd eksportu',
        'message' => $e->getMessage()
    ]);
}
?>

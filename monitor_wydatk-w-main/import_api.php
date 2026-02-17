<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'DataSourceFactory.php';

Auth::requireLogin();

$current_user = Auth::getCurrentUser();
$user_id = $current_user['id'];

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    try {
        $file = $_FILES['file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Błąd przesyłania: " . $file['error']);
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception("Plik za duży (max 5 MB)");
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $records = [];
        
        if ($ext === 'json') {
            $content = file_get_contents($file['tmp_name']);
            $records = json_decode($content, true);
            if (!is_array($records)) {
                $records = [$records];
            }
        } elseif ($ext === 'csv') {
            if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
                $header = fgetcsv($handle);
                while (($row = fgetcsv($handle)) !== false) {
                    if (count($row) === count($header)) {
                        $records[] = array_combine($header, $row);
                    }
                }
                fclose($handle);
            }
        } else {
            throw new Exception("Nieobsługiwany format: {$ext}. Wspierane: json, csv");
        }
        
        $user_settings = get_user_settings();
        $current_source = $user_settings['data_source'] ?? 'mysql';
        $ds = DataSourceFactory::create($current_source);
        
        $import_mode = $_POST['mode'] ?? 'merge';
        $result = $ds->import($records, $import_mode, $user_id);
        
        echo json_encode([
            'success' => true,
            'message' => "Zaimportowano {$result['count']} rekordów",
            'data' => $result
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'error' => 'Brak pliku do importu'
    ]);
}
?>

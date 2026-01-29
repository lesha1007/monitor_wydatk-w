<?php
require_once 'config.php';
require_once 'DataSourceFactory.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $ds = DataSourceFactory::getInstance();
    $dane = $ds->getChartData();

    echo json_encode($dane, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Błąd podczas pobierania danych',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

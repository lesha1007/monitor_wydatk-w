<?php
require_once 'db.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $sql = "SELECT * FROM wydatki ORDER BY data_wydatku DESC, created_at DESC";
    $stmt = $pdo->query($sql);
    $dane = $stmt->fetchAll();

    echo json_encode($dane, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Błąd podczas pobierania danych',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>

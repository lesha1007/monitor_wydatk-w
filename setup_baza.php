<?php
$host = 'localhost';
$user = 'root';
$password = '';

try {
    $pdo = new PDO(
        "mysql:host=$host;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );

    $pdo->exec("CREATE DATABASE IF NOT EXISTS portfel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Baza danych 'portfel' została utworzona lub już istnieje<br>";

    $pdo = new PDO(
        "mysql:host=$host;dbname=portfel;charset=utf8mb4",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );

    $sql = "CREATE TABLE IF NOT EXISTS wydatki (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nazwa VARCHAR(100) NOT NULL,
        kwota DECIMAL(10, 2) NOT NULL,
        kategoria VARCHAR(50) NOT NULL,
        data_wydatku DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    $pdo->exec($sql);
    echo "✓ Tabela 'wydatki' została utworzona lub już istnieje<br>";

    echo "<br><strong style='color: green;'>Baza danych została pomyślnie zainicjalizowana!</strong><br>";
    echo "<a href='index.php'>Przejdź do aplikacji</a>";

} catch (PDOException $e) {
    echo "<strong style='color: red;'>Błąd:</strong> " . $e->getMessage();
}
?>

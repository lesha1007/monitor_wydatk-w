<?php
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test API - Monitor Wydatków</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; padding: 20px; }
        .container { max-width: 1000px; }
        .card { margin-top: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        pre { background-color: #f5f5f5; padding: 15px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4"><i class="fas fa-flask"></i> Test API aplikacji</h1>
        
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Status połączenia z bazą</h5>
            </div>
            <div class="card-body">
                <?php
                require_once 'db.php';
                
                try {
                    $test = $pdo->query("SELECT 1");
                    echo '<div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i> 
                            <strong>Połączenie OK!</strong> Baza danych jest dostępna.
                          </div>';
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger" role="alert">
                            <i class="fas fa-times-circle"></i> 
                            <strong>Błąd:</strong> ' . $e->getMessage() . '
                          </div>';
                }
                ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Tabela wydatki - zawartość</h5>
            </div>
            <div class="card-body">
                <?php
                try {
                    $sql = "SELECT * FROM wydatki ORDER BY data_wydatku DESC LIMIT 10";
                    $stmt = $pdo->query($sql);
                    $dane = $stmt->fetchAll();

                    if (empty($dane)) {
                        echo '<div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle"></i> Tabela jest pusta. Dodaj kilka wydatków.
                              </div>';
                    } else {
                        echo '<table class="table table-striped">
                                <thead class="table-light">
                                  <tr>
                                    <th>ID</th>
                                    <th>Nazwa</th>
                                    <th>Kwota</th>
                                    <th>Kategoria</th>
                                    <th>Data</th>
                                  </tr>
                                </thead>
                                <tbody>';
                        
                        foreach ($dane as $row) {
                            echo '<tr>
                                    <td>' . $row['id'] . '</td>
                                    <td>' . htmlspecialchars($row['nazwa']) . '</td>
                                    <td>' . number_format($row['kwota'], 2, ',', ' ') . ' zł</td>
                                    <td><span class="badge bg-secondary">' . $row['kategoria'] . '</span></td>
                                    <td>' . $row['data_wydatku'] . '</td>
                                  </tr>';
                        }
                        
                        echo '</tbody></table>';
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">
                            <strong>Błąd:</strong> ' . $e->getMessage() . '
                          </div>';
                }
                ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Test API - dane_wykresu.php (JSON)</h5>
            </div>
            <div class="card-body">
                <p>Pobieranie danych z API asynchronicznie...</p>
                <div id="wynik" class="alert alert-light">
                    <i class="fas fa-spinner fa-spin"></i> Ładowanie...
                </div>
                <p class="text-muted">
                    Endpoint: <code>dane_wykresu.php</code>
                </p>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-warning">
                <h5 class="mb-0">Statystyki bazy</h5>
            </div>
            <div class="card-body">
                <?php
                try {
                    $count = $pdo->query("SELECT COUNT(*) as cnt FROM wydatki")->fetch()['cnt'];
                    $suma = $pdo->query("SELECT SUM(kwota) as total FROM wydatki")->fetch()['total'] ?? 0;
                    $srednia = $pdo->query("SELECT AVG(kwota) as avg FROM wydatki")->fetch()['avg'] ?? 0;
                    
                    echo '<div class="row">
                            <div class="col-md-4">
                              <div class="alert alert-info">
                                <strong>Ilość wydatków:</strong><br>
                                <h5>' . $count . '</h5>
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="alert alert-success">
                                <strong>Suma wydatków:</strong><br>
                                <h5>' . number_format($suma, 2, ',', ' ') . ' zł</h5>
                              </div>
                            </div>
                            <div class="col-md-4">
                              <div class="alert alert-warning">
                                <strong>Średnia na wydatek:</strong><br>
                                <h5>' . number_format($srednia, 2, ',', ' ') . ' zł</h5>
                              </div>
                            </div>
                          </div>';
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
                }
                ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Serwer - informacje</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>Wersja PHP:</strong></td>
                        <td><?php echo phpversion(); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Serwer:</strong></td>
                        <td><?php echo $_SERVER['SERVER_SOFTWARE']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Katalog:</strong></td>
                        <td><?php echo $_SERVER['DOCUMENT_ROOT']; ?></td>
                    </tr>
                    <tr>
                        <td><strong>PDO dostępne:</strong></td>
                        <td><?php echo extension_loaded('pdo_mysql') ? '<span class="badge bg-success">TAK</span>' : '<span class="badge bg-danger">NIE</span>'; ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="mt-4 mb-4">
            <a href="index.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Powrót do aplikacji
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        async function testAPI() {
            try {
                const response = await fetch('dane_wykresu.php');
                const data = await response.json();
                
                const wynik = document.getElementById('wynik');
                
                if (Array.isArray(data) && data.length > 0) {
                    wynik.className = 'alert alert-success';
                    let html = '<strong><i class="fas fa-check-circle"></i> Sukces!</strong> API zwraca dane w JSON:<br><br>';
                    html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                    wynik.innerHTML = html;
                } else if (Array.isArray(data) && data.length === 0) {
                    wynik.className = 'alert alert-warning';
                    wynik.innerHTML = '<strong><i class="fas fa-info-circle"></i> Brak danych</strong><br>Tablica jest pusta. Dodaj wydatki, aby zobaczyć dane.';
                } else if (data.error) {
                    wynik.className = 'alert alert-danger';
                    wynik.innerHTML = '<strong><i class="fas fa-times-circle"></i> Błąd:</strong> ' + data.error;
                }
            } catch (error) {
                const wynik = document.getElementById('wynik');
                wynik.className = 'alert alert-danger';
                wynik.innerHTML = '<strong><i class="fas fa-times-circle"></i> Błąd:</strong> ' + error.message;
            }
        }
        
        document.addEventListener('DOMContentLoaded', testAPI);
    </script>
</body>
</html>

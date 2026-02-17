<?php
require_once 'auth.php';
require_once 'config.php';
require_once 'DataSourceFactory.php';

Auth::requireLogin();

$current_user = Auth::getCurrentUser();
$user_id = $current_user['id'];

$format = $_GET['format'] ?? $_POST['format'] ?? 'json';
$user_settings = get_user_settings();
$current_source = $user_settings['data_source'] ?? 'mysql';

if (isset($_GET['download'])) {
    try {
        $ds = DataSourceFactory::create($current_source);
        $data = $ds->export($user_id);

        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="wydatki_' . date('Y-m-d_His') . '.csv"');

            $output = fopen('php://output', 'w');
            
            if (!empty($data)) {
                fputcsv($output, array_keys($data[0]), ',');  
                foreach ($data as $row) {
                    fputcsv($output, $row, ',');
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
        die("Błąd: " . htmlspecialchars($e->getMessage()));
    }
}

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eksport danych - Monitor Wydatków</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; padding: 20px 0; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 0; margin-bottom: 30px; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .format-option { cursor: pointer; }
        .format-option:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    <div class="header">
        <div class="container d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-download"></i> Eksport danych</h1>
            <a href="index.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Powrót
            </a>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-file-download"></i> Wybierz format eksportu</h5>
                    </div>
                    <div class="card-body">
                        <p>Wybierz format, w którym chcesz pobrać dane:</p>

                        <div class="row">

                            <div class="col-md-6 mb-3">
                                <div class="card format-option h-100" onclick="exportData('csv')">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-csv fa-3x text-success mb-3"></i>
                                        <h5>CSV</h5>
                                        <p class="text-muted">Kompatybilny z Excel i Google Sheets</p>
                                        <button class="btn btn-success btn-sm">
                                            <i class="fas fa-download"></i> Pobierz CSV
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- JSON -->
                            <div class="col-md-6 mb-3">
                                <div class="card format-option h-100" onclick="exportData('json')">
                                    <div class="card-body text-center">
                                        <i class="fas fa-file-code fa-3x text-info mb-3"></i>
                                        <h5>JSON</h5>
                                        <p class="text-muted">Format strukturalny, łatwy do integracji</p>
                                        <button class="btn btn-info btn-sm">
                                            <i class="fas fa-download"></i> Pobierz JSON
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informacje o eksporcie</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $ds = DataSourceFactory::create($current_source);
                            $data = $ds->export();
                            $stats = $ds->getStats();
                            
                            echo '<table class="table table-sm">
                                    <tr>
                                        <td><strong>Liczba rekordów:</strong></td>
                                        <td>' . count($data) . '</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Całkowita suma:</strong></td>
                                        <td>' . number_format($stats['total'], 2, ',', ' ') . ' zł</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Średnia:</strong></td>
                                        <td>' . number_format($stats['average'], 2, ',', ' ') . ' zł</td>
                                    </tr>
                                    <tr>
                                        <td><strong>Źródło danych:</strong></td>
                                        <td>' . htmlspecialchars($current_source) . '</td>
                                    </tr>
                                  </table>';
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">Błąd: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-check"></i> Korzyści eksportu</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> Backup danych</li>
                            <li><i class="fas fa-check text-success"></i> Import do innego systemu</li>
                            <li><i class="fas fa-check text-success"></i> Analiza w Excel</li>
                            <li><i class="fas fa-check text-success"></i> Udostępnianie kolegom</li>
                            <li><i class="fas fa-check text-success"></i> Integracja z aplikacjami</li>
                        </ul>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Uwagi</h5>
                    </div>
                    <div class="card-body">
                        <small>
                            <p><strong>CSV:</strong> Otwarty w Excel, Google Sheets, Numbers</p>
                            <p><strong>JSON:</strong> Łatwy do przetworzenia programistycznie</p>
                            <p>Dane zawierają wszystkie pola wydatków łącznie z datą utworzenia.</p>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function exportData(format) {
            window.location.href = 'export_api.php?format=' + format;
        }
    </script>
</body>
</html>

<?php
/**
 * IMPORT DANYCH
 * Umożliwia wczytanie danych z pliku CSV lub JSON
 */

require_once 'config.php';
require_once 'DataSourceFactory.php';

$message = '';
$message_type = '';
$preview_data = null;
$import_mode = 'merge'; // merge lub replace

// Obsługa przesłanego pliku
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $action = $_POST['action'] ?? 'preview';
    $import_mode = $_POST['mode'] ?? 'merge';

    // Walidacja pliku
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "Błąd przesyłania pliku: " . $file['error'];
        $message_type = 'danger';
    } elseif ($file['size'] > 5 * 1024 * 1024) {
        $message = "Plik jest za duży (max 5 MB)";
        $message_type = 'danger';
    } else {
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);

        try {
            if ($ext === 'json') {
                $content = file_get_contents($file['tmp_name']);
                $data = json_decode($content, true);

                if ($data === null) {
                    throw new Exception("Nieprawidłowy format JSON");
                }

                // Jeśli to tablica obiektów
                if (isset($data[0]) && is_array($data[0])) {
                    $records = $data;
                } else {
                    $records = [$data];
                }
            } elseif ($ext === 'csv') {
                $records = [];
                if (($handle = fopen($file['tmp_name'], 'r')) !== false) {
                    $header = null;
                    
                    while (($row = fgetcsv($handle)) !== false) {
                        if ($header === null) {
                            $header = $row;
                            continue;
                        }

                        if (count($row) === count($header)) {
                            $records[] = array_combine($header, $row);
                        }
                    }
                    fclose($handle);
                }
            } else {
                throw new Exception("Nieobsługiwany format pliku. Wspierane: JSON, CSV");
            }

            // Walidacja danych
            $ds = DataSourceFactory::getInstance();
            $valid_count = 0;
            $invalid_count = 0;
            $valid_records = [];

            foreach ($records as $record) {
                $validation = $ds->validate($record);
                if ($validation['valid']) {
                    $valid_count++;
                    $valid_records[] = $record;
                } else {
                    $invalid_count++;
                }
            }

            if ($action === 'preview') {
                $preview_data = [
                    'records' => array_slice($valid_records, 0, 10), // Pierwsze 10
                    'valid_count' => $valid_count,
                    'invalid_count' => $invalid_count,
                    'total' => count($records)
                ];
                
                $message = "Znaleziono $valid_count poprawnych i $invalid_count niepoprawnych rekordów";
                $message_type = 'info';
            } elseif ($action === 'confirm') {
                // Zaimportuj dane
                $result = $ds->import($valid_records, $import_mode);

                if ($result['success']) {
                    $message = "Pomyślnie zaimportowano " . $result['count'] . " rekordów";
                    $message_type = 'success';
                } else {
                    $message = "Błąd importu: " . $result['message'];
                    $message_type = 'danger';
                }
            }
        } catch (Exception $e) {
            $message = "Błąd: " . $e->getMessage();
            $message_type = 'danger';
        }
    }
}

$user_settings = get_user_settings();
$current_source = $user_settings['data_source'] ?? 'mysql';
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import danych - Monitor Wydatków</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; padding: 20px 0; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 0; margin-bottom: 30px; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .drop-zone { border: 2px dashed #667eea; border-radius: 10px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .drop-zone.dragover { background-color: #f0f0ff; border-color: #764ba2; }
    </style>
</head>
<body>
    <div class="header">
        <div class="container d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-upload"></i> Import danych</h1>
            <a href="index.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Powrót
            </a>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-8">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                        <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Formularz importu -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-file-import"></i> Wybierz plik do importu</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data" id="importForm">
                            <!-- Drop zone -->
                            <div class="drop-zone" id="dropZone">
                                <i class="fas fa-cloud-upload-alt fa-3x mb-3 text-primary"></i>
                                <p><strong>Przeciągnij plik tutaj</strong> lub kliknij aby wybrać</p>
                                <small class="text-muted">Obsługiwane formaty: JSON, CSV (max 5 MB)</small>
                                <input type="file" id="fileInput" name="file" accept=".json,.csv" style="display: none;">
                            </div>

                            <div id="fileName" class="mt-3" style="display: none;">
                                <div class="alert alert-info">
                                    Wybrany plik: <strong id="selectedName"></strong>
                                </div>
                            </div>

                            <!-- Opcje importu -->
                            <div class="mt-4">
                                <h6>Opcje importu:</h6>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="mode" id="merge" value="merge" checked>
                                    <label class="form-check-label" for="merge">
                                        <strong>Scalić z istniejącymi danymi</strong>
                                        <br><small class="text-muted">Nowe rekordy będą dodane do istniejących</small>
                                    </label>
                                </div>
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="mode" id="replace" value="replace">
                                    <label class="form-check-label" for="replace">
                                        <strong>Nadpisać istniejące dane</strong>
                                        <br><small class="text-muted">Wszystkie istniejące rekordy zostaną usunięte</small>
                                    </label>
                                </div>
                            </div>

                            <!-- Przyciski akcji -->
                            <div class="mt-4">
                                <button type="submit" name="action" value="preview" class="btn btn-info" id="previewBtn" style="display: none;">
                                    <i class="fas fa-eye"></i> Podgląd
                                </button>
                                <button type="reset" class="btn btn-secondary">
                                    <i class="fas fa-redo"></i> Wyczyść
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Podgląd danych -->
                <?php if ($preview_data): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-warning">
                            <h5 class="mb-0"><i class="fas fa-eye"></i> Podgląd danych (pierwszych <?php echo min(10, $preview_data['total']); ?> z <?php echo $preview_data['total']; ?>)</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <div class="alert alert-success mb-0">
                                        <strong>Poprawne:</strong> <?php echo $preview_data['valid_count']; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="alert alert-danger mb-0">
                                        <strong>Błędne:</strong> <?php echo $preview_data['invalid_count']; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="alert alert-info mb-0">
                                        <strong>Razem:</strong> <?php echo $preview_data['total']; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($preview_data['records'])): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Nazwa</th>
                                                <th>Kategoria</th>
                                                <th class="text-end">Kwota</th>
                                                <th>Data</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($preview_data['records'] as $record): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($record['nazwa'] ?? ''); ?></td>
                                                    <td><?php echo htmlspecialchars($record['kategoria'] ?? ''); ?></td>
                                                    <td class="text-end"><?php echo number_format($record['kwota'] ?? 0, 2, ',', ' '); ?> zł</td>
                                                    <td><?php echo htmlspecialchars($record['data_wydatku'] ?? ''); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Potwierdzenie importu -->
                                <hr>
                                <form method="POST" enctype="multipart/form-data">
                                    <input type="hidden" name="file" value="<?php echo htmlspecialchars($_FILES['file']['name'] ?? ''); ?>">
                                    <input type="hidden" name="action" value="confirm">
                                    <input type="hidden" name="mode" value="<?php echo htmlspecialchars($import_mode); ?>">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Potwierdź import (<?php echo $preview_data['valid_count']; ?> rekordów)
                                    </button>
                                    <button type="button" class="btn btn-secondary" onclick="location.reload()">
                                        <i class="fas fa-times"></i> Anuluj
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Boczny panel -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Obsługiwane formaty</h5>
                    </div>
                    <div class="card-body">
                        <h6>JSON</h6>
                        <pre><code>[{
  "nazwa": "Zakupy",
  "kwota": 100.50,
  "kategoria": "Jedzenie",
  "data_wydatku": "2026-01-29"
}]</code></pre>

                        <h6>CSV</h6>
                        <pre><code>nazwa,kwota,kategoria,data_wydatku
Zakupy,100.50,Jedzenie,2026-01-29</code></pre>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Wymagane pola</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> <code>nazwa</code></li>
                            <li><i class="fas fa-check text-success"></i> <code>kwota</code></li>
                            <li><i class="fas fa-check text-success"></i> <code>kategoria</code></li>
                            <li><i class="fas fa-check text-success"></i> <code>data_wydatku</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        const fileName = document.getElementById('fileName');
        const selectedName = document.getElementById('selectedName');
        const previewBtn = document.getElementById('previewBtn');

        // Klik na drop zone
        dropZone.addEventListener('click', () => fileInput.click());

        // Drag and drop
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('dragover');
        });

        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('dragover');
        });

        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            handleFileSelect();
        });

        // Zmiana pliku
        fileInput.addEventListener('change', handleFileSelect);

        function handleFileSelect() {
            if (fileInput.files.length > 0) {
                const file = fileInput.files[0];
                selectedName.textContent = file.name;
                fileName.style.display = 'block';
                previewBtn.style.display = 'inline-block';
            }
        }
    </script>
</body>
</html>

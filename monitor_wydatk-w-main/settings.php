<?php

require_once 'auth.php';
require_once 'config.php';
require_once 'DataSourceFactory.php';

Auth::requireLogin();

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'change_source' && isset($_POST['data_source'])) {
            try {
                $new_source = $_POST['data_source'];
                if (is_valid_data_source($new_source)) {
                    $settings = get_user_settings();
                    $settings['data_source'] = $new_source;
                    save_user_settings($settings);
                    
                    $message = "Źródło danych zmienione na: " . $new_source;
                    $message_type = 'success';
                } else {
                    $message = "Nieznane źródło danych";
                    $message_type = 'danger';
                }
            } catch (Exception $e) {
                $message = "Błąd: " . $e->getMessage();
                $message_type = 'danger';
            }
        } elseif ($_POST['action'] === 'test_sources') {

        }
    }
}

$available_sources = get_available_sources();
$test_results = null;

if (isset($_POST['action']) && $_POST['action'] === 'test_sources') {
    try {
        $test_results = DataSourceFactory::testAll();
    } catch (Exception $e) {
        $message = "Błąd podczas testowania: " . $e->getMessage();
        $message_type = 'danger';
    }
}

$user_settings = get_user_settings();
$current_source = $user_settings['data_source'] ?? 'mysql';

try {
    $ds = DataSourceFactory::create($current_source);
    $all_data = $ds->getAll();
    $record_count = count($all_data);
    $current_info = [
        'source' => $current_source,
        'type' => ucfirst($current_source),
        'status' => 'OK',
        'records' => $record_count,
        'connection_time' => date('Y-m-d H:i:s')
    ];
} catch (Exception $e) {
    $current_info = [
        'error' => $e->getMessage(),
        'source' => $current_source,
        'type' => ucfirst($current_source),
        'status' => 'ERROR: ' . $e->getMessage(),
        'records' => 0
    ];
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ustawienia - Monitor Wydatków</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; padding: 20px 0; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 0; margin-bottom: 30px; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .source-card { margin-bottom: 20px; cursor: pointer; transition: all 0.3s; }
        .source-card:hover { transform: translateY(-5px); box-shadow: 0 8px 12px rgba(0,0,0,0.15); }
        .source-card.active { border: 3px solid #667eea; }
        .status-badge { padding: 10px 15px; border-radius: 5px; font-weight: 500; }
        .status-ok { background-color: #d4edda; color: #155724; }
        .status-error { background-color: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="header">
        <div class="container d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-cog"></i> Ustawienia</h1>
            <a href="index.php" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Powrót
            </a>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($message)): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Bieżące źródło danych -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-database"></i> Bieżące źródło danych</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <p><strong>Aktywne źródło:</strong></p>
                                <h3><?php echo $available_sources[$current_source]['name']; ?></h3>
                                <p class="text-muted"><?php echo $available_sources[$current_source]['description']; ?></p>
                            </div>
                            <div class="col-md-8">
                                <h6>Informacje:</h6>
                                <table class="table table-sm">
                                    <?php if (isset($current_info['error'])): ?>
                                        <tr>
                                            <td colspan="2" class="text-danger">
                                                <strong>Błąd:</strong> <?php echo htmlspecialchars($current_info['error']); ?>
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <tr>
                                            <td><strong>Typ:</strong></td>
                                            <td><?php echo $current_info['type'] ?? 'N/A'; ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <span class="status-badge <?php echo strpos($current_info['status'], 'OK') !== false ? 'status-ok' : 'status-error'; ?>">
                                                    <?php echo $current_info['status']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Liczba rekordów:</strong></td>
                                            <td><?php echo $current_info['records'] ?? '0'; ?></td>
                                        </tr>
                                        <?php if (isset($current_info['file'])): ?>
                                            <tr>
                                                <td><strong>Plik:</strong></td>
                                                <td><small><?php echo htmlspecialchars($current_info['file']); ?></small></td>
                                            </tr>
                                            <tr>
                                                <td><strong>Rozmiar pliku:</strong></td>
                                                <td><?php echo isset($current_info['file_size']) ? number_format($current_info['file_size'], 0, ',', ' ') . ' B' : 'N/A'; ?></td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <td><strong>Ostatnia zmiana:</strong></td>
                                            <td><?php echo $current_info['last_modified'] ?? $current_info['connection_time'] ?? 'N/A'; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wybór źródła danych -->
        <div class="row mb-4">
            <div class="col-md-12">
                <h3 class="mb-3"><i class="fas fa-list"></i> Dostępne źródła danych</h3>
            </div>
        </div>

        <div class="row mb-4">
            <?php foreach ($available_sources as $key => $source): ?>
                <div class="col-md-4 mb-3">
                    <div class="card source-card <?php echo $key === $current_source ? 'active' : ''; ?>">
                        <div class="card-body">
                            <h5 class="card-title">
                                <i class="fas fa-<?php echo $key === 'mysql' ? 'database' : ($key === 'json' ? 'file-code' : 'file-csv'); ?>"></i>
                                <?php echo htmlspecialchars($source['name']); ?>
                            </h5>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($source['description']); ?></p>
                            
                            <div class="mb-3">
                                <?php if ($source['available']): ?>
                                    <span class="badge bg-success"><i class="fas fa-check"></i> Dostępne</span>
                                <?php else: ?>
                                    <span class="badge bg-danger"><i class="fas fa-times"></i> Niedostępne</span>
                                <?php endif; ?>
                            </div>

                            <form method="POST" action="settings.php" style="display: inline;">
                                <input type="hidden" name="action" value="change_source">
                                <input type="hidden" name="data_source" value="<?php echo htmlspecialchars($key); ?>">
                                <button type="submit" class="btn btn-sm <?php echo $key === $current_source ? 'btn-primary' : 'btn-outline-primary'; ?>" 
                                        <?php echo !$source['available'] ? 'disabled' : ''; ?>>
                                    <i class="fas fa-<?php echo $key === $current_source ? 'check-circle' : 'arrow-right'; ?>"></i>
                                    <?php echo $key === $current_source ? 'Aktywne' : 'Użyj'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Test źródeł danych -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-flask"></i> Test źródeł danych</h5>
                    </div>
                    <div class="card-body">
                        <p>Kliknij przycisk poniżej aby przetestować dostępność wszystkich źródeł danych:</p>
                        
                        <form method="POST" action="settings.php">
                            <input type="hidden" name="action" value="test_sources">
                            <button type="submit" class="btn btn-info">
                                <i class="fas fa-play"></i> Testuj wszystkie źródła
                            </button>
                        </form>

                        <?php if ($test_results): ?>
                            <hr>
                            <h6>Wyniki testów:</h6>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Źródło</th>
                                            <th>Status</th>
                                            <th>Połączenie</th>
                                            <th>Informacje</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($test_results as $source => $result): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars(ucfirst($source)); ?></strong></td>
                                                <td>
                                                    <?php if ($result['available']): ?>
                                                        <span class="badge bg-success"><i class="fas fa-check"></i> Dostępne</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger"><i class="fas fa-times"></i> Niedostępne</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($result['test'])): ?>
                                                        <span class="badge <?php echo $result['test'] ? 'bg-success' : 'bg-warning'; ?>">
                                                            <?php echo $result['test'] ? 'OK' : 'BŁĄD'; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        N/A
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($result['info'])): ?>
                                                        <?php echo htmlspecialchars($result['info']['status'] ?? 'N/A'); ?>
                                                        (<?php echo htmlspecialchars($result['info']['records'] ?? '0'); ?> rekordów)
                                                    <?php elseif (isset($result['error'])): ?>
                                                        <small class="text-danger"><?php echo htmlspecialchars($result['error']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

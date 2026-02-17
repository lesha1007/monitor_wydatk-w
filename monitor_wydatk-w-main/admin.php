<?php

require_once 'auth.php';
require_once 'config.php';
require_once 'DataSourceFactory.php';

Auth::requireLogin();
Auth::requireAdmin();

$akcja = $_GET['akcja'] ?? '';
$wiadomosc = '';
$typ_wiadomosci = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($akcja === 'usun' && isset($_POST['id'])) {
        try {
            $id = (int)$_POST['id'];
            $user_settings = get_user_settings();
            $current_source = $user_settings['data_source'] ?? 'mysql';
            $ds = DataSourceFactory::create($current_source);
            $ds->delete($id);
            $wiadomosc = 'Wydatek został usunięty pomyślnie!';
            $typ_wiadomosci = 'success';
        } catch (Exception $e) {
            $wiadomosc = 'Błąd: ' . $e->getMessage();
            $typ_wiadomosci = 'danger';
        }
    } elseif ($akcja === 'czyszczenie' && isset($_POST['potwierdz'])) {
        try {
            $user_settings = get_user_settings();
            $current_source = $user_settings['data_source'] ?? 'mysql';
            $ds = DataSourceFactory::create($current_source);
            $wszystkie_wydatki = $ds->getAll();
            foreach ($wszystkie_wydatki as $wydatek) {
                $ds->delete($wydatek['id']);
            }
            $wiadomosc = 'Wszystkie wydatki zostały usunięte!';
            $typ_wiadomosci = 'warning';
        } catch (Exception $e) {
            $wiadomosc = 'Błąd: ' . $e->getMessage();
            $typ_wiadomosci = 'danger';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Admin - Monitor Wydatków</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; padding: 20px 0; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 0; margin-bottom: 30px; }
        .card { box-shadow: 0 4px 6px rgba(0,0,0,0.1); margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-cogs"></i> Panel Administracyjny</h1>
            <p class="mb-0"><a href="index.php" class="text-white"><i class="fas fa-home"></i> Powrót</a></p>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($wiadomosc)): ?>
            <div class="alert alert-<?php echo $typ_wiadomosci; ?> alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($wiadomosc); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Statystyki</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $user_settings = get_user_settings();
                            $current_source = $user_settings['data_source'] ?? 'mysql';
                            $ds = DataSourceFactory::create($current_source);
                            $wszystkie_wydatki = $ds->getAll();
                            
                            $ilosc = count($wszystkie_wydatki);
                            $suma = array_sum(array_map(fn($w) => $w['kwota'], $wszystkie_wydatki));
                            $srednia = $ilosc > 0 ? $suma / $ilosc : 0;
                            $minimum = $ilosc > 0 ? min(array_map(fn($w) => $w['kwota'], $wszystkie_wydatki)) : 0;
                            $maksimum = $ilosc > 0 ? max(array_map(fn($w) => $w['kwota'], $wszystkie_wydatki)) : 0;
                            
                            echo '<div class="list-group list-group-flush">
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span><strong>Ilość wydatków:</strong></span>
                                        <span class="badge bg-primary">' . $ilosc . '</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span><strong>Suma wydatków:</strong></span>
                                        <span class="badge bg-success">' . number_format($suma, 2, ',', ' ') . ' zł</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span><strong>Średnia:</strong></span>
                                        <span class="badge bg-info">' . number_format($srednia, 2, ',', ' ') . ' zł</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span><strong>Minimum:</strong></span>
                                        <span class="badge bg-warning">' . number_format($minimum, 2, ',', ' ') . ' zł</span>
                                    </div>
                                    <div class="list-group-item d-flex justify-content-between">
                                        <span><strong>Maksimum:</strong></span>
                                        <span class="badge bg-danger">' . number_format($maksimum, 2, ',', ' ') . ' zł</span>
                                    </div>
                                  </div>';
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-pie-chart"></i> Rozkład po kategoriach</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $user_settings = get_user_settings();
                            $current_source = $user_settings['data_source'] ?? 'mysql';
                            $ds = DataSourceFactory::create($current_source);
                            $wszystkie_wydatki = $ds->getAll();
                            
                            $kategorie_stats = [];
                            foreach ($wszystkie_wydatki as $w) {
                                $kat = $w['kategoria'];
                                if (!isset($kategorie_stats[$kat])) {
                                    $kategorie_stats[$kat] = ['counts' => 0, 'suma' => 0];
                                }
                                $kategorie_stats[$kat]['counts']++;
                                $kategorie_stats[$kat]['suma'] += floatval($w['kwota']);
                            }
                            
                            usort($kategorie_stats, fn($a, $b) => $b['suma'] <=> $a['suma']);
                            
                            if (!empty($kategorie_stats)) {
                                echo '<div class="list-group list-group-flush">';
                                foreach ($kategorie_stats as $kat_name => $kat_data) {
                                    echo '<div class="list-group-item d-flex justify-content-between">
                                            <span>
                                                <strong>' . htmlspecialchars($kat_name) . '</strong>
                                                <br><small class="text-muted">' . $kat_data['counts'] . ' wydatek(i)</small>
                                            </span>
                                            <span class="badge bg-secondary">' . number_format($kat_data['suma'], 2, ',', ' ') . ' zł</span>
                                          </div>';
                                }
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-info">Brak danych</div>';
                            }
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-list"></i> Wydatki</h5>
            </div>
            <div class="card-body">
                <?php
                try {
                    $user_settings = get_user_settings();
                    $current_source = $user_settings['data_source'] ?? 'mysql';
                    $ds = DataSourceFactory::create($current_source);
                    $wydatki = $ds->getAll();
                    
                    if (!empty($wydatki)) {
                        echo '<div class="table-responsive">
                              <table class="table table-hover table-striped">
                                <thead class="table-light">
                                  <tr>
                                    <th>ID</th>
                                    <th>Nazwa</th>
                                    <th>Kategoria</th>
                                    <th class="text-end">Kwota</th>
                                    <th>Data</th>
                                    <th>Akcja</th>
                                  </tr>
                                </thead>
                                <tbody>';
                        
                        foreach ($wydatki as $w) {
                            echo '<tr>
                                    <td>' . $w['id'] . '</td>
                                    <td>' . htmlspecialchars($w['nazwa']) . '</td>
                                    <td><span class="badge bg-secondary">' . htmlspecialchars($w['kategoria']) . '</span></td>
                                    <td class="text-end">' . number_format($w['kwota'], 2, ',', ' ') . ' zł</td>
                                    <td>' . $w['data_wydatku'] . '</td>
                                    <td>
                                        <form method="POST" action="?akcja=usun" style="display:inline;">
                                            <input type="hidden" name="id" value="' . $w['id'] . '">
                                            <button type="submit" class="btn btn-sm btn-danger" 
                                                    onclick="return confirm(\'Czy na pewno?\')">
                                                <i class="fas fa-trash"></i> Usuń
                                            </button>
                                        </form>
                                    </td>
                                  </tr>';
                        }
                        
                        echo '</tbody></table></div>';
                    } else {
                        echo '<div class="alert alert-info">Brak wydatków</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">' . $e->getMessage() . '</div>';
                }
                ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card border-warning">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0"><i class="fas fa-download"></i> Eksport danych</h5>
                    </div>
                    <div class="card-body">
                        <p>Pobierz dane w formacie JSON (do analizy lub backupu)</p>
                        <a href="#" onclick="eksportJSON()" class="btn btn-warning w-100">
                            <i class="fas fa-file-export"></i> Pobierz JSON
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6 mb-4">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Niebezpieczne operacje</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>UWAGA!</strong> Poniższe akcje nie mogą być cofnięte.</p>
                        <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#modalCzyszczenie">
                            <i class="fas fa-trash-alt"></i> Wyczyść wszystkie wydatki
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalCzyszczenie" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Potwierdzenie</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="?akcja=czyszczenie">
                    <div class="modal-body">
                        <p><strong>Czy na pewno chcesz usunąć WSZYSTKIE wydatki?</strong></p>
                        <p class="text-danger">Ta operacja nie może być cofnięta!</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Anuluj</button>
                        <button type="submit" name="potwierdz" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Usuń wszystko
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function eksportJSON() {
            fetch('dane_wykresu.php')
                .then(response => response.json())
                .then(data => {
                    fetch('api_export.php')
                        .then(r => r.json())
                        .then(wydatki => {
                            const json = JSON.stringify(wydatki, null, 2);
                            const blob = new Blob([json], { type: 'application/json' });
                            const url = window.URL.createObjectURL(blob);
                            const a = document.createElement('a');
                            a.href = url;
                            a.download = 'wydatki_' + new Date().toISOString().split('T')[0] + '.json';
                            document.body.appendChild(a);
                            a.click();
                            window.URL.revokeObjectURL(url);
                        });
                });
        }
    </script>
</body>
</html>

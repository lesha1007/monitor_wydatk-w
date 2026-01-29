<?php
require_once 'config.php';
require_once 'DataSourceFactory.php';

$user_settings = get_user_settings();
$current_source = $user_settings['data_source'] ?? 'mysql';

try {
    $ds = DataSourceFactory::create($current_source);
} catch (Exception $e) {
    die("Błąd: " . htmlspecialchars($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor Wydatków</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        .container-main {
            max-width: 1000px;
            margin: 0 auto;
        }
        .card-form {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .chart-container {
            position: relative;
            height: 400px;
            margin-top: 30px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            padding: 20px;
            background-color: white;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
            border-radius: 0;
        }
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 30px;
            font-weight: 500;
        }
        .btn-submit:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            color: white;
        }
        .form-label {
            font-weight: 500;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <h1><i class="fas fa-wallet"></i> Monitor Wydatków Osobistych</h1>
                <p class="mb-0">Śledź i wizualizuj swoje wydatki • Źródło: <strong><?php echo htmlspecialchars($current_source); ?></strong></p>
            </div>
            <div class="btn-group" role="group">
                <a href="szczegoly.php" class="btn btn-light">
                    <i class="fas fa-list"></i> Szczegóły
                </a>
                <div class="dropdown">
                    <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-tools"></i> Menu
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog"></i> Ustawienia</a></li>
                        <li><a class="dropdown-item" href="admin.php"><i class="fas fa-cogs"></i> Administrator</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="import.php"><i class="fas fa-upload"></i> Import danych</a></li>
                        <li><a class="dropdown-item" href="export.php"><i class="fas fa-download"></i> Eksport danych</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="test_api.php"><i class="fas fa-flask"></i> Test API</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="container-main">
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card card-form">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Dodaj nowy wydatek</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="dodaj.php">
                            <div class="mb-3">
                                <label for="nazwa" class="form-label">Nazwa wydatku</label>
                                <input type="text" class="form-control" id="nazwa" name="nazwa" 
                                       placeholder="np. Zakupy spożywcze" required maxlength="100">
                            </div>

                            <div class="mb-3">
                                <label for="kwota" class="form-label">Kwota (zł)</label>
                                <input type="number" class="form-control" id="kwota" name="kwota" 
                                       placeholder="0.00" step="0.01" min="0.01" required>
                            </div>

                            <div class="mb-3">
                                <label for="kategoria" class="form-label">Kategoria</label>
                                <select class="form-select" id="kategoria" name="kategoria" required>
                                    <option value="">-- Wybierz kategorię --</option>
                                    <option value="Jedzenie">Jedzenie</option>
                                    <option value="Transport">Transport</option>
                                    <option value="Rozrywka">Rozrywka</option>
                                    <option value="Rachunki">Rachunki</option>
                                    <option value="Inne">Inne</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="data_wydatku" class="form-label">Data wydatku</label>
                                <input type="date" class="form-control" id="data_wydatku" name="data_wydatku" required>
                            </div>

                            <button type="submit" class="btn btn-submit text-white w-100">
                                <i class="fas fa-save"></i> Zapisz wydatek
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="chart-container">
                    <h5 class="mb-3">Rozkład wydatków po kategoriach</h5>
                    <canvas id="chartWydatki"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        async function zaladujWykres() {
            try {
                const response = await fetch('dane_wykresu.php');
                
                if (!response.ok) {
                    throw new Error('Błąd podczas pobierania danych');
                }
                
                const data = await response.json();
                
                if (data.length === 0) {
                    console.log('Brak danych do wyświetlenia');
                    return;
                }

                const kategorie = data.map(item => item.kategoria);
                const sumy = data.map(item => parseFloat(item.suma));

                const kolory = [
                    '#FF6384', // Jedzenie
                    '#36A2EB', // Transport
                    '#FFCE56', // Rozrywka
                    '#4BC0C0', // Rachunki
                    '#9966FF'  // Inne
                ];

                const ctx = document.getElementById('chartWydatki').getContext('2d');
                new Chart(ctx, {
                    type: 'pie',
                    data: {
                        labels: kategorie,
                        datasets: [{
                            data: sumy,
                            backgroundColor: kolory.slice(0, kategorie.length),
                            borderColor: '#fff',
                            borderWidth: 2
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 15,
                                    font: {
                                        size: 12
                                    }
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                        const percent = ((value / total) * 100).toFixed(1);
                                        return `${label}: ${value.toFixed(2)} zł (${percent}%)`;
                                    }
                                }
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Błąd:', error);
            }
        }

        document.addEventListener('DOMContentLoaded', zaladujWykres);

        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('data_wydatku').value = today;
        });
    </script>
</body>
</html>

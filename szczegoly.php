<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Szczegóły wydatków - Monitor Wydatków</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        .badge-kategoria {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        .table-hover tbody tr:hover {
            background-color: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="container">
            <h1><i class="fas fa-list"></i> Szczegóły wydatków</h1>
            <p class="mb-0"><a href="index.php" class="text-white"><i class="fas fa-home"></i> Powrót do domu</a></p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-table"></i> Lista wszystkich wydatków</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        require_once 'db.php';

                        try {
                            $sql = "SELECT id, nazwa, kwota, kategoria, data_wydatku, created_at 
                                    FROM wydatki 
                                    ORDER BY data_wydatku DESC, created_at DESC";
                            $stmt = $pdo->query($sql);
                            $wydatki = $stmt->fetchAll();

                            if (empty($wydatki)) {
                                echo '<div class="alert alert-info" role="alert">
                                        <i class="fas fa-info-circle"></i> Brak zapisanych wydatków. 
                                        <a href="index.php">Dodaj pierwszy wydatek</a>
                                      </div>';
                            } else {
                                $kolory_kategorii = [
                                    'Jedzenie' => 'danger',
                                    'Transport' => 'info',
                                    'Rozrywka' => 'warning',
                                    'Rachunki' => 'success',
                                    'Inne' => 'secondary'
                                ];

                                echo '<div class="table-responsive">
                                      <table class="table table-hover table-striped">
                                        <thead class="table-light">
                                          <tr>
                                            <th>Lp.</th>
                                            <th>Nazwa</th>
                                            <th>Kategoria</th>
                                            <th class="text-end">Kwota (zł)</th>
                                            <th>Data</th>
                                            <th>Dodano</th>
                                          </tr>
                                        </thead>
                                        <tbody>';

                                $suma = 0;
                                foreach ($wydatki as $index => $wydatek) {
                                    $kolor = $kolory_kategorii[$wydatek['kategoria']] ?? 'secondary';
                                    $suma += $wydatek['kwota'];
                                    
                                    echo '<tr>
                                            <td>' . ($index + 1) . '</td>
                                            <td>' . htmlspecialchars($wydatek['nazwa']) . '</td>
                                            <td>
                                              <span class="badge bg-' . $kolor . '">
                                                ' . htmlspecialchars($wydatek['kategoria']) . '
                                              </span>
                                            </td>
                                            <td class="text-end font-weight-bold">' . number_format($wydatek['kwota'], 2, ',', ' ') . '</td>
                                            <td>' . date('d.m.Y', strtotime($wydatek['data_wydatku'])) . '</td>
                                            <td>' . date('d.m.Y H:i', strtotime($wydatek['created_at'])) . '</td>
                                          </tr>';
                                }

                                echo '  </tbody>
                                      <tfoot class="table-light fw-bold">
                                        <tr>
                                          <td colspan="3">RAZEM WYDATKÓW:</td>
                                          <td class="text-end">' . number_format($suma, 2, ',', ' ') . ' zł</td>
                                          <td colspan="2"></td>
                                        </tr>
                                      </tfoot>
                                    </table>
                                </div>';

                                // Statystyki
                                echo '<hr>';
                                echo '<div class="row mt-4">
                                        <div class="col-md-6">
                                          <h6>Statystyki kategorii:</h6>
                                          <ul class="list-unstyled">';

                                $stat_sql = "SELECT kategoria, COUNT(*) as liczba, SUM(kwota) as suma 
                                            FROM wydatki 
                                            GROUP BY kategoria 
                                            ORDER BY suma DESC";
                                $stat_stmt = $pdo->query($stat_sql);
                                $statystyki = $stat_stmt->fetchAll();

                                foreach ($statystyki as $stat) {
                                    echo '<li>
                                            <strong>' . htmlspecialchars($stat['kategoria']) . ':</strong> 
                                            ' . $stat['liczba'] . ' wydatek(i), 
                                            razem: ' . number_format($stat['suma'], 2, ',', ' ') . ' zł
                                          </li>';
                                }

                                echo '    </ul>
                                        </div>
                                      </div>';
                            }

                        } catch (PDOException $e) {
                            echo '<div class="alert alert-danger" role="alert">
                                    <strong>Błąd:</strong> ' . htmlspecialchars($e->getMessage()) . '
                                  </div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

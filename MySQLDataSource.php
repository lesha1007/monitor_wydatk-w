<?php
/**
 * MYSQL DATA SOURCE
 * 
 * Implementacja interfejsu DataSourceInterface dla MySQL
 * Służy jako główne źródło danych w trybie produkcyjnym
 */

require_once 'DataSourceInterface.php';

class MySQLDataSource implements DataSourceInterface
{
    private $pdo;
    private $db_config;

    public function __construct($db_config)
    {
        $this->db_config = $db_config;
        $this->connect();
    }

    /**
     * Nawiąż połączenie z bazą danych
     */
    private function connect()
    {
        try {
            $dsn = "mysql:host={$this->db_config['host']};dbname={$this->db_config['db']};charset={$this->db_config['charset']}";
            
            $this->pdo = new PDO(
                $dsn,
                $this->db_config['user'],
                $this->db_config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("Błąd połączenia z MySQL: " . $e->getMessage());
        }
    }

    public function getAll()
    {
        try {
            $sql = "SELECT * FROM wydatki ORDER BY data_wydatku DESC, created_at DESC";
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Błąd podczas pobierania danych: " . $e->getMessage());
        }
    }

    public function getById($id)
    {
        try {
            $sql = "SELECT * FROM wydatki WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => (int)$id]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new Exception("Błąd podczas pobierania wydatku: " . $e->getMessage());
        }
    }

    public function add($record)
    {
        try {
            $this->validate($record);

            $sql = "INSERT INTO wydatki (nazwa, kwota, kategoria, data_wydatku) 
                    VALUES (:nazwa, :kwota, :kategoria, :data_wydatku)";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nazwa' => $record['nazwa'],
                ':kwota' => (float)$record['kwota'],
                ':kategoria' => $record['kategoria'],
                ':data_wydatku' => $record['data_wydatku']
            ]);

            return (int)$this->pdo->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Błąd podczas dodawania wydatku: " . $e->getMessage());
        }
    }

    public function update($id, $record)
    {
        try {
            $validation = $this->validate($record);
            if (!$validation['valid']) {
                throw new Exception("Dane nie przejdły walidacji: " . implode(", ", $validation['errors']));
            }

            $sql = "UPDATE wydatki SET nazwa = :nazwa, kwota = :kwota, kategoria = :kategoria, data_wydatku = :data_wydatku 
                    WHERE id = :id";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => (int)$id,
                ':nazwa' => $record['nazwa'],
                ':kwota' => (float)$record['kwota'],
                ':kategoria' => $record['kategoria'],
                ':data_wydatku' => $record['data_wydatku']
            ]);

            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception("Błąd podczas aktualizacji wydatku: " . $e->getMessage());
        }
    }

    public function delete($id)
    {
        try {
            $sql = "DELETE FROM wydatki WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => (int)$id]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            throw new Exception("Błąd podczas usuwania wydatku: " . $e->getMessage());
        }
    }

    public function getChartData()
    {
        try {
            $sql = "SELECT kategoria, SUM(kwota) AS suma 
                    FROM wydatki 
                    GROUP BY kategoria 
                    ORDER BY suma DESC";
            
            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Błąd podczas pobierania danych wykresu: " . $e->getMessage());
        }
    }

    public function getStats()
    {
        try {
            $sql = "SELECT 
                        COUNT(*) as count,
                        SUM(kwota) as total,
                        AVG(kwota) as average,
                        MIN(kwota) as min,
                        MAX(kwota) as max
                    FROM wydatki";
            
            $stmt = $this->pdo->query($sql);
            $row = $stmt->fetch();
            
            return [
                'count' => (int)($row['count'] ?? 0),
                'total' => (float)($row['total'] ?? 0),
                'average' => (float)($row['average'] ?? 0),
                'min' => (float)($row['min'] ?? 0),
                'max' => (float)($row['max'] ?? 0)
            ];
        } catch (PDOException $e) {
            throw new Exception("Błąd podczas pobierania statystyk: " . $e->getMessage());
        }
    }

    public function getFiltered($filters = [])
    {
        try {
            $sql = "SELECT * FROM wydatki WHERE 1=1";
            $params = [];

            if (!empty($filters['kategoria'])) {
                $sql .= " AND kategoria = :kategoria";
                $params[':kategoria'] = $filters['kategoria'];
            }

            if (!empty($filters['from_date'])) {
                $sql .= " AND data_wydatku >= :from_date";
                $params[':from_date'] = $filters['from_date'];
            }

            if (!empty($filters['to_date'])) {
                $sql .= " AND data_wydatku <= :to_date";
                $params[':to_date'] = $filters['to_date'];
            }

            if (isset($filters['min_amount']) && $filters['min_amount'] !== '') {
                $sql .= " AND kwota >= :min_amount";
                $params[':min_amount'] = (float)$filters['min_amount'];
            }

            if (isset($filters['max_amount']) && $filters['max_amount'] !== '') {
                $sql .= " AND kwota <= :max_amount";
                $params[':max_amount'] = (float)$filters['max_amount'];
            }

            $sql .= " ORDER BY data_wydatku DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new Exception("Błąd podczas filtrowania danych: " . $e->getMessage());
        }
    }

    public function clear()
    {
        try {
            $sql = "DELETE FROM wydatki";
            $this->pdo->exec($sql);
            return true;
        } catch (PDOException $e) {
            throw new Exception("Błąd podczas czyszczenia danych: " . $e->getMessage());
        }
    }

    public function import($data, $mode = 'merge')
    {
        try {
            if ($mode === 'replace') {
                $this->clear();
            }

            $count = 0;
            foreach ($data as $record) {
                try {
                    $this->add($record);
                    $count++;
                } catch (Exception $e) {
                    // Pominięcie błędnych rekordów
                    continue;
                }
            }

            return [
                'success' => true,
                'message' => "Zaimportowano $count rekordów",
                'count' => $count
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => "Błąd podczas importu: " . $e->getMessage(),
                'count' => 0
            ];
        }
    }

    public function export()
    {
        return $this->getAll();
    }

    public function getInfo()
    {
        try {
            $stats = $this->getStats();
            return [
                'type' => 'MySQL',
                'status' => 'OK',
                'host' => $this->db_config['host'],
                'database' => $this->db_config['db'],
                'records' => $stats['count'],
                'connection_time' => date('Y-m-d H:i:s')
            ];
        } catch (Exception $e) {
            return [
                'type' => 'MySQL',
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
        }
    }

    public function test()
    {
        try {
            $this->pdo->query("SELECT 1");
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function validate($record)
    {
        $errors = [];

        // Validacja nazwy
        if (empty($record['nazwa']) || strlen($record['nazwa']) > 100) {
            $errors[] = "Nazwa musi mieć od 1 do 100 znaków";
        }

        // Validacja kwoty
        if (empty($record['kwota']) || !is_numeric($record['kwota']) || $record['kwota'] <= 0) {
            $errors[] = "Kwota musi być liczbą dodatnią";
        }

        // Validacja kategorii
        $valid_categories = ['Jedzenie', 'Transport', 'Rozrywka', 'Rachunki', 'Inne'];
        if (empty($record['kategoria']) || !in_array($record['kategoria'], $valid_categories)) {
            $errors[] = "Kategoria musi być jedną z: " . implode(", ", $valid_categories);
        }

        // Validacja daty
        if (empty($record['data_wydatku']) || strtotime($record['data_wydatku']) === false) {
            $errors[] = "Data musi być w formacie YYYY-MM-DD";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>

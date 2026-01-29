<?php
/**
 * JSON DATA SOURCE
 * 
 * Implementacja interfejsu DataSourceInterface dla pliku JSON
 * Przydatna do demonstracji i testowania bez potrzeby bazy danych
 */

require_once 'DataSourceInterface.php';

class JsonDataSource implements DataSourceInterface
{
    private $file_path;
    private $data = [];

    public function __construct($file_path = null)
    {
        $this->file_path = $file_path ?: __DIR__ . '/data/data.json';
        $this->load();
    }

    /**
     * Załaduj dane z pliku JSON
     */
    private function load()
    {
        if (file_exists($this->file_path)) {
            $content = file_get_contents($this->file_path);
            $this->data = json_decode($content, true) ?: [];
        } else {
            $this->data = [];
            $this->save();
        }
    }

    /**
     * Zapisz dane do pliku JSON
     */
    private function save()
    {
        $dir = dirname($this->file_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        file_put_contents(
            $this->file_path,
            json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    /**
     * Wygeneruj nowe ID
     */
    private function getNextId()
    {
        if (empty($this->data)) {
            return 1;
        }
        
        $max_id = max(array_column($this->data, 'id', null));
        return ($max_id ?? 0) + 1;
    }

    public function getAll()
    {
        $sorted = $this->data;
        usort($sorted, function ($a, $b) {
            $date_cmp = strtotime($b['data_wydatku']) - strtotime($a['data_wydatku']);
            if ($date_cmp === 0) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            }
            return $date_cmp;
        });
        return $sorted;
    }

    public function getById($id)
    {
        foreach ($this->data as $record) {
            if ($record['id'] == $id) {
                return $record;
            }
        }
        return null;
    }

    public function add($record)
    {
        try {
            $validation = $this->validate($record);
            if (!$validation['valid']) {
                throw new Exception(implode(", ", $validation['errors']));
            }

            $id = $this->getNextId();
            $new_record = array_merge($record, [
                'id' => $id,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->data[] = $new_record;
            $this->save();

            return $id;
        } catch (Exception $e) {
            throw new Exception("Błąd podczas dodawania wydatku: " . $e->getMessage());
        }
    }

    public function update($id, $record)
    {
        try {
            $validation = $this->validate($record);
            if (!$validation['valid']) {
                throw new Exception(implode(", ", $validation['errors']));
            }

            foreach ($this->data as $key => $item) {
                if ($item['id'] == $id) {
                    $this->data[$key] = array_merge($record, [
                        'id' => $id,
                        'created_at' => $item['created_at']
                    ]);
                    $this->save();
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            throw new Exception("Błąd podczas aktualizacji wydatku: " . $e->getMessage());
        }
    }

    public function delete($id)
    {
        foreach ($this->data as $key => $item) {
            if ($item['id'] == $id) {
                unset($this->data[$key]);
                $this->data = array_values($this->data);
                $this->save();
                return true;
            }
        }
        return false;
    }

    public function getChartData()
    {
        $grouped = [];
        
        foreach ($this->data as $record) {
            $cat = $record['kategoria'];
            if (!isset($grouped[$cat])) {
                $grouped[$cat] = 0;
            }
            $grouped[$cat] += (float)$record['kwota'];
        }

        $result = [];
        foreach ($grouped as $kategoria => $suma) {
            $result[] = [
                'kategoria' => $kategoria,
                'suma' => number_format($suma, 2, '.', '')
            ];
        }

        usort($result, function ($a, $b) {
            return (float)$b['suma'] - (float)$a['suma'];
        });

        return $result;
    }

    public function getStats()
    {
        if (empty($this->data)) {
            return [
                'count' => 0,
                'total' => 0,
                'average' => 0,
                'min' => 0,
                'max' => 0
            ];
        }

        $amounts = array_column($this->data, 'kwota');
        $amounts = array_map('floatval', $amounts);

        return [
            'count' => count($this->data),
            'total' => (float)array_sum($amounts),
            'average' => (float)(array_sum($amounts) / count($amounts)),
            'min' => (float)min($amounts),
            'max' => (float)max($amounts)
        ];
    }

    public function getFiltered($filters = [])
    {
        $result = $this->data;

        if (!empty($filters['kategoria'])) {
            $result = array_filter($result, function ($item) use ($filters) {
                return $item['kategoria'] === $filters['kategoria'];
            });
        }

        if (!empty($filters['from_date'])) {
            $result = array_filter($result, function ($item) use ($filters) {
                return $item['data_wydatku'] >= $filters['from_date'];
            });
        }

        if (!empty($filters['to_date'])) {
            $result = array_filter($result, function ($item) use ($filters) {
                return $item['data_wydatku'] <= $filters['to_date'];
            });
        }

        if (isset($filters['min_amount']) && $filters['min_amount'] !== '') {
            $result = array_filter($result, function ($item) use ($filters) {
                return (float)$item['kwota'] >= (float)$filters['min_amount'];
            });
        }

        if (isset($filters['max_amount']) && $filters['max_amount'] !== '') {
            $result = array_filter($result, function ($item) use ($filters) {
                return (float)$item['kwota'] <= (float)$filters['max_amount'];
            });
        }

        usort($result, function ($a, $b) {
            return strtotime($b['data_wydatku']) - strtotime($a['data_wydatku']);
        });

        return array_values($result);
    }

    public function clear()
    {
        $this->data = [];
        $this->save();
        return true;
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
        $stats = $this->getStats();
        return [
            'type' => 'JSON',
            'status' => 'OK',
            'file' => $this->file_path,
            'file_exists' => file_exists($this->file_path),
            'records' => $stats['count'],
            'file_size' => file_exists($this->file_path) ? filesize($this->file_path) : 0,
            'last_modified' => file_exists($this->file_path) ? date('Y-m-d H:i:s', filemtime($this->file_path)) : 'N/A'
        ];
    }

    public function test()
    {
        try {
            return is_writable(dirname($this->file_path));
        } catch (Exception $e) {
            return false;
        }
    }

    public function validate($record)
    {
        $errors = [];

        if (empty($record['nazwa']) || strlen($record['nazwa']) > 100) {
            $errors[] = "Nazwa musi mieć od 1 do 100 znaków";
        }

        if (empty($record['kwota']) || !is_numeric($record['kwota']) || $record['kwota'] <= 0) {
            $errors[] = "Kwota musi być liczbą dodatnią";
        }

        $valid_categories = ['Jedzenie', 'Transport', 'Rozrywka', 'Rachunki', 'Inne'];
        if (empty($record['kategoria']) || !in_array($record['kategoria'], $valid_categories)) {
            $errors[] = "Kategoria musi być jedną z: " . implode(", ", $valid_categories);
        }

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

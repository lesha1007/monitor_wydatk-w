<?php

require_once 'DataSourceInterface.php';

class CsvDataSource implements DataSourceInterface
{
    private $file_path;
    private $delimiter = ',';
    private $encoding = 'UTF-8';
    private $data = [];

    public function __construct($file_path = null, $delimiter = ',', $encoding = 'UTF-8')
    {
        $this->file_path = $file_path ?: __DIR__ . '/data/data.csv';
        $this->delimiter = $delimiter;
        $this->encoding = $encoding;
        $this->load();
    }

    private function load()
    {
        if (!file_exists($this->file_path)) {
            $this->data = [];
            $this->save();
            return;
        }

        $this->data = [];
        if (($handle = fopen($this->file_path, 'r')) !== false) {
            $header = null;
            
            while (($row = fgetcsv($handle, 1000, $this->delimiter)) !== false) {
                if ($header === null) {
                    $header = $row;
                    continue;
                }

                if (count($row) !== count($header)) {
                    continue;
                }

                $record = array_combine($header, $row);
                $record['id'] = (int)$record['id'];
                $record['kwota'] = (float)$record['kwota'];
                $this->data[] = $record;
            }
            fclose($handle);
        }
    }

    private function save()
    {
        $dir = dirname($this->file_path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (($handle = fopen($this->file_path, 'w')) !== false) {
            if (!empty($this->data)) {
                $header = array_keys($this->data[0]);
                fputcsv($handle, $header, $this->delimiter);

                foreach ($this->data as $record) {
                    fputcsv($handle, $record, $this->delimiter);
                }
            } else {
                $header = ['id', 'nazwa', 'kwota', 'kategoria', 'data_wydatku', 'created_at'];
                fputcsv($handle, $header, $this->delimiter);
            }
            fclose($handle);
        }
    }

    private function getNextId()
    {
        if (empty($this->data)) {
            return 1;
        }
        
        $max_id = max(array_column($this->data, 'id'));
        return $max_id + 1;
    }

    public function getAll($user_id = null)
    {
        $result = $this->data;
        
        if ($user_id !== null) {
            $result = array_filter($result, function ($item) use ($user_id) {
                return ($item['user_id'] ?? 1) == $user_id;
            });
        }

        $sorted = array_values($result);
        usort($sorted, function ($a, $b) {
            $date_cmp = strtotime($b['data_wydatku']) - strtotime($a['data_wydatku']);
            if ($date_cmp === 0) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            }
            return $date_cmp;
        });
        return $sorted;
    }

    public function getById($id, $user_id = null)
    {
        foreach ($this->data as $record) {
            if ($record['id'] == $id) {
                if ($user_id === null || ($record['user_id'] ?? 1) == $user_id) {
                    return $record;
                }
            }
        }
        return null;
    }

    public function add($record, $user_id = null)
    {
        try {
            $validation = $this->validate($record);
            if (!$validation['valid']) {
                throw new Exception(implode(", ", $validation['errors']));
            }

            $id = $this->getNextId();
            $new_record = array_merge($record, [
                'id' => $id,
                'user_id' => $user_id ?? 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->data[] = $new_record;
            $this->save();

            return $id;
        } catch (Exception $e) {
            throw new Exception("Błąd podczas dodawania wydatku: " . $e->getMessage());
        }
    }

    public function update($id, $record, $user_id = null)
    {
        try {
            $validation = $this->validate($record);
            if (!$validation['valid']) {
                throw new Exception(implode(", ", $validation['errors']));
            }

            foreach ($this->data as $key => $item) {
                if ($item['id'] == $id) {
                    if ($user_id === null || ($item['user_id'] ?? 1) == $user_id) {
                        $this->data[$key] = array_merge($record, [
                            'id' => $id,
                            'user_id' => $item['user_id'] ?? 1,
                            'created_at' => $item['created_at']
                        ]);
                        $this->save();
                        return true;
                    }
                }
            }

            return false;
        } catch (Exception $e) {
            throw new Exception("Błąd podczas aktualizacji wydatku: " . $e->getMessage());
        }
    }

    public function delete($id, $user_id = null)
    {
        foreach ($this->data as $key => $item) {
            if ($item['id'] == $id) {
                if ($user_id === null || ($item['user_id'] ?? 1) == $user_id) {
                    unset($this->data[$key]);
                    $this->data = array_values($this->data);
                    $this->save();
                    return true;
                }
            }
        }
        return false;
    }

    public function getChartData($user_id = null)
    {
        $data = $this->getAll($user_id);
        $grouped = [];
        
        foreach ($data as $record) {
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

    public function getStats($user_id = null)
    {
        $data = $this->getAll($user_id);
        
        if (empty($data)) {
            return [
                'count' => 0,
                'total' => 0,
                'average' => 0,
                'min' => 0,
                'max' => 0
            ];
        }

        $amounts = array_column($data, 'kwota');
        $amounts = array_map('floatval', $amounts);

        return [
            'count' => count($data),
            'total' => (float)array_sum($amounts),
            'average' => (float)(array_sum($amounts) / count($amounts)),
            'min' => (float)min($amounts),
            'max' => (float)max($amounts)
        ];
    }

    public function getFiltered($filters = [], $user_id = null)
    {
        $result = $this->getAll($user_id);

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

    public function import($data, $mode = 'merge', $user_id = null)
    {
        try {
            if ($mode === 'replace') {
                $this->clear();
            }

            $count = 0;
            foreach ($data as $record) {
                try {
                    $this->add($record, $user_id);
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

    public function export($user_id = null)
    {
        return $this->getAll($user_id);
    }

    public function getInfo()
    {
        $stats = $this->getStats();
        return [
            'type' => 'CSV',
            'status' => 'OK',
            'file' => $this->file_path,
            'file_exists' => file_exists($this->file_path),
            'records' => $stats['count'],
            'file_size' => file_exists($this->file_path) ? filesize($this->file_path) : 0,
            'delimiter' => $this->delimiter,
            'encoding' => $this->encoding,
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

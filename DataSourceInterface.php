<?php

interface DataSourceInterface
{
    /**
     * Pobiera wszystkie wydatki
     * 
     * @return array 
     *               [
     *                   ['id' => 1, 'nazwa' => 'Zakupy', 'kwota' => 100, ...],
     *                   ['id' => 2, 'nazwa' => 'Benzyna', 'kwota' => 50, ...],
     *                   ...
     *               ]
     */
    public function getAll();

    /**
     * Pobiera pojedynczy wydatek po ID
     * 
     * @param int $id Identyfikator wydatku
     * @return array|null Tablica z danymi wydatku lub null
     */
    public function getById($id);

    /**
     * Dodaje nowy wydatek
     * 
     * @param array $record Dane wydatku
     *                       [
     *                           'nazwa' => 'string',
     *                           'kwota' => 'float',
     *                           'kategoria' => 'string',
     *                           'data_wydatku' => 'YYYY-MM-DD'
     *                       ]
     * 
     * @return int ID nowo dodanego wydatku
     * @throws Exception Jeśli zapis się nie powiedzie
     */
    public function add($record);

    /**
     * Aktualizuje istniejący wydatek
     * 
     * @param int $id Identyfikator wydatku
     * @param array $record Nowe dane wydatku
     * 
     * @return bool true jeśli aktualizacja się powiodła
     * @throws Exception Jeśli aktualizacja się nie powiedzie
     */
    public function update($id, $record);

    /**
     * Usuwa wydatek
     * 
     * @param int $id Identyfikator wydatku
     * @return bool true jeśli usunięcie się powiodło
     * @throws Exception Jeśli usunięcie się nie powiedzie
     */
    public function delete($id);

    /**
     * Pobiera dane do wykresu (pogrupowane po kategoriach)
     * 
     * @return array Tablica z danymi do wykresu
     *               [
     *                   ['kategoria' => 'Jedzenie', 'suma' => '345.50'],
     *                   ['kategoria' => 'Transport', 'suma' => '150.00'],
     *                   ...
     *               ]
     */
    public function getChartData();

    /**
     * Pobiera statystyki
     * 
     * @return array Tablica ze statystykami
     *               [
     *                   'total' => suma wszystkich wydatków,
     *                   'count' => liczba wydatków,
     *                   'average' => średnia,
     *                   'min' => minimum,
     *                   'max' => maksimum
     *               ]
     */
    public function getStats();

    /**
     * Pobiera wydatki z filtrowaniem
     * 
     * @param array $filters Tablica z filtrami
     *                        [
     *                            'kategoria' => 'Jedzenie',
     *                            'from_date' => '2026-01-01',
     *                            'to_date' => '2026-01-31',
     *                            'min_amount' => 0,
     *                            'max_amount' => 1000
     *                        ]
     * 
     * @return array Tablica filtrowanych wydatków
     */
    public function getFiltered($filters = []);

    /**
     * Usuwa wszystkie wydatki
     * 
     * @return bool true jeśli operacja się powiodła
     */
    public function clear();

    /**
     * Importuje dane z tablicy
     * 
     * @param array $data Tablica z danymi
     * @param string $mode 'merge' - scalanie, 'replace' - nadpisanie
     * 
     * @return array ['success' => bool, 'message' => string, 'count' => int]
     */
    public function import($data, $mode = 'merge');

    /**
     * Eksportuje wszystkie dane
     * 
     * @return array Tablica ze wszystkimi wydatkami
     */
    public function export();

    /**
     * Zwraca informacje o źródle danych
     * 
     * @return array ['type' => 'mysql', 'status' => 'ok', ...]
     */
    public function getInfo();

    /**
     * Testuje połączenie ze źródłem danych
     * 
     * @return bool true jeśli połączenie działa
     */
    public function test();

    /**
     * Waliduje strukturę rekordu
     * 
     * @param array $record Rekord do walidacji
     * @return array ['valid' => bool, 'errors' => []]
     */
    public function validate($record);
}
?>

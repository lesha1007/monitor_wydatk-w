<?php
require_once 'config.php';
require_once 'DataSourceFactory.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nazwa = $_POST['nazwa'] ?? '';
        $kwota = $_POST['kwota'] ?? '';
        $kategoria = $_POST['kategoria'] ?? '';
        $data_wydatku = $_POST['data_wydatku'] ?? '';

        $record = [
            'nazwa' => $nazwa,
            'kwota' => $kwota,
            'kategoria' => $kategoria,
            'data_wydatku' => $data_wydatku
        ];

        $ds = DataSourceFactory::getInstance();
        $validation = $ds->validate($record);

        if (!$validation['valid']) {
            throw new Exception(implode(", ", $validation['errors']));
        }

        $ds->add($record);
        header('Location: index.php');
        exit();

    } catch (Exception $e) {
        header('Location: index.php?error=' . urlencode($e->getMessage()));
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
?>

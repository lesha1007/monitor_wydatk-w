<?php

$data_source = getenv('DATA_SOURCE') ?: 'mysql';


$db_config = [
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'db'       => getenv('DB_NAME') ?: 'portfel',
    'user'     => getenv('DB_USER') ?: 'root',
    'password' => getenv('DB_PASS') ?: '',
    'charset'  => 'utf8mb4'
];

$data_dir = __DIR__ . '/data';
$data_files = [
    'json' => $data_dir . '/data.json',
    'csv'  => $data_dir . '/data.csv'
];
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0755, true);
}

$categories = [
    'Jedzenie',
    'Transport',
    'Rozrywka',
    'Rachunki',
    'Inne'
];

$app_config = [
    'name'      => 'Monitor Wydatków',
    'version'   => '2.0.0',
    'timezone'  => 'Europe/Warsaw',
    'language'  => 'pl',
    'max_import_size' => 5 * 1024 * 1024, 
    'max_exports' => 10000, 
    'date_format' => 'Y-m-d',
    'decimal_places' => 2,
    'csv_delimiter' => ',',
    'csv_encoding' => 'UTF-8'
];

$user_settings_file = $data_dir . '/settings.json';

function get_user_settings() {
    global $user_settings_file;
    
    if (file_exists($user_settings_file)) {
        $json = file_get_contents($user_settings_file);
        return json_decode($json, true) ?: [];
    }
    
    return [
        'data_source' => 'mysql',
        'theme' => 'light',
        'notifications' => true
    ];
}

function save_user_settings($settings) {
    global $user_settings_file, $data_dir;

    if (!is_dir($data_dir)) {
        mkdir($data_dir, 0755, true);
    }
    
    file_put_contents($user_settings_file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

date_default_timezone_set($app_config['timezone']);

function get_data_dir() {
    global $data_dir;
    return $data_dir;
}

function get_data_file($type) {
    global $data_files;
    return $data_files[$type] ?? null;
}

function get_data_source() {
    global $data_source;
    return $data_source;
}

function is_valid_data_source($source) {
    return in_array($source, ['mysql', 'json', 'csv']);
}

function is_mysql_available() {
    global $db_config;
    
    try {
        $pdo = new PDO(
            "mysql:host={$db_config['host']};dbname={$db_config['db']};charset={$db_config['charset']}",
            $db_config['user'],
            $db_config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function get_available_sources() {
    $sources = [
        'mysql' => [
            'name' => 'Baza danych MySQL',
            'description' => 'Główne źródło danych (produkcja)',
            'available' => is_mysql_available()
        ],
        'json' => [
            'name' => 'Plik JSON',
            'description' => 'Tryb demonstracyjny, bez serwera',
            'available' => true
        ],
        'csv' => [
            'name' => 'Plik CSV',
            'description' => 'Alternatywne źródło (testowanie)',
            'available' => true
        ]
    ];
    
    return $sources;
}
?>

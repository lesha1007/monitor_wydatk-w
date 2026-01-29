<?php

require_once 'DataSourceInterface.php';
require_once 'MySQLDataSource.php';
require_once 'JsonDataSource.php';
require_once 'CsvDataSource.php';
require_once 'config.php';

class DataSourceFactory
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = self::create();
        }
        return self::$instance;
    }

    public static function create($source = null)
    {
        global $data_source, $db_config, $data_files;

        $source = $source ?: $data_source;

        switch ($source) {
            case 'mysql':
                return new MySQLDataSource($db_config);
            
            case 'json':
                return new JsonDataSource($data_files['json']);
            
            case 'csv':
                return new CsvDataSource($data_files['csv']);
            
            default:
                throw new Exception("Nieznane źródło danych: $source");
        }
    }

    public static function setDataSource($source)
    {
        global $data_source;
        
        if (!is_valid_data_source($source)) {
            throw new Exception("Nieważne źródło danych: $source");
        }

        $data_source = $source;
        self::$instance = null; 
        return self::getInstance();
    }

    public static function getAvailableSources()
    {
        return get_available_sources();
    }

    public static function testAll()
    {
        $results = [];
        
        foreach (['mysql', 'json', 'csv'] as $source) {
            try {
                $ds = self::create($source);
                $results[$source] = [
                    'available' => true,
                    'info' => $ds->getInfo(),
                    'test' => $ds->test()
                ];
            } catch (Exception $e) {
                $results[$source] = [
                    'available' => false,
                    'error' => $e->getMessage()
                ];
            }
        }

        return $results;
    }
}
?>

<?php

interface DataSourceInterface
{
    public function getAll($user_id = null);

    public function getById($id, $user_id = null);

    public function add($record, $user_id = null);

    public function update($id, $record, $user_id = null);

    public function delete($id, $user_id = null);

    public function getChartData($user_id = null);

    public function getStats($user_id = null);

    public function getFiltered($filters = [], $user_id = null);

    public function clear();

    public function import($data, $mode = 'merge');

    public function export();

    public function getInfo();

    public function test();

    public function validate($record);
}
?>

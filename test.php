<?php
require_once __DIR__ . '/Database.php';

$rows = Database::query('SELECT NOW() AS datetime');
echo $rows[0]['datetime'] . PHP_EOL;

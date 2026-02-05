<?php

define('BASE_PATH', dirname(__DIR__));
define('SRC_PATH', BASE_PATH . '/src');

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();


$core_files = [
    'REST.php',
    'Controller.php',
    'Router.php'
];
foreach ($core_files as $core_file) {
    require_once SRC_PATH . '/Core/' . $core_file;
}


foreach (glob(SRC_PATH . '/Database/*.php') as $database_file) {
    require_once $database_file;
}


foreach (glob(SRC_PATH . '/Store/*.php') as $store_file) {
    require_once $store_file;
}


foreach (glob(SRC_PATH . '/Controllers/*.php') as $controller) {
    require_once $controller;
}

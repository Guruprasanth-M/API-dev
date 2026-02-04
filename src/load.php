<?php

define('BASE_PATH', dirname(__DIR__));
define('SRC_PATH', BASE_PATH . '/src');

require BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

require_once SRC_PATH . '/Core/REST.php';
require_once SRC_PATH . '/Core/Controller.php';
require_once SRC_PATH . '/Core/Router.php';
require_once SRC_PATH . '/Database/Connection.php';
require_once SRC_PATH . '/Database/Migration.php';
require_once SRC_PATH . '/Store/User.php';
require_once SRC_PATH . '/Store/Session.php';
require_once SRC_PATH . '/Store/Auth.php';

foreach (glob(SRC_PATH . '/Controllers/*.php') as $controller) {
    require_once $controller;
}

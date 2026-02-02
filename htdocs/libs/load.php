<?php
include_once 'includes/Database.class.php';
include_once 'includes/Response.class.php';
include_once 'includes/User.class.php';


require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

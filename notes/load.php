<?php
/**
 * Notes Module Loader
 * 
 * Loads all notes-specific classes: Store, Controllers, Database
 * This file is included by the main src/load.php
 * Depends on: BASE_PATH constant, Core classes (Controller, Session, User)
 */

define('NOTES_PATH', BASE_PATH . '/notes');

// Load notes store classes
foreach (glob(NOTES_PATH . '/Store/*.php') as $store_file) {
    require_once $store_file;
}

// Load notes controllers
foreach (glob(NOTES_PATH . '/Controllers/*.php') as $controller) {
    require_once $controller;
}

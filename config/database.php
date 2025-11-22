<?php
// FILE: /config/database.php

/**
 * Database configuration
 *
 * Returns database connection settings from environment variables
 */

return [
    'host'     => getenv('DB_HOST') ?: 'localhost',
    'port'     => getenv('DB_PORT') ?: '3306',
    'database' => getenv('DB_DATABASE') ?: 'splashbook',
    'username' => getenv('DB_USERNAME') ?: 'root',
    'password' => getenv('DB_PASSWORD') ?: '',
    'charset'  => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
];

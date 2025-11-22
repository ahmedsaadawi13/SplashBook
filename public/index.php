<?php
// FILE: /public/index.php

/**
 * SplashBook - Entry Point
 *
 * All requests are routed through this file
 */

// Load autoloader
require_once __DIR__ . '/../bootstrap/autoload.php';

// Create router instance
$router = new Router();

// Load routes
require_once __DIR__ . '/../config/routes.php';

// Dispatch request
$router->dispatch();

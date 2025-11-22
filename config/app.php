<?php
// FILE: /config/app.php

/**
 * Application configuration
 *
 * Returns general application settings
 */

return [
    'name' => getenv('APP_NAME') ?: 'SplashBook',
    'env' => getenv('APP_ENV') ?: 'production',
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => getenv('APP_TIMEZONE') ?: 'UTC',

    // File upload settings
    'max_upload_size' => getenv('MAX_UPLOAD_SIZE') ?: 5242880, // 5MB default
    'allowed_image_types' => explode(',', getenv('ALLOWED_IMAGE_TYPES') ?: 'jpg,jpeg,png,gif'),
    'allowed_document_types' => explode(',', getenv('ALLOWED_DOCUMENT_TYPES') ?: 'pdf,doc,docx'),

    // Session settings
    'session_lifetime' => getenv('SESSION_LIFETIME') ?: 7200, // 2 hours

    // Security
    'require_https' => getenv('REQUIRE_HTTPS') === 'true',
];

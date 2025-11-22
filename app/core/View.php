<?php
// FILE: /app/core/View.php

/**
 * View - Handles rendering of view templates
 *
 * Provides a simple templating system with layout support
 * and automatic escaping for security.
 */
class View
{
    private $viewsPath;
    private $layoutsPath;

    /**
     * Constructor - sets up view paths
     */
    public function __construct()
    {
        $this->viewsPath = __DIR__ . '/../views/';
        $this->layoutsPath = __DIR__ . '/../views/layouts/';
    }

    /**
     * Render a view with a layout
     *
     * @param string $viewName View file name (without .php)
     * @param array $data Data to pass to the view
     * @param string|null $layout Layout file name (without .php), null for no layout
     */
    public function render($viewName, $data = [], $layout = 'layout')
    {
        // Extract data to variables
        extract($data);

        // Add session to views
        $session = new Session();

        // Capture view content
        ob_start();
        $viewFile = $this->viewsPath . $viewName . '.php';

        if (!file_exists($viewFile)) {
            throw new Exception("View file not found: $viewFile");
        }

        require $viewFile;
        $content = ob_get_clean();

        // If layout is specified, render with layout
        if ($layout !== null) {
            $layoutFile = $this->layoutsPath . $layout . '.php';

            if (!file_exists($layoutFile)) {
                throw new Exception("Layout file not found: $layoutFile");
            }

            require $layoutFile;
        } else {
            // No layout, just output content
            echo $content;
        }
    }

    /**
     * Escape HTML for output (prevents XSS)
     *
     * @param string $value Value to escape
     * @return string Escaped value
     */
    public function escape($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Render a partial view
     *
     * @param string $partialName Partial file name (without .php)
     * @param array $data Data to pass to the partial
     */
    public function partial($partialName, $data = [])
    {
        extract($data);
        $partialFile = $this->viewsPath . 'partials/' . $partialName . '.php';

        if (file_exists($partialFile)) {
            require $partialFile;
        }
    }
}

// Global helper function for escaping output in views
if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

// Global helper function for URL generation
if (!function_exists('url')) {
    function url($path = '') {
        $baseUrl = rtrim(getenv('APP_URL') ?: 'http://localhost', '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }
}

// Global helper function for asset URLs
if (!function_exists('asset')) {
    function asset($path) {
        return url('assets/' . ltrim($path, '/'));
    }
}

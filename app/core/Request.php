<?php
// FILE: /app/core/Request.php

/**
 * Request - Handles HTTP request data
 *
 * Provides convenient access to request data with automatic
 * sanitization and validation support.
 */
class Request
{
    /**
     * Get a value from GET parameters
     *
     * @param string $key Parameter name
     * @param mixed $default Default value if not found
     * @return mixed Parameter value
     */
    public function get($key, $default = null)
    {
        return isset($_GET[$key]) ? $this->clean($_GET[$key]) : $default;
    }

    /**
     * Get a value from POST parameters
     *
     * @param string $key Parameter name
     * @param mixed $default Default value if not found
     * @return mixed Parameter value
     */
    public function post($key, $default = null)
    {
        return isset($_POST[$key]) ? $this->clean($_POST[$key]) : $default;
    }

    /**
     * Get all POST data
     *
     * @return array POST data
     */
    public function allPost()
    {
        return $_POST;
    }

    /**
     * Get all GET data
     *
     * @return array GET data
     */
    public function allGet()
    {
        return $_GET;
    }

    /**
     * Get a value from request (checks POST then GET)
     *
     * @param string $key Parameter name
     * @param mixed $default Default value if not found
     * @return mixed Parameter value
     */
    public function input($key, $default = null)
    {
        if (isset($_POST[$key])) {
            return $this->clean($_POST[$key]);
        }
        if (isset($_GET[$key])) {
            return $this->clean($_GET[$key]);
        }
        return $default;
    }

    /**
     * Get a value from server variables
     *
     * @param string $key Server variable name
     * @param mixed $default Default value if not found
     * @return mixed Server variable value
     */
    public function server($key, $default = null)
    {
        return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
    }

    /**
     * Check if request method is POST
     *
     * @return bool
     */
    public function isPost()
    {
        return $this->server('REQUEST_METHOD') === 'POST';
    }

    /**
     * Check if request method is GET
     *
     * @return bool
     */
    public function isGet()
    {
        return $this->server('REQUEST_METHOD') === 'GET';
    }

    /**
     * Check if request is AJAX
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->server('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest';
    }

    /**
     * Get request method
     *
     * @return string HTTP method (GET, POST, etc.)
     */
    public function method()
    {
        return $this->server('REQUEST_METHOD', 'GET');
    }

    /**
     * Get request URI
     *
     * @return string Request URI
     */
    public function uri()
    {
        return $this->server('REQUEST_URI', '/');
    }

    /**
     * Get uploaded file
     *
     * @param string $key File input name
     * @return array|null File information or null
     */
    public function file($key)
    {
        return isset($_FILES[$key]) ? $_FILES[$key] : null;
    }

    /**
     * Check if a file was uploaded
     *
     * @param string $key File input name
     * @return bool
     */
    public function hasFile($key)
    {
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Basic input cleaning (trim whitespace)
     * Note: Does not escape - use prepared statements for SQL and htmlspecialchars for HTML output
     *
     * @param mixed $value Value to clean
     * @return mixed Cleaned value
     */
    private function clean($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'clean'], $value);
        }

        return is_string($value) ? trim($value) : $value;
    }

    /**
     * Get client IP address
     *
     * @return string IP address
     */
    public function ip()
    {
        if ($this->server('HTTP_CLIENT_IP')) {
            return $this->server('HTTP_CLIENT_IP');
        }
        if ($this->server('HTTP_X_FORWARDED_FOR')) {
            return $this->server('HTTP_X_FORWARDED_FOR');
        }
        return $this->server('REMOTE_ADDR', '0.0.0.0');
    }

    /**
     * Get user agent
     *
     * @return string User agent string
     */
    public function userAgent()
    {
        return $this->server('HTTP_USER_AGENT', '');
    }
}

<?php
// FILE: /app/core/Session.php

/**
 * Session - Handles session management
 *
 * Provides a secure wrapper for PHP sessions with
 * CSRF token support and flash messages.
 */
class Session
{
    /**
     * Constructor - starts session if not already started
     */
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));

            session_start();
        }

        // Generate CSRF token if not exists
        if (!$this->has('csrf_token')) {
            $this->set('csrf_token', $this->generateCsrfToken());
        }
    }

    /**
     * Set a session value
     *
     * @param string $key Session key
     * @param mixed $value Value to store
     */
    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get a session value
     *
     * @param string $key Session key
     * @param mixed $default Default value if not found
     * @return mixed Session value
     */
    public function get($key, $default = null)
    {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
    }

    /**
     * Check if session key exists
     *
     * @param string $key Session key
     * @return bool
     */
    public function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session value
     *
     * @param string $key Session key
     */
    public function remove($key)
    {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy the session
     */
    public function destroy()
    {
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Set a flash message
     *
     * @param string $type Message type (success, error, warning, info)
     * @param string $message Message text
     */
    public function setFlash($type, $message)
    {
        $_SESSION['flash'][$type] = $message;
    }

    /**
     * Get and remove a flash message
     *
     * @param string $type Message type
     * @return string|null Flash message
     */
    public function getFlash($type)
    {
        if (isset($_SESSION['flash'][$type])) {
            $message = $_SESSION['flash'][$type];
            unset($_SESSION['flash'][$type]);
            return $message;
        }
        return null;
    }

    /**
     * Check if a flash message exists
     *
     * @param string $type Message type
     * @return bool
     */
    public function hasFlash($type)
    {
        return isset($_SESSION['flash'][$type]);
    }

    /**
     * Generate a CSRF token
     *
     * @return string CSRF token
     */
    private function generateCsrfToken()
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Get the CSRF token
     *
     * @return string CSRF token
     */
    public function getCsrfToken()
    {
        return $this->get('csrf_token');
    }

    /**
     * Validate a CSRF token
     *
     * @param string $token Token to validate
     * @return bool
     */
    public function validateCsrfToken($token)
    {
        return hash_equals($this->getCsrfToken(), $token);
    }

    /**
     * Regenerate session ID (prevents session fixation)
     */
    public function regenerate()
    {
        session_regenerate_id(true);
    }
}

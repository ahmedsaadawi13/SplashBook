<?php
// FILE: /app/core/Controller.php

/**
 * Controller - Base controller class
 *
 * Provides common functionality for all controllers including
 * view rendering, authentication checks, and request handling.
 */
class Controller
{
    protected $view;
    protected $request;
    protected $session;

    /**
     * Constructor - initializes view and request objects
     */
    public function __construct()
    {
        $this->view = new View();
        $this->request = new Request();
        $this->session = new Session();
    }

    /**
     * Render a view
     *
     * @param string $viewName View file name (without .php)
     * @param array $data Data to pass to the view
     * @param string $layout Layout file (default: 'layout')
     */
    protected function render($viewName, $data = [], $layout = 'layout')
    {
        $this->view->render($viewName, $data, $layout);
    }

    /**
     * Return JSON response
     *
     * @param mixed $data Data to encode as JSON
     * @param int $statusCode HTTP status code
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to a URL
     *
     * @param string $url URL to redirect to
     */
    protected function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    protected function isAuthenticated()
    {
        return $this->session->has('user_id');
    }

    /**
     * Get current authenticated user
     *
     * @return array|null User data or null
     */
    protected function getCurrentUser()
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        return $this->session->get('user');
    }

    /**
     * Require authentication - redirect to login if not authenticated
     */
    protected function requireAuth()
    {
        if (!$this->isAuthenticated()) {
            $this->session->setFlash('error', 'Please log in to continue');
            $this->redirect('/login');
        }
    }

    /**
     * Require specific role
     *
     * @param array $allowedRoles Array of allowed roles
     */
    protected function requireRole($allowedRoles)
    {
        $this->requireAuth();

        $user = $this->getCurrentUser();
        if (!in_array($user['role'], $allowedRoles)) {
            $this->session->setFlash('error', 'Access denied');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Get current tenant ID from session
     *
     * @return int|null
     */
    protected function getTenantId()
    {
        $user = $this->getCurrentUser();
        return $user['tenant_id'] ?? null;
    }

    /**
     * Validate CSRF token
     *
     * @return bool
     */
    protected function validateCsrf()
    {
        $token = $this->request->post('csrf_token');
        return $this->session->validateCsrfToken($token);
    }

    /**
     * Require CSRF token validation
     */
    protected function requireCsrf()
    {
        if (!$this->validateCsrf()) {
            $this->session->setFlash('error', 'Invalid request. Please try again.');
            $this->redirect($this->request->server('HTTP_REFERER', '/'));
        }
    }

    /**
     * Set flash message
     *
     * @param string $type Message type (success, error, warning, info)
     * @param string $message Message text
     */
    protected function setFlash($type, $message)
    {
        $this->session->setFlash($type, $message);
    }

    /**
     * Validate input data
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array Validation errors (empty if valid)
     */
    protected function validate($data, $rules)
    {
        return Validator::validate($data, $rules);
    }

    /**
     * Check if current user is platform admin
     *
     * @return bool
     */
    protected function isPlatformAdmin()
    {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === 'platform_admin';
    }

    /**
     * Check if current user is tenant admin
     *
     * @return bool
     */
    protected function isTenantAdmin()
    {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === 'tenant_admin';
    }
}

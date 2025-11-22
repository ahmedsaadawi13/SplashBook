<?php
// FILE: /app/core/Router.php

/**
 * Router - Handles URL routing and dispatching
 *
 * Maps URLs to controller actions with support for
 * route parameters and HTTP method filtering.
 */
class Router
{
    private $routes = [];
    private $groupPrefix = '';
    private $groupMiddleware = [];

    /**
     * Add a GET route
     *
     * @param string $path Route path
     * @param string $controller Controller and method (Controller@method)
     */
    public function get($path, $controller)
    {
        $this->addRoute('GET', $path, $controller);
    }

    /**
     * Add a POST route
     *
     * @param string $path Route path
     * @param string $controller Controller and method (Controller@method)
     */
    public function post($path, $controller)
    {
        $this->addRoute('POST', $path, $controller);
    }

    /**
     * Add a route that accepts both GET and POST
     *
     * @param string $path Route path
     * @param string $controller Controller and method (Controller@method)
     */
    public function any($path, $controller)
    {
        $this->addRoute('GET', $path, $controller);
        $this->addRoute('POST', $path, $controller);
    }

    /**
     * Add a route to the routing table
     *
     * @param string $method HTTP method
     * @param string $path Route path
     * @param string $controller Controller and method
     */
    private function addRoute($method, $path, $controller)
    {
        $path = $this->groupPrefix . $path;

        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'controller' => $controller,
            'middleware' => $this->groupMiddleware
        ];
    }

    /**
     * Create a route group with a prefix
     *
     * @param string $prefix Path prefix
     * @param callable $callback Callback to define routes
     */
    public function group($prefix, $callback)
    {
        $previousPrefix = $this->groupPrefix;
        $this->groupPrefix .= $prefix;

        call_user_func($callback, $this);

        $this->groupPrefix = $previousPrefix;
    }

    /**
     * Dispatch the current request to the appropriate controller
     */
    public function dispatch()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove base path if application is in a subdirectory
        $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($basePath && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        $uri = '/' . trim($uri, '/');
        if ($uri !== '/') {
            $uri = rtrim($uri, '/');
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            // Convert route path to regex pattern
            $pattern = $this->convertToRegex($route['path']);

            if (preg_match($pattern, $uri, $matches)) {
                // Remove full match
                array_shift($matches);

                // Extract controller and method
                list($controllerName, $methodName) = explode('@', $route['controller']);

                // Load and instantiate controller
                $controllerFile = __DIR__ . '/../controllers/' . $controllerName . '.php';

                if (!file_exists($controllerFile)) {
                    $this->notFound();
                    return;
                }

                require_once $controllerFile;

                if (!class_exists($controllerName)) {
                    $this->notFound();
                    return;
                }

                $controller = new $controllerName();

                if (!method_exists($controller, $methodName)) {
                    $this->notFound();
                    return;
                }

                // Call the controller method with route parameters
                call_user_func_array([$controller, $methodName], $matches);
                return;
            }
        }

        // No route matched
        $this->notFound();
    }

    /**
     * Convert route path to regex pattern
     *
     * @param string $path Route path with placeholders like {id}
     * @return string Regex pattern
     */
    private function convertToRegex($path)
    {
        // Escape forward slashes
        $pattern = str_replace('/', '\/', $path);

        // Convert {param} to named capture group
        $pattern = preg_replace('/\{(\w+)\}/', '([^\/]+)', $pattern);

        // Anchor the pattern
        return '/^' . $pattern . '$/';
    }

    /**
     * Handle 404 Not Found
     */
    private function notFound()
    {
        http_response_code(404);
        echo '<h1>404 Not Found</h1>';
        echo '<p>The page you are looking for could not be found.</p>';
    }
}

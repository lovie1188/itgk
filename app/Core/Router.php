<?php

/**
 * Router - Enterprise MVC Router with Middleware Support
 * 
 * Provides clean URL routing, middleware pipeline, route groups,
 * and proper request normalization for both web and API routes.
 * 
 * @package App\Core
 * @author SoftTech Team
 */

declare(strict_types=1);

namespace App\Core;

use App\Middleware\MiddlewareInterface;
use App\Exceptions\NotFoundException;
use App\Helpers\Logger;

class Router
{
    /**
     * Registered routes
     * @var array
     */
    private array $routes = [];

    /**
     * Global middlewares applied to all routes
     * @var array
     */
    private array $globalMiddlewares = [];

    /**
     * Current group prefix for nested routes
     * @var string
     */
    private string $currentPrefix = '';

    /**
     * Current group middlewares
     * @var array
     */
    private array $currentMiddlewares = [];

    /**
     * Route parameters extracted from URL
     * @var array
     */
    private array $routeParams = [];

    /**
     * Named routes for URL generation
     * @var array
     */
    private array $namedRoutes = [];

    /**
     * Base URL path (e.g., '/certificate/')
     * @var string
     */
    private string $basePath = '/';

    /**
     * Add a route
     * 
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param string $path URL path (e.g., '/users/{id}')
     * @param string $controller Controller class name
     * @param string $action Controller method name
     * @param array $middlewares Middlewares to apply
     * @param string|null $name Route name for URL generation
     * @return self
     */
    public function add(
        string $method,
        string $path,
        string $controller,
        string $action,
        array $middlewares = [],
        ?string $name = null
    ): self {
        $fullPath = $this->currentPrefix . $this->normalizePath($path);
        $fullMiddlewares = array_merge($this->currentMiddlewares, $middlewares);

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'controller' => $controller,
            'action' => $action,
            'middlewares' => $fullMiddlewares,
            'pattern' => $this->pathToPattern($fullPath)
        ];

        // Store named route
        if ($name !== null) {
            $this->namedRoutes[$name] = $fullPath;
        }

        return $this;
    }

    /**
     * Add GET route
     */
    public function get(string $path, string $controller, string $action, array $middlewares = [], ?string $name = null): self
    {
        return $this->add('GET', $path, $controller, $action, $middlewares, $name);
    }

    /**
     * Add POST route
     */
    public function post(string $path, string $controller, string $action, array $middlewares = [], ?string $name = null): self
    {
        return $this->add('POST', $path, $controller, $action, $middlewares, $name);
    }

    /**
     * Add PUT route
     */
    public function put(string $path, string $controller, string $action, array $middlewares = [], ?string $name = null): self
    {
        return $this->add('PUT', $path, $controller, $action, $middlewares, $name);
    }

    /**
     * Add DELETE route
     */
    public function delete(string $path, string $controller, string $action, array $middlewares = [], ?string $name = null): self
    {
        return $this->add('DELETE', $path, $controller, $action, $middlewares, $name);
    }

    /**
     * Add PATCH route
     */
    public function patch(string $path, string $controller, string $action, array $middlewares = [], ?string $name = null): self
    {
        return $this->add('PATCH', $path, $controller, $action, $middlewares, $name);
    }

    /**
     * Create a route group with shared attributes
     * 
     * @param array $attributes Group attributes (prefix, middleware)
     * @param callable $callback Callback to define routes
     * @return self
     */
    public function group(array $attributes, callable $callback): self
    {
        // Save current state
        $previousPrefix = $this->currentPrefix;
        $previousMiddlewares = $this->currentMiddlewares;

        // Apply group attributes
        if (isset($attributes['prefix'])) {
            $this->currentPrefix = $previousPrefix . $this->normalizePath($attributes['prefix']);
        }

        if (isset($attributes['middleware'])) {
            $middlewares = is_array($attributes['middleware'])
                ? $attributes['middleware']
                : [$attributes['middleware']];
            $this->currentMiddlewares = array_merge($previousMiddlewares, $middlewares);
        }

        // Execute callback
        $callback($this);

        // Restore previous state
        $this->currentPrefix = $previousPrefix;
        $this->currentMiddlewares = $previousMiddlewares;

        return $this;
    }

    /**
     * Add global middleware
     * 
     * @param string $middleware Middleware class name
     * @return self
     */
    public function middleware(string $middleware): self
    {
        $this->globalMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * Dispatch the request
     * 
     * @param string $uri Request URI
     * @param string $method HTTP method
     * @return void
     * @throws NotFoundException If route not found
     */
    public function dispatch(string $uri, string $method): void
    {
        // Normalize URI
        $uri = $this->normalizeRequestUri($uri);
        $method = strtoupper($method);

        Logger::debug('Router dispatching', [
            'uri' => $uri,
            'method' => $method
        ]);

        // Run global middlewares
        foreach ($this->globalMiddlewares as $middleware) {
            $this->runMiddleware($middleware);
        }

        // Find matching route
        foreach ($this->routes as $route) {
            if ($this->matchRoute($route, $uri, $method)) {
                // Run route-specific middlewares
                foreach ($route['middlewares'] as $middleware) {
                    $this->runMiddleware($middleware);
                }

                // Execute controller
                $this->executeController($route);
                return;
            }
        }

        // No route found
        throw new NotFoundException("Route not found: {$method} {$uri}");
    }

    /**
     * Generate URL for named route
     * 
     * @param string $name Route name
     * @param array $params Route parameters
     * @return string
     * @throws \InvalidArgumentException If route not found
     */
    public function url(string $name, array $params = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new \InvalidArgumentException("Route '{$name}' not found");
        }

        $path = $this->namedRoutes[$name];

        // Replace parameters
        foreach ($params as $key => $value) {
            $path = str_replace("{{$key}}", (string)$value, $path);
        }

        return BASE_URL . ltrim($path, '/');
    }

    /**
     * Get all registered routes
     * 
     * @return array
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Normalize request URI
     * 
     * For clean URLs (via .htaccess), we receive the full REQUEST_URI.
     * Strip off the BASE_URL to get the route path.
     * Example: REQUEST_URI=/certificate/dashboard, BASE_URL=/certificate/ -> route=/dashboard
     * 
     * @param string $uri Request URI
     * @return string Normalized route path
     */
    private function normalizeRequestUri(string $uri): string
    {
        // Remove query string
        $uri = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Get base path from BASE_URL or calculate from SCRIPT_NAME
        $basePath = getenv('BASE_URL') ?: '/certificate/';
        $basePath = $this->normalizePath($basePath);

        // Strip base path from URI
        if ($basePath !== '/' && strpos($uri, $basePath) === 0) {
            $uri = substr($uri, strlen($basePath));
        }

        // Normalize the remaining path
        return $this->normalizePath($uri);
    }

    /**
     * Normalize path (ensure leading slash, no trailing slash)
     * 
     * @param string $path Path to normalize
     * @return string
     */
    private function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : $path;
    }

    /**
     * Convert path with parameters to regex pattern
     * 
     * @param string $path Path with {param} placeholders
     * @return string Regex pattern
     */
    private function pathToPattern(string $path): string
    {
        // Replace {param} with regex for capturing
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Match route against URI and method
     * 
     * @param array $route Route definition
     * @param string $uri Request URI
     * @param string $method HTTP method
     * @return bool
     */
    private function matchRoute(array $route, string $uri, string $method): bool
    {
        // Check method
        if ($route['method'] !== $method) {
            return false;
        }

        // Match pattern
        if (!preg_match($route['pattern'], $uri, $matches)) {
            return false;
        }

        // Extract parameters
        array_shift($matches); // Remove full match
        $this->routeParams = $matches;

        return true;
    }

    /**
     * Run middleware
     * 
     * @param string $middlewareClass Middleware class name
     * @return void
     */
    private function runMiddleware(string $middlewareClass): void
    {
        // Handle middleware with parameters (e.g., RoleMiddleware::class . ':SUPERADMIN')
        $parts = explode(':', $middlewareClass, 2);
        $className = $parts[0];
        $params = isset($parts[1]) ? explode(',', $parts[1]) : [];

        if (!class_exists($className)) {
            throw new \RuntimeException("Middleware class '{$className}' not found");
        }

        $middleware = new $className(...$params);

        if (!$middleware instanceof MiddlewareInterface) {
            throw new \RuntimeException(
                "Middleware '{$className}' must implement MiddlewareInterface"
            );
        }

        $middleware->handle();
    }

    /**
     * Execute controller action
     * 
     * @param array $route Route definition
     * @return void
     */
    private function executeController(array $route): void
    {
        $controllerClass = $route['controller'];
        $action = $route['action'];

        // Check if controller uses namespace
        if (strpos($controllerClass, '\\') === false) {
            $controllerClass = 'App\\Controllers\\' . $controllerClass;
        }

        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller '{$controllerClass}' not found");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $action)) {
            throw new \RuntimeException(
                "Method '{$action}' not found in controller '{$controllerClass}'"
            );
        }

        // Call action with route parameters
        $controller->$action(...$this->routeParams);
    }

    /**
     * Get current route parameters
     * 
     * @return array
     */
    public function getParams(): array
    {
        return $this->routeParams;
    }

    /**
     * Get specific route parameter
     * 
     * @param int $index Parameter index
     * @param mixed $default Default value
     * @return mixed
     */
    public function getParam(int $index, mixed $default = null): mixed
    {
        return $this->routeParams[$index] ?? $default;
    }
}

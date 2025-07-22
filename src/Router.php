<?php

namespace Mgx\Router;

use Closure;
use Mgx\Router\Route;
use Mgx\Router\ControllerResolver;
use Mgx\Router\MiddlewareInterface;
use Mgx\Router\Request;

class Router
{
    protected array $routes = [];
    protected array $namedRoutes = [];
    protected array $globalMiddlewares = [];
    protected array $groupStack = [];
    protected ?Closure $fallbackHandler = null;

    public function get(string $uri, string|array|Closure $handler): Route
    {
        return $this->add('GET', $uri, $handler);
    }

    public function post(string $uri, string|array|Closure $handler): Route
    {
        return $this->add('POST', $uri, $handler);
    }

    public function put(string $uri, string|array|Closure $handler): Route
    {
        return $this->add('PUT', $uri, $handler);
    }

    public function delete(string $uri, string|array|Closure $handler): Route
    {
        return $this->add('DELETE', $uri, $handler);
    }

    public function patch(string $uri, string|array|Closure $handler): Route
    {
        return $this->add('PATCH', $uri, $handler);
    }

    public function middleware(string $middlewareClass): void
    {
        $this->globalMiddlewares[] = $middlewareClass;
    }

    public function group(array $attributes, Closure $callback): void
    {
        $this->groupStack[] = $attributes;
        $callback($this);
        array_pop($this->groupStack);
    }

    public function fallback(Closure $callback): void
    {
        $this->fallbackHandler = $callback;
    }

    protected function add(string $method, string $uri, string|array|Closure $handler): Route
    {
        $prefix = $namespace = '';
        $middlewares = [];

        foreach ($this->groupStack as $group) {
            if (isset($group['prefix'])) {
                $prefix .= rtrim($group['prefix'], '/');
            }
            if (isset($group['namespace'])) {
                $namespace = rtrim($group['namespace'], '\\') . '\\';
            }
            if (isset($group['middleware'])) {
                $middlewares = array_merge($middlewares, (array)$group['middleware']);
            }
        }

        $uri = $prefix . '/' . ltrim($uri, '/');
        $uri = '/' . trim($uri, '/');

        if (is_string($handler) && str_contains($handler, '@')) {
            $handler = $namespace . $handler;
        }

        $route = new Route($method, $uri, $handler, $middlewares);
        $this->routes[] = $route;

        // Named route desteği
        if (method_exists($route, 'getName') && $route->getName()) {
            $this->namedRoutes[$route->getName()] = $route;
        }

        return $route;
    }

    public function route(string $name, array $params = []): ?string
    {
        if (!isset($this->namedRoutes[$name])) return null;

        $uri = $this->namedRoutes[$name]->getPath();

        foreach ($params as $key => $value) {
            $uri = str_replace("{" . $key . "}", $value, $uri);
        }

        return $uri;
    }

    public function dispatch(?Request $request = null): void
    {
        $request = $request ?: new Request();

        $resolver = new ControllerResolver();

        foreach ($this->routes as $route) {
            if ($route->matches($request)) {
                $middlewarePipeline = $this->buildMiddlewarePipeline(
                    fn($req) => $route->run($req, $resolver),
                    $this->globalMiddlewares
                );
                echo $middlewarePipeline($request);
                return;
            }
        }

        if ($this->fallbackHandler) {
            echo call_user_func($this->fallbackHandler);
        } else {
            http_response_code(404);
            echo "404 Not Found";
        }
    }

    protected function buildMiddlewarePipeline(callable $handler, array $middlewares): callable
    {
        return array_reduce(
            array_reverse($middlewares),
            function ($next, $middlewareClass) {
                return function (Request $request) use ($middlewareClass, $next) {
                    $middleware = new $middlewareClass();
                    if (!$middleware instanceof MiddlewareInterface) {
                        throw new \Exception("Middleware '{$middlewareClass}' interface uygulamıyor.");
                    }
                    return $middleware->handle($request, $next);
                };
            },
            $handler
        );
    }
}
<?php

namespace Mgx\Router;

use Closure;
use Mgx\Router\MiddlewareInterface;
use Mgx\Router\ControllerResolver;
use Mgx\Router\Request;

class Route
{
    protected string $method;
    protected string $path;
    protected string|array|Closure $handler;
    protected array $middlewares = [];
    protected ?string $name = null;

    protected string $pattern;
    protected array $paramNames = [];

    public function __construct(string $method, string $path, string|array|Closure $handler, array $middlewares = [])
    {
        $this->method = strtoupper($method);
        $this->path = $path;
        $this->handler = $handler;
        $this->middlewares = $middlewares;
        $this->compilePathToRegex();
    }

    protected function compilePathToRegex(): void
    {
        $pattern = preg_replace_callback('#\{([^}]+)\}#', function ($matches) {
            $this->paramNames[] = $matches[1];
            return '([^/]+)';
        }, $this->path);

        $this->pattern = '#^' . $pattern . '$#';
    }

    public function matches(Request $request): bool
    {
        if ($this->method !== $request->method()) {
            return false;
        }
        return (bool) preg_match($this->pattern, $request->uri());
    }

    public function extractParams(Request $request): void
    {
        if (preg_match($this->pattern, $request->uri(), $matches)) {
            array_shift($matches);
            $params = array_combine($this->paramNames, $matches);
            $request->setRouteParams($params ?? []);
        }
    }

    public function middleware(array $middlewares): self
    {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function run(Request $request, ?ControllerResolver $resolver = null): mixed
    {
        $this->extractParams($request);

        $handler = $this->getHandler($resolver);

        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            function ($next, $middlewareClass) {
                return function ($request) use ($middlewareClass, $next) {
                    $middleware = new $middlewareClass();
                    if (!$middleware instanceof MiddlewareInterface) {
                        throw new \Exception("Middleware {$middlewareClass} MiddlewareInterface implement etmiyor.");
                    }
                    return $middleware->handle($request, $next);
                };
            },
            $handler
        );

        return $pipeline($request);
    }

    protected function getHandler(?ControllerResolver $resolver = null): Closure
    {
        if ($this->handler instanceof Closure) {
            return $this->handler;
        }

        if (is_array($this->handler)) {
            [$controllerClass, $method] = $this->handler;
            return fn($request) => (new $controllerClass())->$method($request);
        }

        if (is_string($this->handler) && str_contains($this->handler, '@')) {
            if ($resolver) {
                [$instance, $method] = $resolver->resolve($this->handler);
                return fn($request) => $instance->$method($request);
            } else {
                [$controllerClass, $method] = explode('@', $this->handler);
                return fn($request) => (new $controllerClass())->$method($request);
            }
        }

        throw new \Exception('Geçersiz route handler tanımı.');
    }
}
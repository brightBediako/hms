<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{methods: list<string>, pattern: string, handler: callable|array{0: class-string, 1: string}, middleware: array<string, mixed>}> */
    private array $routes = [];

    /** @param array<string, mixed> $middleware */
    public function get(string $pattern, callable|array $handler, array $middleware = []): self
    {
        return $this->add(['GET'], $pattern, $handler, $middleware);
    }

    /** @param array<string, mixed> $middleware */
    public function post(string $pattern, callable|array $handler, array $middleware = []): self
    {
        return $this->add(['POST'], $pattern, $handler, $middleware);
    }

    /** @param list<string> $methods */
    /** @param array<string, mixed> $middleware */
    public function match(array $methods, string $pattern, callable|array $handler, array $middleware = []): self
    {
        $normalized = array_map(static fn (string $m): string => strtoupper($m), $methods);

        return $this->add($normalized, $pattern, $handler, $middleware);
    }

    /** @param list<string> $methods */
    /** @param array<string, mixed> $middleware */
    private function add(array $methods, string $pattern, callable|array $handler, array $middleware): self
    {
        $pattern = '/' . trim($pattern, '/');
        if ($pattern !== '/') {
            $pattern = rtrim($pattern, '/') ?: '/';
        }

        $this->routes[] = [
            'methods' => $methods,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];

        return $this;
    }

    public function dispatch(Request $request): void
    {
        $method = $request->method();
        $path = $request->path();

        foreach ($this->routes as $route) {
            if (!in_array($method, $route['methods'], true)) {
                continue;
            }

            $params = $this->matchPath($route['pattern'], $path);
            if ($params === null) {
                continue;
            }

            $this->runMiddleware($request, $route['methods'], $route['middleware']);

            $handler = $route['handler'];

            if (is_array($handler)) {
                [$class, $action] = $handler;
                $controller = new $class();
                $controller->{$action}($request, ...array_values($params));
                return;
            }

            $handler($request, ...array_values($params));
            return;
        }

        Response::notFound('No route matched for ' . $method . ' ' . $path);
    }

    /** @param list<string> $methods */
    /** @param array<string, mixed> $middleware */
    private function runMiddleware(Request $request, array $methods, array $middleware): void
    {
        $isStateChanging = !in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
        $csrfRequired = $middleware['csrf'] ?? $isStateChanging;

        if ($csrfRequired) {
            CSRF::validateRequest($request);
        }

        if (!empty($middleware['guest'])) {
            Auth::requireGuest();
        }

        if (!empty($middleware['auth'])) {
            Auth::requireLogin();
        }

        if (isset($middleware['permission']) && is_string($middleware['permission'])) {
            Auth::requirePermission($middleware['permission']);
        }
    }

    /** @return array<string, string>|null */
    private function matchPath(string $pattern, string $path): ?array
    {
        $paramNames = [];
        $regex = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static function (array $matches) use (&$paramNames): string {
                $paramNames[] = $matches[1];
                return '([^/]+)';
            },
            $pattern
        );

        $regex = '#^' . $regex . '$#';

        if (!preg_match($regex, $path, $matches)) {
            return null;
        }

        array_shift($matches);
        $params = [];

        foreach ($paramNames as $index => $name) {
            $params[$name] = $matches[$index] ?? '';
        }

        return $params;
    }
}

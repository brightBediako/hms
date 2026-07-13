<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<int, array{methods: list<string>, pattern: string, handler: callable|array{0: class-string, 1: string}}> */
    private array $routes = [];

    public function get(string $pattern, callable|array $handler): self
    {
        return $this->add(['GET'], $pattern, $handler);
    }

    public function post(string $pattern, callable|array $handler): self
    {
        return $this->add(['POST'], $pattern, $handler);
    }

    public function match(array $methods, string $pattern, callable|array $handler): self
    {
        $normalized = array_map(static fn (string $m): string => strtoupper($m), $methods);

        return $this->add($normalized, $pattern, $handler);
    }

    /** @param list<string> $methods */
    private function add(array $methods, string $pattern, callable|array $handler): self
    {
        $pattern = '/' . trim($pattern, '/');
        if ($pattern !== '/') {
            $pattern = rtrim($pattern, '/') ?: '/';
        }

        $this->routes[] = [
            'methods' => $methods,
            'pattern' => $pattern,
            'handler' => $handler,
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

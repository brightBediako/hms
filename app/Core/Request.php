<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /** @param array<string, mixed> $query */
    /** @param array<string, mixed> $request */
    /** @param array<string, mixed> $server */
    /** @param array<string, mixed> $cookies */
    /** @param array<string, mixed> $files */
    public function __construct(
        private array $query,
        private array $request,
        private array $server,
        private array $cookies,
        private array $files,
        private ?string $rawBody = null,
    ) {
    }

    public static function capture(): self
    {
        return new self(
            $_GET,
            $_POST,
            $_SERVER,
            $_COOKIE,
            $_FILES,
            file_get_contents('php://input') ?: null,
        );
    }

    public function method(): string
    {
        $method = strtoupper((string) ($this->server['REQUEST_METHOD'] ?? 'GET'));
        $override = $this->input('_method');

        if (is_string($override) && $override !== '') {
            return strtoupper($override);
        }

        return $method;
    }

    public function path(): string
    {
        $uri = (string) ($this->server['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);

        if (!is_string($path) || $path === '') {
            return '/';
        }

        $scriptName = (string) ($this->server['SCRIPT_NAME'] ?? '');
        $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        if ($base !== '' && $base !== '/' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }

        $path = '/' . trim($path, '/');

        if ($path === '/') {
            return '/';
        }

        return rtrim($path, '/') ?: '/';
    }

    /** @return array<string, mixed> */
    public function query(): array
    {
        return $this->query;
    }

    /** @return array<string, mixed> */
    public function post(): array
    {
        return $this->request;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->request[$key] ?? $this->query[$key] ?? $default;
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        if (isset($this->server[$key])) {
            return (string) $this->server[$key];
        }

        if (strcasecmp($name, 'Content-Type') === 0 && isset($this->server['CONTENT_TYPE'])) {
            return (string) $this->server['CONTENT_TYPE'];
        }

        return $default;
    }

    public function ip(): string
    {
        return (string) ($this->server['REMOTE_ADDR'] ?? '0.0.0.0');
    }

    public function isMethod(string $method): bool
    {
        return $this->method() === strtoupper($method);
    }

    /** @return array<string, mixed> */
    public function files(): array
    {
        return $this->files;
    }

    public function rawBody(): ?string
    {
        return $this->rawBody;
    }
}

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

        $path = str_replace('\\', '/', $path);
        $scriptName = str_replace('\\', '/', (string) ($this->server['SCRIPT_NAME'] ?? ''));
        $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        // Prefer longest matching install base first.
        // e.g. SCRIPT_NAME=/hms/public/index.php with REQUEST_URI=/hms
        // must strip /hms (project root rewrite), not leave /hms as a route.
        $candidates = [];
        if ($scriptDir !== '' && $scriptDir !== '/' && $scriptDir !== '.') {
            $candidates[] = $scriptDir;
            if (str_ends_with($scriptDir, '/public')) {
                $projectBase = substr($scriptDir, 0, -strlen('/public'));
                if ($projectBase !== '' && $projectBase !== '/') {
                    $candidates[] = $projectBase;
                }
            }
        }

        try {
            $appUrl = (string) (Config::app('url') ?? '');
        } catch (\Throwable) {
            $appUrl = '';
        }

        if ($appUrl !== '') {
            $appPath = parse_url($appUrl, PHP_URL_PATH);
            if (is_string($appPath) && $appPath !== '' && $appPath !== '/') {
                $appPath = rtrim(str_replace('\\', '/', $appPath), '/');
                $candidates[] = $appPath;
                if (str_ends_with($appPath, '/public')) {
                    $projectBase = substr($appPath, 0, -strlen('/public'));
                    if ($projectBase !== '' && $projectBase !== '/') {
                        $candidates[] = $projectBase;
                    }
                }
            }
        }

        $candidates = array_values(array_unique(array_filter(
            $candidates,
            static fn (string $base): bool => $base !== '' && $base !== '/'
        )));
        usort($candidates, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($candidates as $base) {
            if ($path === $base || str_starts_with($path, $base . '/')) {
                $path = substr($path, strlen($base)) ?: '/';
                break;
            }
        }

        // Direct hits on …/public/index.php should resolve as "/"
        if ($path === '/index.php' || str_ends_with($path, '/index.php')) {
            $path = substr($path, 0, -strlen('/index.php')) ?: '/';
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

<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     */
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        private readonly array $query,
        private readonly array $body,
        public readonly string $ip,
    ) {
    }

    public static function fromGlobals(): self
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        if (!is_string($path) || $path === '') {
            $path = '/';
        }

        return self::create(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path,
            $_GET,
            $_POST,
            $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
        );
    }

    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     */
    public static function create(
        string $method,
        string $path,
        array $query = [],
        array $body = [],
        string $ip = '127.0.0.1',
    ): self {
        $normalized = rtrim($path, '/');

        return new self($method, $normalized === '' ? '/' : $normalized, $query, $body, $ip);
    }

    public function query(string $key, string $default = ''): string
    {
        $value = $this->query[$key] ?? null;

        return is_string($value) ? $value : $default;
    }

    public function input(string $key, string $default = ''): string
    {
        $value = $this->body[$key] ?? null;

        return is_string($value) ? $value : $default;
    }
}

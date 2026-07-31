<?php

declare(strict_types=1);

namespace App\Core;

final class Config
{
    /**
     * @param array<string, string> $values
     */
    private function __construct(
        private readonly array $values,
        private readonly string $basePath,
    ) {
    }

    public static function load(string $basePath): self
    {
        return new self(self::parseEnvFile($basePath . '/.env'), $basePath);
    }

    public function get(string $key, string $default = ''): string
    {
        return $this->values[$key] ?? $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        if (!isset($this->values[$key])) {
            return $default;
        }

        return filter_var($this->values[$key], FILTER_VALIDATE_BOOL);
    }

    public function basePath(string $relative = ''): string
    {
        return $relative === '' ? $this->basePath : $this->basePath . DIRECTORY_SEPARATOR . $relative;
    }

    /**
     * @return array<string, string>
     */
    private static function parseEnvFile(string $path): array
    {
        if (!is_file($path)) {
            return [];
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return [];
        }

        $values = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            $values[trim($key)] = trim(trim($value), "\"'");
        }

        return $values;
    }
}
